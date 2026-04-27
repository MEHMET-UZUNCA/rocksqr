<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#111827">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="RocksQR KDS">
    <meta name="mobile-web-app-capable" content="yes">
    <link rel="apple-touch-icon" href="/favicon.ico">
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
    <div id="toast-container" class="fixed top-4 right-4 z-[9999] flex flex-col gap-2 pointer-events-none"></div>
    <header class="bg-primary border-b border-gold/30 px-6 py-3 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <h1 class="text-2xl font-bold text-gold">
                <i class="fas fa-utensils mr-2"></i>
                {{ \App\Models\Setting::get('kitchen_screen_title', 'Mutfak Ekrani') }}
                <span class="text-sm text-gray-400 font-normal ml-2">Symphony POS</span>
            </h1>
            <span id="clock" class="text-gray-300 text-2xl font-bold"></span>
        </div>
        <div class="flex items-center gap-6">
            <div class="flex items-center gap-2">
                <span id="live-dot" class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></span>
                <span class="text-sm text-gray-400">Canli</span>
            </div>
            <div class="text-sm text-gray-400">
                <span id="order-count" class="text-gold font-bold text-lg">0</span> aktif hesap
                <span class="mx-2 text-gray-600">|</span>
                <span id="msg-count" class="text-yellow-400 font-bold text-lg">0</span> checksiz mesaj
                <span class="mx-2 text-gray-600">|</span>
                <span id="completed-today" class="text-emerald-400 font-bold text-lg">0</span> tamamlanan bugün
            </div>
            <a href="/kitchen" class="text-gray-400 hover:text-gold transition" title="Yerel KDS ekranı">
                <i class="fas fa-database mr-1"></i> Yerel
            </a>
            <a href="/kitchen-ana" class="text-gray-400 hover:text-teal-400 transition" title="Ana Mutfak KDS">
                <i class="fas fa-tv mr-1"></i> Ana Mutfak
            </a>
            <button onclick="toggleFullscreen()" class="text-gray-400 hover:text-gold transition" title="Tam ekran (F11)">
                <i id="fs-icon" class="fas fa-expand"></i>
            </button>
            <a href="/admin" class="text-gray-400 hover:text-gold transition">
                <i class="fas fa-arrow-left mr-1"></i> Admin
            </a>
        </div>
    </header>

    <main class="p-2">
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

        <div id="orders-grid" class="grid gap-2 items-start" data-cols="{{ \App\Models\Setting::get('kitchen_card_columns', 4) }}"></div>

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
        let previousMsgKeys = [];
        let isFirstLoad = true;

        function updateClock() {
            document.getElementById('clock').textContent = new Date().toLocaleTimeString('tr-TR');
        }
        setInterval(updateClock, 1000); updateClock();

        // Grid sütun sayısını data-cols attribute'undan al
        (function initGridCols() {
            const grid = document.getElementById('orders-grid');
            const n = parseInt(grid.dataset.cols) || 4;
            grid.style.gridTemplateColumns = 'repeat(' + n + ', minmax(0, 1fr))';
        })();

        function escapeHtml(s) {
            return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        }

        function showToast(message, type) {
            const c = document.getElementById('toast-container');
            if (!c) return;
            const bg = type === 'error' ? 'bg-red-600 border-red-400'
                     : type === 'success' ? 'bg-emerald-600 border-emerald-400'
                     : 'bg-gray-700 border-gray-500';
            const icon = type === 'error' ? 'fa-circle-exclamation'
                       : type === 'success' ? 'fa-circle-check'
                       : 'fa-circle-info';
            const el = document.createElement('div');
            el.className = `pointer-events-auto ${bg} text-white border-2 rounded-lg px-4 py-3 shadow-2xl flex items-center gap-3 min-w-[280px] max-w-md transform transition-all duration-300 translate-x-full opacity-0`;
            el.innerHTML = `<i class="fas ${icon} text-xl"></i><span class="font-medium text-sm flex-1">${escapeHtml(message)}</span>`;
            c.appendChild(el);
            requestAnimationFrame(() => { el.classList.remove('translate-x-full', 'opacity-0'); });
            setTimeout(() => {
                el.classList.add('translate-x-full', 'opacity-0');
                setTimeout(() => el.remove(), 350);
            }, 3500);
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

        // Mutfak mesajı için ayrı ses (alarm tarzı, dikkat çekici)
        function playMessageSound() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                // Iki kere üst-alt ikili bip (alarm hissi)
                [[880, 0], [660, 0.18], [880, 0.40], [660, 0.58]].forEach(([freq, t]) => {
                    const osc = ctx.createOscillator(); const gain = ctx.createGain();
                    osc.type = 'square'; osc.frequency.value = freq;
                    gain.gain.setValueAtTime(0.0001, ctx.currentTime + t);
                    gain.gain.exponentialRampToValueAtTime(0.35, ctx.currentTime + t + 0.02);
                    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + t + 0.16);
                    osc.connect(gain); gain.connect(ctx.destination);
                    osc.start(ctx.currentTime + t);
                    osc.stop(ctx.currentTime + t + 0.18);
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
            const isAddition = !!order.is_addition;
            const isReopened = !!order.is_reopened;
            const borderClass = isAddition ? 'border-orange-500' : (isReopened ? 'border-yellow-600' : (isNew ? 'new-order border-gold' : 'border-blue-500'));

            const itemsHtml = (order.items || []).map(it => {
                const isReturned  = !!it.is_returned;
                const isCombo     = !!it.is_combo;
                const isCond      = !!it.is_condiment;
                const textClass   = isReturned ? 'line-through text-red-400' : '';
                const qtyColor    = isReturned ? 'text-red-400' : (isCond ? 'text-amber-300' : 'text-gold');
                const badge = isReturned
                    ? `<span class="ml-1 px-1 py-0.5 rounded text-[9px] font-bold bg-red-700 text-white uppercase">İade</span>`
                    : (isCombo ? `<span class="ml-1 px-1 py-0.5 rounded text-[9px] font-bold bg-amber-800/80 text-amber-200 uppercase">Combo</span>` : '');

                const subHtml = (it.sub_items || []).map(sub => {
                    const subRet   = !!sub.is_returned;
                    const subText  = subRet ? 'line-through text-red-400' : 'text-gray-300';
                    const subBadge = subRet ? `<span class="ml-1 px-1 py-0.5 rounded text-[9px] font-bold bg-red-700 text-white">İade</span>` : '';
                    return `<div class="flex items-center pl-4 py-0 text-sm">
                        <span class="text-amber-600 mr-1.5 select-none">└</span>
                        <span class="${subText} font-medium">${escapeHtml(sub.name)}${subBadge}</span>
                        ${sub.note ? `<span class="text-yellow-300 ml-2 text-xs">— ${escapeHtml(sub.note)}</span>` : ''}
                    </div>`;
                }).join('');

                return `
                <div class="py-0.5 border-b border-gray-700">
                    <div class="flex justify-between items-start">
                        <div class="flex-1 min-w-0">
                            <div class="text-lg leading-tight ${textClass}">
                                <span class="${qtyColor} font-bold text-xl">x${it.qty}</span> <span class="font-semibold">${escapeHtml(it.name)}</span>${badge}
                            </div>
                            ${it.note ? `<div class="text-sm text-yellow-300"><i class="fas fa-comment-dots mr-1"></i>${escapeHtml(it.note)}</div>` : ''}
                        </div>
                        <div class="text-xs text-gray-500 ml-2 flex-shrink-0">${formatTime(it.item_time)}</div>
                    </div>
                    ${subHtml}
                </div>`;
            }).join('');

            // Mesajları LineKind'a göre ayır
            const mesajItems = (order.messages || []).filter(m => m.line_kind !== 'MARS');
            const marsItems  = (order.messages || []).filter(m => m.line_kind === 'MARS');

            const messagesHtml = mesajItems.length > 0 ? `
                <div class="mx-2 mb-1 p-1.5 bg-yellow-900/40 border border-yellow-500/60 rounded-lg">
                    <div class="text-xs text-yellow-400 font-bold uppercase mb-0.5">
                        <i class="fas fa-bullhorn mr-1"></i>Mutfak Mesajları
                    </div>
                    ${mesajItems.map(m => `
                        <div class="text-yellow-100 text-base leading-snug py-0.5">
                            ${m.note ? escapeHtml(m.note) : escapeHtml(m.name)}
                        </div>
                    `).join('')}
                </div>
            ` : '';

            const marsHtml = marsItems.length > 0 ? `
                <div class="mx-2 mb-1 p-1.5 bg-orange-950/70 border border-orange-500/60 rounded-lg">
                    <div class="text-xs text-orange-400 font-bold uppercase mb-0.5">
                        <i class="fas fa-fire mr-1"></i>Mars Mesajları
                    </div>
                    ${marsItems.map(m => `
                        <div class="text-orange-100 text-base leading-snug py-0.5">
                            <span class="text-orange-300 font-semibold">${escapeHtml(m.name.replace(/^[\s\-]+|[\s\-]+$/g,''))}</span>
                            ${m.note ? `<span class="text-orange-200">: <b>${escapeHtml(m.note)}</b></span>` : ''}
                        </div>
                    `).join('')}
                </div>
            ` : '';

            const checkLabel = order.check_number
                ? `Chk #${escapeHtml(order.check_number)}`
                : `<span class="text-yellow-400">CHECKSIZ</span>`;

            // Tüm unit_ids'leri düzleştir → served_item_keys fingerprint
            const allUnitIds = (order.items || []).flatMap(it =>
                (it.unit_ids && it.unit_ids.length) ? it.unit_ids : (it.item_id ? [String(it.item_id)] : [])
            );

            return `
            <div class="bg-gray-800 rounded-lg border-2 ${borderClass} overflow-hidden">
                <div class="px-2 py-1 bg-gray-750 border-b border-gray-700">
                    <div class="flex items-center justify-between">
                        <span class="text-xl font-bold text-gold">Masa ${escapeHtml(order.table_no || '-')}</span>
                        <div class="flex items-center gap-1">
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-700 text-blue-100"><i class="fas fa-server mr-0.5"></i>SYM</span>
                            ${isAddition ? `<span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-orange-600 text-white animate-pulse"><i class="fas fa-plus-circle mr-0.5"></i>EK</span>` : ''}
                            ${isReopened ? `<span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-yellow-700 text-yellow-100"><i class="fas fa-rotate-right mr-0.5"></i>YENİDEN</span>` : ''}
                        </div>
                    </div>
                    <div class="flex items-center justify-between mt-0.5">
                        <span class="text-[11px] text-gray-400">${checkLabel}</span>
                        <span class="elapsed-counter px-2 py-0.5 rounded text-xs ${timeBg}" data-order-time="${escapeHtml(order.order_time || '')}">${fmtElapsed(elapsed)}</span>
                    </div>
                    ${order.waiter_name ? `<div class="text-[11px] text-gray-300 mt-0.5"><i class="fas fa-user mr-1 text-gray-500"></i>${escapeHtml(order.waiter_name)}</div>` : ''}
                </div>
                <div class="px-2 py-1 text-sm">${itemsHtml || '<div class="text-gray-500 text-center py-1">Urun yok</div>'}</div>
                ${messagesHtml}${marsHtml}
                <div class="px-2 pt-1 pb-1 border-t border-gray-700">
                    <button data-complete-kind="check"
                            data-complete-gk="${escapeHtml(order.check_number ? String(order.check_number) : ('T' + (order.table_no || '')))}"
                            data-complete-cn="${escapeHtml(order.check_number ? String(order.check_number) : '')}"
                            data-complete-tno="${escapeHtml(String(order.table_no || ''))}"
                            data-complete-items="${escapeHtml(JSON.stringify(allUnitIds))}"
                            onclick="completeOrderFromBtn(this)"
                            class="w-full py-1 bg-emerald-600 hover:bg-emerald-700 rounded text-xs font-bold text-white">
                        <i class="fas fa-check-circle mr-1"></i>Onayla → Servis
                    </button>
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
                        <div class="text-lg leading-tight"><span class="text-purple-300 font-bold text-xl">x${it.qty}</span> <span class="font-semibold">${escapeHtml(it.name)}</span></div>
                    </div>
                </div>
            `).join('');

            return `
            <div class="bg-purple-950/40 rounded-lg border-2 border-purple-500 qr-card overflow-hidden">
                <div class="flex items-center justify-between px-4 py-2 bg-purple-900/40">
                    <div class="flex items-center gap-3">
                        <span class="text-xl font-bold text-purple-200">
                            <i class="fas fa-qrcode mr-1"></i>Masa ${escapeHtml(order.table_no || '-')}
                        </span>
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-purple-700 text-purple-100" title="QR Menu siparisi">
                            <i class="fas fa-mobile-screen mr-0.5"></i>QR MENU
                        </span>
                        <span class="px-2 py-1 rounded text-xs font-bold bg-purple-700 text-white">QR #${order.qr_order_id}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="elapsed-counter px-2 py-1 rounded text-xs ${timeBg}" data-order-time="${escapeHtml(order.order_time || '')}">${fmtElapsed(elapsed)}</span>
                    </div>
                </div>
                <div class="px-4 py-1 text-xs text-purple-300 border-b border-purple-800"><i class="fas fa-mobile-screen mr-1"></i>QR Menu siparişi</div>
                ${order.order_note ? `<div class="mx-4 mt-2 p-2 bg-yellow-900/40 border border-yellow-500/60 rounded text-yellow-200 text-xs"><i class="fas fa-sticky-note mr-1"></i>${escapeHtml(order.order_note)}</div>` : ''}
                <div class="px-4 py-3 text-sm text-purple-100">${itemsHtml || '<div class="text-gray-500 text-center py-2">Urun yok</div>'}</div>
                <div class="px-4 py-2 border-t border-purple-800">
                    <button onclick="confirmQr(${order.qr_order_id})" class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 rounded-lg text-sm font-bold text-white">
                        <i class="fas fa-check-circle mr-1"></i>Onayla → Servis
                    </button>
                </div>
            </div>`;
        }

        function buildCompletedOrderCard(order) {
            // Onaylanmis Symphony hesabi
            if (order.is_check) {
                const gkAttr = escapeHtml(order.group_key || '');
                const label = order.check_number ? ('Chk #' + escapeHtml(order.check_number)) : 'Checksiz';
                return `
                <div class="bg-gray-800 rounded-lg border-2 border-blue-700 p-3 text-xs">
                    <div class="flex items-center justify-between mb-1 gap-1">
                        <span class="font-bold text-blue-300">
                            <i class="fas fa-receipt mr-1"></i>${label}
                        </span>
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-700 text-blue-100">
                            <i class="fas fa-server mr-0.5"></i>SYMPHONY
                        </span>
                        <span class="text-gray-500 ml-auto">Masa ${escapeHtml(order.table_no || '-')}</span>
                    </div>
                    <p class="text-gray-300 italic">Hesap servise teslim edildi</p>
                    <button data-uncomplete-key="${gkAttr}"
                            onclick="uncomplete(this.dataset.uncompleteKey)"
                            class="mt-2 w-full py-1 bg-amber-500 hover:bg-amber-600 rounded text-black font-bold text-xs">
                        <i class="fas fa-undo mr-1"></i>Geri Al
                    </button>
                </div>`;
            }
            // Onaylanmis mutfak mesaji (Symphony kaynakli)
            if (order.is_message) {
                const gkAttr = escapeHtml(order.group_key || '');
                return `
                <div class="bg-yellow-900/30 rounded-lg border-2 border-yellow-600/60 p-3 text-xs">
                    <div class="flex items-center justify-between mb-1 gap-1">
                        <span class="font-bold text-yellow-300">
                            <i class="fas fa-bullhorn mr-1"></i>Mesaj
                        </span>
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-700 text-blue-100" title="Symphony POS hesabindan">
                            <i class="fas fa-server mr-0.5"></i>SYMPHONY
                        </span>
                        <span class="text-gray-500 ml-auto">Masa ${escapeHtml(order.table_no || '-')}</span>
                    </div>
                    <p class="text-yellow-100 truncate">
                        ${order.qty > 1 ? `<span class="text-yellow-400">x${order.qty}</span> ` : ''}${escapeHtml(order.name || '—')}
                        ${order.note ? ` <span class="text-yellow-400/80">— ${escapeHtml(order.note)}</span>` : ''}
                    </p>
                    <button data-uncomplete-key="${gkAttr}"
                            onclick="uncomplete(this.dataset.uncompleteKey)"
                            class="mt-2 w-full py-1 bg-amber-500 hover:bg-amber-600 rounded text-black font-bold text-xs">
                        <i class="fas fa-undo mr-1"></i>Geri Al
                    </button>
                </div>`;
            }
            // QR siparis tamamlanmasi
            const items = (order.items || []).map(i => `${i.name} x${i.qty}`).join(', ');
            return `
            <div class="bg-gray-800 rounded-lg border-2 border-emerald-700 p-3 text-xs">
                <div class="flex items-center justify-between mb-1 gap-1">
                    <span class="font-bold text-emerald-400">
                        <i class="fas fa-qrcode mr-1"></i>QR #${order.qr_order_id}
                    </span>
                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-purple-700 text-purple-100" title="QR Menu siparisi">
                        <i class="fas fa-mobile-screen mr-0.5"></i>QR MENU
                    </span>
                    <span class="text-gray-500 ml-auto">Masa ${escapeHtml(order.table_no || '-')}</span>
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
            const isMars = msg.line_kind === 'MARS';
            const borderColor = isMars ? 'border-orange-500/70' : 'border-yellow-500/70';
            const bgColor     = isMars ? 'bg-orange-950/40' : 'bg-yellow-900/30';
            const textColor   = isMars ? 'text-orange-300' : 'text-yellow-300';
            const icon        = isMars ? 'fa-fire' : 'fa-comment-dots';
            const title       = isMars
                ? escapeHtml(msg.name.replace(/^[\s\-]+|[\s\-]+$/g,''))
                : escapeHtml(msg.table_no ? 'Masa ' + msg.table_no : 'Mesaj');
            const body = isMars
                ? `<span class="${textColor} font-semibold">${escapeHtml(msg.name.replace(/^[\s\-]+|[\s\-]+$/g,''))}</span>${msg.note ? `: <b class="text-white">${escapeHtml(msg.note)}</b>` : ''}`
                : `${escapeHtml(msg.name)}${msg.note ? `<div class="text-xs text-yellow-200 mt-1">${escapeHtml(msg.note)}</div>` : ''}`;
            return `
            <div class="${bgColor} rounded-lg border-2 ${borderColor} msg-flash overflow-hidden p-3">
                <div class="flex items-center justify-between mb-2">
                    <span class="${textColor} font-bold">
                        <i class="fas ${icon} mr-1"></i>${title}
                    </span>
                    <span class="elapsed-counter text-xs ${textColor}" data-order-time="${escapeHtml(msg.item_time || '')}">${fmtElapsed(elapsed)}</span>
                </div>
                <div class="text-white font-medium">${body}</div>
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

        function completeOrderFromBtn(btn) {
            const itemKeys = JSON.parse(btn.dataset.completeItems || '[]');
            completeOrder(btn.dataset.completeKind, btn.dataset.completeGk, btn.dataset.completeCn, btn.dataset.completeTno, itemKeys);
        }

        function completeOrder(kind, groupKey, checkNumber, tableNo, itemKeys) {
            postJson('/kitchen-pos/complete', {
                kind, group_key: groupKey, check_number: checkNumber, table_no: tableNo,
                item_keys: itemKeys || [],
            }).then(() => fetchOnce()).catch(e => console.error(e));
        }

        function uncomplete(groupKey) {
            postJson('/kitchen-pos/uncomplete', { group_key: groupKey })
                .then(d => { if (d && d.success === false) showToast(d.message || 'Geri alınamadı.', 'error'); fetchOnce(); })
                .catch(e => console.error(e));
        }

        function confirmQr(orderId) {
            postJson('/kitchen-pos/qr/' + orderId + '/confirm', {}, 'PATCH')
                .then(() => fetchOnce()).catch(e => console.error(e));
        }

        function undoQr(orderId) {
            postJson('/kitchen-pos/qr/' + orderId + '/undo', {}, 'PATCH')
                .then(d => { if (d && d.success === false) showToast(d.message || 'Geri alınamadı.', 'error'); fetchOnce(); })
                .catch(e => console.error(e));
        }

        function render(data) {
            const orders = data.orders || [];
            const messages = data.messages || [];
            const completed = data.completed || [];
            const completedMsgs = data.completed_msgs || [];
            const completedChecks = data.completed_checks || [];
            const completedLimit = data.completed_limit || 6;

            document.getElementById('order-count').textContent = orders.length;
            document.getElementById('msg-count').textContent = messages.length;
            document.getElementById('completed-today').textContent = data.completed_today || 0;

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
            const allCompleted = [...completed, ...completedMsgs, ...completedChecks]
                .sort((a, b) => {
                    const ta = Date.parse(a.completed_at || 0) || 0;
                    const tb = Date.parse(b.completed_at || 0) || 0;
                    return tb - ta;
                })
                .slice(0, completedLimit);
            if (allCompleted.length > 0) {
                compSect.classList.remove('hidden');
                compBadge.textContent = `son ${completedLimit}`;
                compGrid.innerHTML = allCompleted.map(buildCompletedOrderCard).join('');
            } else {
                compSect.classList.add('hidden');
            }

            // Yeni sipariş sesi
            const ids = orders.map(o => o.source === 'qr' ? ('Q' + o.qr_order_id) : (o.check_number || ('T' + o.table_no)));
            // Tüm mesaj id'lerini topla (hem checksiz, hem hesap içi)
            const msgKeys = [];
            (messages || []).forEach(m => msgKeys.push('CL-' + (m.item_id || (m.table_no + '-' + (m.name || '')))));
            (orders || []).forEach(o => (o.messages || []).forEach(m => msgKeys.push('IN-' + ((o.check_number || o.table_no) + '-' + (m.item_id || m.name || '')))));

            if (!isFirstLoad) {
                const newOnes = ids.filter(id => !previousIds.includes(id));
                const newMsgs = msgKeys.filter(k => !previousMsgKeys.includes(k));
                if (newOnes.length > 0) playOrderSound();
                if (newMsgs.length > 0) playMessageSound();
            }
            previousIds = ids;
            previousMsgKeys = msgKeys;
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

        // Her saniye elapsed-counter span'larını güncelle (5sn polling'i beklemeden)
        setInterval(function tickElapsed() {
            document.querySelectorAll('.elapsed-counter[data-order-time]').forEach(function(span) {
                const iso = span.dataset.orderTime;
                if (!iso) return;
                const secs = elapsedSince(iso);
                if (secs == null) return;
                const minTotal = Math.floor(secs / 60);
                const newBg = minTotal > 15 ? 'bg-red-600' : minTotal > 10 ? 'bg-yellow-600' : 'bg-green-600';
                // Renk sınıfını güncelle
                ['bg-red-600','bg-yellow-600','bg-green-600','bg-teal-700','bg-teal-500'].forEach(c => span.classList.remove(c));
                span.classList.add(newBg);
                span.textContent = fmtElapsed(secs);
            });
        }, 1000);

        document.addEventListener('click', function enableAudio() {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            ctx.resume();
            document.removeEventListener('click', enableAudio);
        }, { once: true });

        // Tam ekran (F11 alternatifi, PWA olarak da çalışır)
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

        // PWA service worker (offline değil, sadece installable yapmak için)
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch(() => {});
            });
        }
    </script>
</body>
</html>
