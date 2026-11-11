<?php
/**
 * Controller cơ sở
 */
abstract class Controller
{
    /** Chạy hành động, bắt lỗi nghiệp vụ và hiển thị thông báo */
    protected function attempt(callable $fn, ?string $successMessage = null)
    {
        try {
            $result = $fn();
            if ($successMessage) {
                flash('success', $successMessage);
            }
            return $result === null ? true : $result;
        } catch (RuntimeException | InvalidArgumentException $e) {
            flash('danger', $e->getMessage());
            return false;
        } catch (PDOException $e) {
            flash('danger', config('debug') ? 'Lỗi CSDL: ' . $e->getMessage() : 'Lỗi lưu dữ liệu.');
            return false;
        }
    }

    protected function requireId(string $table, string $key = 'id', string $label = 'bản ghi'): array
    {
        $r = row("SELECT * FROM `$table` WHERE id = ?", [input_int($key)]);
        if (!$r) {
            abort(404, 'Không tìm thấy ' . $label . '.');
        }
        return $r;
    }

    protected function fail(string $message): void
    {
        throw new RuntimeException($message);
    }
}
