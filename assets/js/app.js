/* Thông báo gần thời gian thực (polling) + tiện ích giao diện */
(function () {
    const listEl = document.getElementById('notiList');
    const countEl = document.getElementById('notiCount');
    let lastId = null;

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : s;
        return d.innerHTML;
    }

    function toast(n) {
        const box = document.getElementById('toastBox');
        if (!box || !window.bootstrap) return;
        const el = document.createElement('div');
        el.className = 'toast align-items-center border-0 text-bg-' + (n.type === 'danger' ? 'danger' : (n.type === 'success' ? 'success' : 'primary'));
        el.innerHTML = '<div class="d-flex"><div class="toast-body"><strong>' + esc(n.title) + '</strong><br>' + esc(n.message) +
            '</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>';
        box.appendChild(el);
        new bootstrap.Toast(el, { delay: 6000 }).show();
    }

    function poll() {
        if (!window.APP || !listEl) return;
        fetch(APP.pollUrl, { credentials: 'same-origin' })
            .then(r => r.ok ? r.json() : null)
            .then(data => {
                if (!data) return;
                countEl.textContent = data.unread;
                countEl.classList.toggle('d-none', data.unread === 0);
                if (!data.items.length) {
                    listEl.innerHTML = '<div class="p-3 text-muted small">Không có thông báo.</div>';
                } else {
                    listEl.innerHTML = data.items.map(n =>
                        '<a class="noti-item ' + (n.is_read == 0 ? 'unread' : '') + '" href="' + esc(n.open_url) + '">' +
                        '<div class="fw-semibold"><i class="bi bi-dot text-' + esc(n.type) + '"></i>' + esc(n.title) + '</div>' +
                        '<div class="small">' + esc(n.message) + '</div><small>' + esc(n.ago) + '</small></a>').join('');
                }
                if (lastId !== null && data.items.length && data.items[0].id > lastId && data.items[0].is_read == 0) {
                    toast(data.items[0]);
                }
                if (data.items.length) lastId = data.items[0].id;
                else if (lastId === null) lastId = 0;
            })
            .catch(() => {});
    }
    poll();
    setInterval(poll, 30000);

    // Định dạng ô nhập tiền: 1000000 -> 1.000.000
    document.addEventListener('input', function (e) {
        if (!e.target.classList.contains('money')) return;
        const digits = e.target.value.replace(/[^\d]/g, '');
        e.target.value = digits ? Number(digits).toLocaleString('vi-VN') : '';
    });
    document.querySelectorAll('input.money').forEach(function (el) {
        const raw = el.value.trim();
        // Giá trị thô từ CSDL kiểu DECIMAL (vd 1575000000.00): dấu chấm là phần thập phân, không phải phân cách nghìn
        const n = /^\d+\.\d{1,2}$/.test(raw) ? Math.round(parseFloat(raw)) : Number(raw.replace(/[^\d]/g, ''));
        if (raw !== '' && !isNaN(n)) el.value = n.toLocaleString('vi-VN');
    });

    // Xác nhận thao tác
    document.addEventListener('submit', function (e) {
        const btn = e.submitter;
        const msg = (btn && btn.getAttribute('data-confirm')) || e.target.getAttribute('data-confirm');
        if (msg && !confirm(msg)) e.preventDefault();
    });

    // Giữ tab theo tham số URL ?tab=
    const tab = new URLSearchParams(location.search).get('tab');
    if (tab && window.bootstrap) {
        const trigger = document.querySelector('[data-bs-target="#tab-' + tab + '"]');
        if (trigger) bootstrap.Tab.getOrCreateInstance(trigger).show();
    }

    // Tự tính thành tiền trong bảng giá dự thầu
    document.querySelectorAll('[data-bid-table]').forEach(function (table) {
        const recalc = function () {
            let total = 0;
            table.querySelectorAll('tr[data-qty]').forEach(function (tr) {
                const qty = parseFloat(tr.dataset.qty) || 0;
                const price = parseFloat((tr.querySelector('input.money').value || '0').replace(/\./g, '')) || 0;
                const amount = Math.round(qty * price);
                tr.querySelector('.amount').textContent = amount.toLocaleString('vi-VN');
                total += amount;
            });
            const t = table.querySelector('.bid-total');
            if (t) t.textContent = total.toLocaleString('vi-VN') + ' đ';
        };
        table.addEventListener('input', recalc);
        recalc();
    });
})();
