<?php
class NotificationsController extends Controller
{
    public function index(): void
    {
        $p = paginate('SELECT COUNT(*) FROM notifications WHERE user_id = ?',
            'SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC', [user_id()], 25);
        view('notifications/index', ['title' => 'Thông báo', 'p' => $p]);
    }

    public function poll(): void
    {
        $items = rows('SELECT id, title, message, link, type, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 8', [user_id()]);
        foreach ($items as &$n) {
            $n['id'] = (int) $n['id'];
            $n['ago'] = time_ago($n['created_at']);
            $n['open_url'] = url('notifications/open', ['id' => $n['id']]);
        }
        unset($n);
        json_response([
            'unread' => (int) val('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0', [user_id()]),
            'items'  => $items,
        ]);
    }

    public function open(): void
    {
        $n = row('SELECT * FROM notifications WHERE id = ? AND user_id = ?', [input_int('id'), user_id()]);
        if (!$n) {
            redirect('notifications/index');
        }
        update_row('notifications', ['is_read' => 1], 'id = ?', [$n['id']]);
        if ($n['link'] && str_starts_with($n['link'], 'index.php?')) {
            header('Location: ' . $n['link']);
            exit;
        }
        redirect('notifications/index');
    }

    public function readAll(): void
    {
        q('UPDATE notifications SET is_read = 1 WHERE user_id = ?', [user_id()]);
        redirect_back();
    }
}
