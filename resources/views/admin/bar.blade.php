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
<body class="bg-gray-900 font-poppins text-white min-h-screen">
    <header class="bg-primary border-b border-gold/30 px-6 py-3 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <h1 class="text-2xl font-bold text-gold">
                <i class="fas fa-wine-glass mr-2"></i>{{ \App\Models\Setting::get('bar_screen_title', 'KDS - Bar Ekrani') }}
            </h1>
            <span id="clock" class="text-gray-400 text-lg"></span>
        </div>
        <div class="flex items-center gap-6">
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></span>
                <span class="text-sm text-gray-400">Canli</span>
            </div>
            <div class="text-sm text-gray-400">
                <span id="order-count" class="text-gold font-bold text-lg">0</span> aktif siparis
            </div>
            <button onclick="toggleFullscreen()" id="fs-btn" class="text-gray-400 hover:text-gold transition" title="Tam ekran">
                <i id="fs-icon" class="fas fa-expand"></i>
            </button>
            <a href="/admin" class="text-gray-400 hover:text-gold transition">
                <i class="fas fa-arrow-left mr-1"></i> Admin
            </a>
        </div>
    </header>

    <div id="waiter-bar" class="hidden bg-red-900/50 border-b border-red-500 px-6 py-3">
        <div class="flex items-center gap-3 mb-2">
            <i class="fas fa-bell text-red-400 text-xl waiter-alert"></i>
            <span class="font-bold text-red-300">GARSON CAGRILARI</span>
            <span id="waiter-count" class="text-red-400 text-sm"></span>
        </div>
        <div id="waiter-calls-list" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-7 gap-3"></div>
    </div>

    <!-- Sipariş Hazır Bölümü -->
    <div id="ready-bar" class="hidden bg-emerald-900/40 border-b border-emerald-500 px-6 py-4">
        <div class="flex items-center gap-3 mb-3">
            <i class="fas fa-concierge-bell text-emerald-400 text-xl animate-bounce"></i>
            <span class="font-bold text-emerald-300 text-lg">SİPARİŞ HAZIR — SERVISE GÖTÜR</span>
            <span id="ready-count" class="text-emerald-400 text-sm"></span>
            <span id="ready-limit" class="text-xs text-emerald-200 bg-emerald-700/50 px-2 py-0.5 rounded"></span>
        </div>
        <div id="ready-orders-list" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-7 gap-3"></div>
    </div>

    <main class="p-4">
        <div id="orders-grid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-2 items-start"></div>
        <!-- Sayfa geçiş butonları (sol alt, sabit) -->
        <div id="pagination-controls" class="hidden fixed bottom-4 left-4 z-50 flex items-center gap-1 bg-gray-800/95 border border-gray-600 rounded-xl px-3 py-2 shadow-2xl backdrop-blur-sm"></div>
        <div id="no-orders" class="hidden text-center py-20">
            <i class="fas fa-check-circle text-6xl text-green-500 mb-4"></i>
            <p class="text-2xl text-gray-400">Tum siparisler tamamlandi!</p>
            <p class="text-gray-500 mt-2">Yeni siparisler otomatik olarak gorunecek.</p>
        </div>

        <div id="completed-section" class="hidden mt-8 border-t border-gray-700 pt-6">
            <h2 class="text-lg font-semibold text-emerald-400 mb-3 flex items-center gap-2">
                <i class="fas fa-check-double"></i>
                Son Tamamlananlar
                <span id="completed-limit-badge" class="text-xs bg-gray-700 px-2 py-0.5 rounded-full text-gray-400"></span>
            </h2>
            <div id="completed-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-7 gap-3 opacity-80"></div>
        </div>
    </main>

    <script>
        let previousOrderIds = [];
        let previousWaiterIds = [];
        let previousReadyIds = [];
        let isFirstLoad = true;
        let currentPage = 1;
        const CARDS_PER_PAGE = {{ (int)\App\Models\Setting::get('bar_cards_per_page', 8) }};
        let lastSymOrders = [];   // Symphony API hata verince son bilinen siparisleri koru
        let lastOrderKeys = '';   // Flicker onlemek icin stabil key

        function updateClock() {
            const now = new Date();
            document.getElementById('clock').textContent = now.toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
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
            .then(() => fetchData())
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
            const bar   = document.getElementById('ready-bar');
            const list  = document.getElementById('ready-orders-list');
            const count = document.getElementById('ready-count');
            const limit = document.getElementById('ready-limit');

            if (!readyOrders || readyOrders.length === 0) {
                bar.classList.add('hidden');
                return;
            }

            bar.classList.remove('hidden');
            count.textContent = `(${readyOrders.length} adet)`;
            limit.textContent = readyLimit ? `son ${readyLimit}` : '';

            list.innerHTML = readyOrders.map(order => {
                const isSymphony = order.source === 'symphony';
                let items = [];
                try { items = Array.isArray(order.items) ? order.items : JSON.parse(order.items); }
                catch(e) { items = []; }
                const itemSummary = items.map(i => {
                    const nm = i.name || (i.id ? getProductName(i.id) : '');
                    return `${nm} x${i.quantity || 1}`;
                }).join(', ');
                const readySecs = order.ready_seconds !== null ? order.ready_seconds : order.seconds_ago;
                const timeStr = String(Math.floor(readySecs / 3600)).padStart(2,'0') + ':'
                              + String(Math.floor((readySecs % 3600) / 60)).padStart(2,'0') + ':'
                              + String(readySecs % 60).padStart(2,'0');
                const hasNote = order.order_note && order.order_note.trim() !== '';

                let undoHtml = '';
                // Bar ekraninda Geri Al butonu kaldirildi (sadece KDS'de gorunsun).

                const deliveredHtml = isSymphony
                    ? `<button onclick="markSymphonyDelivered('${order.group_key}')" class="mt-2 w-full py-1 bg-emerald-600 hover:bg-emerald-700 rounded text-white font-bold text-xs"><i class="fas fa-truck mr-1"></i>Servis Edildi</button>`
                    : `<button onclick="markQrDelivered(${order.id})" class="mt-2 w-full py-1 bg-emerald-600 hover:bg-emerald-700 rounded text-white font-bold text-xs"><i class="fas fa-truck mr-1"></i>Servis Edildi</button>`;

                const sourceBadge = isSymphony
                    ? `<span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-700 text-blue-100"><i class="fas fa-server mr-0.5"></i>SYM</span>`
                    : `<span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-purple-700 text-purple-100"><i class="fas fa-mobile-screen mr-0.5"></i>QR</span>`;

                const idLabel = isSymphony ? '' : `<span class="text-xs text-gray-400">#${order.id}</span>`;

                return `
                <div class="bg-emerald-900/60 border-2 border-emerald-400 rounded-lg p-3">
                    <div class="flex items-center justify-between mb-1">
                        <span class="font-bold text-emerald-300 text-base">${order.table_no ? 'Masa ' + order.table_no : 'Paket'} ${idLabel}</span>
                        <div class="flex items-center gap-1">
                            ${sourceBadge}
                            <span class="text-xs bg-emerald-700 px-2 py-0.5 rounded text-white">${timeStr}</span>
                        </div>
                    </div>
                    <p class="text-gray-300 text-xs truncate">${itemSummary}</p>
                    ${hasNote ? `<p class="mt-1 text-yellow-400 text-xs truncate"><i class="fas fa-exclamation-triangle mr-1 animate-pulse"></i>${order.order_note}</p>` : ''}
                    <div class="mt-2 flex items-center gap-1 text-emerald-400 text-xs font-bold">
                        <i class="fas fa-check-circle"></i> Siparis Hazir
                    </div>
                    ${undoHtml}
                    ${deliveredHtml}
                </div>`;
            }).join('');
        }

        function renderOrders(orders) {
            const grid = document.getElementById('orders-grid');
            const noOrders = document.getElementById('no-orders');

            if (orders.length === 0) {
                grid.classList.add('hidden');
                noOrders.classList.remove('hidden');
                document.getElementById('order-count').textContent = 0;
                renderPagination(0);
                return;
            }

            grid.classList.remove('hidden');
            noOrders.classList.add('hidden');
            document.getElementById('order-count').textContent = orders.length;

            const totalPages = Math.max(1, Math.ceil(orders.length / CARDS_PER_PAGE));
            if (currentPage > totalPages) currentPage = totalPages;
            const pageOrders = orders.slice((currentPage - 1) * CARDS_PER_PAGE, currentPage * CARDS_PER_PAGE);

            // Stabil key: siparis id/group_key + status kombinasyonu
            // seconds_ago haric, yani sadece gercek degisim olunca yeniden render et
            const newKeys = pageOrders.map(o => {
                const id = o.source === 'symphony' ? ('S:' + (o.group_key || o.check_number || o.table_no)) : ('Q:' + o.id);
                const status = o.bar_status || '';
                const itemCount = (o.items || []).length;
                return id + '|' + status + '|' + itemCount;
            }).join(',');

            if (newKeys === lastOrderKeys) {
                // Icerik degismedi — sadece elapsed sayaclarini guncelle, innerHTML dokunma
                renderPagination(totalPages);
                return;
            }
            lastOrderKeys = newKeys;

            grid.innerHTML = pageOrders.map(order => {
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
                    sourceBadge = `<span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-purple-700 text-purple-100"><i class="fas fa-mobile-screen mr-0.5"></i>QR</span>`;
                }

                const totalSecs = order.seconds_ago || 0;
                const hrs = String(Math.floor(totalSecs / 3600)).padStart(2, '0');
                const mins = String(Math.floor((totalSecs % 3600) / 60)).padStart(2, '0');
                const secs = String(totalSecs % 60).padStart(2, '0');
                const timeStr = hrs + ':' + mins + ':' + secs;
                const minTotal = Math.floor(totalSecs / 60);
                // Symphony için bar eşikleri (5/10 dk), QR için eski eşikler (10/15 dk)
                const timeBg = isSymphony
                    ? (minTotal >= 10 ? 'bg-red-600' : minTotal >= 5 ? 'bg-yellow-600' : 'bg-green-600')
                    : (minTotal > 15 ? 'bg-red-600' : minTotal > 10 ? 'bg-yellow-600' : 'bg-green-600');

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
                        btn = `<button disabled class="w-full py-1 bg-gray-700 text-gray-400 rounded text-xs font-bold flex items-center justify-center gap-1 cursor-not-allowed">
                                <i class="fas fa-hourglass-half animate-pulse"></i> POS bekleniyor...
                            </button>`;
                    } else {
                        btn = `<div class="w-full py-1 bg-blue-600/40 border border-blue-500 rounded text-xs font-bold text-center flex items-center justify-center gap-1 text-blue-100">
                                <i class="fas fa-utensils"></i> Mutfakta hazirlaniyor
                            </div>`;
                    }
                    footer = `${priceLine}<div class="px-2 py-1 border-t border-gray-700">${btn}</div>`;
                }

                return `
                <div class="bg-gray-800 rounded-lg border-2 ${borderClass} overflow-hidden">
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
                            <span class="bar-elapsed px-2 py-0.5 rounded text-xs ${timeBg}" data-order-time="${(order.order_time || '').replace(/['"<>&]/g, '')}" data-is-symphony="${isSymphony ? '1' : '0'}">${timeStr}</span>
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
            renderPagination(totalPages);
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
            const sect  = document.getElementById('completed-section');
            const grid  = document.getElementById('completed-grid');
            const badge = document.getElementById('completed-limit-badge');

            const hasCompleted = completedOrders && completedOrders.length > 0;
            const hasAttended  = attendedCalls && attendedCalls.length > 0;

            if (!hasCompleted && !hasAttended) {
                sect.classList.add('hidden');
                return;
            }

            sect.classList.remove('hidden');
            badge.textContent = limit ? ('son ' + limit) : '';

            const orderCards = (completedOrders || []).map(order => {
                let items = [];
                try { items = Array.isArray(order.items) ? order.items : JSON.parse(order.items); }
                catch(e) { items = []; }
                const summary = items.map(i => {
                    const nm = i.name || (i.id ? getProductName(i.id) : '');
                    return `${nm} x${i.quantity || 1}`;
                }).join(', ');
                const isSymphony = order.source === 'symphony';
                const idLabel = isSymphony
                    ? (order.check_number ? `<span class="text-gray-500">CHK #${order.check_number}</span>` : '')
                    : `<span class="text-gray-500">#${order.id}</span>`;
                const srcBadge = isSymphony
                    ? `<span class="px-1 py-0.5 rounded text-[9px] font-bold bg-blue-700 text-blue-100 ml-1">SYM</span>`
                    : `<span class="px-1 py-0.5 rounded text-[9px] font-bold bg-purple-700 text-purple-100 ml-1">QR</span>`;
                return `
                <div class="bg-gray-800 rounded-lg border-2 border-emerald-700 p-3 text-xs">
                    <div class="flex items-center justify-between mb-1">
                        <span class="font-bold text-emerald-400">${order.table_no ? 'Masa ' + order.table_no : 'Paket'} ${idLabel}${srcBadge}</span>
                    </div>
                    <p class="text-gray-300 truncate">${summary || '—'}</p>
                </div>`;
            });

            const callCards = (attendedCalls || []).map(call => `
                <div class="bg-gray-800 rounded-lg border-2 border-green-700 p-3 text-xs">
                    <div class="flex items-center justify-between mb-1">
                        <span class="font-bold text-green-400">
                            ${call.table_no ? 'Masa ' + call.table_no : 'Genel'}
                            <span class="px-1 py-0.5 rounded text-[9px] font-bold bg-green-800 text-green-200 ml-1"><i class="fas fa-bell-slash mr-0.5"></i>ÇAĞRI</span>
                        </span>
                        <span class="text-gray-500">${call.attended_at}</span>
                    </div>
                    <p class="text-gray-400 truncate">${call.note || 'Garson çağrısı ilgilenildi'}</p>
                </div>`);

            grid.innerHTML = [...orderCards, ...callCards].join('');
        }

        function renderWaiterCalls(calls) {
            const bar = document.getElementById('waiter-bar');
            const list = document.getElementById('waiter-calls-list');

            if (calls.length === 0) {
                list.innerHTML = '';
                bar.classList.add('hidden');
                return;
            }

            bar.classList.remove('hidden');
            document.getElementById('waiter-count').textContent = `(${calls.length} adet)`;
            list.innerHTML = calls.map(call => {
                const minTotal = Math.floor(call.seconds_ago / 60);
                const timeBg = minTotal > 10 ? 'bg-red-600' : minTotal > 5 ? 'bg-yellow-600' : 'bg-green-600';
                const timeStr = String(Math.floor(call.seconds_ago / 3600)).padStart(2, '0') + ':' + String(Math.floor((call.seconds_ago % 3600) / 60)).padStart(2, '0') + ':' + String(call.seconds_ago % 60).padStart(2, '0');
                return `
                <div class="bg-red-900/70 border border-red-500 rounded-lg p-3">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-lg font-bold text-white">${call.table_no ? 'Masa ' + call.table_no : 'Genel'}</span>
                        <span class="px-2 py-0.5 rounded text-xs ${timeBg}">${timeStr}</span>
                    </div>
                    ${call.note ? `<p class="text-red-300 text-sm mb-2 truncate"><i class="fas fa-comment mr-1"></i>${call.note}</p>` : ''}
                    <div class="flex items-center justify-end">
                        <button onclick="attendWaiterCall(${call.id})" class="px-3 py-1 bg-green-600 hover:bg-green-700 rounded text-xs font-bold transition">
                            <i class="fas fa-check mr-1"></i>Ilgilendi
                        </button>
                    </div>
                </div>`;
            }).join('');
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

        fetchData();
        setInterval(fetchData, 5000);

        // Her saniye elapsed sayaclarini guncelle (flicker olmadan)
        setInterval(function tickElapsed() {
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
                const newBg = isSym
                    ? (minTotal >= 10 ? 'bg-red-600' : minTotal >= 5 ? 'bg-yellow-600' : 'bg-green-600')
                    : (minTotal > 15 ? 'bg-red-600' : minTotal > 10 ? 'bg-yellow-600' : 'bg-green-600');
                ['bg-red-600','bg-yellow-600','bg-green-600'].forEach(c => span.classList.remove(c));
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
