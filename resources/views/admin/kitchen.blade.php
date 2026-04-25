<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ \App\Models\Setting::get('kitchen_screen_title', 'POOL Mutfak Ekrani') }}</title>
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
    </style>
</head>
<body class="bg-gray-900 font-poppins text-white min-h-screen">
    <header class="bg-primary border-b border-gold/30 px-6 py-3 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <h1 class="text-2xl font-bold text-gold">
                <i class="fas fa-utensils mr-2"></i>{{ \App\Models\Setting::get('kitchen_screen_title', 'POOL Mutfak Ekrani') }}
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
            <a href="/kitchen-pos" class="text-gray-400 hover:text-gold transition" title="Symphony POS KDS ekranı">
                <i class="fas fa-server mr-1"></i> Symphony
            </a>
            <a href="/admin" class="text-gray-400 hover:text-gold transition">
                <i class="fas fa-arrow-left mr-1"></i> Admin
            </a>
        </div>
    </header>

    <main class="p-4">
        <div id="orders-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4"></div>
        <div id="no-orders" class="hidden text-center py-20">
            <i class="fas fa-check-circle text-6xl text-green-500 mb-4"></i>
            <p class="text-2xl text-gray-400">Tum siparisler tamamlandi!</p>
            <p class="text-gray-500 mt-2">Yeni siparisler otomatik olarak gorunecek.</p>
        </div>

        <!-- Completed Orders Section -->
        <div id="completed-section" class="hidden mt-8 border-t border-gray-700 pt-6">
            <h2 class="text-lg font-semibold text-gray-400 mb-3 flex items-center gap-2">
                <i class="fas fa-check-double text-emerald-500"></i>
                Son Tamamlananlar
                <span id="completed-limit-badge" class="text-xs bg-gray-700 px-2 py-1 rounded-full text-gray-400"></span>
            </h2>
            <div id="completed-grid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3 opacity-60"></div>
        </div>
    </main>

    <script>
        let previousOrderIds = [];
        let isFirstLoad = true;
        let eventSource = null;
        let sseRetryTimeout = null;

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

        function updateStatus(orderId, newStatus) {
            fetch(`/kitchen/orders/${orderId}/status`, {
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
            .then(() => { /* SSE will push the update */ })
            .catch(err => console.error(err));
        }

        function confirmOrder(orderId) { updateStatus(orderId, 'preparing'); }
        function markReady(orderId)    { updateStatus(orderId, 'ready'); }

        function buildOrderCard(order, compact) {
            const kitchenStatus = order.kitchen_status;
            const isNew       = kitchenStatus === 'new';
            const isPreparing = kitchenStatus === 'preparing';
            const isReady     = kitchenStatus === 'ready';

            const borderClass = isNew ? 'new-order border-gold' : isReady ? 'border-emerald-500' : 'border-blue-500';
            const statusBg    = isNew ? 'bg-yellow-500' : isPreparing ? 'bg-blue-500' : 'bg-emerald-600';
            const statusText  = isNew ? 'YENI' : isPreparing ? 'HAZIRLANIYOR' : 'HAZIRLANDI';

            const totalSecs = isPreparing && order.preparing_seconds !== null
                ? order.preparing_seconds
                : isReady && order.ready_seconds !== null
                    ? order.ready_seconds
                    : order.seconds_ago;
            const hrs  = String(Math.floor(totalSecs / 3600)).padStart(2, '0');
            const mins = String(Math.floor((totalSecs % 3600) / 60)).padStart(2, '0');
            const secs = String(totalSecs % 60).padStart(2, '0');
            const timeStr  = hrs + ':' + mins + ':' + secs;
            const minTotal = Math.floor(totalSecs / 60);
            const timeBg   = minTotal > 15 ? 'bg-red-600' : minTotal > 10 ? 'bg-yellow-600' : 'bg-green-600';

            let items = [];
            try { items = Array.isArray(order.items) ? order.items : JSON.parse(order.items); }
            catch(e) { items = []; }

            const hasNote = order.order_note && order.order_note.trim() !== '';

            // Compact card (completed section)
            if (compact) {
                const itemSummary = items.map(i => `${getProductName(i.id)} x${i.quantity}`).join(', ');
                const undoHtml = order.can_undo_ready
                    ? `<button onclick="updateStatus(${order.id}, 'preparing')" class="mt-2 w-full py-1 bg-amber-500 hover:bg-amber-600 rounded text-black font-bold text-xs">Geri Al (${order.undo_remaining_seconds}s)</button>`
                    : '';
                return `
                <div class="bg-gray-800 rounded-lg border-2 border-emerald-700 p-3 text-xs">
                    <div class="flex items-center justify-between mb-1">
                        <span class="font-bold text-emerald-400">#${order.id}</span>
                        <span class="text-gray-500">${order.created_at}</span>
                    </div>
                    <p class="text-gray-300 truncate">${itemSummary}</p>
                    ${hasNote ? `<p class="mt-1 text-yellow-400 truncate"><i class="fas fa-exclamation-circle mr-1"></i>${order.order_note}</p>` : ''}
                    <div class="mt-1 text-emerald-500 font-bold">${timeStr}</div>
                    ${undoHtml}
                </div>`;
            }

            // Note pulse badge
            const noteBadge = hasNote ? `
                <div class="mx-4 mb-2 p-2 bg-yellow-900/40 border border-yellow-500/60 rounded-lg flex items-start gap-2">
                    <span class="text-yellow-400 animate-pulse mt-0.5 flex-shrink-0"><i class="fas fa-exclamation-triangle"></i></span>
                    <span class="text-yellow-300 text-sm font-medium">${order.order_note}</span>
                </div>` : '';

            const itemsHtml = items.map(item =>
                `<div class="flex justify-between py-1 border-b border-gray-700">
                    <span>${getProductName(item.id)}</span>
                    <span class="font-bold text-gold">x${item.quantity}</span>
                </div>`
            ).join('');

            const actionBtn = isNew
                ? `<button onclick="confirmOrder(${order.id})" class="w-full py-2 bg-gold hover:bg-yellow-600 text-primary rounded-lg text-sm font-bold transition flex items-center justify-center gap-2">
                       <i class="fas fa-check-circle"></i> Onayla
                   </button>`
                : isPreparing
                ? `<button onclick="markReady(${order.id})" class="w-full py-2 bg-amber-500 hover:bg-amber-600 rounded-lg text-sm font-bold transition flex items-center justify-center gap-2 text-black">
                       <i class="fas fa-bell-concierge"></i> Hazirla
                   </button>`
                : `<div class="w-full py-2 bg-emerald-700 rounded-lg text-sm font-bold text-center flex items-center justify-center gap-2 text-white">
                       <i class="fas fa-check-circle"></i> Hazirlandi
                   </div>`;

            return `
            <div class="bg-gray-800 rounded-lg border-2 ${borderClass} overflow-hidden">
                <div class="flex items-center justify-between px-4 py-2 bg-gray-750">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl font-bold text-gold">
                            Siparis #${order.id}
                            <span class="text-base text-gray-300 ml-2 inline-block">Masa ${order.table_no}</span>
                            ${hasNote ? ' <span class="text-yellow-400 text-base animate-pulse" title="Not var!"><i class="fas fa-sticky-note"></i></span>' : ''}
                        </span>
                        <span class="px-2 py-1 rounded text-xs font-bold ${statusBg}">${statusText}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-1 rounded text-xs ${timeBg}">${timeStr}</span>
                        <span class="text-gray-400 text-sm">${order.created_at}</span>
                    </div>
                </div>
                ${noteBadge}
                <div class="px-4 py-3 text-sm">${itemsHtml}</div>
                <div class="px-4 py-2 border-t border-gray-700 flex justify-between items-center">
                    <span class="font-bold text-gold">${parseFloat(order.total_price).toFixed(2)} TL</span>
                </div>
                <div class="px-4 py-2 border-t border-gray-700">${actionBtn}</div>
            </div>`;
        }

        function renderOrders(orders, completed, completedLimit) {
            const grid       = document.getElementById('orders-grid');
            const noOrders   = document.getElementById('no-orders');
            const compSect   = document.getElementById('completed-section');
            const compGrid   = document.getElementById('completed-grid');
            const compBadge  = document.getElementById('completed-limit-badge');
            const countEl    = document.getElementById('order-count');

            // Active orders
            if (orders.length === 0) {
                grid.classList.add('hidden');
                noOrders.classList.remove('hidden');
                countEl.textContent = '0';
            } else {
                grid.classList.remove('hidden');
                noOrders.classList.add('hidden');
                countEl.textContent = orders.length;
                grid.innerHTML = orders.map(o => buildOrderCard(o, false)).join('');
            }

            // Completed orders
            if (completed && completed.length > 0) {
                compSect.classList.remove('hidden');
                compBadge.textContent = `son ${completedLimit}`;
                compGrid.innerHTML = completed.map(o => buildOrderCard(o, true)).join('');
            } else {
                compSect.classList.add('hidden');
            }
        }

        function handleData(data) {
            const orders    = data.orders    || [];
            const completed = data.completed || [];
            const limit     = data.completed_limit || 6;

            const currentIds = orders.map(o => o.id);
            if (!isFirstLoad) {
                const newOnes = currentIds.filter(id => !previousOrderIds.includes(id));
                if (newOnes.length > 0) playOrderSound();
            }
            previousOrderIds = currentIds;
            isFirstLoad = false;
            renderOrders(orders, completed, limit);
        }

        // --- SSE with polling fallback ---
        let useFallbackPolling = false;

        function startSSE() {
            if (eventSource) { eventSource.close(); eventSource = null; }
            clearTimeout(sseRetryTimeout);

            eventSource = new EventSource('/kitchen/sse');

            eventSource.onmessage = function(event) {
                try { handleData(JSON.parse(event.data)); }
                catch(e) { console.error('SSE parse error', e); }
            };

            eventSource.onerror = function() {
                console.warn('SSE error, switching to polling fallback...');
                eventSource.close();
                eventSource = null;
                useFallbackPolling = true;
                // polling fallback every 3s
                sseRetryTimeout = setTimeout(startPollingFallback, 500);
            };
        }

        function startPollingFallback() {
            fetch('/kitchen/api/orders')
                .then(r => r.json())
                .then(handleData)
                .catch(e => console.error('Fetch error', e));
            sseRetryTimeout = setTimeout(startPollingFallback, 3000);
        }

        startSSE();

        document.addEventListener('click', function enableAudio() {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            ctx.resume();
            document.removeEventListener('click', enableAudio);
        }, { once: true });

        // Reconnect SSE after tab becomes visible again
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden && !useFallbackPolling) {
                startSSE();
            }
        });
    </script>
</body>
</html>
