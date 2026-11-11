<?php
/**
 * Mã hóa dữ liệu khi lưu trữ (encryption at rest) - mô hình Envelope Encryption:
 *  - Khóa chủ (KEK) = app_key trong config (không lưu trong CSDL).
 *  - Khóa dữ liệu (DEK) AES-256 lưu trong bảng encryption_keys, đã được "bọc" bằng KEK.
 *  - Mỗi phiên bản tài liệu được mã hóa AES-256-GCM (có xác thực toàn vẹn) với DEK đang hoạt động.
 *  - Hỗ trợ khóa do hệ thống sinh (system) và khóa do khách hàng tự quản lý (customer / BYOK).
 *  - Thu hồi khóa (revoke) => các tài liệu mã hóa bằng khóa đó không thể giải mã (crypto-shredding).
 */
class Crypto
{
    const CIPHER = 'aes-256-gcm';

    public static function masterKey(): string
    {
        $key = base64_decode((string) config('app_key'), true);
        if ($key === false || strlen($key) !== 32) {
            throw new RuntimeException('app_key chưa được cấu hình đúng (cần base64 của 32 byte). Hãy chạy install.php.');
        }
        return $key;
    }

    public static function encrypt(string $plain, string $key): array
    {
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plain, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
        if ($cipher === false) {
            throw new RuntimeException('Mã hóa thất bại');
        }
        return [$cipher, $iv, $tag];
    }

    public static function decrypt(string $cipher, string $key, string $iv, string $tag): string
    {
        $plain = openssl_decrypt($cipher, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($plain === false) {
            throw new RuntimeException('Giải mã thất bại: dữ liệu đã bị thay đổi hoặc sai khóa.');
        }
        return $plain;
    }

    public static function wrap(string $dek): string
    {
        [$c, $iv, $tag] = self::encrypt($dek, self::masterKey());
        return base64_encode($iv . $tag . $c);
    }

    public static function unwrap(string $wrapped): string
    {
        $raw = base64_decode($wrapped, true);
        if ($raw === false || strlen($raw) < 29) {
            throw new RuntimeException('Khóa bọc không hợp lệ');
        }
        return self::decrypt(substr($raw, 28), self::masterKey(), substr($raw, 0, 12), substr($raw, 12, 16));
    }

    public static function fingerprint(string $dek): string
    {
        return substr(hash('sha256', $dek), 0, 16);
    }

    /** Khóa đang hoạt động; tự tạo khóa hệ thống nếu chưa có */
    public static function activeKey(): array
    {
        $k = row("SELECT * FROM encryption_keys WHERE status = 'active' ORDER BY id DESC LIMIT 1");
        if (!$k) {
            $id = self::createSystemKey('Khóa hệ thống mặc định', true);
            $k = row('SELECT * FROM encryption_keys WHERE id = ?', [$id]);
        }
        return $k;
    }

    public static function createSystemKey(string $name, bool $activate = true): int
    {
        return self::storeKey($name, 'system', random_bytes(32), $activate);
    }

    /** BYOK: khách hàng cung cấp khóa 256-bit dạng base64 hoặc hex */
    public static function importCustomerKey(string $name, string $material, bool $activate = true): int
    {
        $material = trim($material);
        $dek = base64_decode($material, true);
        if ($dek === false || strlen($dek) !== 32) {
            $dek = ctype_xdigit($material) && strlen($material) === 64 ? hex2bin($material) : false;
        }
        if ($dek === false || strlen($dek) !== 32) {
            throw new InvalidArgumentException('Khóa phải là 32 byte (256-bit) ở dạng base64 hoặc 64 ký tự hex.');
        }
        return self::storeKey($name, 'customer', $dek, $activate);
    }

    private static function storeKey(string $name, string $type, string $dek, bool $activate): int
    {
        if ($activate) {
            q("UPDATE encryption_keys SET status = 'inactive' WHERE status = 'active'");
        }
        return insert('encryption_keys', [
            'name'        => $name,
            'key_type'    => $type,
            'wrapped_key' => self::wrap($dek),
            'fingerprint' => self::fingerprint($dek),
            'status'      => $activate ? 'active' : 'inactive',
            'created_by'  => user_id(),
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    public static function keyMaterial(int $keyId): string
    {
        $k = row('SELECT * FROM encryption_keys WHERE id = ?', [$keyId]);
        if (!$k) {
            throw new RuntimeException('Không tìm thấy khóa mã hóa #' . $keyId);
        }
        if ($k['status'] === 'revoked') {
            throw new RuntimeException('Khóa mã hóa "' . $k['name'] . '" đã bị thu hồi, tài liệu không thể giải mã.');
        }
        return self::unwrap($k['wrapped_key']);
    }

    /** Mã hóa nội dung tệp bằng khóa đang hoạt động */
    public static function encryptFile(string $content): array
    {
        $key = self::activeKey();
        [$c, $iv, $tag] = self::encrypt($content, self::keyMaterial((int) $key['id']));
        return ['cipher' => $c, 'iv' => base64_encode($iv), 'tag' => base64_encode($tag), 'key_id' => (int) $key['id']];
    }

    public static function decryptFile(string $cipher, string $ivB64, string $tagB64, int $keyId): string
    {
        return self::decrypt($cipher, self::keyMaterial($keyId), base64_decode($ivB64), base64_decode($tagB64));
    }
}
