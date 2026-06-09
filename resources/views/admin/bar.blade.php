<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ \App\Models\Setting::get('bar_screen_title', 'KDS - Bar Ekrani') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1a1a2e',
                        gold: '#d4af37',
                        'light-primary': '#2a2a4e',
                    },
                    fontFamily: {
                        poppins: ['Poppins', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        @keyframes pulse-border {
            0%, 100% { border-color: #d4af37; }
            50% { border-color: #ef4444; }
        }
        .new-order { animation: pulse-border 1.5s ease-in-out infinite; }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        .waiter-alert { animation: shake 0.5s ease-in-out infinite; }
    </style>
</head>
<body class="bg-gray-900 font-poppins text-white h-screen flex flex-col" style="overflow:hidden">
    <header class="bg-primary px-3 py-1 flex items-center justify-between">
        <div class="flex items-center gap-1 bg-yellow-900/60 border border-yellow-700 rounded px-2 py-0.5">
            <i class="fas fa-wine-glass text-gold text-[10px]"></i>
            <span class="text-gold font-bold text-sm">{{ \App\Models\Setting::get('bar_screen_title', 'KDS - Bar') }}</span>
        </div>
        <div class="flex items-center gap-2">
            <span id="clock" class="text-gold font-bold text-base tabular-nums"></span>
            <span class="text-gray-600 text-xs">|</span>
            <span id="clock-date" class="text-gray-300 text-xs font-medium"></span>
        </div>
        <div class="flex items-center gap-1.5">
            <div class="flex items-center gap-1 bg-blue-900/60 border border-blue-700 rounded px-2 py-0.5">
                <i class="fas fa-server text-blue-400 text-[10px]"></i>
                <span id="header-sym-count" class="text-blue-200 font-bold text-sm">0</span>
                <span class="text-blue-600 text-[10px]">SYM</span>
            </div>
            <div class="flex items-center gap-1 bg-orange-900/60 border border-orange-700 rounded px-2 py-0.5">
                <i class="fas fa-mobile-screen text-orange-400 text-[10px]"></i>
                <span id="header-qr-count" class="text-orange-200 font-bold text-sm">0</span>
                <span class="text-orange-600 text-[10px]">QR</span>
            </div>
            <div class="flex items-center gap-1 bg-emerald-900/60 border border-emerald-700 rounded px-2 py-0.5">
                <i class="fas fa-concierge-bell text-emerald-400 text-[10px]"></i>
                <span id="header-ready-count" class="text-emerald-200 font-bold text-sm">0</span>
                <span class="text-emerald-600 text-[10px]">hazır</span>
            </div>
            <div class="flex items-center gap-1 bg-red-900/60 border border-red-700 rounded px-2 py-0.5">
                <i class="fas fa-bell text-red-400 text-[10px] waiter-alert"></i>
                <span id="header-waiter-count" class="text-red-200 font-bold text-sm">0</span>
                <span class="text-red-600 text-[10px]">çağrı</span>
            </div>
            <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse ml-1"></span>
            <button onclick="toggleFullscreen()" id="fs-btn" class="text-gray-400 hover:text-gold transition ml-1" title="Tam ekran">
                <i id="fs-icon" class="fas fa-expand text-sm"></i>
            </button>
            <a href="/admin" class="text-gray-400 hover:text-gold transition text-xs">
                <i class="fas fa-arrow-left"></i>
            </a>
        </div>
    </header>

    <!-- Garson Çağrıları + Sipariş Hazır: tek grid -->
    <div id="top-bar" class="hidden px-2 py-1">
        <div id="combined-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-7 gap-2"></div>
    </div>

    <main class="p-2 flex-1 min-h-0 overflow-y-auto" style="padding-bottom:50px">
        <div id="orders-grid" class="grid gap-1.5 items-start" style="grid-template-columns: repeat(auto-fill, minmax(200px, 1fr))"></div>
        <div id="no-orders" class="hidden text-center py-20">
            <i class="fas fa-check-circle text-6xl text-green-500 mb-4"></i>
            <p class="text-2xl text-gray-400">Tum siparisler tamamlandi!</p>
            <p class="text-gray-500 mt-2">Yeni siparisler otomatik olarak gorunecek.</p>
        </div>
    </main>

    <!-- Toast bildirimi -->
    <div id="bar-toast" class="hidden fixed top-4 left-1/2 -translate-x-1/2 z-[1000] px-4 py-3 rounded-lg shadow-xl text-sm font-bold text-white min-w-[260px] text-center"></div>

    <!-- İptal onay modalı (fullscreen'de confirm() çalışmadığı için custom modal) -->
    <div id="cancel-modal" class="hidden fixed inset-0 z-[999] flex items-center justify-center bg-black/70">
        <div class="bg-gray-800 border-2 border-red-600 rounded-xl px-6 py-5 shadow-2xl max-w-xs w-full mx-4 text-center">
            <i class="fas fa-triangle-exclamation text-red-400 text-3xl mb-3"></i>
            <p class="text-white font-semibold mb-1">Siparişi iptal et?</p>
            <p class="text-gray-400 text-sm mb-4">Bu işlem geri alınamaz.</p>
            <div class="flex gap-3">
                <button id="cancel-modal-no" class="flex-1 py-2 bg-gray-600 hover:bg-gray-500 rounded-lg text-sm font-bold text-white transition">Vazgeç</button>
                <button id="cancel-modal-yes" class="flex-1 py-2 bg-red-600 hover:bg-red-500 rounded-lg text-sm font-bold text-white transition"><i class="fas fa-ban mr-1"></i>İptal Et</button>
            </div>
        </div>
    </div>

    <!-- Son Tamamlananlar: sabit alt şerit -->
    <div id="completed-bar" class="fixed bottom-0 left-0 right-0 bg-gray-900 border-t border-gray-700 px-3 py-1.5" style="z-index:50;min-height:38px">
        <div class="flex items-center gap-2 flex-wrap" id="completed-grid"></div>
    </div>

    <script>
        let previousOrderIds = [];
        let previousWaiterIds = [];
        let previousReadyIds = [];
        let isFirstLoad = true;
        let lastSymOrders = [];   // Symphony API hata verince son bilinen siparisleri koru
        let lastOrderKeys = '';   // Flicker onlemek icin stabil key
        let _waiterCards = [];    // Shared top-bar state
        let _readyCards  = [];    // Shared top-bar state

        // ── Sayaç kalıcılığı (kitchen-pos ile ortak localStorage) ───────────
        // kitchen-pos ve bar ekranı aynı kpos_start_ anahtarını paylaşır,
        // böylece Symphony hesapları her iki ekranda da aynı süreyi gösterir.
        const LS_PREFIX = 'kpos_start_';

        function getStartTime(groupKey, apiOrderTime) {
            const lsKey = LS_PREFIX + groupKey;
            const stored = localStorage.getItem(lsKey);
            if (stored) return stored;
            const t = apiOrderTime ? new Date(apiOrderTime.replace(' ', 'T')) : null;
            const ts = (t && !isNaN(t.getTime())) ? apiOrderTime : new Date().toISOString();
            localStorage.setItem(lsKey, ts);
            return ts;
        }

        function clearStartTime(groupKey) {
            localStorage.removeItem(LS_PREFIX + groupKey);
        }

        function symGroupKey(order) {
            // Must match kitchen-pos.blade.php format: bare check_number (no prefix) or T+tableNo
            return order.check_number ? String(order.check_number) : ('T' + (order.table_no || ''));
        }

        function refreshTopBar() {
            const topBar = document.getElementById('top-bar');
            const grid   = document.getElementById('combined-grid');
            const all    = [..._waiterCards, ..._readyCards];

            // Header badge sayılarını daima güncelle (0 bile olsa göster)
            document.getElementById('header-waiter-count').textContent = _waiterCards.length;
            document.getElementById('header-ready-count').textContent  = _readyCards.length;

            if (all.length === 0) { topBar.classList.add('hidden'); return; }
            topBar.classList.remove('hidden');
            grid.innerHTML = all.join('');
        }

        function updateClock() {
            const now = new Date();
            document.getElementById('clock').textContent = now.toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            document.getElementById('clock-date').textContent = now.toLocaleDateString('tr-TR', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' });
        }
        setInterval(updateClock, 1000);
        updateClock();

        function toggleFullscreen() {
            const icon = document.getElementById('fs-icon');
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().then(() => {
                    icon.classList.remove('fa-expand');
                    icon.classList.add('fa-compress');
                }).catch(() => {});
            } else {
                document.exitFullscreen().then(() => {
                    icon.classList.remove('fa-compress');
                    icon.classList.add('fa-expand');
                }).catch(() => {});
            }
        }
        document.addEventListener('fullscreenchange', () => {
            const icon = document.getElementById('fs-icon');
            if (document.fullscreenElement) {
                icon.classList.remove('fa-expand'); icon.classList.add('fa-compress');
            } else {
                icon.classList.remove('fa-compress'); icon.classList.add('fa-expand');
            }
        });

        function getProductName(productId) {
            const products = @json(\App\Models\Product::pluck('name', 'id'));
            return products[productId] || 'Urun #' + productId;
        }

        function playOrderSound() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                [523.25, 659.25, 783.99].forEach((freq, i) => {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'sine';
                    osc.frequency.value = freq;
                    gain.gain.setValueAtTime(0.3, ctx.currentTime + i * 0.2);
                    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + i * 0.2 + 0.5);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(ctx.currentTime + i * 0.2);
                    osc.stop(ctx.currentTime + i * 0.2 + 0.5);
                });
            } catch(e) { console.log('Audio error:', e); }
        }

        function playReadySound() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                [783.99, 1046.5, 1318.5].forEach((freq, i) => {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'sine';
                    osc.frequency.value = freq;
                    gain.gain.setValueAtTime(0.4, ctx.currentTime + i * 0.15);
                    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + i * 0.15 + 0.4);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(ctx.currentTime + i * 0.15);
                    osc.stop(ctx.currentTime + i * 0.15 + 0.4);
                });
            } catch(e) { console.log('Audio error:', e); }
        }

        function playWaiterSound() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                for (let i = 0; i < 4; i++) {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'square';
                    osc.frequency.value = i % 2 === 0 ? 880 : 1100;
                    gain.gain.setValueAtTime(0.2, ctx.currentTime + i * 0.15);
                    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + i * 0.15 + 0.12);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(ctx.currentTime + i * 0.15);
                    osc.stop(ctx.currentTime + i * 0.15 + 0.12);
                }
            } catch(e) { console.log('Audio error:', e); }
        }

        function updateStatus(orderId, newStatus) {
            fetch(`/bar/orders/${orderId}/status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ status: newStatus })
            })
            .then(async (r) => {
                const data = await r.json();
                if (!r.ok || data.success === false) {
                    throw new Error(data.message || 'Durum guncellenemedi.');
                }
                return data;
            })
            .then(() => fetchData())
            .catch(err => console.error(err));
        }

        function attendWaiterCall(callId) {
            fetch(`/bar/waiter-calls/${callId}/attend`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(r => r.json())
            .then(data => { if (data.success) fetchData(); })
            .catch(err => console.error(err));
        }

        function confirmOrder(orderId) {
            updateStatus(orderId, 'preparing');
        }

        let _cancelPendingId = null;
        const _cancelModal = document.getElementById('cancel-modal');
        document.getElementById('cancel-modal-no').addEventListener('click', () => {
            _cancelModal.classList.add('hidden');
            _cancelPendingId = null;
        });
        document.getElementById('cancel-modal-yes').addEventListener('click', () => {
            _cancelModal.classList.add('hidden');
            if (_cancelPendingId === null) return;
            const orderId = _cancelPendingId;
            _cancelPendingId = null;

            // Optimistik: kart anında kaldır
            const card = document.querySelector(`[data-order-id="${orderId}"]`);
            if (card) card.remove();
            const grid = document.getElementById('orders-grid');
            if (grid && grid.querySelectorAll('[data-order-id]').length === 0) {
                grid.classList.add('hidden');
                document.getElementById('no-orders').classList.remove('hidden');
            }
            lastOrderKeys = ''; // Force re-render on next fetchData

            fetch(`/bar/orders/${orderId}/cancel`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
            })
            .then(async (r) => {
                const text = await r.text();
                let data = {};
                try { data = JSON.parse(text); } catch(e) {}
                if (!r.ok || data.success === false) {
                    const msg = data.message || ('HTTP ' + r.status);
                    showToast('İptal hatası: ' + msg, 'error');
                    lastOrderKeys = '';
                    fetchData();
                    return;
                }
                fetchData();
            })
            .catch(err => {
                showToast('Bağlantı hatası: ' + err.message, 'error');
                lastOrderKeys = '';
                fetchData();
            });
        });

        function cancelOrder(orderId) {
            _cancelPendingId = orderId;
            _cancelModal.classList.remove('hidden');
        }

        function showToast(msg, type = 'info') {
            const t = document.getElementById('bar-toast');
            t.textContent = msg;
            t.className = 'fixed top-4 left-1/2 -translate-x-1/2 z-[1000] px-4 py-3 rounded-lg shadow-xl text-sm font-bold text-white min-w-[260px] text-center ' +
                (type === 'error' ? 'bg-red-600' : 'bg-green-600');
            clearTimeout(t._to);
            t._to = setTimeout(() => t.classList.add('hidden'), 4000);
        }

        function undoReady(orderId) {
            fetch(`/kitchen/orders/${orderId}/status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ status: 'preparing' })
            })
            .then(async (r) => {
                const data = await r.json();
                if (!r.ok || data.success === false) {
                    throw new Error(data.message || 'Geri alinamadi.');
                }
                return data;
            })
            .then(() => fetchData())
            .catch(err => console.error(err));
        }

        function undoSymphonyReady(groupKey) {
            fetch(`/kitchen-pos/uncomplete`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ group_key: groupKey })
            })
            .then(async (r) => {
                const data = await r.json();
                if (!r.ok || data.success === false) {
                    throw new Error(data.message || 'Geri alinamadi.');
                }
                return data;
            })
            .then(() => fetchData())
            .catch(err => alert(err.message));
        }

        function markSymphonyDelivered(groupKey) {
            fetch(`/bar/symphony/delivered`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ group_key: groupKey })
            })
            .then(r => r.json())
            .then(() => { clearStartTime(groupKey); fetchData(); })
            .catch(err => console.error(err));
        }

        function markQrDelivered(orderId) {
            fetch(`/kitchen/orders/${orderId}/status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ status: 'completed' })
            })
            .then(r => r.json())
            .then(() => fetchData())
            .catch(err => console.error(err));
        }

        function renderReadyOrders(readyOrders, readyLimit = null) {
            if (!readyOrders || readyOrders.length === 0) {
                _readyCards = [];
                refreshTopBar();
                return;
            }
            _readyCards = readyOrders.map(order => {
                const isSymphony = order.source === 'symphony';
                let items = [];
                try { items = Array.isArray(order.items) ? order.items : JSON.parse(order.items); }
                catch(e) { items = []; }

                // Symphony check kaydında placeholder varsa lastSymOrders'dan gerçek ürünleri al
                if (isSymphony && items.length === 1 && items[0].name && items[0].name.startsWith('Adisyon #')) {
                    const match = lastSymOrders.find(s =>
                        s.group_key === order.group_key ||
                        (order.table_no && String(s.table_no) === String(order.table_no))
                    );
                    if (match && match.items && match.items.length > 0) {
                        items = match.items;
                    }
                }

                const itemSummary = items.map(i => {
                    const nm = i.name || (i.id ? getProductName(i.id) : '');
                    return `${nm} x${i.quantity || i.qty || 1}`;
                }).join(', ');

                const readySecs = order.ready_seconds !== null ? order.ready_seconds : order.seconds_ago;
                const timeStr = String(Math.floor(readySecs / 3600)).padStart(2,'0') + ':'
                              + String(Math.floor((readySecs % 3600) / 60)).padStart(2,'0') + ':'
                              + String(readySecs % 60).padStart(2,'0');
                const readyMinTotal = Math.floor(readySecs / 60);
                const readyTimeBg = timerBg(readyMinTotal, TIMER.ready);
                const hasNote = order.order_note && order.order_note.trim() !== '';
                const deliveredHtml = isSymphony
                    ? `<button onclick="markSymphonyDelivered('${order.group_key}')" class="mt-1.5 w-full py-0.5 bg-emerald-600 hover:bg-emerald-700 rounded text-white font-bold text-[10px]"><i class="fas fa-truck mr-1"></i>Servis Edildi</button>`
                    : `<button onclick="markQrDelivered(${order.id})" class="mt-1.5 w-full py-0.5 bg-emerald-600 hover:bg-emerald-700 rounded text-white font-bold text-[10px]"><i class="fas fa-truck mr-1"></i>Servis Edildi</button>`;
                const srcBadge = isSymphony
                    ? `<span class="px-1 py-0.5 rounded text-[9px] font-bold bg-blue-700 text-blue-100"><i class="fas fa-server"></i></span>`
                    : `<span class="px-1 py-0.5 rounded text-[9px] font-bold bg-orange-700 text-orange-100"><i class="fas fa-mobile-screen"></i></span>`;
                return `<div class="bg-emerald-950 rounded-lg p-2 text-xs">
                    <div class="flex items-center justify-between mb-1">
                        <span class="font-bold text-emerald-300">${order.table_no ? 'Masa ' + order.table_no : 'Paket'} ${srcBadge}</span>
                        <span class="ready-elapsed text-[10px] ${readyTimeBg} px-1.5 py-0.5 rounded text-white font-bold" data-order-time="${(order.order_time || '').replace(/['"<>&]/g, '')}">${timeStr}</span>
                    </div>
                    <p class="text-gray-300 text-[10px] truncate">${itemSummary || '—'}</p>
                    ${hasNote ? `<p class="text-yellow-400 text-[10px] truncate"><i class="fas fa-exclamation-triangle mr-0.5"></i>${order.order_note}</p>` : ''}
                    ${deliveredHtml}
                </div>`;
            });
            refreshTopBar();
        }

        function renderOrders(orders) {
            const grid = document.getElementById('orders-grid');
            const noOrders = document.getElementById('no-orders');

            if (orders.length === 0) {
                grid.classList.add('hidden');
                noOrders.classList.remove('hidden');
                return;
            }

            grid.classList.remove('hidden');
            noOrders.classList.add('hidden');

            // Stabil key: siparis id/group_key + status kombinasyonu
            // seconds_ago haric, yani sadece gercek degisim olunca yeniden render et
            const newKeys = orders.map(o => {
                const id = o.source === 'symphony' ? ('S:' + (o.group_key || o.check_number || o.table_no)) : ('Q:' + o.id);
                const status = o.bar_status || '';
                const itemCount = (o.items || []).length;
                return id + '|' + status + '|' + itemCount;
            }).join(',');

            if (newKeys === lastOrderKeys) {
                // Icerik degismedi — sadece elapsed sayaclarini guncelle, innerHTML dokunma
                return;
            }
            lastOrderKeys = newKeys;

            grid.innerHTML = orders.map(order => {
                const isSymphony = order.source === 'symphony';
                const isNew = !isSymphony && order.bar_status === 'new';

                // Symphony'ler her zaman onaylı sayılır (POS'a girilmiş)
                let borderClass, statusBg, statusText, sourceBadge;
                if (isSymphony) {
                    borderClass = 'border-blue-500';
                    statusBg = 'bg-blue-600';
                    statusText = 'POS';
                    sourceBadge = `<span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-700 text-blue-100"><i class="fas fa-server mr-0.5"></i>SYM</span>`;
                } else {
                    const inSym = order.in_symphony === true;
                    borderClass = isNew
                        ? (inSym ? 'new-order border-gold' : 'border-orange-500')
                        : 'border-green-500';
                    statusBg = isNew ? (inSym ? 'bg-yellow-500' : 'bg-orange-500') : 'bg-blue-500';
                    statusText = isNew ? (inSym ? 'YENI' : 'POS BEKLENIYOR') : 'MUTFAKTA';
                    sourceBadge = `<span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-orange-700 text-orange-100"><i class="fas fa-mobile-screen mr-0.5"></i>QR</span>`;
                }

                const totalSecs = order.seconds_ago || 0;
                const hrs = String(Math.floor(totalSecs / 3600)).padStart(2, '0');
                const mins = String(Math.floor((totalSecs % 3600) / 60)).padStart(2, '0');
                const secs = String(totalSecs % 60).padStart(2, '0');
                const timeStr = hrs + ':' + mins + ':' + secs;
                const minTotal = Math.floor(totalSecs / 60);
                // Symphony için bar eşikleri (5/10 dk), QR için eski eşikler (10/15 dk)
                const timeBg = timerBg(minTotal, isSymphony ? TIMER.sym : TIMER.qr);

                const gk = isSymphony ? symGroupKey(order) : null;
                const startTime = isSymphony ? getStartTime(gk, order.order_time) : (order.order_time || '');

                let items = [];
                try { items = Array.isArray(order.items) ? order.items : JSON.parse(order.items); }
                catch(e) { items = []; }

                const itemsHtml = items.map(item => {
                    const name = item.name || (item.id ? getProductName(item.id) : '');
                    const qty  = item.quantity || item.qty || 1;
                    const note = item.note || '';
                    return `<div class="flex justify-between py-0.5 border-b border-gray-700">
                        <span class="truncate pr-2">${name}${note ? ` <span class="text-yellow-400 text-xs">(${note})</span>` : ''}</span>
                        <span class="font-bold text-gold shrink-0">x${qty}</span>
                    </div>`;
                }).join('');

                const chkLabel = isSymphony && order.check_number ? `Chk #${order.check_number}` : (isSymphony ? '' : `Chk #${order.id}`);

                let footer;
                if (isSymphony) {
                    footer = `<div class="px-2 py-1 border-t border-gray-700">
                        <div class="w-full py-1 bg-blue-600/30 border border-blue-500 rounded text-xs font-bold text-center text-blue-200 flex items-center justify-center gap-1">
                            <i class="fas fa-server"></i> POS'ta
                        </div>
                    </div>`;
                } else {
                    const priceLine = order.total_price ? `<div class="px-2 py-1 border-t border-gray-700 flex justify-between items-center">
                        <span class="font-bold text-gold text-sm">${parseFloat(order.total_price).toFixed(2)} TL</span>
                    </div>` : '';
                    const inSym = order.in_symphony === true;
                    let btn;
                    if (isNew && inSym) {
                        btn = `<button onclick="confirmOrder(${order.id})" class="w-full py-0.5 bg-gold hover:bg-yellow-600 text-primary rounded text-[11px] font-bold transition flex items-center justify-center gap-1">
                                <i class="fas fa-check-circle"></i> Onayla (POS'ta var)
                            </button>`;
                    } else if (isNew && !inSym) {
                        btn = `<div class="flex gap-1">
                                <div class="flex-1 py-1 bg-gray-700 text-gray-400 rounded text-xs font-bold flex items-center justify-center gap-1">
                                    <i class="fas fa-hourglass-half animate-pulse"></i> POS bekleniyor...
                                </div>
                                <button onclick="cancelOrder(${order.id})" class="px-2 py-1 bg-red-700 hover:bg-red-600 text-white rounded text-[11px] font-bold transition flex items-center justify-center gap-1" title="Siparişi iptal et">
                                    <i class="fas fa-times"></i> İptal
                                </button>
                            </div>`;
                    } else {
                        btn = `<div class="w-full py-1 bg-blue-600/40 border border-blue-500 rounded text-xs font-bold text-center flex items-center justify-center gap-1 text-blue-100">
                                <i class="fas fa-utensils"></i> Mutfakta hazirlaniyor
                            </div>`;
                    }
                    footer = `${priceLine}<div class="px-2 py-1 border-t border-gray-700">${btn}</div>`;
                }

                return `
                <div class="bg-gray-800 rounded-lg border-2 ${borderClass} overflow-hidden" data-order-id="${order.id}">
                    <div class="px-2 py-1 bg-gray-750 border-b border-gray-700">
                        <div class="flex items-center justify-between">
                            <span class="text-xl font-bold text-gold">${order.table_no ? 'Masa ' + order.table_no : 'Paket'}</span>
                            <div class="flex items-center gap-1">
                                ${sourceBadge}
                                ${(!isSymphony && statusText === 'YENI') ? `<span class="px-1.5 py-0.5 rounded text-[10px] font-bold ${statusBg}">${statusText}</span>` : ''}
                            </div>
                        </div>
                        <div class="flex items-center justify-between mt-0.5">
                            <span class="text-[11px] text-gray-400">${chkLabel}</span>
                            <span class="bar-elapsed px-2 py-0.5 rounded text-sm font-bold ${timeBg}" data-order-time="${startTime.replace(/['"<>&]/g, '')}" data-is-symphony="${isSymphony ? '1' : '0'}">${timeStr}</span>
                        </div>
                        ${isSymphony && order.waiter_name ? `<div class="text-[11px] text-gray-300 mt-0.5"><i class="fas fa-user mr-1 text-gray-500"></i>${order.waiter_name}</div>` : ''}
                    </div>
                    <div class="px-2 py-1 text-sm">
                        ${itemsHtml || '<div class="text-gray-500 text-center py-1">Urun yok</div>'}
                        ${order.order_note ? `<div class="mt-1 p-1.5 bg-yellow-900/30 rounded text-yellow-300 text-xs"><i class="fas fa-sticky-note mr-1"></i>${order.order_note}</div>` : ''}
                    </div>
                    ${footer}
                </div>`;
            }).join('');
        }

        function renderPagination(totalPages) {
            const pager = document.getElementById('pagination-controls');
            if (totalPages <= 1) { pager.classList.add('hidden'); return; }
            pager.classList.remove('hidden');
            let html = `<span class="text-gray-400 text-xs mr-1">Sayfa</span>`;
            if (currentPage > 1) {
                html += `<button onclick="goPage(${currentPage - 1})" class="px-2 py-1 rounded bg-gray-700 hover:bg-gray-600 text-white text-sm"><i class="fas fa-chevron-left"></i></button>`;
            }
            for (let i = 1; i <= totalPages; i++) {
                const active = i === currentPage ? 'bg-gold text-gray-900 font-bold' : 'bg-gray-700 text-white hover:bg-gray-600';
                html += `<button onclick="goPage(${i})" class="px-3 py-1 rounded ${active} text-sm">${i}</button>`;
            }
            if (currentPage < totalPages) {
                html += `<button onclick="goPage(${currentPage + 1})" class="px-2 py-1 rounded bg-gray-700 hover:bg-gray-600 text-white text-sm"><i class="fas fa-chevron-right"></i></button>`;
            }
            pager.innerHTML = html;
        }

        function goPage(p) {
            currentPage = p;
            fetchData();
        }

        function renderCompletedOrders(completedOrders, limit, attendedCalls) {
            const grid  = document.getElementById('completed-grid');
            if (!grid) return;

            const hasCompleted = completedOrders && completedOrders.length > 0;
            const hasAttended  = attendedCalls && attendedCalls.length > 0;

            const prefix = `<span class="inline-flex items-center gap-1 text-xs text-emerald-400 font-bold shrink-0 mr-1"><i class="fas fa-check-double"></i> SON ${limit ? limit : ''}:</span>`;

            if (!hasCompleted && !hasAttended) {
                grid.innerHTML = prefix + `<span class="text-gray-500 text-xs">Henüz tamamlanan yok.</span>`;
                return;
            }

            const orderChips = (completedOrders || []).map(order => {
                let items = [];
                try { items = Array.isArray(order.items) ? order.items : JSON.parse(order.items); }
                catch(e) { items = []; }
                const summary = items.map(i => {
                    const nm = i.name || (i.id ? getProductName(i.id) : '');
                    return `${nm} x${i.quantity || 1}`;
                }).join(', ');
                const isSymphony = order.source === 'symphony';
                const srcBadge = isSymphony
                    ? `<span class="bg-blue-800 text-blue-200 text-[9px] px-1 rounded font-bold">SYM</span>`
                    : `<span class="bg-orange-800 text-orange-200 text-[9px] px-1 rounded font-bold">QR</span>`;
                const tableLabel = order.table_no ? 'M' + order.table_no : 'Pkt';
                const isCancelled = order.status === 'cancelled' || order.bar_status === 'cancelled';
                if (isCancelled) {
                    return `<span class="inline-flex items-center gap-1 bg-gray-800 border border-red-900 rounded px-2 py-1 text-xs text-red-400" style="min-width:160px;max-width:240px">
                        <i class="fas fa-ban text-red-600 shrink-0"></i>
                        <span class="font-bold shrink-0">${tableLabel}</span>${srcBadge}<span class="text-gray-400 truncate line-through">${summary || '—'}</span>
                    </span>`;
                }
                return `<span class="inline-flex items-center gap-1 bg-gray-800 border border-emerald-900 rounded px-2 py-1 text-xs text-emerald-300" style="min-width:160px;max-width:240px">
                    <i class="fas fa-check text-emerald-600 shrink-0"></i>
                    <span class="font-bold shrink-0">${tableLabel}</span>${srcBadge}<span class="text-gray-400 truncate">${summary || '—'}</span>
                </span>`;
            });

            const callChips = (attendedCalls || []).map(call => {
                const tableLabel = call.table_no ? 'M' + call.table_no : 'Gen';
                return `<span class="inline-flex items-center gap-1 bg-gray-800 border border-green-900 rounded px-2 py-1 text-xs text-green-300" style="min-width:120px;max-width:200px">
                    <i class="fas fa-bell-slash text-green-600 shrink-0"></i>
                    <span class="font-bold shrink-0">${tableLabel}</span><span class="text-gray-400 truncate">${call.note || 'Çağrı'}</span>
                </span>`;
            });

            grid.innerHTML = prefix + [...orderChips, ...callChips].join('');
        }

        function renderWaiterCalls(calls) {
            if (calls.length === 0) {
                _waiterCards = [];
                refreshTopBar();
                return;
            }
            _waiterCards = calls.map(call => {
                const minTotal = Math.floor(call.seconds_ago / 60);
                const timeBg = timerBg(minTotal, TIMER.waiter);
                const timeStr = String(Math.floor(call.seconds_ago / 3600)).padStart(2,'0') + ':' + String(Math.floor((call.seconds_ago % 3600) / 60)).padStart(2,'0') + ':' + String(call.seconds_ago % 60).padStart(2,'0');
                return `<div class="bg-red-950 rounded-lg p-2 text-xs">
                    <div class="flex items-center justify-between mb-1">
                        <span class="font-bold text-red-200 flex items-center gap-1"><i class="fas fa-bell text-red-400 waiter-alert text-[10px]"></i>${call.table_no ? 'Masa ' + call.table_no : 'Genel'}</span>
                        <span class="waiter-elapsed px-1.5 py-0.5 rounded text-[10px] ${timeBg}" data-order-time="${(call.order_time || '').replace(/['"<>&]/g, '')}">${timeStr}</span>
                    </div>
                    ${call.note ? `<p class="text-red-300 text-[10px] mb-1 truncate">${call.note}</p>` : ''}
                    <button onclick="attendWaiterCall(${call.id})" class="w-full py-0.5 bg-green-700 hover:bg-green-600 rounded text-[10px] font-bold transition"><i class="fas fa-check mr-0.5"></i>İlgilendi</button>
                </div>`;
            });
            refreshTopBar();
        }

        function renderSymphonyOrders(symphonyOrders) {
            // Artık kullanılmıyor — Symphony siparişleri ana grid'de QR ile birlikte renderlanıyor.
        }

        function fetchData() {
            Promise.all([
                fetch('/bar/api/orders').then(r => r.json()).catch(() => null),
                fetch('/bar/api/symphony').then(r => r.json()).catch(() => null),
            ]).then(([data, sym]) => {
                if (!data) return;

                // Symphony API basarisiz olursa son bilinen siparisleri kullan
                if (sym && sym.orders) {
                    lastSymOrders = sym.orders;
                }

                // QR + Symphony tek listede birlestir, en yeni ustte
                const qrOrders = (data.orders || []).map(o => ({ ...o, source: 'qr' }));
                const symOrders = lastSymOrders.map(o => ({
                    ...o,
                    source: 'symphony',
                    bar_status: 'approved',
                    created_at: (o.order_time || '').replace(/.*T(\d{2}:\d{2}).*/, '$1'), // HH:MM
                }));

                // Hibrit dogrulama: ayni masada Symphony girisi varsa QR Onayla aktif olsun
                const symphonyTables = new Set(symOrders.map(o => String(o.table_no || '')).filter(t => t !== ''));
                qrOrders.forEach(q => {
                    q.in_symphony = q.table_no ? symphonyTables.has(String(q.table_no)) : false;
                });

                const allOrders = [...qrOrders, ...symOrders].sort((a, b) => {
                    return (a.seconds_ago || 0) - (b.seconds_ago || 0);
                });

                // Header SYM/QR sayacını güncelle
                document.getElementById('header-sym-count').textContent = symOrders.length;
                document.getElementById('header-qr-count').textContent  = qrOrders.length;

                const currentOrderIds = qrOrders.map(o => o.id);
                const currentWaiterIds = (data.waiter_calls || []).map(c => c.id);

                if (!isFirstLoad) {
                    const newOrders = currentOrderIds.filter(id => !previousOrderIds.includes(id));
                    if (newOrders.length > 0) {
                        playOrderSound();
                    }

                    const newCalls = currentWaiterIds.filter(id => !previousWaiterIds.includes(id));
                    if (newCalls.length > 0) {
                        playWaiterSound();
                    }
                }

                const currentReadyIds = (data.ready_orders || []).map(o => o.id);
                if (!isFirstLoad) {
                    const newReady = currentReadyIds.filter(id => !previousReadyIds.includes(id));
                    if (newReady.length > 0) playReadySound();
                }

                previousOrderIds  = currentOrderIds;
                previousWaiterIds = currentWaiterIds;
                previousReadyIds  = currentReadyIds;
                isFirstLoad = false;

                renderOrders(allOrders);
                renderReadyOrders(data.ready_orders || [], data.ready_orders_limit || null);
                renderCompletedOrders(data.completed_orders || [], data.completed_orders_limit || null, data.attended_calls || []);
                renderWaiterCalls(data.waiter_calls || []);
            }).catch(err => console.error('Fetch error:', err));
        }

        // Sayaç renk eşikleri (admin ayarlarından, dakika cinsinden)
        // TIMER ve timerBg fetchData'dan ÖNCE tanımlanmalı
        const TIMER = {
            qr:     { yellow: {{ (int)\App\Models\Setting::get('timer_qr_yellow', 5) }},  orange: {{ (int)\App\Models\Setting::get('timer_qr_orange', 10) }},  red: {{ (int)\App\Models\Setting::get('timer_qr_red', 15) }} },
            sym:    { yellow: {{ (int)\App\Models\Setting::get('timer_sym_yellow', 3) }},  orange: {{ (int)\App\Models\Setting::get('timer_sym_orange', 6) }},   red: {{ (int)\App\Models\Setting::get('timer_sym_red', 10) }} },
            ready:  { yellow: {{ (int)\App\Models\Setting::get('timer_ready_yellow', 3) }}, orange: {{ (int)\App\Models\Setting::get('timer_ready_orange', 7) }}, red: {{ (int)\App\Models\Setting::get('timer_ready_red', 12) }} },
            waiter: { yellow: {{ (int)\App\Models\Setting::get('timer_waiter_yellow', 2) }}, orange: {{ (int)\App\Models\Setting::get('timer_waiter_orange', 5) }}, red: {{ (int)\App\Models\Setting::get('timer_waiter_red', 10) }} },
        };

        function timerBg(min, thresholds) {
            if (min >= thresholds.red)    return 'bg-red-600';
            if (min >= thresholds.orange) return 'bg-orange-500';
            if (min >= thresholds.yellow) return 'bg-yellow-600';
            return 'bg-green-600';
        }

        fetchData();
        setInterval(fetchData, 5000);

        // Her saniye tum elapsed sayaclarini guncelle (flicker olmadan)
        setInterval(function tickElapsed() {
            // Aktif siparis sayaci
            document.querySelectorAll('.bar-elapsed[data-order-time]').forEach(function(span) {
                const iso = span.dataset.orderTime;
                if (!iso) return;
                const d = new Date(iso.replace(' ', 'T'));
                if (isNaN(d.getTime())) return;
                const totalSecs = Math.max(0, Math.floor((Date.now() - d.getTime()) / 1000));
                const h = String(Math.floor(totalSecs / 3600)).padStart(2, '0');
                const m = String(Math.floor((totalSecs % 3600) / 60)).padStart(2, '0');
                const s = String(totalSecs % 60).padStart(2, '0');
                span.textContent = h + ':' + m + ':' + s;
                const minTotal = Math.floor(totalSecs / 60);
                const isSym = span.dataset.isSymphony === '1';
                const newBg = timerBg(minTotal, isSym ? TIMER.sym : TIMER.qr);
                ['bg-red-600','bg-orange-500','bg-yellow-600','bg-green-600'].forEach(c => span.classList.remove(c));
                span.classList.add(newBg);
            });
            // Hazir siparis sayaci
            document.querySelectorAll('.ready-elapsed[data-order-time]').forEach(function(span) {
                const iso = span.dataset.orderTime;
                if (!iso) return;
                const d = new Date(iso.replace(' ', 'T'));
                if (isNaN(d.getTime())) return;
                const totalSecs = Math.max(0, Math.floor((Date.now() - d.getTime()) / 1000));
                const h = String(Math.floor(totalSecs / 3600)).padStart(2, '0');
                const m = String(Math.floor((totalSecs % 3600) / 60)).padStart(2, '0');
                const s = String(totalSecs % 60).padStart(2, '0');
                span.textContent = h + ':' + m + ':' + s;
                const minTotal = Math.floor(totalSecs / 60);
                const newBg = timerBg(minTotal, TIMER.ready);
                ['bg-red-600','bg-orange-500','bg-yellow-600','bg-green-600','bg-emerald-800'].forEach(c => span.classList.remove(c));
                span.classList.add(newBg);
            });
            // Garson cagri sayaci
            document.querySelectorAll('.waiter-elapsed[data-order-time]').forEach(function(span) {
                const iso = span.dataset.orderTime;
                if (!iso) return;
                const d = new Date(iso.replace(' ', 'T'));
                if (isNaN(d.getTime())) return;
                const totalSecs = Math.max(0, Math.floor((Date.now() - d.getTime()) / 1000));
                const h = String(Math.floor(totalSecs / 3600)).padStart(2, '0');
                const m = String(Math.floor((totalSecs % 3600) / 60)).padStart(2, '0');
                const s = String(totalSecs % 60).padStart(2, '0');
                span.textContent = h + ':' + m + ':' + s;
                const minTotal = Math.floor(totalSecs / 60);
                const newBg = timerBg(minTotal, TIMER.waiter);
                ['bg-red-600','bg-orange-500','bg-yellow-600','bg-green-600'].forEach(c => span.classList.remove(c));
                span.classList.add(newBg);
            });
        }, 1000);

        document.addEventListener('click', function enableAudio() {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            ctx.resume();
            document.removeEventListener('click', enableAudio);
        }, { once: true });
    </script>
</body>
</html>
