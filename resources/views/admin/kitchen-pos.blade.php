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
        @keyframes kpos-chip-marquee { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }
        #kpos-ticker-inner { display: flex; align-items: stretch; gap: 4px; flex-wrap: nowrap; }
        .kpos-chip-text { display: inline-block; white-space: nowrap; will-change: transform; line-height: 1.2; }
        #kpos-completed-bar ::-webkit-scrollbar { display: none; }
        @keyframes iade-blink { 0%,100% { background-color: rgba(239,68,68,0.10); } 50% { background-color: rgba(239,68,68,0.35); } }
        .iade-blink { animation: iade-blink 0.9s ease-in-out infinite; border-radius: 4px; }
        .watermark { position: fixed; inset: 0; display: flex; align-items: center; justify-content: center; flex-direction: column; line-height: 1.04; pointer-events: none; user-select: none; overflow: hidden; z-index: 0; }
        .watermark span { font-weight: 800; letter-spacing: 0.05em; white-space: nowrap; color: rgba(255,255,255,0.06); font-size: min(8.5vw, 170px); text-align: center; }
        /* MBB-20 klavye seçim göstergeleri */
        .kbd-sel { box-shadow: 0 0 0 3px #ffffff, 0 0 16px rgba(255,255,255,0.35); }
        .kbd-row-sel { background: rgba(212,175,55,0.18); box-shadow: inset 3px 0 0 #d4af37; }
    </style>
</head>
<body class="bg-gray-900 font-poppins text-white min-h-screen">
    <div id="toast-container" class="fixed inset-0 z-[9999] flex flex-col items-center justify-center gap-3 pointer-events-none"></div>
    <header class="relative z-10 bg-primary px-3 py-1 flex items-center justify-between border-b border-gold/20">
        <div class="flex items-center gap-1 bg-yellow-900/60 border border-yellow-700 rounded px-2 py-0.5">
            <i class="fas fa-utensils text-gold text-[10px]"></i>
            <span class="text-gold font-bold text-sm">{{ \App\Models\Setting::get('kitchen_screen_title', 'Mutfak Ekrani') }} <span class="text-gray-500 font-normal text-xs">Symphony POS</span></span>
        </div>
        <div class="flex items-center gap-3">
            <span id="clock" class="text-gold font-bold text-[26px] leading-none tabular-nums"></span>
            <span class="text-gray-600 text-sm">|</span>
            <span id="clock-date" class="text-gray-300 text-[15px] font-medium"></span>
        </div>
        <div class="flex items-center gap-1.5">
            <div class="flex items-center gap-2 text-[10px]">
                <div class="flex items-center gap-1 bg-gray-800 border border-gray-700 rounded px-2 py-0.5">
                    <span id="order-count" class="text-gold font-bold text-sm">0</span>
                    <span class="text-gray-400">aktif hesap</span>
                </div>
                <div class="flex items-center gap-1 bg-gray-800 border border-gray-700 rounded px-2 py-0.5">
                    <span id="msg-count" class="text-yellow-400 font-bold text-sm">0</span>
                    <span class="text-gray-400">checksiz msg</span>
                </div>
                <div class="flex items-center gap-1 bg-gray-800 border border-gray-700 rounded px-2 py-0.5">
                    <span id="completed-today" class="text-emerald-400 font-bold text-sm">0</span>
                    <span class="text-gray-400">tamamlanan</span>
                </div>
            </div>
            <span id="live-dot" class="w-2 h-2 bg-green-500 rounded-full animate-pulse ml-1" title="Canlı"></span>
            <button onclick="toggleFullscreen()" class="text-gray-400 hover:text-gold transition text-sm px-1" title="Tam ekran">
                <i id="fs-icon" class="fas fa-expand text-sm"></i>
            </button>
        </div>
    </header>

    <main class="p-2 relative" style="padding-bottom:60px">
        <div class="watermark"><span>ROCKS SERVICES</span><span>KDS MUTFAK</span></div>
        @php
            $screenBg = \App\Models\Setting::get('screen_bg_image_kpos', '');
            $screenBgOpacity = (int) \App\Models\Setting::get('screen_bg_opacity_kpos', 30);
            $screenBgSize = (int) \App\Models\Setting::get('screen_bg_size_kpos', 60);
        @endphp
        @if($screenBg)
        <div style="position:fixed;inset:0;display:flex;align-items:center;justify-content:center;pointer-events:none;user-select:none">
            <img src="{{ asset('images/' . $screenBg) }}?v={{ @filemtime(public_path('images/' . $screenBg)) }}" alt=""
                 style="max-height:{{ $screenBgSize }}vh;max-width:{{ $screenBgSize }}vw;object-fit:contain;opacity:{{ round($screenBgOpacity / 100, 2) }}">
        </div>
        @endif
        <!-- Checksiz Mutfak Mesajları -->
        <div id="checkless-section" class="hidden mb-6 relative">
            <h2 class="text-lg font-semibold text-yellow-400 mb-3 flex items-center gap-2">
                <i class="fas fa-comment-dots animate-pulse"></i>
                Checksiz Mutfak Mesajlari
                <span class="text-xs bg-yellow-900/50 px-2 py-0.5 rounded-full text-yellow-300">
                    <span id="checkless-badge">0</span>
                </span>
            </h2>
            <div id="checkless-grid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3"></div>
        </div>

        <div id="orders-grid" class="grid gap-2 items-start relative" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr))"></div>

        <div id="no-orders" class="hidden text-center py-20">
            <i class="fas fa-check-circle text-6xl text-green-500 mb-4"></i>
            <p class="text-2xl text-gray-400">Acik siparis bulunamadi.</p>
            <p class="text-gray-500 mt-2">Symphony POS'tan yeni siparisler otomatik gorunecek.</p>
        </div>

        <!-- Son tamamlananlar artık sabit alt şeritte gösteriliyor -->

        <div id="error-box" class="hidden mt-6 p-4 bg-red-900/40 border border-red-500/60 rounded-lg text-red-300">
            <i class="fas fa-exclamation-triangle mr-2"></i><span id="error-msg"></span>
        </div>
    </main>

    <!-- Son Tamamlananlar: sabit alt şerit (2 satır kart) -->
    <div id="kpos-completed-bar" class="fixed bottom-0 left-0 right-0 bg-gray-900 border-t border-gray-700 px-2 py-1" style="z-index:50">
        <div class="flex items-start gap-2">
            <div class="flex flex-col items-center justify-center shrink-0 border-r border-gray-700 pr-2 mr-0.5" style="min-width:42px">
                <span class="text-emerald-400 font-bold text-[11px] leading-none">SON</span>
                <span id="kpos-completed-limit" class="text-emerald-300 font-bold text-base leading-tight tabular-nums">—</span>
            </div>
            <div class="flex-1 overflow-x-auto" style="-webkit-overflow-scrolling:touch;scrollbar-width:none">
                <div id="kpos-ticker-inner" class="h-full"><span class="text-gray-600 text-xs italic flex items-center h-full">Henüz tamamlanan yok.</span></div>
            </div>
        </div>
    </div>

    <script>
        let previousIds = [];
        let previousMsgKeys = [];
        let isFirstLoad = true;
        let lastCompletedBarKey = '';

        // ── MBB-20 Kısayolları (Admin → Ayarlar → Diğer Ayarlar) ──────────
        @php
            $kbdDefaults = [
                'home' => 'a', 'end' => '1',
                'card_prev' => 'c', 'card_next' => '3',
                'item_prev' => '2', 'item_next' => 'b',
                'ok' => '7', 'cancel' => '6',
                'done1' => 'f', 'done2' => 'g', 'done3' => 'h', 'done4' => 'ı',
                'select_done' => 'j', 'recall' => '5',
            ];
            $kbdCfg = [];
            foreach ($kbdDefaults as $kbdKey => $kbdDef) {
                $kbdVal = mb_strtolower(trim((string) \App\Models\Setting::get('kitchen_sc_' . $kbdKey, $kbdDef)));
                $kbdCfg[$kbdKey] = $kbdVal !== '' ? $kbdVal : $kbdDef;
            }
        @endphp
        const KBD = @json($kbdCfg);
        const KBD_RECALL_WINDOW = @json((int) \App\Models\Setting::get('kitchen_sc_recall_window', 30));

        let kbdSel = { gk: null, mode: 'card', idx: -1 };
        let kbdRecallStack = [];

        function kbdCards() {
            return Array.from(document.querySelectorAll('#orders-grid [data-kbd-gk]'));
        }
        function kbdCardEl(gk) {
            return kbdCards().find(c => c.dataset.kbdGk === gk) || null;
        }
        function kbdRows(card) {
            return Array.from(card.querySelectorAll('.kbd-row:not([data-kbd-ret="1"])'));
        }
        function applyKbdSel() {
            document.querySelectorAll('#orders-grid .kbd-sel').forEach(el => el.classList.remove('kbd-sel'));
            document.querySelectorAll('#orders-grid .kbd-row-sel').forEach(el => el.classList.remove('kbd-row-sel'));
            if (!kbdSel.gk) return;
            const card = kbdCardEl(kbdSel.gk);
            if (!card) { kbdSel = { gk: null, mode: 'card', idx: -1 }; return; }
            card.classList.add('kbd-sel');
            if (kbdSel.mode === 'item') {
                const rows = kbdRows(card);
                if (!rows.length) { kbdSel.mode = 'card'; return; }
                kbdSel.idx = Math.min(Math.max(kbdSel.idx, 0), rows.length - 1);
                rows[kbdSel.idx].classList.add('kbd-row-sel');
            }
        }
        function kbdSelectCard(gk) {
            if (!kbdCardEl(gk)) return;
            kbdSel = { gk, mode: 'card', idx: -1 };
            applyKbdSel();
        }
        function kbdMoveCard(dir) {
            const cards = kbdCards();
            if (!cards.length) return;
            if (!kbdSel.gk) { kbdSelectCard(cards[dir > 0 ? 0 : cards.length - 1].dataset.kbdGk); return; }
            const at = cards.findIndex(c => c.dataset.kbdGk === kbdSel.gk);
            kbdSelectCard(cards[Math.min(Math.max(at + dir, 0), cards.length - 1)].dataset.kbdGk);
        }
        function kbdMoveItem(dir) {
            if (!kbdSel.gk) {
                const c = kbdCards();
                if (!c.length) return;
                kbdSelectCard(c[0].dataset.kbdGk);
            }
            const card = kbdCardEl(kbdSel.gk);
            if (!card) return;
            if (card.dataset.kbdQr) { showToast('QR sipariş: SELECT/DONE ile onaylanır.', 'default'); return; }
            const rows = kbdRows(card);
            if (!rows.length) return;
            if (kbdSel.mode !== 'item') { kbdSel.mode = 'item'; kbdSel.idx = dir > 0 ? 0 : rows.length - 1; }
            else { kbdSel.idx = Math.min(Math.max(kbdSel.idx + dir, 0), rows.length - 1); }
            applyKbdSel();
        }
        function kbdServeNth(n) {
            if (!kbdSel.gk) { showToast('Önce kart seçin — HOME, END veya SAĞ/SOL YÖN.', 'error'); return; }
            const card = kbdCardEl(kbdSel.gk);
            if (!card) return;
            if (card.dataset.kbdQr) { showToast('QR siparişte ürün tek tek hazırlanmaz; SELECT/DONE ile onaylayın.', 'error'); return; }
            const rows = kbdRows(card).filter(r => r.querySelector('[data-item-ready]'));
            const row = rows[n - 1];
            if (!row) { showToast(n + '. sırada hazırlanacak ürün yok.', 'error'); return; }
            serveItem(row.querySelector('[data-item-ready]'));
        }
        function kbdSelectDone() {
            if (!kbdSel.gk) { showToast('Önce kart veya ürün seçin.', 'error'); return; }
            const card = kbdCardEl(kbdSel.gk);
            if (!card) return;
            if (card.dataset.kbdQr) { confirmQr(Number(card.dataset.kbdQr)); return; }
            if (kbdSel.mode === 'item') {
                kbdSel.mode = 'card';
                kbdSel.idx = -1;
                applyKbdSel();
                return;
            }
            const completeBtn = card.querySelector('[data-complete-gk]');
            if (completeBtn) completeOrderFromBtn(completeBtn);
        }
        function kbdPushRecall(entry) {
            kbdRecallStack.push(Object.assign({ ts: Date.now() }, entry));
            if (kbdRecallStack.length > 20) kbdRecallStack.shift();
        }
        function kbdRecall() {
            const last = kbdRecallStack.pop();
            if (!last) { showToast('Geri çağrılacak işlem yok.', 'default'); return; }
            if (Date.now() - last.ts > KBD_RECALL_WINDOW * 1000) {
                showToast('Süre doldu — son yapılan işlem geri çağrılamaz.', 'error');
                return;
            }
            if (last.type === 'qr') { undoQr(last.id); return; }
            if (last.type === 'item') {
                postJson('/kitchen-pos/item-unserve', { group_key: last.gk, item_keys: last.keys })
                    .then(d => { if (d && d.success === false) showToast(d.message || 'Geri alınamadı.', 'error'); fetchOnce(); })
                    .catch(e => console.error(e));
                return;
            }
            uncomplete(last.gk);
        }

        document.addEventListener('keydown', function (e) {
            if (e.target.closest && e.target.closest('input, textarea, select')) return;
            if (e.ctrlKey || e.altKey || e.metaKey) return;
            const tok = e.key.length === 1 ? e.key.toLocaleLowerCase('tr') : e.key;
            if (!tok) return;
            let action = null;
            for (const [act, key] of Object.entries(KBD)) {
                if (key && key.toLocaleLowerCase('tr') === tok) { action = act; break; }
            }
            if (!action) return;
            if (!['home', 'end', 'card_prev', 'card_next', 'item_prev', 'item_next'].includes(action) && e.repeat) return;
            e.preventDefault();
            switch (action) {
                case 'home': { const c = kbdCards(); if (c.length) kbdSelectCard(c[0].dataset.kbdGk); break; }
                case 'end': { const c = kbdCards(); if (c.length) kbdSelectCard(c[c.length - 1].dataset.kbdGk); break; }
                case 'card_prev': kbdMoveCard(-1); break;
                case 'card_next': kbdMoveCard(1); break;
                case 'item_prev': kbdMoveItem(-1); break;
                case 'item_next': kbdMoveItem(1); break;
                case 'ok': {
                    if (!kbdSel.gk) {
                        const c = kbdCards();
                        if (!c.length) break;
                        kbdSel = { gk: c[0].dataset.kbdGk, mode: 'card', idx: -1 };
                    }
                    const card = kbdCardEl(kbdSel.gk);
                    if (!card) break;
                    if (card.dataset.kbdQr) { showToast('QR sipariş: SELECT/DONE ile onaylanır.', 'default'); applyKbdSel(); break; }
                    const rows = kbdRows(card);
                    if (rows.length) { kbdSel.mode = 'item'; kbdSel.idx = 0; }
                    applyKbdSel();
                    break;
                }
                case 'cancel': {
                    if (kbdSel.mode === 'item') { kbdSel.mode = 'card'; kbdSel.idx = -1; }
                    else { kbdSel = { gk: null, mode: 'card', idx: -1 }; }
                    applyKbdSel();
                    break;
                }
                case 'done1': kbdServeNth(1); break;
                case 'done2': kbdServeNth(2); break;
                case 'done3': kbdServeNth(3); break;
                case 'done4': kbdServeNth(4); break;
                case 'select_done': kbdSelectDone(); break;
                case 'recall': kbdRecall(); break;
            }
        });

        // ── Sayaç kalıcılığı (localStorage) ─────────────────────────────────
        // API her 5sn'de DOM'u yeniden kursa da start time localStorage'da saklanır.
        const LS_PREFIX = 'kpos_start_';

        function getStartTime(groupKey, apiOrderTime, ageSeconds) {
            const lsKey = LS_PREFIX + groupKey;
            // Sunucunun hesapladığı yaş önceliklidir: tarayıcı/sunucu saat kaymalarına
            // bağışıkdır, F5 sonrası da doğru kalır.
            if (ageSeconds != null && isFinite(ageSeconds) && ageSeconds >= 0) {
                const ts = new Date(Date.now() - (ageSeconds * 1000)).toISOString();
                localStorage.setItem(lsKey, ts);
                return ts;
            }
            const stored = localStorage.getItem(lsKey);
            if (stored) return stored;
            // İlk kez görüldü: API zamanı geçerliyse kullan, değilse şimdiki zaman
            const t = apiOrderTime ? new Date(apiOrderTime.replace(' ', 'T')) : null;
            const ts = (t && !isNaN(t.getTime())) ? apiOrderTime : new Date().toISOString();
            localStorage.setItem(lsKey, ts);
            return ts;
        }

        function clearStartTime(groupKey) {
            localStorage.removeItem(LS_PREFIX + groupKey);
        }

        // Ekran saati kaynağı: sunucu / veritabanı / tarayıcı (admin panelinden seçilir)
        const CLOCK_SOURCE = @json(\App\Support\Clock::source());
        let serverClockOffsetMs = null;

        function updateClock() {
            const now = new Date(Date.now() + (CLOCK_SOURCE !== 'browser' && serverClockOffsetMs != null ? serverClockOffsetMs : 0));
            document.getElementById('clock').textContent = now.toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            document.getElementById('clock-date').textContent = now.toLocaleDateString('tr-TR', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' });
        }
        setInterval(updateClock, 1000); updateClock();

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
            el.className = `pointer-events-none ${bg} text-white border-4 rounded-2xl px-10 py-8 shadow-2xl flex items-center gap-6 max-w-4xl transform transition-all duration-300 scale-90 opacity-0`;
            el.innerHTML = `<i class="fas ${icon} text-5xl"></i><span class="font-bold text-4xl leading-snug">${escapeHtml(message)}</span>`;
            c.appendChild(el);
            requestAnimationFrame(() => { el.classList.remove('scale-90', 'opacity-0'); });
            setTimeout(() => {
                el.classList.add('scale-90', 'opacity-0');
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
            const groupKey = order.check_number ? String(order.check_number) : ('T' + (order.table_no || ''));
            const startTime = getStartTime(groupKey, order.order_time, order.age_seconds);
            const elapsed = elapsedSince(startTime);
            const minTotal = elapsed ? Math.floor(elapsed / 60) : 0;
            const timeBg = minTotal > 15 ? 'bg-red-600' : minTotal > 10 ? 'bg-yellow-600' : 'bg-green-600';
            const isNew = elapsed != null && elapsed < 120;
            const isAddition = !!order.is_addition;
            const isReopened = !!order.is_reopened;
            const borderClass = isAddition ? 'border-orange-500' : (isReopened ? 'border-yellow-600' : (isNew ? 'new-order border-gold' : 'border-blue-500'));

            // Tüm unit_ids'leri düzleştir → served_item_keys fingerprint.
            // Mesaj satırları dahil: yalnız mesajlı (ürünsüz) hesapta parmak izi boş kalırsa
            // kart sunucuda "YENİDEN" olarak ekranda takılı kalır.
            const allUnitIds = [
                ...(order.items || []).flatMap(it =>
                    (it.unit_ids && it.unit_ids.length) ? it.unit_ids : (it.item_id ? [String(it.item_id)] : [])
                ),
                ...(order.messages || []).flatMap(m =>
                    (m.unit_ids && m.unit_ids.length) ? m.unit_ids : (m.item_id ? ['M' + String(m.item_id)] : [])
                ),
            ];
            // Ürün isimleri → tamamlama sonrası alt şerit için localStorage'a yazılacak
            const itemNamesList = (order.items || [])
                .filter(it => !it.is_returned)
                .map(it => `${it.qty}x ${it.name}`)
                .join(' · ');

            const unservedCount = (order.items || []).filter(it => !it.is_returned && !it.served).length;

            // MESAJ satırları POS'taki satır konumuna (pos) göre ürünler arasına serpiştirilir
            const mesajItems = (order.messages || []).filter(m => m.line_kind !== 'MARS');
            const msgsHtmlAt = p => mesajItems.filter(m => (m.pos ?? 0) === p).map(m => `
                <div class="py-0.5 border-b border-gray-700">
                    <div class="text-yellow-100 text-base leading-snug"><i class="fas fa-comment-dots mr-1 text-yellow-400"></i>${m.note ? escapeHtml(m.note) : escapeHtml(m.name)}</div>
                </div>`).join('');

            const itemsHtml = msgsHtmlAt(0) + (order.items || []).map((it, ri) => {
                const isReturned  = !!it.is_returned;
                const isCombo     = !!it.is_combo;
                const isCond      = !!it.is_condiment;
                const isServed    = !!it.served;
                const textClass   = isReturned ? 'line-through text-red-400' : (isServed ? 'line-through text-gray-500' : '');
                const qtyColor    = isReturned ? 'text-red-400' : (isCond ? 'text-amber-300' : 'text-gold');
                const badge = isReturned
                    ? `<span class="ml-1 px-1 py-0.5 rounded text-[9px] font-bold bg-red-700 text-white uppercase">İade</span>`
                    : (isCombo ? `<span class="ml-1 px-1 py-0.5 rounded text-[9px] font-bold bg-amber-800/80 text-amber-200 uppercase">Combo</span>` : '');

                const itemUnits = (it.unit_ids && it.unit_ids.length) ? it.unit_ids : (it.item_id ? [String(it.item_id)] : []);
                const isLast = !isReturned && !isServed && unservedCount === 1;
                const readyBtn = isReturned ? '' : (isServed
                    ? `<button data-item-gk="${escapeHtml(groupKey)}"
                              data-item-units="${escapeHtml(JSON.stringify(itemUnits))}"
                              data-item-unserve="1"
                              onclick="unserveItem(this)"
                              title="Hazır işaretini geri al"
                              class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-700 hover:bg-emerald-600 text-emerald-100 transition shrink-0">
                        <i class="fas fa-check mr-0.5"></i><span class="line-through">Hazır</span>
                     </button>`
                    : `<button data-item-gk="${escapeHtml(groupKey)}"
                              data-item-cn="${escapeHtml(order.check_number ? String(order.check_number) : '')}"
                              data-item-tno="${escapeHtml(String(order.table_no || ''))}"
                              data-item-units="${escapeHtml(JSON.stringify(itemUnits))}"
                              data-item-last="${isLast ? '1' : '0'}"
                              data-item-start="${escapeHtml(startTime)}"
                              data-item-names="${escapeHtml(itemNamesList)}"
                              data-item-self="${escapeHtml(it.name)}"
                              data-item-self-qty="${it.qty}"
                              data-item-ready="1"
                              onclick="serveItem(this)"
                              title="${isLast ? 'Son ürün → hesap tamamlanır' : 'Ürünü hazır işaretle'}"
                              class="px-1.5 py-0.5 rounded text-[10px] font-bold border border-amber-500/70 bg-amber-600/20 hover:bg-amber-600/50 text-amber-200 transition shrink-0">
                        Hazır
                     </button>`);

                const subHtml = (it.sub_items || []).map(sub => {
                    const subRet   = !!sub.is_returned;
                    const subText  = subRet ? 'line-through text-red-400' : (isServed ? 'line-through text-gray-500' : 'text-gray-300');
                    const subBadge = subRet ? `<span class="ml-1 px-1 py-0.5 rounded text-[9px] font-bold bg-red-700 text-white">İade</span>` : '';
                    return `<div class="flex items-center pl-4 py-0 text-sm${subRet ? ' iade-blink' : ''}">
                        <span class="text-amber-600 mr-1.5 select-none">└</span>
                        <span class="${subText} font-medium">${escapeHtml(sub.name)}${subBadge}</span>
                        ${sub.note ? `<span class="text-yellow-300 ml-2 text-xs">— ${escapeHtml(sub.note)}</span>` : ''}
                    </div>`;
                }).join('');

                return `
                <div class="py-0.5 border-b border-gray-700 kbd-row" data-kbd-row="${ri}"${isReturned ? ' data-kbd-ret="1"' : ''}>
                    <div class="flex justify-between items-start">
                        <div class="flex-1 min-w-0">
                            <div class="text-lg leading-tight ${textClass}${isReturned ? ' iade-blink' : ''}">
                                <span class="${qtyColor} font-bold text-xl">${it.qty}x</span> <span class="font-semibold">${escapeHtml(it.name)}</span>${badge}
                            </div>
                            ${it.note ? `<div class="text-sm text-yellow-300"><i class="fas fa-comment-dots mr-1"></i>${escapeHtml(it.note)}</div>` : ''}
                        </div>
                        <div class="ml-2 flex-shrink-0 flex items-center gap-1.5">
                            <span class="text-xs text-gray-500 ${isServed ? 'line-through' : ''}">${formatTime(it.item_time)}</span>
                            ${readyBtn}
                        </div>
                    </div>
                    ${subHtml}
                </div>` + msgsHtmlAt(ri + 1);
            }).join('');

            // MARS alt kutuda kalır; MESAJ satırları ürünler arasına serpiştirildi
            const marsItems  = (order.messages || []).filter(m => m.line_kind === 'MARS');

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

            return `
            <div data-kbd-gk="${escapeHtml(groupKey)}" class="bg-gray-800 rounded-lg border-2 ${borderClass} overflow-hidden">
                <div class="px-2 py-1 bg-gray-750 border-b border-gray-700">
                    <div class="flex items-center justify-between">
                        <span class="text-xl font-bold text-gold">TBL ${escapeHtml(order.table_no || '-')}${order._seqTotal > 1 ? ` <span class="text-orange-300">#${order._seq}/${order._seqTotal}</span>` : ''}</span>
                        <div class="flex items-center gap-1">
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-700 text-blue-100"><i class="fas fa-server mr-0.5"></i>SYM</span>
                            ${isAddition ? `<span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-orange-600 text-white animate-pulse"><i class="fas fa-plus-circle mr-0.5"></i>EK</span>` : ''}
                            ${isReopened ? `<span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-yellow-700 text-yellow-100"><i class="fas fa-rotate-right mr-0.5"></i>YENİDEN</span>` : ''}
                        </div>
                    </div>
                    <div class="flex items-center justify-between mt-0.5">
                        <span class="text-[11px] text-gray-400">${checkLabel}</span>
                        <span class="elapsed-counter px-2 py-0.5 rounded text-xs ${timeBg}" data-order-time="${escapeHtml(startTime)}">${fmtElapsed(elapsed)}</span>
                    </div>
                    ${order.waiter_name ? `<div class="text-[11px] text-gray-300 mt-0.5"><i class="fas fa-user mr-1 text-gray-500"></i>${escapeHtml(order.waiter_name)}</div>` : ''}
                </div>
                <div class="px-2 py-1 text-sm">${itemsHtml || '<div class="text-gray-500 text-center py-1">Urun yok</div>'}</div>
                ${marsHtml}
                <div class="px-2 pt-1 pb-1 border-t border-gray-700">
                    <button data-complete-kind="check"
                            data-complete-gk="${escapeHtml(groupKey)}"
                            data-complete-cn="${escapeHtml(order.check_number ? String(order.check_number) : '')}"
                            data-complete-tno="${escapeHtml(String(order.table_no || ''))}"
                            data-complete-items="${escapeHtml(JSON.stringify(allUnitIds))}"
                            data-complete-start="${escapeHtml(startTime)}"
                            data-complete-names="${escapeHtml(itemNamesList)}"
                            onclick="completeOrderFromBtn(this)"
                            class="w-full py-0.5 bg-emerald-600 hover:bg-emerald-700 rounded text-[11px] font-bold text-white">
                        <i class="fas fa-check-circle mr-0.5"></i>Komple Hazır
                    </button>
                </div>
            </div>`;
        }

        function buildQrOrderCard(order) {
            const groupKey = 'Q' + order.qr_order_id;
            const startTime = getStartTime(groupKey, order.order_time);
            const elapsed = elapsedSince(startTime);
            const minTotal = elapsed ? Math.floor(elapsed / 60) : 0;
            const timeBg = minTotal > 15 ? 'bg-red-600' : minTotal > 10 ? 'bg-yellow-600' : 'bg-green-600';

            const itemsHtml = (order.items || []).map(it => `
                <div class="flex justify-between items-start py-1 border-b border-gray-700">
                    <div class="flex-1 min-w-0">
                        <div class="text-lg leading-tight"><span class="text-purple-300 font-bold text-xl">${it.qty}x</span> <span class="font-semibold">${escapeHtml(it.name)}</span></div>
                    </div>
                </div>
            `).join('');

            return `
            <div data-kbd-gk="${groupKey}" data-kbd-qr="${order.qr_order_id}" class="bg-purple-950/40 rounded-lg border-2 border-purple-500 qr-card overflow-hidden">
                <div class="flex items-center justify-between px-4 py-2 bg-purple-900/40">
                    <div class="flex items-center gap-3">
                        <span class="text-xl font-bold text-purple-200 leading-tight">
                            ${(order.table_no || order.room_no)
                                ? `${order.table_no ? `<div>TBL ${escapeHtml(order.table_no)}</div>` : ''}${order.room_no ? `<div class="font-bold text-amber-300">RM ${escapeHtml(order.room_no)}</div>` : ''}`
                                : 'Paket'}
                        </span>
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-purple-700 text-purple-100" title="QR Menu siparisi">
                            <i class="fas fa-mobile-screen mr-0.5"></i>QR MENU
                        </span>
                        <span class="px-2 py-1 rounded text-xs font-bold bg-purple-700 text-white">QR #${order.qr_order_id}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="elapsed-counter px-2 py-1 rounded text-xs ${timeBg}" data-order-time="${escapeHtml(startTime)}">${fmtElapsed(elapsed)}</span>
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
                            <i class="fas fa-server mr-0.5"></i>SYM
                        </span>
                        <span class="text-gray-500 ml-auto">TBL ${escapeHtml(order.table_no || '-')}</span>
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
                            <i class="fas fa-server mr-0.5"></i>SYM
                        </span>
                        <span class="text-gray-500 ml-auto">TBL ${escapeHtml(order.table_no || '-')}</span>
                    </div>
                    <p class="text-yellow-100 truncate">
                        ${order.qty > 1 ? `<span class="text-yellow-400">${order.qty}x</span> ` : ''}${escapeHtml(order.name || '—')}
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
            const items = (order.items || []).map(i => `${i.qty}x ${i.name}`).join(', ');
            return `
            <div class="bg-gray-800 rounded-lg border-2 border-emerald-700 p-3 text-xs">
                <div class="flex items-center justify-between mb-1 gap-1">
                    <span class="font-bold text-emerald-400">
                        <i class="fas fa-qrcode mr-1"></i>QR #${order.qr_order_id}
                    </span>
                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-purple-700 text-purple-100" title="QR Menu siparisi">
                        <i class="fas fa-mobile-screen mr-0.5"></i>QR MENU
                    </span>
                    <span class="text-gray-500 ml-auto">TBL ${escapeHtml(order.table_no || '-')}</span>
                </div>
                <p class="text-gray-300 truncate">${escapeHtml(items) || '—'}</p>
                <button onclick="undoQr(${order.qr_order_id})"
                        class="mt-2 w-full py-1 bg-amber-500 hover:bg-amber-600 rounded text-black font-bold text-xs">
                    <i class="fas fa-undo mr-1"></i>Geri Al
                </button>
            </div>`;
        }

        function buildChecklessCard(msg) {
            const groupKey = 'M' + (msg.item_id || '');
            const startTime = getStartTime(groupKey, msg.item_time, msg.age_seconds);
            const elapsed = elapsedSince(startTime);
            const isMars = msg.line_kind === 'MARS';
            const borderColor = isMars ? 'border-orange-500/70' : 'border-yellow-500/70';
            const bgColor     = isMars ? 'bg-orange-950/40' : 'bg-yellow-900/30';
            const textColor   = isMars ? 'text-orange-300' : 'text-yellow-300';
            const icon        = isMars ? 'fa-fire' : 'fa-comment-dots';
            const title       = isMars
                ? escapeHtml(msg.name.replace(/^[\s\-]+|[\s\-]+$/g,''))
                : escapeHtml(msg.table_no ? 'TBL ' + msg.table_no : 'Mesaj');
            const body = isMars
                ? `<span class="${textColor} font-semibold">${escapeHtml(msg.name.replace(/^[\s\-]+|[\s\-]+$/g,''))}</span>${msg.note ? `: <b class="text-white">${escapeHtml(msg.note)}</b>` : ''}`
                : `${escapeHtml(msg.name)}${msg.note ? `<div class="text-xs text-yellow-200 mt-1">${escapeHtml(msg.note)}</div>` : ''}`;
            return `
            <div class="${bgColor} rounded-lg border-2 ${borderColor} msg-flash overflow-hidden p-3">
                <div class="flex items-center justify-between mb-2">
                    <span class="${textColor} font-bold">
                        <i class="fas ${icon} mr-1"></i>${title}
                    </span>
                    <span class="elapsed-counter text-xs ${textColor}" data-order-time="${escapeHtml(startTime)}">${fmtElapsed(elapsed)}</span>
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
            const startTime = btn.dataset.completeStart || null;
            const itemNames = btn.dataset.completeNames || '';
            completeOrder(btn.dataset.completeKind, btn.dataset.completeGk, btn.dataset.completeCn, btn.dataset.completeTno, itemKeys, startTime, itemNames, '', '');
        }

        function completeOrder(kind, groupKey, checkNumber, tableNo, itemKeys, startTime, itemNames, selfName, selfQty) {
            if (itemNames) localStorage.setItem('kpos_items_' + groupKey, itemNames);
            // kind=item: onaylanan urunun kendi adi/adedi bar hazir kartina yazilmak üzere gider;
            // kind=check: tüm grup listesi (feed kaybolduğunda yedek içerik). 250: DB varchar sınırı.
            const nm = ((kind === 'check' ? itemNames : selfName) || '').substring(0, 250);
            postJson('/kitchen-pos/complete', {
                kind, group_key: groupKey, check_number: checkNumber, table_no: tableNo,
                item_keys: itemKeys || [],
                first_seen_at: startTime || null,
                name: nm || null,
                qty: kind === 'item' ? (parseInt(selfQty, 10) || null) : null,
            }).then(() => {
                kbdPushRecall({ type: kind === 'item' ? 'item' : 'check', gk: groupKey, keys: itemKeys || [] });
                clearStartTime(groupKey);
                fetchOnce();
            }).catch(e => console.error(e));
        }

        function uncomplete(groupKey) {
            postJson('/kitchen-pos/uncomplete', { group_key: groupKey })
                .then(d => { if (d && d.success === false) showToast(d.message || 'Geri alınamadı.', 'error'); fetchOnce(); })
                .catch(e => console.error(e));
        }

        function serveItem(btn) {
            // Son bekleyen ürünse hesabı tamamen tamamla (kind=check → bar hazır düşer)
            const kind = btn.dataset.itemLast === '1' ? 'check' : 'item';
            completeOrder(kind, btn.dataset.itemGk, btn.dataset.itemCn || '', btn.dataset.itemTno || '',
                JSON.parse(btn.dataset.itemUnits || '[]'), btn.dataset.itemStart || null, btn.dataset.itemNames || '',
                btn.dataset.itemSelf || '', btn.dataset.itemSelfQty || '');
        }

        function unserveItem(btn) {
            postJson('/kitchen-pos/item-unserve', {
                group_key: btn.dataset.itemGk,
                item_keys: JSON.parse(btn.dataset.itemUnits || '[]'),
            }).then(d => {
                if (d && d.success === false) showToast(d.message || 'Geri alınamadı.', 'error');
                fetchOnce();
            }).catch(e => console.error(e));
        }

        function confirmQr(orderId) {
            postJson('/kitchen-pos/qr/' + orderId + '/confirm', {}, 'PATCH')
                .then(() => { kbdPushRecall({ type: 'qr', id: orderId }); clearStartTime('Q' + orderId); fetchOnce(); })
                .catch(e => console.error(e));
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
            const completedItems = data.completed_items || [];
            const completedLimit = data.completed_limit || 6;

            if (data.server_now) {
                const parsed = Date.parse(data.server_now);
                if (!isNaN(parsed)) serverClockOffsetMs = parsed - Date.now();
            }

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

            // Aynı masadan gelen siparişlere sıra rozeti (#1/2) — en eski = #1
            const seqGroups = {};
            orders.forEach(o => {
                if (o.source === 'qr') return;
                const t = String(o.table_no || '');
                if (!t) return;
                (seqGroups[t] = seqGroups[t] || []).push(o);
            });
            Object.values(seqGroups).forEach(list => {
                if (list.length < 2) return;
                list.sort((a, b) => String(a.order_time || '').localeCompare(String(b.order_time || '')));
                list.forEach((o, i) => { o._seq = i + 1; o._seqTotal = list.length; });
            });

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

            // Tamamlananlar → alt şerit (tek ürün onayları dahil)
            const allCompleted = [...completed, ...completedMsgs, ...completedChecks, ...completedItems]
                .sort((a, b) => {
                    const ta = Date.parse(a.completed_at || 0) || 0;
                    const tb = Date.parse(b.completed_at || 0) || 0;
                    return tb - ta;
                })
                .slice(0, completedLimit);
            renderCompletedBar(allCompleted, completedLimit);

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
            applyKbdSel();
            isFirstLoad = false;
        }

        function fmtPrep(secs) {
            if (!secs) return '';
            const m = Math.floor(secs / 60), s = secs % 60;
            return m > 0 ? `${m} dk ${s} sn` : `${s} sn`;
        }

        function renderCompletedBar(allCompleted, limit) {
            const inner = document.getElementById('kpos-ticker-inner');
            const limitEl = document.getElementById('kpos-completed-limit');
            if (limitEl && limit) limitEl.textContent = limit;
            if (!inner) return;

            // Aynı liste ise DOM'a dokunma → animasyon sıfırlanmasın
            const newKey = (allCompleted || []).map(o => (o.group_key || '') + (o.completed_at || '')).join('|');
            if (newKey === lastCompletedBarKey) return;
            lastCompletedBarKey = newKey;

            if (!allCompleted || allCompleted.length === 0) {
                inner.innerHTML = '<span class="text-gray-600 text-xs italic pl-1">Henüz tamamlanan yok.</span>';
                return;
            }

            inner.innerHTML = allCompleted.map(order => {
                const tableLabel = order.table_no ? 'TBL ' + escapeHtml(String(order.table_no)) : '—';
                let borderCls, accentCls, badgeHtml, titleHtml, contentText, undoFn;

                if (order.is_check) {
                    borderCls  = 'border-blue-800';
                    accentCls  = 'bg-blue-950/60';
                    const chkLabel = order.check_number ? 'Chk #' + escapeHtml(String(order.check_number)) : 'Checksiz';
                    badgeHtml  = `<span class="px-1 py-0.5 rounded text-[9px] font-bold bg-blue-700 text-blue-100">SYM</span>`;
                    titleHtml  = `<span class="font-bold text-blue-200 text-xs">${tableLabel}</span> ${badgeHtml} <span class="text-blue-300 text-[10px]">${chkLabel}</span>`;
                    const storedItems = localStorage.getItem('kpos_items_' + (order.group_key || ''));
                    const prep = fmtPrep(order.prep_seconds);
                    contentText = storedItems || (prep ? `Hazırlık: ${prep}` : chkLabel);
                    const gk = escapeHtml(order.group_key || '');
                    undoFn = `uncomplete('${gk}')`;
                } else if (order.is_item) {
                    borderCls  = 'border-emerald-800';
                    accentCls  = 'bg-emerald-950/60';
                    badgeHtml  = `<span class="px-1 rounded text-[9px] font-bold bg-emerald-700 text-emerald-100">ÜRÜN</span>`;
                    const chkPart = order.check_number ? ` <span class="text-emerald-300 text-[10px]">Chk #${escapeHtml(String(order.check_number))}</span>` : '';
                    titleHtml  = `<span class="font-bold text-emerald-200 text-xs">${tableLabel}</span> ${badgeHtml}${chkPart}`;
                    contentText = order.items_list || '—';
                    const gk = escapeHtml(order.group_key || '');
                    undoFn = `uncomplete('${gk}')`;
                } else if (order.is_message) {
                    borderCls  = 'border-yellow-800';
                    accentCls  = 'bg-yellow-950/60';
                    badgeHtml  = `<span class="px-1 py-0.5 rounded text-[9px] font-bold bg-yellow-700 text-yellow-100">MSG</span>`;
                    titleHtml  = `<span class="font-bold text-yellow-200 text-xs">${tableLabel}</span> ${badgeHtml}`;
                    contentText = (order.qty > 1 ? `${order.qty}x ` : '') + (order.name || 'Mesaj') + (order.note ? ' — ' + order.note : '');
                    const gk = escapeHtml(order.group_key || '');
                    undoFn = `uncomplete('${gk}')`;
                } else {
                    borderCls  = 'border-purple-800';
                    accentCls  = 'bg-purple-950/60';
                    const qrId = order.qr_order_id || order.id || 0;
                    badgeHtml  = `<span class="px-1 py-0.5 rounded text-[9px] font-bold bg-purple-700 text-purple-100">QR</span>`;
                    titleHtml  = `<span class="font-bold text-purple-200 text-xs">${tableLabel}</span> ${badgeHtml} <span class="text-purple-300 text-[10px]">#${qrId}</span>`;
                    contentText = (order.items || []).map(i => `${i.qty||1}x ${i.name||''}`).join(' · ') || '—';
                    undoFn = `undoQr(${Number(qrId)})`;
                }

                return `<div class="flex-shrink-0 border ${borderCls} rounded-lg overflow-hidden max-w-[240px]">
                    <div class="${accentCls} px-1.5" style="padding-top:4px;padding-bottom:0">
                        <div class="flex items-center gap-1 leading-none flex-wrap" style="margin-bottom:2px">${titleHtml}</div>
                        <div class="overflow-hidden">
                            <span class="kpos-chip-text text-[10px] text-gray-300" style="line-height:1.2">${escapeHtml(contentText)}</span>
                        </div>
                    </div>
                    <div class="px-1.5" style="padding-top:2px;padding-bottom:3px">
                        <button onclick="${undoFn}"
                            class="w-full bg-amber-500 hover:bg-amber-600 active:bg-amber-700 rounded text-black font-bold text-[10px] transition" style="padding:1px 0;line-height:1.4">
                            <i class="fas fa-undo mr-0.5"></i>Geri Al
                        </button>
                    </div>
                </div>`;
            }).join('');

            // Her kart içeriği her zaman kayar; data-orig ile çift katlanmayı önle
            requestAnimationFrame(() => {
                inner.querySelectorAll('.kpos-chip-text').forEach(span => {
                    const orig = (span.dataset.orig || span.textContent).trim();
                    if (!orig) return;
                    span.dataset.orig = orig;
                    span.textContent = orig + '    ·    ' + orig;
                    // 30px/sn hız, minimum 6 saniye
                    const dur = Math.max(6, (span.scrollWidth / 2) / 30);
                    span.style.animation = `kpos-chip-marquee ${dur}s linear infinite`;
                });
            });
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
