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
        <div id="waiter-calls-list" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3"></div>
    </div>

    <!-- Sipariş Hazır Bölümü -->
    <div id="ready-bar" class="hidden bg-emerald-900/40 border-b border-emerald-500 px-6 py-4">
        <div class="flex items-center gap-3 mb-3">
            <i class="fas fa-concierge-bell text-emerald-400 text-xl animate-bounce"></i>
            <span class="font-bold text-emerald-300 text-lg">SİPARİŞ HAZIR — SERVISE GÖTÜR</span>
            <span id="ready-count" class="text-emerald-400 text-sm"></span>
            <span id="ready-limit" class="text-xs text-emerald-200 bg-emerald-700/50 px-2 py-0.5 rounded"></span>
        </div>
        <div id="ready-orders-list" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3"></div>
    </div>

    <main class="p-4">
        <!-- Symphony BDS canlı siparişler (read-only, sadece bilgi) -->
        <div id="symphony-section" class="hidden mb-6">
            <h2 class="text-lg font-semibold text-blue-300 mb-3 flex items-center gap-2">
                <i class="fas fa-server"></i>
                Symphony POS Siparişleri
                <span id="symphony-count" class="text-xs bg-blue-700/50 px-2 py-0.5 rounded-full text-blue-200"></span>
                <span class="text-xs text-gray-400 font-normal">(POS'tan canlı, otomatik güncellenir)</span>
            </h2>
            <div id="symphony-grid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3"></div>
        </div>

        <!-- QR Menu yeni siparişler (onay gerekir) -->
        <h2 class="text-lg font-semibold text-purple-300 mb-3 flex items-center gap-2 hidden" id="qr-section-title">
            <i class="fas fa-qrcode"></i>
            QR Menu Siparişleri
            <span class="text-xs text-gray-400 font-normal">(Onayla → Symphony'e işle)</span>
        </h2>
        <div id="orders-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4"></div>
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
            <div id="completed-grid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3 opacity-80"></div>
        </div>
    </main>

    <script>
        let previousOrderIds = [];
        let previousWaiterIds = [];
        let previousReadyIds = [];
        let isFirstLoad = true;

        function updateClock() {
            const now = new Date();
            document.getElementById('clock').textContent = now.toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        }
        setInterval(updateClock, 1000);
        updateClock();

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
                if (order.can_undo_ready) {
                    if (isSymphony) {
                        undoHtml = `<button onclick="undoSymphonyReady('${order.group_key}')" class="mt-2 w-full py-1 bg-amber-500 hover:bg-amber-600 rounded text-black font-bold text-xs">Geri Al (${order.undo_remaining_seconds}s)</button>`;
                    } else {
                        undoHtml = `<button onclick="undoReady(${order.id})" class="mt-2 w-full py-1 bg-amber-500 hover:bg-amber-600 rounded text-black font-bold text-xs">Geri Al (${order.undo_remaining_seconds}s)</button>`;
                    }
                }

                const deliveredHtml = isSymphony
                    ? `<button onclick="markSymphonyDelivered('${order.group_key}')" class="mt-2 w-full py-1 bg-emerald-600 hover:bg-emerald-700 rounded text-white font-bold text-xs"><i class="fas fa-truck mr-1"></i>Servis Edildi</button>`
                    : '';

                const sourceBadge = isSymphony
                    ? `<span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-700 text-blue-100"><i class="fas fa-server mr-0.5"></i>SYMPHONY</span>`
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
                return;
            }

            grid.classList.remove('hidden');
            noOrders.classList.add('hidden');
            document.getElementById('order-count').textContent = orders.length;

            grid.innerHTML = orders.map(order => {
                const isNew = order.bar_status === 'new';
                const borderClass = isNew ? 'new-order border-gold' : 'border-green-500';
                const statusBg = isNew ? 'bg-yellow-500' : 'bg-blue-500';
                const statusText = isNew ? 'YENI' : 'ONAYLANDI';
                const totalSecs = order.seconds_ago;
                const hrs = String(Math.floor(totalSecs / 3600)).padStart(2, '0');
                const mins = String(Math.floor((totalSecs % 3600) / 60)).padStart(2, '0');
                const secs = String(totalSecs % 60).padStart(2, '0');
                const timeStr = hrs + ':' + mins + ':' + secs;
                const minTotal = Math.floor(totalSecs / 60);
                const timeBg = minTotal > 15 ? 'bg-red-600' : minTotal > 10 ? 'bg-yellow-600' : 'bg-green-600';

                let items = [];
                try {
                    items = Array.isArray(order.items) ? order.items : JSON.parse(order.items);
                } catch(e) { items = []; }

                const itemsHtml = items.map(item =>
                    `<div class="flex justify-between py-1 border-b border-gray-700">
                        <span>${getProductName(item.id)}</span>
                        <span class="font-bold text-gold">x${item.quantity}</span>
                    </div>`
                ).join('');

                return `
                <div class="bg-gray-800 rounded-lg border-2 ${borderClass} overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-2 bg-gray-750">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl font-bold text-gold">
                                ${order.table_no ? 'Masa ' + order.table_no : 'Paket'}
                            </span>
                            <span class="px-2 py-1 rounded text-xs font-bold ${statusBg}">${statusText}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-1 rounded text-xs ${timeBg}">${timeStr}</span>
                            <span class="text-gray-400 text-sm">${order.created_at}</span>
                        </div>
                    </div>
                    <div class="px-4 py-3 text-sm">
                        ${itemsHtml}
                        ${order.order_note ? `<div class="mt-2 p-2 bg-yellow-900/30 rounded text-yellow-300 text-xs"><i class="fas fa-sticky-note mr-1"></i>${order.order_note}</div>` : ''}
                    </div>
                    <div class="px-4 py-2 border-t border-gray-700 flex justify-between items-center">
                        <span class="font-bold text-gold">${parseFloat(order.total_price).toFixed(2)} TL</span>
                    </div>
                    <div class="px-4 py-2 border-t border-gray-700">
                        ${isNew ? `<button onclick="confirmOrder(${order.id})" class="w-full py-2 bg-gold hover:bg-yellow-600 text-primary rounded-lg text-sm font-bold transition flex items-center justify-center gap-2">
                            <i class="fas fa-check-circle"></i> Onayla
                        </button>` : `<div class="w-full py-2 bg-green-600 rounded-lg text-sm font-bold text-center flex items-center justify-center gap-2 text-white">
                            <i class="fas fa-check-circle"></i> Onaylandi
                        </div>`}
                    </div>
                </div>`;
            }).join('');
        }

        function renderCompletedOrders(completedOrders, limit) {
            const sect  = document.getElementById('completed-section');
            const grid  = document.getElementById('completed-grid');
            const badge = document.getElementById('completed-limit-badge');

            if (!completedOrders || completedOrders.length === 0) {
                sect.classList.add('hidden');
                return;
            }

            sect.classList.remove('hidden');
            badge.textContent = limit ? ('son ' + limit) : '';
            grid.innerHTML = completedOrders.map(order => {
                let items = [];
                try { items = Array.isArray(order.items) ? order.items : JSON.parse(order.items); }
                catch(e) { items = []; }
                const summary = items.map(i => `${getProductName(i.id)} x${i.quantity}`).join(', ');
                return `
                <div class="bg-gray-800 rounded-lg border-2 border-emerald-700 p-3 text-xs">
                    <div class="flex items-center justify-between mb-1">
                        <span class="font-bold text-emerald-400">${order.table_no ? 'Masa ' + order.table_no : 'Paket'} <span class="text-gray-500">#${order.id}</span></span>
                        <span class="text-gray-500">${order.created_at}</span>
                    </div>
                    <p class="text-gray-300 truncate">${summary || '—'}</p>
                </div>`;
            }).join('');
        }

        function renderWaiterCalls(calls) {
            const bar = document.getElementById('waiter-bar');
            const list = document.getElementById('waiter-calls-list');

            if (calls.length === 0) {
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
                    <div class="flex items-center justify-between">
                        <span class="text-red-400 text-xs">${call.created_at}</span>
                        <button onclick="attendWaiterCall(${call.id})" class="px-3 py-1 bg-green-600 hover:bg-green-700 rounded text-xs font-bold transition">
                            <i class="fas fa-check mr-1"></i>Ilgilendi
                        </button>
                    </div>
                </div>`;
            }).join('');
        }

        function renderSymphonyOrders(symphonyOrders) {
            const section = document.getElementById('symphony-section');
            const grid    = document.getElementById('symphony-grid');
            const count   = document.getElementById('symphony-count');

            if (!symphonyOrders || symphonyOrders.length === 0) {
                section.classList.add('hidden');
                return;
            }
            section.classList.remove('hidden');
            count.textContent = symphonyOrders.length + ' adisyon';

            grid.innerHTML = symphonyOrders.map(o => {
                const secs = o.seconds_ago || 0;
                const mins = Math.floor(secs / 60);
                const timeStr = String(Math.floor(secs / 3600)).padStart(2,'0') + ':'
                              + String(Math.floor((secs % 3600) / 60)).padStart(2,'0') + ':'
                              + String(secs % 60).padStart(2,'0');
                // Renk eşikleri: <5dk yeşil, 5-10dk sarı, 10+dk kırmızı
                let cardCls, badgeCls;
                if (mins >= 10)      { cardCls = 'bg-red-950/60 border-red-500';        badgeCls = 'bg-red-600'; }
                else if (mins >= 5)  { cardCls = 'bg-yellow-900/40 border-yellow-500';  badgeCls = 'bg-yellow-600'; }
                else                 { cardCls = 'bg-blue-950/40 border-blue-500';      badgeCls = 'bg-green-600'; }

                const itemsHtml = (o.items || []).map(it =>
                    `<div class="flex justify-between text-xs py-0.5 border-b border-gray-700/40">
                        <span class="truncate pr-1">${it.name || ''}</span>
                        <span class="font-bold text-blue-200 shrink-0">x${it.qty || 1}</span>
                    </div>`
                ).join('');
                const checkLabel = o.check_number ? '#' + o.check_number : 'Adisyonsuz';

                return `
                <div class="rounded-lg border-2 p-3 ${cardCls}">
                    <div class="flex items-center justify-between mb-1">
                        <div class="flex items-center gap-1">
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-700 text-blue-100"><i class="fas fa-server mr-0.5"></i>SYMPHONY</span>
                            <span class="font-bold text-base text-white">${o.table_no ? 'M ' + o.table_no : '—'}</span>
                            <span class="text-[10px] text-gray-400">${checkLabel}</span>
                        </div>
                        <span class="text-xs ${badgeCls} px-2 py-0.5 rounded text-white font-bold">${timeStr}</span>
                    </div>
                    <div class="text-xs">${itemsHtml}</div>
                </div>`;
            }).join('');
        }

        function fetchData() {
            Promise.all([
                fetch('/bar/api/orders').then(r => r.json()).catch(() => null),
                fetch('/bar/api/symphony').then(r => r.json()).catch(() => null),
            ]).then(([data, sym]) => {
                if (!data) return;
                const currentOrderIds = data.orders.map(o => o.id);
                const currentWaiterIds = data.waiter_calls.map(c => c.id);

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

                renderOrders(data.orders);
                renderReadyOrders(data.ready_orders || [], data.ready_orders_limit || null);
                renderCompletedOrders(data.completed_orders || [], data.completed_orders_limit || null);
                renderWaiterCalls(data.waiter_calls);

                renderSymphonyOrders(sym && sym.orders ? sym.orders : []);

                // QR başlığı sadece QR siparişi varsa görünsün
                const qrTitle = document.getElementById('qr-section-title');
                if (qrTitle) qrTitle.classList.toggle('hidden', (data.orders || []).length === 0);
            }).catch(err => console.error('Fetch error:', err));
        }

        fetchData();
        setInterval(fetchData, 5000);

        document.addEventListener('click', function enableAudio() {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            ctx.resume();
            document.removeEventListener('click', enableAudio);
        }, { once: true });
    </script>
</body>
</html>
