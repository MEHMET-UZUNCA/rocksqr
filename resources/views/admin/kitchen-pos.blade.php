<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ \App\Models\Setting::get('kitchen_screen_title', 'Mutfak Ekrani') }} - Symphony POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: { extend: {
                colors: { primary: '#1a1a2e', gold: '#d4af37', 'light-primary': '#2a2a4e' },
                fontFamily: { poppins: ['Poppins', 'sans-serif'] }
            }}
        }
    </script>
    <style>
        @keyframes pulse-border { 0%,100% { border-color: #d4af37; } 50% { border-color: #ef4444; } }
        .new-order { animation: pulse-border 1.5s ease-in-out infinite; }
        @keyframes flash { 0%,100% { background-color: rgba(234,179,8,0.15);} 50% { background-color: rgba(234,179,8,0.45);} }
        .msg-flash { animation: flash 1.2s ease-in-out infinite; }
        @keyframes pulse-qr { 0%,100% { border-color: #a855f7; box-shadow: 0 0 0 0 rgba(168,85,247,0.4);} 50% { border-color: #d946ef; box-shadow: 0 0 0 6px rgba(168,85,247,0);} }
        .qr-card { animation: pulse-qr 2s ease-in-out infinite; }
    </style>
</head>
<body class="bg-gray-900 font-poppins text-white min-h-screen">
    <header class="bg-primary border-b border-gold/30 px-6 py-3 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <h1 class="text-2xl font-bold text-gold">
                <i class="fas fa-utensils mr-2"></i>
                {{ \App\Models\Setting::get('kitchen_screen_title', 'Mutfak Ekrani') }}
                <span class="text-sm text-gray-400 font-normal ml-2">Symphony POS</span>
            </h1>
            <span id="clock" class="text-gray-400 text-lg"></span>
        </div>
        <div class="flex items-center gap-6">
            <div class="flex items-center gap-2">
                <span id="live-dot" class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></span>
                <span class="text-sm text-gray-400">Canli</span>
                <span id="fetched-at" class="text-xs text-gray-500"></span>
            </div>
            <div class="text-sm text-gray-400">
                <span id="order-count" class="text-gold font-bold text-lg">0</span> aktif hesap
                <span class="mx-2 text-gray-600">|</span>
                <span id="msg-count" class="text-yellow-400 font-bold text-lg">0</span> checksiz mesaj
            </div>
            <a href="/kitchen" class="text-gray-400 hover:text-gold transition" title="Yerel KDS ekranı">
                <i class="fas fa-database mr-1"></i> Yerel
            </a>
            <a href="/admin" class="text-gray-400 hover:text-gold transition">
                <i class="fas fa-arrow-left mr-1"></i> Admin
            </a>
        </div>
    </header>

    <main class="p-4">
        <!-- Checksiz Mutfak Mesajları -->
        <div id="checkless-section" class="hidden mb-6">
            <h2 class="text-lg font-semibold text-yellow-400 mb-3 flex items-center gap-2">
                <i class="fas fa-comment-dots animate-pulse"></i>
                Checksiz Mutfak Mesajlari
                <span class="text-xs bg-yellow-900/50 px-2 py-0.5 rounded-full text-yellow-300">
                    <span id="checkless-badge">0</span>
                </span>
            </h2>
            <div id="checkless-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3"></div>
        </div>

        <div id="orders-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4"></div>

        <div id="no-orders" class="hidden text-center py-20">
            <i class="fas fa-check-circle text-6xl text-green-500 mb-4"></i>
            <p class="text-2xl text-gray-400">Acik siparis bulunamadi.</p>
            <p class="text-gray-500 mt-2">Symphony POS'tan yeni siparisler otomatik gorunecek.</p>
        </div>

        <!-- Tamamlananlar -->
        <div id="completed-section" class="hidden mt-8 border-t border-gray-700 pt-6">
            <h2 class="text-lg font-semibold text-emerald-400 mb-3 flex items-center gap-2">
                <i class="fas fa-check-double"></i>
                Son Tamamlananlar
                <span id="completed-limit-badge" class="text-xs bg-gray-700 px-2 py-0.5 rounded-full text-gray-400"></span>
            </h2>
            <div id="completed-grid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3 opacity-80"></div>
        </div>

        <div id="error-box" class="hidden mt-6 p-4 bg-red-900/40 border border-red-500/60 rounded-lg text-red-300">
            <i class="fas fa-exclamation-triangle mr-2"></i><span id="error-msg"></span>
        </div>
    </main>

    <script>
        let previousIds = [];
        let isFirstLoad = true;

        function updateClock() {
            document.getElementById('clock').textContent = new Date().toLocaleTimeString('tr-TR');
        }
        setInterval(updateClock, 1000); updateClock();

        function escapeHtml(s) {
            return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
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
            const secs = Math.max(0, Math.floor((Date.now() - d.getTime()) / 1000));
            return secs;
        }

        function fmtElapsed(secs) {
            if (secs == null) return '';
            const h = String(Math.floor(secs / 3600)).padStart(2,'0');
            const m = String(Math.floor((secs % 3600) / 60)).padStart(2,'0');
            const s = String(secs % 60).padStart(2,'0');
            return `${h}:${m}:${s}`;
        }

        function buildOrderCard(order) {
            // QR (yerel) siparişi için farklı stil ve farklı buton seti
            if (order.source === 'qr') {
                return buildQrOrderCard(order);
            }
            const elapsed = elapsedSince(order.order_time);
            const minTotal = elapsed ? Math.floor(elapsed / 60) : 0;
            const timeBg = minTotal > 15 ? 'bg-red-600' : minTotal > 10 ? 'bg-yellow-600' : 'bg-green-600';
            const isNew = elapsed != null && elapsed < 120;
            const borderClass = isNew ? 'new-order border-gold' : 'border-blue-500';

            const itemsHtml = (order.items || []).map(it => `
                <div class="flex justify-between items-start py-1 border-b border-gray-700">
                    <div class="flex-1 min-w-0">
                        <div class="truncate"><span class="text-gold font-bold">x${it.qty}</span> ${escapeHtml(it.name)}</div>
                        ${it.note ? `<div class="text-xs text-yellow-300 mt-0.5"><i class="fas fa-comment-dots mr-1"></i>${escapeHtml(it.note)}</div>` : ''}
                    </div>
                    <div class="text-xs text-gray-500 ml-2 flex-shrink-0">${formatTime(it.item_time)}</div>
                </div>
            `).join('');

            const messagesHtml = (order.messages || []).length > 0 ? `
                <div class="mx-4 mb-2 p-2 bg-yellow-900/40 border border-yellow-500/60 rounded-lg">
                    <div class="text-xs text-yellow-400 font-bold uppercase mb-1">
                        <i class="fas fa-bullhorn mr-1"></i>Mutfak Mesajlari
                    </div>
                    ${(order.messages || []).map(m => `
                        <div class="text-yellow-200 text-sm py-0.5">
                            <span class="text-yellow-400">${m.qty > 1 ? 'x'+m.qty+' ' : ''}</span>${escapeHtml(m.name)}
                            ${m.note ? ` <span class="text-yellow-400/80">— ${escapeHtml(m.note)}</span>` : ''}
                        </div>
                    `).join('')}
                </div>
            ` : '';

            const checkLabel = order.check_number
                ? `Hesap #${escapeHtml(order.check_number)}`
                : `<span class="text-yellow-400">CHECKSIZ</span>`;

            return `
            <div class="bg-gray-800 rounded-lg border-2 ${borderClass} overflow-hidden">
                <div class="flex items-center justify-between px-4 py-2 bg-gray-750">
                    <div class="flex items-center gap-3">
                        <span class="text-xl font-bold text-gold">
                            Masa ${escapeHtml(order.table_no || '-')}
                        </span>
                        <span class="px-2 py-1 rounded text-xs font-bold bg-gray-700 text-gray-200">${checkLabel}</span>
                        ${order.covers ? `<span class="text-xs text-gray-400"><i class="fas fa-user mr-1"></i>${order.covers}</span>` : ''}
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-1 rounded text-xs ${timeBg}">${fmtElapsed(elapsed)}</span>
                        <span class="text-gray-400 text-xs">${formatTime(order.order_time)}</span>
                    </div>
                </div>
                ${order.rvc ? `<div class="px-4 py-1 text-xs text-gray-400 border-b border-gray-700"><i class="fas fa-store mr-1"></i>${escapeHtml(order.rvc)}</div>` : ''}
                ${messagesHtml}
                <div class="px-4 py-3 text-sm">${itemsHtml || '<div class="text-gray-500 text-center py-2">Urun yok</div>'}</div>
                <div class="px-4 py-2 border-t border-gray-700 text-center text-xs text-gray-500">
                    <i class="fas fa-eye mr-1"></i>Symphony POS — sadece görüntüleme
                </div>
            </div>`;
        }

        function buildQrOrderCard(order) {
            const elapsed = elapsedSince(order.order_time);
            const minTotal = elapsed ? Math.floor(elapsed / 60) : 0;
            const timeBg = minTotal > 15 ? 'bg-red-600' : minTotal > 10 ? 'bg-yellow-600' : 'bg-green-600';

            const itemsHtml = (order.items || []).map(it => `
                <div class="flex justify-between items-start py-1 border-b border-gray-700">
                    <div class="flex-1 min-w-0">
                        <div class="truncate"><span class="text-purple-300 font-bold">x${it.qty}</span> ${escapeHtml(it.name)}</div>
                    </div>
                </div>
            `).join('');

            const symBadge = order.symphony_processed
                ? `<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-700 text-emerald-100" title="Symphony POS'a manuel girildi"><i class="fas fa-check mr-1"></i>SYMPHONY OK</span>`
                : `<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-red-700 text-red-100 animate-pulse" title="Symphony POS'a henüz işlenmedi"><i class="fas fa-exclamation mr-1"></i>SYMPHONY?</span>`;

            const symBtn = order.symphony_processed
                ? `<button onclick="toggleSymphony(${order.qr_order_id})" class="flex-1 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-xs font-bold text-gray-300"><i class="fas fa-undo mr-1"></i>Symphony işaretini kaldır</button>`
                : `<button onclick="toggleSymphony(${order.qr_order_id})" class="flex-1 py-2 bg-indigo-600 hover:bg-indigo-700 rounded-lg text-xs font-bold text-white"><i class="fas fa-database mr-1"></i>Symphony'e işlendi</button>`;

            return `
            <div class="bg-purple-950/40 rounded-lg border-2 border-purple-500 qr-card overflow-hidden">
                <div class="flex items-center justify-between px-4 py-2 bg-purple-900/40">
                    <div class="flex items-center gap-3">
                        <span class="text-xl font-bold text-purple-200">
                            <i class="fas fa-qrcode mr-1"></i>Masa ${escapeHtml(order.table_no || '-')}
                        </span>
                        <span class="px-2 py-1 rounded text-xs font-bold bg-purple-700 text-white">QR #${order.qr_order_id}</span>
                        ${symBadge}
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-1 rounded text-xs ${timeBg}">${fmtElapsed(elapsed)}</span>
                        <span class="text-gray-400 text-xs">${formatTime(order.order_time)}</span>
                    </div>
                </div>
                <div class="px-4 py-1 text-xs text-purple-300 border-b border-purple-800"><i class="fas fa-mobile-screen mr-1"></i>QR Menu siparişi</div>
                ${order.order_note ? `<div class="mx-4 mt-2 p-2 bg-yellow-900/40 border border-yellow-500/60 rounded text-yellow-200 text-xs"><i class="fas fa-sticky-note mr-1"></i>${escapeHtml(order.order_note)}</div>` : ''}
                <div class="px-4 py-3 text-sm text-purple-100">${itemsHtml || '<div class="text-gray-500 text-center py-2">Urun yok</div>'}</div>
                <div class="px-4 py-2 border-t border-purple-800 flex gap-2">
                    ${symBtn}
                    <button onclick="confirmQr(${order.qr_order_id})" class="flex-1 py-2 bg-emerald-600 hover:bg-emerald-700 rounded-lg text-sm font-bold text-white">
                        <i class="fas fa-check-circle mr-1"></i>Onayla → Servis
                    </button>
                </div>
            </div>`;
        }

        function buildCompletedOrderCard(order) {
            const items = (order.items || []).map(i => `${i.name} x${i.qty}`).join(', ');
            return `
            <div class="bg-gray-800 rounded-lg border-2 border-emerald-700 p-3 text-xs">
                <div class="flex items-center justify-between mb-1">
                    <span class="font-bold text-emerald-400">
                        <i class="fas fa-qrcode mr-1"></i>QR #${order.qr_order_id}
                    </span>
                    <span class="text-gray-500">Masa ${escapeHtml(order.table_no || '-')}</span>
                </div>
                <p class="text-gray-300 truncate">${escapeHtml(items) || '—'}</p>
                <button onclick="undoQr(${order.qr_order_id})"
                        class="mt-2 w-full py-1 bg-amber-500 hover:bg-amber-600 rounded text-black font-bold text-xs">
                    <i class="fas fa-undo mr-1"></i>Geri Al
                </button>
            </div>`;
        }

        function buildChecklessCard(msg) {
            const elapsed = elapsedSince(msg.item_time);
            const groupKey = 'M' + (msg.item_id || '');
            return `
            <div class="bg-yellow-900/30 rounded-lg border-2 border-yellow-500/70 msg-flash overflow-hidden p-3">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-yellow-300 font-bold">
                        <i class="fas fa-comment-dots mr-1"></i>${escapeHtml(msg.table_no ? 'Masa ' + msg.table_no : 'Mesaj')}
                    </span>
                    <span class="text-xs text-yellow-400">${fmtElapsed(elapsed)}</span>
                </div>
                <div class="text-white font-medium">
                    ${msg.qty > 1 ? `<span class="text-yellow-400">x${msg.qty}</span> ` : ''}${escapeHtml(msg.name)}
                </div>
                ${msg.note ? `<div class="text-xs text-yellow-200 mt-1">${escapeHtml(msg.note)}</div>` : ''}
                ${msg.rvc ? `<div class="text-xs text-gray-400 mt-1"><i class="fas fa-store mr-1"></i>${escapeHtml(msg.rvc)}</div>` : ''}
                <button onclick="completeOrder('checkless_msg', ${JSON.stringify(groupKey)}, '', ${JSON.stringify(msg.table_no || '')})"
                        class="mt-2 w-full py-1 bg-emerald-600 hover:bg-emerald-700 rounded text-white font-bold text-xs">
                    <i class="fas fa-check mr-1"></i>Onayla
                </button>
            </div>`;
        }

        function postJson(url, body, method) {
            return fetch(url, {
                method: method || 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: body ? JSON.stringify(body) : null,
            }).then(r => r.json());
        }

        function completeOrder(kind, groupKey, checkNumber, tableNo) {
            postJson('/kitchen-pos/complete', {
                kind, group_key: groupKey, check_number: checkNumber, table_no: tableNo,
            }).then(() => fetchOnce()).catch(e => console.error(e));
        }

        function uncomplete(groupKey) {
            postJson('/kitchen-pos/uncomplete', { group_key: groupKey })
                .then(() => fetchOnce()).catch(e => console.error(e));
        }

        function confirmQr(orderId) {
            postJson('/kitchen-pos/qr/' + orderId + '/confirm', {}, 'PATCH')
                .then(() => fetchOnce()).catch(e => console.error(e));
        }

        function undoQr(orderId) {
            postJson('/kitchen-pos/qr/' + orderId + '/undo', {}, 'PATCH')
                .then(d => { if (d && d.success === false) alert(d.message || 'Geri alınamadı.'); fetchOnce(); })
                .catch(e => console.error(e));
        }

        function toggleSymphony(orderId) {
            postJson('/kitchen-pos/qr/' + orderId + '/symphony', {}, 'PATCH')
                .then(() => fetchOnce()).catch(e => console.error(e));
        }

        function render(data) {
            const orders = data.orders || [];
            const messages = data.messages || [];
            const completed = data.completed || [];
            const completedMsgs = data.completed_msgs || [];
            const completedLimit = data.completed_limit || 6;

            document.getElementById('order-count').textContent = orders.length;
            document.getElementById('msg-count').textContent = messages.length;
            document.getElementById('fetched-at').textContent = data.fetched_at ? '· ' + data.fetched_at : '';

            const errBox = document.getElementById('error-box');
            const errMsg = document.getElementById('error-msg');
            if (data.success === false && data.message) {
                errBox.classList.remove('hidden');
                errMsg.textContent = data.message;
            } else {
                errBox.classList.add('hidden');
            }

            // Checksiz mesajlar
            const cs = document.getElementById('checkless-section');
            const csGrid = document.getElementById('checkless-grid');
            if (messages.length > 0) {
                cs.classList.remove('hidden');
                document.getElementById('checkless-badge').textContent = messages.length;
                csGrid.innerHTML = messages.map(buildChecklessCard).join('');
            } else {
                cs.classList.add('hidden');
            }

            // Hesaplar
            const grid = document.getElementById('orders-grid');
            const noOrders = document.getElementById('no-orders');
            if (orders.length === 0 && messages.length === 0) {
                grid.classList.add('hidden');
                noOrders.classList.remove('hidden');
            } else {
                noOrders.classList.add('hidden');
                grid.classList.remove('hidden');
                grid.innerHTML = orders.map(buildOrderCard).join('');
            }

            // Tamamlananlar
            const compSect = document.getElementById('completed-section');
            const compGrid = document.getElementById('completed-grid');
            const compBadge = document.getElementById('completed-limit-badge');
            const allCompleted = [...completed, ...completedMsgs];
            if (allCompleted.length > 0) {
                compSect.classList.remove('hidden');
                compBadge.textContent = `son ${completedLimit}`;
                compGrid.innerHTML = allCompleted.map(buildCompletedOrderCard).join('');
            } else {
                compSect.classList.add('hidden');
            }

            // Yeni sipariş sesi
            const ids = orders.map(o => o.source === 'qr' ? ('Q' + o.qr_order_id) : (o.check_number || ('T' + o.table_no)));
            if (!isFirstLoad) {
                const newOnes = ids.filter(id => !previousIds.includes(id));
                if (newOnes.length > 0) playOrderSound();
            }
            previousIds = ids;
            isFirstLoad = false;
        }

        function fetchOnce() {
            fetch('/kitchen-pos/api')
                .then(r => r.json())
                .then(render)
                .catch(e => {
                    document.getElementById('error-box').classList.remove('hidden');
                    document.getElementById('error-msg').textContent = 'Bağlantı hatası: ' + e.message;
                });
        }

        fetchOnce();
        setInterval(fetchOnce, 5000);

        document.addEventListener('click', function enableAudio() {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            ctx.resume();
            document.removeEventListener('click', enableAudio);
        }, { once: true });
    </script>
</body>
</html>
