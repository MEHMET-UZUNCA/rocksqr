<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>KDS - Mutfak Ekranı</title>
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
    <!-- Header -->
    <header class="bg-primary border-b border-gold/30 px-6 py-3 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <h1 class="text-2xl font-bold text-gold">
                <i class="fas fa-utensils mr-2"></i>KDS - Mutfak Ekranı
            </h1>
            <span id="clock" class="text-gray-400 text-lg"></span>
        </div>
        <div class="flex items-center gap-6">
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></span>
                <span class="text-sm text-gray-400">Canlı</span>
            </div>
            <div class="text-sm text-gray-400">
                <span id="order-count" class="text-gold font-bold text-lg">0</span> aktif sipariş
            </div>
            <a href="/admin" class="text-gray-400 hover:text-gold transition">
                <i class="fas fa-arrow-left mr-1"></i> Admin
            </a>
        </div>
    </header>

    <!-- Waiter Calls Bar -->
    <div id="waiter-bar" class="hidden bg-red-900/50 border-b border-red-500 px-6 py-3">
        <div class="flex items-center gap-3 mb-2">
            <i class="fas fa-bell text-red-400 text-xl waiter-alert"></i>
            <span class="font-bold text-red-300">GARSON ÇAĞRILARI</span>
            <span id="waiter-count" class="text-red-400 text-sm"></span>
        </div>
        <div id="waiter-calls-list" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3"></div>
    </div>

    <!-- Orders Grid -->
    <main class="p-4">
        <div id="orders-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <!-- Orders will be populated by JS -->
        </div>
        <div id="no-orders" class="hidden text-center py-20">
            <i class="fas fa-check-circle text-6xl text-green-500 mb-4"></i>
            <p class="text-2xl text-gray-400">Tüm siparişler tamamlandı!</p>
            <p class="text-gray-500 mt-2">Yeni siparişler otomatik olarak görünecek.</p>
        </div>
    </main>

    <!-- Audio elements for notifications -->
    <audio id="order-sound" preload="auto">
        <source src="data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgiJ2NdGFZW3KRo52Kc1xZXnqSm5eOgXRubnqIk5WTjIR+eHt/hYyRk5KOiYR/fH2AhYqOkZKRjoiDf3x9gIWKjpKSkY2HgX16e3+EiY2RkpGOiIF8eXp9goiMkJKSj4uFf3p5e36DhoyQk5KQjIaBe3h5fIGGi4+Sk5GNiIF7eHl7gIWKj5KTkY6IgXt4eHqAhImOkpOSj4uEfnl4eX2ChYuQk5ORjYeBe3d3eX2BhoyRk5OSjoiBenZ2d3yAhYuQk5SSjoqDfHd2d3t/hIqPk5WSj4yFf3l2dnh8gYaLkJOVk5CMhn94dXV3e4CFipCUlZOQjIZ+d3R0dXqAhYuRlJaTkY2Gfnd0dHV5f4WLkZWWk5KOh355dHN0eH6Ei5GVl5WTj4h+eHRyc3d9g4qRlZeWk5CJf3l0cnJ2fIKJkJWYl5WRi4B6dHFxdHuBh46TmJiWk4yBe3VxcHJ5f4aNk5iZl5SOg3x2cW9xd36EjJOYmpiVkIR9d3Fub3V8goqSlpmamJORhX54cm5udHqBiJCWmZqZlZKHgHlzbm1yeX+Hi5SZm5qXlI2DfHZwbW9zeYCFjJOYnJuZlpCHgXpzbW1xdnuCiI+VmZuamJWQiYJ7c21sbHN5f4aPl5ucm5mWkIqDfHRtbGxydnuBh4+WmpybmpaRi4V+dnBsa2xwd3yCiJCXm52cmpeTjIaAfHRuampsbnR6gYiQl5ydnJuXk42Ig350bWlpam50eoGIkJecnp2cm5aSjYiCfHZwamhpa25zeYCHjpWbnp6dnJiVkIuGgHt1b2poaGtudHqAh46Wm56fnp2ZlpGMh4F7dXBqaGdqbXN5f4aOlpuen5+enJiVkIuGgXx2cGtpaGlsb3V7gYeOlpuen5+fnpuXk4+KhYB7dm9raWhpa21zeIGHj5acn6CgoJ6cmJSTjoqFgHp0b2tpaWlrbHJ4f4WNlJqen6CgoJ6cmJWRjYmDfnl0b2ppaWlrbHF3fYSMlJqen6GhoJ+dmZaSkY2IhH96dXBsaWlpa2xxdnyDi5OanqChoaCgn52alZKOiYV/enVwbGppaWpscHV7goqSlpyfoKGioaCgn5yZlZKOioWAe3ZxbWppaWlrbnR6gYmSlpyfoaKioaCgnpyZlZKOioaBfHdzb2tpaWlrbHJ4fYSMk5meoKGioqKhoJ6cmZaSkYyHg356dW9samlpa2xwdXuBiZGXnJ+goqKioaCgnpyZlpOPi4eDfnp1cW5raWlqa25zeICIkJecn6GhoqKhoJ+enJqXlJCMiISAfHd0cGxqaWlqbHB1e4GIkJecn6ChoqKioaCfnZuYlZGNiYWBfXl1cW1raWlqa25yeICIj5abnaChoaKioaCfnZuYlZKOioaDf3t3c29tamlpamxvdHqAh46WmpyfoKGioqKgoJ6cmpiVkY2KhoJ/e3h0cW1raWlqam1xdnuBh46Vmpyfoaaho6Ghn5+dnJqXlJCMiYV/" >
    </audio>
    <audio id="waiter-sound" preload="auto">
        <source src="data:audio/wav;base64,UklGRpQFAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YXAFAACAgICAgICAgICAgICBgoSHi4+TlpmdoKKjoqCcmJOOiYWBfnx7e3x9f4KFiY2RlJeZmpudnJuZlpKOioaBfnx7e3x+gIOGio2QlJeZm5ycnJqYlZGNiYWBfnx7fH1/goWIjJCTlpibnJycm5mXlJCMiIR/fHt7fH6AgoaJjJCTlpibnJ2cnJqYlZGNiYR/fHp7fH5/goaJjJCUl5mbnJ2dnJqYlZGNiIR/fHp6e31/goWJjZCUl5qcnp6dnJqXlJCMh4N+e3p6e31/goaJjZGUl5qcnp+enZuYlZGMh4N+e3l5e31/g4aKjZGVmJqcnp+fnpyZlpKNiIN+e3l5en1/g4aKjpGVmJudnp+fnpyZlpKNiIN+eng5en1/g4eKjpKVmZudnp+gn52alZGMh4J9eXh4eX1/g4eKjpKWmZydoKCgn52alZGMhX95d3d4en1/g4iLj5OWmp2foKGhn52alZCLhX94d3Z3eXx/g4iMkJOXmp2foaGgoJ2alZCLhX54dnZ3eXuAg4iMkJSYm56goaKhoJ6bllCKhH13dXR2eHuAhImNkZSYnJ+goaKioJ6bllCJg3x1dHR1d3qAhImOkZWZnJ+hoquioJ6bllCIg3t0c3J0d3mAhIqOkpWZnaChouOioJ6bl5KIg3t0cnF0dnmAhIqOk5aanaCio+SjoZ+cl5KHgnp0cXBzdniAhYqPk5ebnqGjpOWko6Cdl5KHgXpzb29ydXiAhouQlJicn6KkpealoqCdmJKHgXlybm5xdHeAhouRlZmcoKKlpuempaKfm5eRhoB5cm1sbnF1eIGHjJGWmpygoqWnp+empaOgnJeRhX94cW1rbW9zeICHjJKWm56ho6ao6OinpaOgnJaSg354cGxqa21wd3+Gi5GWm56ho6ap6umnpaOgnJaRg354b2tpamtudnuEio+Ul5ueoqSm" >
    </audio>

    <script>
        let previousOrderIds = [];
        let previousWaiterIds = [];
        let isFirstLoad = true;

        function updateClock() {
            const now = new Date();
            document.getElementById('clock').textContent = now.toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        }
        setInterval(updateClock, 1000);
        updateClock();

        function getProductName(productId) {
            const products = @json(\App\Models\Product::pluck('name', 'id'));
            return products[productId] || 'Ürün #' + productId;
        }

        function playOrderSound() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                // Pleasant ding-dong for orders
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

        function playWaiterSound() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                // Urgent bell sound for waiter calls
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
            fetch(`/kitchen/orders/${orderId}/status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ status: newStatus })
            })
            .then(r => r.json())
            .then(data => { if (data.success) fetchData(); })
            .catch(err => console.error(err));
        }

        function attendWaiterCall(callId) {
            fetch(`/kitchen/waiter-calls/${callId}/attend`, {
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
                const isNew = order.status === 'new';
                const borderClass = isNew ? 'new-order border-gold' : 'border-green-500';
                const statusBg = isNew ? 'bg-yellow-500' : 'bg-blue-500';
                const statusText = isNew ? 'YENİ' : 'HAZIRLANIYOR';
                const totalSecs = order.confirmed_seconds !== null ? order.confirmed_seconds : order.seconds_ago;
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
                        <span class="font-bold text-gold">${parseFloat(order.total_price).toFixed(2)} ₺</span>
                    </div>
                    <div class="px-4 py-2 border-t border-gray-700">
                        ${isNew ? `<button onclick="confirmOrder(${order.id})" class="w-full py-2 bg-gold hover:bg-yellow-600 text-primary rounded-lg text-sm font-bold transition flex items-center justify-center gap-2">
                            <i class="fas fa-check-circle"></i> Onayla
                        </button>` : `<div class="w-full py-2 bg-green-600 rounded-lg text-sm font-bold text-center flex items-center justify-center gap-2 text-white">
                            <i class="fas fa-check-circle"></i> Onaylandı
                        </div>`}
                    </div>
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
                            <i class="fas fa-check mr-1"></i>İlgilendi
                        </button>
                    </div>
                </div>`;
            }).join('');
        }

        function fetchData() {
            fetch('/kitchen/api/orders')
                .then(r => r.json())
                .then(data => {
                    const currentOrderIds = data.orders.map(o => o.id);
                    const currentWaiterIds = data.waiter_calls.map(c => c.id);

                    if (!isFirstLoad) {
                        // Check for new orders
                        const newOrders = currentOrderIds.filter(id => !previousOrderIds.includes(id));
                        if (newOrders.length > 0) {
                            playOrderSound();
                        }

                        // Check for new waiter calls
                        const newCalls = currentWaiterIds.filter(id => !previousWaiterIds.includes(id));
                        if (newCalls.length > 0) {
                            playWaiterSound();
                        }
                    }

                    previousOrderIds = currentOrderIds;
                    previousWaiterIds = currentWaiterIds;
                    isFirstLoad = false;

                    renderOrders(data.orders);
                    renderWaiterCalls(data.waiter_calls);
                })
                .catch(err => console.error('Fetch error:', err));
        }

        // Initial load
        fetchData();

        // Auto-refresh every 5 seconds
        setInterval(fetchData, 5000);

        // Enable audio on first user interaction
        document.addEventListener('click', function enableAudio() {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            ctx.resume();
            document.removeEventListener('click', enableAudio);
        }, { once: true });
    </script>
</body>
</html>
