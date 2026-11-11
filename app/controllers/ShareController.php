<?php
/** Truy cập tài liệu qua liên kết chia sẻ (không cần đăng nhập) */
class ShareController extends Controller
{
    public function open(): void
    {
        $token = (string) input('token', '');
        $link = preg_match('/^[a-f0-9]{48}$/', $token) ? row('SELECT s.*, d.title, d.is_deleted FROM share_links s JOIN documents d ON d.id = s.document_id WHERE s.token = ?', [$token]) : null;
        $error = null;
        if (!$link || $link['is_revoked'] || $link['is_deleted']) {
            $error = 'Liên kết không tồn tại hoặc đã bị thu hồi.';
        } elseif (strtotime($link['expires_at']) < time()) {
            $error = 'Liên kết đã hết hạn.';
        } elseif ($link['max_downloads'] && $link['download_count'] >= $link['max_downloads']) {
            $error = 'Liên kết đã hết lượt tải.';
        }
        $version = $error ? null : DocumentService::currentVersion((int) $link['document_id']);

        if (!$error && is_post()) {
            $attempts = $_SESSION['share_attempts'][$token] ?? 0;
            if ($attempts >= 5) {
                $error = 'Nhập sai mật khẩu quá nhiều lần.';
            } elseif ($link['password_hash'] && !password_verify((string) ($_POST['password'] ?? ''), $link['password_hash'])) {
                $_SESSION['share_attempts'][$token] = $attempts + 1;
                flash('danger', 'Mật khẩu không đúng.');
                redirect('share/open', ['token' => $token]);
            } else {
                q('UPDATE share_links SET download_count = download_count + 1 WHERE id = ?', [$link['id']]);
                Audit::log('share_download', 'document', $link['document_id'], 'Tải qua liên kết #' . $link['id'] . ' từ IP ' . client_ip(), 'guest');
                try {
                    DocumentService::send($version);
                } catch (RuntimeException $e) {
                    $error = $e->getMessage();
                }
            }
        }
        view('share/open', ['title' => 'Tài liệu được chia sẻ', 'link' => $link, 'error' => $error, 'version' => $version, 'token' => $token], 'layouts/guest');
    }
}
