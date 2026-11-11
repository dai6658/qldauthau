<?php
/**
 * Kho lưu trữ tài liệu tập trung:
 *  - Mọi tệp được mã hóa AES-256-GCM trước khi ghi xuống đĩa (thư mục storage/ bị chặn truy cập web).
 *  - Quản lý phiên bản: mỗi lần tải lên tạo version mới, có ghi chú thay đổi, khôi phục phiên bản cũ.
 *  - Kiểm tra toàn vẹn bằng SHA-256.
 *  - Trích xuất văn bản (txt, csv, docx, xlsx, pptx) phục vụ tìm kiếm nội dung.
 */
class DocumentService
{
    /** Chuẩn hóa mảng $_FILES (hỗ trợ nhiều tệp) */
    public static function files(string $field): array
    {
        if (empty($_FILES[$field])) {
            return [];
        }
        $f = $_FILES[$field];
        if (!is_array($f['name'])) {
            return $f['error'] === UPLOAD_ERR_NO_FILE ? [] : [$f];
        }
        $out = [];
        foreach ($f['name'] as $i => $name) {
            if ($f['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $out[] = ['name' => $name, 'type' => $f['type'][$i], 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => $f['size'][$i]];
        }
        return $out;
    }

    public static function validate(array $file): array
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Tải tệp thất bại (mã lỗi ' . $file['error'] . '). Kiểm tra upload_max_filesize trong php.ini.');
        }
        if ($file['size'] > config('upload.max_size')) {
            throw new RuntimeException('Tệp vượt quá dung lượng cho phép (' . file_size(config('upload.max_size')) . ').');
        }
        $name = basename(str_replace('\\', '/', $file['name']));
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, config('upload.allowed'), true)) {
            throw new RuntimeException('Định dạng .' . $ext . ' không được phép.');
        }
        if (!is_uploaded_file($file['tmp_name']) && PHP_SAPI !== 'cli') {
            throw new RuntimeException('Tệp tải lên không hợp lệ.');
        }
        $content = file_get_contents($file['tmp_name']);
        $mime = function_exists('mime_content_type') ? (mime_content_type($file['tmp_name']) ?: 'application/octet-stream') : 'application/octet-stream';
        return ['content' => $content, 'name' => $name, 'ext' => $ext, 'mime' => $mime, 'size' => strlen($content), 'tmp' => $file['tmp_name']];
    }

    /**
     * Tạo tài liệu mới từ tệp upload.
     * $meta: title, category, description, tags, entity_type, entity_id, project_id, department_id, company_id, confidentiality
     */
    public static function create(array $meta, array $file, string $note = 'Phiên bản đầu tiên'): int
    {
        $f = self::validate($file);
        return self::createFromContent($meta, $f['name'], $f['content'], $f['mime'], $note, $f['tmp']);
    }

    public static function createFromContent(array $meta, string $originalName, string $content, string $mime = 'application/octet-stream', string $note = 'Phiên bản đầu tiên', ?string $tmpPath = null): int
    {
        $u = auth();
        $docId = insert('documents', [
            'code'            => gen_code('TL', 'documents'),
            'title'           => $meta['title'] ?: $originalName,
            'category'        => $meta['category'] ?? 'other',
            'description'     => $meta['description'] ?? null,
            'tags'            => $meta['tags'] ?? null,
            'entity_type'     => $meta['entity_type'] ?? 'general',
            'entity_id'       => $meta['entity_id'] ?? null,
            'company_id'      => $meta['company_id'] ?? ($u['company_id'] ?? null),
            'department_id'   => $meta['department_id'] ?? ($u['department_id'] ?? null),
            'project_id'      => $meta['project_id'] ?? null,
            'confidentiality' => $meta['confidentiality'] ?? 'internal',
            'current_version' => 1,
            'created_by'      => $u['id'] ?? null,
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
        self::storeVersion($docId, 1, $originalName, $content, $mime, $note, $tmpPath);
        Audit::log('create', 'document', $docId, 'Tải lên: ' . $originalName);
        return $docId;
    }

    public static function addVersion(int $docId, array $file, string $note): int
    {
        $f = self::validate($file);
        $next = (int) val('SELECT MAX(version_no) FROM document_versions WHERE document_id = ?', [$docId]) + 1;
        self::storeVersion($docId, $next, $f['name'], $f['content'], $f['mime'], $note ?: 'Cập nhật phiên bản', $f['tmp']);
        update_row('documents', ['current_version' => $next, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$docId]);
        Audit::log('new_version', 'document', $docId, "Phiên bản $next: " . $note);
        return $next;
    }

    public static function restore(int $docId, int $versionId, string $note): int
    {
        $v = row('SELECT * FROM document_versions WHERE id = ? AND document_id = ?', [$versionId, $docId]);
        if (!$v) {
            throw new RuntimeException('Phiên bản không tồn tại');
        }
        $content = self::read($v);
        $next = (int) val('SELECT MAX(version_no) FROM document_versions WHERE document_id = ?', [$docId]) + 1;
        $msg = 'Khôi phục từ phiên bản ' . $v['version_no'] . ($note ? ' - ' . $note : '');
        self::storeVersion($docId, $next, $v['original_name'], $content, $v['mime'], $msg, null, $v['content_text']);
        update_row('documents', ['current_version' => $next, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$docId]);
        Audit::log('restore_version', 'document', $docId, $msg);
        return $next;
    }

    private static function storeVersion(int $docId, int $versionNo, string $name, string $content, ?string $mime, string $note, ?string $tmpPath = null, ?string $text = null): void
    {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $enc = Crypto::encryptFile($content);
        $rel = date('Y/m') . '/' . bin2hex(random_bytes(16)) . '.enc';
        $abs = STORAGE_PATH . '/documents/' . $rel;
        if (!is_dir(dirname($abs)) && !mkdir(dirname($abs), 0750, true)) {
            throw new RuntimeException('Không tạo được thư mục lưu trữ: ' . dirname($abs));
        }
        if (file_put_contents($abs, $enc['cipher']) === false) {
            throw new RuntimeException('Không ghi được tệp vào kho lưu trữ');
        }
        if ($text === null && setting('index_document_content', '1') === '1') {
            $text = self::extractText($content, $ext, $tmpPath);
        }
        insert('document_versions', [
            'document_id'   => $docId,
            'version_no'    => $versionNo,
            'original_name' => $name,
            'extension'     => $ext,
            'mime'          => $mime,
            'size_bytes'    => strlen($content),
            'sha256'        => hash('sha256', $content),
            'storage_path'  => $rel,
            'iv'            => $enc['iv'],
            'tag'           => $enc['tag'],
            'key_id'        => $enc['key_id'],
            'content_text'  => $text !== null ? mb_substr($text, 0, 200000) : null,
            'change_note'   => $note,
            'uploaded_by'   => user_id(),
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
    }

    public static function read(array $version): string
    {
        $abs = STORAGE_PATH . '/documents/' . $version['storage_path'];
        if (!is_file($abs)) {
            throw new RuntimeException('Tệp lưu trữ không tồn tại');
        }
        $plain = Crypto::decryptFile(file_get_contents($abs), $version['iv'], $version['tag'], (int) $version['key_id']);
        if (!hash_equals($version['sha256'], hash('sha256', $plain))) {
            throw new RuntimeException('Cảnh báo: tệp không khớp mã băm SHA-256 (có thể đã bị thay đổi).');
        }
        return $plain;
    }

    public static function send(array $version, bool $inline = false): void
    {
        $content = self::read($version);
        $safe = str_replace(['"', "\r", "\n"], '', $version['original_name']);
        header('Content-Type: ' . ($version['mime'] ?: 'application/octet-stream'));
        header('Content-Length: ' . strlen($content));
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $safe . '"; filename*=UTF-8\'\'' . rawurlencode($version['original_name']));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        echo $content;
        exit;
    }

    public static function currentVersion(int $docId): ?array
    {
        return row('SELECT v.* FROM document_versions v JOIN documents d ON d.id = v.document_id AND d.current_version = v.version_no WHERE d.id = ?', [$docId]);
    }

    public static function forEntity(string $type, int $id): array
    {
        return rows('SELECT d.*, v.original_name, v.size_bytes, v.extension, u.full_name AS creator
                     FROM documents d
                     LEFT JOIN document_versions v ON v.document_id = d.id AND v.version_no = d.current_version
                     LEFT JOIN users u ON u.id = d.created_by
                     WHERE d.entity_type = ? AND d.entity_id = ? AND d.is_deleted = 0 ORDER BY d.id DESC', [$type, $id]);
    }

    /** Trích xuất văn bản để tìm kiếm toàn văn */
    public static function extractText(string $content, string $ext, ?string $tmpPath = null): ?string
    {
        try {
            if (in_array($ext, ['txt', 'csv', 'xml', 'eml'], true)) {
                return mb_convert_encoding($content, 'UTF-8', 'UTF-8, Windows-1252');
            }
            if (in_array($ext, ['docx', 'xlsx', 'pptx'], true) && class_exists('ZipArchive')) {
                $tmp = $tmpPath && is_file($tmpPath) ? $tmpPath : tempnam(sys_get_temp_dir(), 'doc');
                if ($tmp !== $tmpPath) {
                    file_put_contents($tmp, $content);
                }
                $zip = new ZipArchive();
                $text = '';
                if ($zip->open($tmp) === true) {
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $n = $zip->getNameIndex($i);
                        if (preg_match('#^(word/document\.xml|xl/sharedStrings\.xml|ppt/slides/slide\d+\.xml)$#', $n)) {
                            $xml = $zip->getFromIndex($i);
                            $text .= ' ' . html_entity_decode(strip_tags(str_replace(['</w:p>', '</a:p>', '</si>'], "\n", $xml)), ENT_QUOTES | ENT_XML1, 'UTF-8');
                        }
                    }
                    $zip->close();
                }
                if ($tmp !== $tmpPath) {
                    @unlink($tmp);
                }
                return trim(preg_replace('/[ \t]+/', ' ', $text));
            }
        } catch (Throwable $e) {
            error_log('extractText: ' . $e->getMessage());
        }
        return null;
    }
}
