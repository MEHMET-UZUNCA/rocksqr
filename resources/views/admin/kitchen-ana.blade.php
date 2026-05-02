<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#0f172a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Ana Mutfak KDS">
    <meta name="mobile-web-app-capable" content="yes">
    <link rel="apple-touch-icon" href="/favicon.ico">
    <title>Ana Mutfak KDS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: { extend: {
                colors: {
                    primary: '#0f172a',
                    teal: { 400: '#2dd4bf', 500: '#14b8a6', 600: '#0d9488', 700: '#0f766e' },
                },
                fontFamily: { poppins: ['Poppins', 'sans-serif'] }
            }}
        }
    </script>
    <style>
        @keyframes pulse-border { 0%,100% { border-color: #2dd4bf; } 50% { border-color: #f59e0b; } }
        .new-order { animation: pulse-border 1.5s ease-in-out infinite; }
        @keyframes fade-in { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
        .card-in { animation: fade-in 0.3s ease-out; }
    </style>
</head>
<body class="bg-slate-900 font-poppins text-white min-h-screen">

    <header class="bg-primary px-3 py-1 flex items-center justify-between border-b border-teal-800/40">
        <div class="flex items-center gap-1 bg-teal-900/60 border border-teal-700 rounded px-2 py-0.5">
            <i class="fas fa-tv text-teal-400 text-[10px]"></i>
            <span class="text-teal-400 font-bold text-sm">Ana Mutfak KDS</span>
        </div>
        <div class="flex items-center gap-2">
            <span id="clock" class="text-teal-300 font-bold text-base tabular-nums"></span>
            <span class="text-gray-600 text-xs">|</span>
            <span id="clock-date" class="text-gray-300 text-xs font-medium"></span>
        </div>
        <div class="flex items-center gap-1.5">
            <div class="flex items-center gap-1 bg-gray-800 border border-gray-700 rounded px-2 py-0.5">
                <span id="order-count" class="text-teal-400 font-bold text-sm">0</span>
                <span class="text-gray-500 text-[10px]">aktif</span>
            </div>
            <span id="live-dot" class="w-2 h-2 bg-teal-500 rounded-full animate-pulse ml-1"></span>
            <button onclick="toggleFullscreen()" class="text-gray-400 hover:text-teal-400 transition text-sm px-1" title="Tam ekran">
                <i id="fs-icon" class="fas fa-expand text-sm"></i>
            </button>
        </div>
    </header>

    <main class="p-4">
        <div id="orders-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4"></div>

        <div id="no-orders" class="hidden text-center py-20">
            <i class="fas fa-check-circle text-6xl text-teal-500 mb-4"></i>
            <p class="text-2xl text-gray-400">Aktif sipariş yok.</p>
            <p class="text-gray-500 mt-2">Yeni siparişler otomatik görünecek.</p>
        </div>

        <div id="error-box" class="hidden mt-6 p-4 bg-red-900/40 border border-red-500/60 rounded-lg text-red-300">
            <i class="fas fa-exclamation-triangle mr-2"></i><span id="error-msg"></span>
        </div>
    </main>

    <script>
        function updateClock() {
            const now = new Date();
            document.getElementById('clock').textContent = now.toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            document.getElementById('clock-date').textContent = now.toLocaleDateString('tr-TR', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' });
        }
        setInterval(updateClock, 1000); updateClock();

        function escapeHtml(s) {
            return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        }

        function formatTime(iso) {
            if (!iso) return '';
            try {
                const d = new Date(iso.replace(' ', 'T'));
                if (isNaN(d.getTime())) return iso;
                return d.toLocaleTimeString('tr-TR', { hour:'2-digit', minute:'2-digit' });
            } catch(e) { return iso; }
        }

        function elapsedSince(iso) {
            if (!iso) return null;
            const d = new Date(iso.replace(' ', 'T'));
            if (isNaN(d.getTime())) return null;
            return Math.max(0, Math.floor((Date.now() - d.getTime()) / 1000));
        }

        function fmtElapsed(secs) {
            if (secs == null) return '';
            const h = String(Math.floor(secs / 3600)).padStart(2,'0');
            const m = String(Math.floor((secs % 3600) / 60)).padStart(2,'0');
            const s = String(secs % 60).padStart(2,'0');
            return `${h}:${m}:${s}`;
        }

        function playOrderSound() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                [523.25, 659.25, 783.99].forEach((freq, i) => {
                    const osc = ctx.createOscillator(); const gain = ctx.createGain();
                    osc.type = 'sine'; osc.frequency.value = freq;
                    gain.gain.setValueAtTime(0.3, ctx.currentTime + i * 0.2);
                    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + i * 0.2 + 0.5);
                    osc.connect(gain); gain.connect(ctx.destination);
                    osc.start(ctx.currentTime + i * 0.2);
                    osc.stop(ctx.currentTime + i * 0.2 + 0.5);
                });
            } catch(e) {}
        }

        function buildOrderCard(order) {
            const elapsed = elapsedSince(order.order_time);
            const minTotal = elapsed ? Math.floor(elapsed / 60) : 0;
            const timeBg = minTotal > 15 ? 'bg-red-600'
                         : minTotal > 10 ? 'bg-yellow-600'
                         : minTotal > 5  ? 'bg-teal-700'
                         : 'bg-teal-600';
            const isNew = elapsed != null && elapsed < 120;
            const isAddition = !!order.is_addition;
            const borderClass = isAddition ? 'border-orange-500' : (isNew ? 'new-order border-teal-400' : 'border-teal-700/60');

            const checkLabel = order.check_number
                ? `Hesap #${escapeHtml(order.check_number)}`
                : `<span class="text-yellow-400">HESAPSIZ</span>`;

            const itemsHtml = (order.items || []).map(it => `
                <div class="flex justify-between items-start py-1 border-b border-slate-700">
                    <div class="flex-1 min-w-0">
                        <div class="text-lg leading-tight">
                            <span class="text-teal-400 font-bold text-xl">x${escapeHtml(it.qty)}</span>
                            <span class="font-semibold">${escapeHtml(it.name)}</span>
                        </div>
                        ${it.note ? `<div class="text-sm text-yellow-300"><i class="fas fa-comment-dots mr-1"></i>${escapeHtml(it.note)}</div>` : ''}
                    </div>
                    <div class="text-xs text-gray-500 ml-2 flex-shrink-0">${formatTime(it.item_time)}</div>
                </div>
            `).join('');

            return `
            <div class="bg-slate-800 rounded-lg border-2 ${borderClass} overflow-hidden card-in">
                <div class="flex items-center justify-between px-4 py-2 bg-slate-750/50">
                    <div class="flex items-center gap-3 flex-wrap">
                        <span class="text-xl font-bold text-teal-300">
                            Masa ${escapeHtml(order.table_no || '-')}
                        </span>
                        <span class="px-2 py-1 rounded text-xs font-bold bg-slate-700 text-gray-200">${checkLabel}</span>
                        ${isAddition ? `<span class="px-2 py-1 rounded text-xs font-bold bg-orange-600 text-white animate-pulse"><i class="fas fa-plus-circle mr-1"></i>EK SİPARİŞ</span>` : ''}
                        ${order.covers ? `<span class="text-xs text-gray-400"><i class="fas fa-user mr-1"></i>${order.covers}</span>` : ''}
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <span class="elapsed-counter px-2 py-1 rounded text-xs font-bold ${timeBg}" data-order-time="${escapeHtml(order.order_time || '')}">${fmtElapsed(elapsed)}</span>
                    </div>
                </div>
                ${order.rvc ? `<div class="px-4 py-1 text-xs text-teal-600/80 border-b border-slate-700"><i class="fas fa-store mr-1"></i>${escapeHtml(order.rvc)}</div>` : ''}
                <div class="px-4 py-3 text-sm">
                    ${itemsHtml || '<div class="text-gray-500 text-center py-2">Ürün yok</div>'}
                </div>
            </div>`;
        }

        let previousGroupKeys = [];
        let isFirstLoad = true;

        function render(data) {
            const orders = data.orders || [];

            document.getElementById('order-count').textContent = orders.length;
            document.getElementById('fetched-at').textContent = data.fetched_at ? '· ' + data.fetched_at : '';

            const errBox = document.getElementById('error-box');
            if (data.success === false && data.message) {
                errBox.classList.remove('hidden');
                document.getElementById('error-msg').textContent = data.message;
            } else {
                errBox.classList.add('hidden');
            }

            const grid = document.getElementById('orders-grid');
            const noOrders = document.getElementById('no-orders');

            if (orders.length === 0) {
                grid.classList.add('hidden');
                noOrders.classList.remove('hidden');
            } else {
                noOrders.classList.add('hidden');
                grid.classList.remove('hidden');
                grid.innerHTML = orders.map(buildOrderCard).join('');
            }

            // Yeni sipariş sesi
            const keys = orders.map(o => o.group_key);
            if (!isFirstLoad) {
                const newOnes = keys.filter(k => !previousGroupKeys.includes(k));
                if (newOnes.length > 0) playOrderSound();
            }
            previousGroupKeys = keys;
            isFirstLoad = false;
        }

        function fetchOnce() {
            fetch('/kitchen-ana/api')
                .then(r => r.json())
                .then(render)
                .catch(e => {
                    document.getElementById('error-box').classList.remove('hidden');
                    document.getElementById('error-msg').textContent = 'Bağlantı hatası: ' + e.message;
                });
        }

        fetchOnce();
        setInterval(fetchOnce, 5000);

        // Her saniye elapsed-counter span'larını güncelle (5sn polling'i beklemeden)
        setInterval(function tickElapsed() {
            document.querySelectorAll('.elapsed-counter[data-order-time]').forEach(function(span) {
                const iso = span.dataset.orderTime;
                if (!iso) return;
                const secs = elapsedSince(iso);
                if (secs == null) return;
                const minTotal = Math.floor(secs / 60);
                const newBg = minTotal > 15 ? 'bg-red-600'
                            : minTotal > 10 ? 'bg-yellow-600'
                            : minTotal > 5  ? 'bg-teal-700'
                            : 'bg-teal-600';
                ['bg-red-600','bg-yellow-600','bg-teal-700','bg-teal-600'].forEach(c => span.classList.remove(c));
                span.classList.add(newBg);
                span.textContent = fmtElapsed(secs);
            });
        }, 1000);

        document.addEventListener('click', function enableAudio() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                ctx.resume();
            } catch(e) {}
            document.removeEventListener('click', enableAudio);
        }, { once: true });

        function toggleFullscreen() {
            const icon = document.getElementById('fs-icon');
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen?.().then(() => {
                    if (icon) icon.className = 'fas fa-compress';
                }).catch(() => {});
            } else {
                document.exitFullscreen?.().then(() => {
                    if (icon) icon.className = 'fas fa-expand';
                }).catch(() => {});
            }
        }
        document.addEventListener('fullscreenchange', () => {
            const icon = document.getElementById('fs-icon');
            if (icon) icon.className = document.fullscreenElement ? 'fas fa-compress' : 'fas fa-expand';
        });
    </script>
</body>
</html>
