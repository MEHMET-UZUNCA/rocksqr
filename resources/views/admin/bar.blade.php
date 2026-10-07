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
        @keyframes ready-blink {
            0%, 100% { background-color: #022c22; border-color: #10b981; }
            50%      { background-color: #065f46; border-color: #6ee7b7; }
        }
        .ready-blink { animation: ready-blink 0.9s ease-in-out infinite; }
        @keyframes iade-blink { 0%,100% { background-color: rgba(239,68,68,0.10); } 50% { background-color: rgba(239,68,68,0.35); } }
        .iade-blink { animation: iade-blink 0.9s ease-in-out infinite; border-radius: 4px; }
        .watermark {
            position: absolute; inset: 0;
            display: flex; align-items: center; justify-content: center;
            pointer-events: none; user-select: none; overflow: hidden;
        }
        .watermark span {
            font-weight: 800; letter-spacing: 0.15em; white-space: nowrap;
            color: rgba(255,255,255,0.10);
            font-size: min(3.5vw, 64px);
            transform: rotate(-45deg);
        }
        /* SON şeridi: tek sıra kayan yazı (içerik sığmazsa) — çift grup kesintisiz döngü */
        @keyframes strip-marquee { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }
        .strip-mask { overflow: hidden; flex: 1; min-width: 0; }
        .strip-ticker { display: flex; align-items: center; width: max-content; animation: strip-marquee 45s linear infinite; }
        .strip-group { display: flex; align-items: center; gap: 4px; padding-right: 4px; }
    </style>
</head>
<body class="bg-gray-900 font-poppins text-white h-screen flex flex-col" style="overflow:hidden">
    <header class="bg-primary px-1 py-1 shrink-0">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-2 items-center min-w-0">
            <div class="flex items-center gap-2 min-w-0">
                <span class="font-extrabold text-base xl:text-lg text-orange-400 tracking-wide leading-none whitespace-nowrap"><i class="fas fa-inbox mr-1.5"></i>GELEN SİPARİŞLER</span>
                <span id="incoming-count" class="text-sm font-bold bg-orange-900/60 border border-orange-700 text-orange-200 rounded px-1.5 shrink-0">0</span>
                <div class="w-px self-stretch bg-gray-400/40 shrink-0"></div>
                <span id="clock" class="text-gold font-extrabold text-xl xl:text-2xl tabular-nums leading-none whitespace-nowrap"></span>
                <span id="clock-date" class="text-gray-300 text-xl xl:text-2xl font-normal leading-none whitespace-nowrap"></span>
            </div>
            <div class="flex items-center gap-2 min-w-0">
                <span class="font-extrabold text-base xl:text-lg text-blue-400 tracking-wide leading-none whitespace-nowrap"><i class="fas fa-utensils mr-1.5"></i>HAZIRLANAN SİPARİŞLER</span>
                <span id="preparing-count" class="text-sm font-bold bg-blue-900/60 border border-blue-700 text-blue-200 rounded px-1.5 shrink-0">0</span>
                <div class="ml-auto flex items-center gap-1.5 shrink-0">
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
                    <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse shrink-0"></span>
                    <button onclick="toggleFullscreen()" id="fs-btn" class="text-gray-400 hover:text-gold transition" title="Tam ekran">
                        <i id="fs-icon" class="fas fa-expand text-sm"></i>
                    </button>
                    <a href="/admin" class="text-gray-400 hover:text-gold transition text-xs">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                </div>
            </div>
        </div>
    </header>

    @php
        $screenBg = \App\Models\Setting::get('screen_bg_image_bar', '');
        $screenBgOpacity = (int) \App\Models\Setting::get('screen_bg_opacity_bar', 30);
        $screenBgSize = (int) \App\Models\Setting::get('screen_bg_size_bar', 60);
    @endphp
    @if($screenBg)
    <div style="position:fixed;inset:0;display:flex;align-items:center;justify-content:center;pointer-events:none;user-select:none">
        <img src="{{ asset('images/' . $screenBg) }}?v={{ @filemtime(public_path('images/' . $screenBg)) }}" alt=""
             style="max-height:{{ $screenBgSize }}vh;max-width:{{ $screenBgSize }}vw;object-fit:contain;opacity:{{ round($screenBgOpacity / 100, 2) }}">
    </div>
    @endif

    <main class="p-1 flex-1 min-h-0 flex flex-col" style="padding-bottom:42px">
        <div id="boards" class="flex-1 min-h-0 grid grid-cols-1 lg:grid-cols-2 gap-2">
            <section class="relative min-h-0 border border-gray-700 rounded-lg bg-gray-900/60 overflow-hidden">
                <div class="watermark"><span>ROCKS SERVICES BDS</span></div>
                <div id="incoming-grid" class="relative h-full overflow-y-auto p-1 grid gap-1 items-start content-start" style="grid-template-columns: repeat(6, minmax(0, 1fr))"></div>
            </section>
            <section class="relative min-h-0 border border-gray-700 rounded-lg bg-gray-900/60 overflow-hidden">
                <div class="watermark"><span>ROCKS SERVICES BDS</span></div>
                <div id="preparing-grid" class="relative h-full overflow-y-auto p-1 grid gap-1 items-start content-start" style="grid-template-columns: repeat(6, minmax(0, 1fr))"></div>
            </section>
        </div>
        <div id="no-orders" class="hidden text-center py-20">
            <i class="fas fa-check-circle text-6xl text-green-500 mb-4"></i>
            <p class="text-2xl text-gray-400">Tüm siparişler tamamlandı!</p>
            <p class="text-gray-500 mt-2">Yeni siparişler otomatik olarak görünecek.</p>
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

    <!-- Son Tamamlananlar: sabit alt şerit, tek sıra (sığmazsa kayar) -->
    <div id="completed-bar" class="fixed bottom-0 left-0 right-0 bg-gray-900 border-t border-gray-700 px-2 py-1" style="z-index:50;min-height:38px">
        <div class="flex items-center gap-2">
            <span id="completed-prefix" class="inline-flex items-center gap-1 text-xs text-emerald-400 font-bold shrink-0"></span>
            <div class="strip-mask"><div id="completed-ticker" class="strip-ticker"></div></div>
        </div>
    </div>

    <script>
        let previousOrderIds = [];
        let previousWaiterIds = [];
        let previousReadyIds = [];
        let isFirstLoad = true;
        let lastSymOrders = [];     // Symphony API hata verince son bilinen siparisleri koru
        let lastIncomingKeys = '';  // Sol kolon flicker onleme anahtari
        let lastPreparingKeys = ''; // Sag kolon flicker onleme anahtari
        let _waiterCards = [];      // Sol kolonun en ustundeki garson cagri kartlari
        let _lastWaiterIds = null;  // Cagri listesi degisimini izlemek icin
        let _lastCompletedKey = null; // Alt serit icerik anahtari — ayniysa DOM'a dokunma (marquee sifirlanmasin)

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

        // ── Bar kategori filtreleri (Admin → Ekran → Bar Ayarları) ─────────
        // RVC 44 (Pool Bar) × Symphony grubu tick'leri — mutfaktan bağımsız ayri set.
        const BAR_TICKS = @json(\App\Support\KitchenFilter::visibleMap(44, 'bar_show'));

        // Öğenin tick kodu: Symphony'de sunucu hesaplar (fc: Bar Mesaj→98, diğer→mg),
        // QR öğelerinde cat food→1 / drink→2.
        function itemTick(item) {
            if (item.fc !== undefined && item.fc !== null) return item.fc;
            return item.cat === 'drink' ? 2 : 1;
        }

        function parseItems(order) {
            try { return Array.isArray(order.items) ? order.items : (JSON.parse(order.items || '[]') || []); }
            catch(e) { return []; }
        }

        function escapeHtml(s) {
            return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        }

        // Gizli kategoriler elenmiş ürün listesi; Symphony hazır kartındaki
        // 'Adisyon #' yer tutucusu lastSymOrders'tan gerçek ürünlerle çözülür.
        // Mesaj satırları (tick 98/99) yalnızca kartta yiyecek varsa gösterilir.
        function visibleItems(order) {
            let items = parseItems(order);
            if (order.source === 'symphony' && items.length === 1 && String(items[0].name || '').startsWith('Adisyon #')) {
                const match = findSymOrder(order);
                if (match && match.items && match.items.length > 0) items = match.items;
                else if (Array.isArray(order.db_items) && order.db_items.length > 0) items = order.db_items;
            }
            const hasFood = items.some(i => itemTick(i) === 1);
            return items.filter(i => {
                const t = itemTick(i);
                if (t === 98 || t === 99) return !!BAR_TICKS[t] && hasFood;
                return BAR_TICKS[t] === undefined ? true : !!BAR_TICKS[t];
            });
        }

        function hasVisibleItems(order) {
            return visibleItems(order).length > 0;
        }

        function symGroupKey(order) {
            // Must match kitchen-pos.blade.php format: bare check_number (no prefix) or T+tableNo
            return order.check_number ? String(order.check_number) : ('T' + (order.table_no || ''));
        }

        function findSymOrder(order) {
            // Mutfak onay kaydında group_key bare ('2245'), canlı feed'de C önekli ('C2245') olabilir
            const norm = k => String(k || '').replace(/^C/, '');
            return lastSymOrders.find(s =>
                norm(s.group_key) === norm(order.group_key) ||
                (order.table_no && String(s.table_no) === String(order.table_no))
            );
        }

        function locLabel(order, tc = 'text-gold', rc = 'text-amber-300') {
            // Konum etiketi alt alta: "TBL 15" / "RM 2313" (tek konumluysa tek satir)
            const seq = order._seqTotal > 1 ? ` <span class="text-gray-400 text-[10px]">#${order._seq}/${order._seqTotal}</span>` : '';
            const t = order.table_no ? `<div class="${tc} font-extrabold text-[13px] truncate leading-tight">TBL ${escapeHtml(order.table_no)}${seq}</div>` : '';
            const r = order.room_no ? `<div class="${rc} font-bold text-[13px] truncate leading-tight">RM ${escapeHtml(order.room_no)}</div>` : '';
            return (t + r) || `<div class="${tc} font-extrabold text-[13px] truncate leading-tight">Paket</div>`;
        }

        function refreshTopBar() {
            // Header badge sayisini daima guncelle (0 bile olsa goster); kartlar sol kolonda
            document.getElementById('header-waiter-count').textContent = _waiterCards.length;
        }

        // Ekran saati kaynağı: sunucu / veritabanı / tarayıcı (admin panelinden seçilir)
        const CLOCK_SOURCE = @json(\App\Support\Clock::source());
        let serverClockOffsetMs = null;

        function updateClock() {
            const now = new Date(Date.now() + (CLOCK_SOURCE !== 'browser' && serverClockOffsetMs != null ? serverClockOffsetMs : 0));
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
                // Mutfak "hazır" zili — çan vuruşu (yeni sipariş sesinden belirgin şekilde farklı)
                [0, 0.35, 0.7].forEach((t) => {
                    [1567.98, 2093.0].forEach((freq, k) => {
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.type = 'triangle';
                        osc.frequency.value = freq;
                        const at = ctx.currentTime + t + k * 0.02;
                        gain.gain.setValueAtTime(k === 0 ? 0.4 : 0.2, at);
                        gain.gain.exponentialRampToValueAtTime(0.001, at + 0.4);
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.start(at);
                        osc.stop(at + 0.4);
                    });
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
            const card = document.querySelector(`#incoming-grid [data-order-id="${orderId}"]`);
            if (card) card.remove();
            document.getElementById('incoming-count').textContent =
                document.querySelectorAll('#incoming-grid [data-order-id]').length;
            lastIncomingKeys = ''; // Force re-render on next fetchData

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
                    lastIncomingKeys = '';
                    fetchData();
                    return;
                }
                fetchData();
            })
            .catch(err => {
                showToast('Bağlantı hatası: ' + err.message, 'error');
                lastIncomingKeys = '';
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

        function orderSig(o) {
            const id = o.source === 'symphony' ? ('S:' + (o.group_key || o.check_number || o.table_no)) : ('Q:' + o.id);
            return id + '|' + (o.bar_status || '') + (o.in_symphony ? '|S1' : '|S0') + '|' + (o.kitchen_status || '') + '|' + (o.items || []).length + '|' + (o.served_count || 0);
        }

        function renderReadyCard(order) {
            const isSymphony = order.source === 'symphony';
            const allItems = visibleItems(order);
            const msgItems = allItems.filter(i => i.mg === 99);
            const items = allItems.filter(i => i.mg !== 99);

            const itemRows = items.map(i => {
                const nm = i.name || (i.id ? getProductName(i.id) : '');
                return `<div class="flex justify-between py-[2px] border-b border-emerald-800/60">
                    <span class="truncate pr-1"><span class="font-bold text-gold mr-1">${i.quantity || i.qty || 1}x</span>${escapeHtml(nm)}</span>
                </div>`;
            }).join('');
            const msgSummary = msgItems.map(m => escapeHtml(m.note || m.name)).join(' • ');

            // Sayaç "hazir olali" suresini saymali; hazir olma anini ready_since_seconds'tan turetiyoruz.
            const readySecs = Math.max(0, (order.ready_since_seconds ?? order.seconds_ago) || 0);
            const readyStartIso = new Date(Date.now() - readySecs * 1000).toISOString();
            const timeStr = String(Math.floor(readySecs / 3600)).padStart(2,'0') + ':'
                          + String(Math.floor((readySecs % 3600) / 60)).padStart(2,'0') + ':'
                          + String(readySecs % 60).padStart(2,'0');
            const readyMinTotal = Math.floor(readySecs / 60);
            const readyTimeBg = timerBg(readyMinTotal, TIMER.ready);
            const hasNote = order.order_note && order.order_note.trim() !== '';
            const deliveredHtml = isSymphony
                ? `<button onclick="markSymphonyDelivered('${order.group_key}')" class="mt-1 w-full py-0.5 bg-emerald-600 hover:bg-emerald-700 rounded text-white font-bold text-[10px]"><i class="fas fa-check mr-1"></i>Tamam</button>`
                : `<button onclick="markQrDelivered(${order.id})" class="mt-1 w-full py-0.5 bg-emerald-600 hover:bg-emerald-700 rounded text-white font-bold text-[10px]"><i class="fas fa-check mr-1"></i>Tamam</button>`;
            const srcBadge = isSymphony
                ? `<span class="px-1 rounded text-[8px] font-bold bg-blue-700 text-blue-100 shrink-0">SYM</span>`
                : `<span class="px-1 rounded text-[8px] font-bold bg-orange-700 text-orange-100 shrink-0">QR</span>`;
            const chkLabel = isSymphony ? (order.check_number ? `Chk #${order.check_number}` : '') : `Chk #${order.id}`;
            const symMatch = isSymphony ? findSymOrder(order) : null;
            const waiterLine = symMatch && symMatch.waiter_name
                ? `<div class="text-[9px] text-gray-400 truncate mt-0.5 mb-1"><i class="fas fa-user mr-0.5 text-gray-500"></i>${escapeHtml(symMatch.waiter_name)}</div>`
                : '';
            // Onay sayacı: hazır adet / toplam adet (toplam feed'den çıkarılamazsa hazır sayısı)
            const feedTotal = symMatch && Array.isArray(symMatch.items)
                ? symMatch.items.reduce((a, i) => a + (i.qty || i.quantity || 1), 0) : 0;
            const servedCount = order.served_count || 0;
            const totalCount = feedTotal > servedCount ? feedTotal : (order.total_units || servedCount);
            const counterChip = isSymphony && order.kind !== 'checkless_msg' && servedCount > 0
                ? `<span class="px-1 rounded text-[8px] font-bold bg-emerald-700 text-emerald-100 shrink-0">${servedCount}/${totalCount}</span>`
                : '';
            return `<div class="ready-blink border-2 border-emerald-500 rounded-lg p-1.5 text-[11px]">
                <div class="flex items-start justify-between gap-1">
                    <div class="min-w-0 leading-tight">${locLabel(order, 'text-emerald-300', 'text-emerald-200')}</div>
                    ${srcBadge}
                </div>
                <div class="flex items-center justify-between gap-1 mt-0.5 mb-1">
                    <div class="flex items-center gap-1 min-w-0">
                        <span class="text-[9px] text-emerald-200/60 truncate">${chkLabel}</span>
                        ${counterChip}
                    </div>
                    <span class="ready-elapsed text-[10px] ${readyTimeBg} px-1 py-0.5 rounded text-white font-bold shrink-0" data-order-time="${readyStartIso}">${timeStr}</span>
                </div>
                ${waiterLine}
                <div>${itemRows || '<div class="text-gray-400 text-center py-0.5">—</div>'}</div>
                ${msgSummary ? `<p class="text-yellow-300 text-[10px] leading-snug mt-0.5"><i class="fas fa-bullhorn mr-0.5"></i>${msgSummary}</p>` : ''}
                ${hasNote ? `<p class="text-yellow-400 text-[10px] truncate mt-0.5"><i class="fas fa-exclamation-triangle mr-0.5"></i>${escapeHtml(order.order_note)}</p>` : ''}
                ${deliveredHtml}
            </div>`;
        }

        function renderOrderCard(order, filterItems) {
            const isSymphony = order.source === 'symphony';
            const isNew = !isSymphony && order.bar_status === 'new';
            const inSym = !isSymphony && order.in_symphony === true;

            let borderClass, sourceBadge;
            if (isSymphony) {
                borderClass = 'border-blue-500';
                sourceBadge = `<span class="px-1 rounded text-[8px] font-bold bg-blue-700 text-blue-100 shrink-0">SYM</span>`;
            } else {
                borderClass = isNew
                    ? (inSym ? 'new-order border-gold' : 'border-orange-500')
                    : 'border-green-500';
                sourceBadge = `<span class="px-1 rounded text-[8px] font-bold bg-orange-700 text-orange-100 shrink-0">QR</span>`;
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

            // Sag kolon (Hazirlanan) gizli kategorileri satirdan da eler; Gelen kolonu filtresiz.
            // Mesaj satirlari (mg=99) ayri kutuya alinmaz; POS'taki satir konumunda sari satir olarak basilir.
            const allItems = filterItems ? visibleItems(order) : parseItems(order);

            const itemsHtml = allItems.map(item => {
                if (item.mg === 99) {
                    return `<div class="py-[2px] border-b border-gray-700">
                        <div class="text-yellow-100 text-[11px] leading-snug"><i class="fas fa-comment-dots mr-1 text-yellow-400"></i>${escapeHtml(item.note || item.name)}</div>
                    </div>`;
                }
                const name = item.name || (item.id ? getProductName(item.id) : '');
                const qty  = item.quantity || item.qty || 1;
                const note = item.note || '';
                const ret  = !!item.is_returned;
                const nameClass = ret ? 'line-through text-red-400' : '';
                const qtyClass  = ret ? 'text-red-400' : 'text-gold';
                const retBadge  = ret ? `<span class="shrink-0 px-1 py-0.5 rounded text-[9px] font-bold bg-red-700 text-white uppercase">İade</span>` : '';
                return `<div class="py-[2px] border-b border-gray-700${ret ? ' iade-blink' : ''}">
                    <div class="flex justify-between items-center">
                        <span class="truncate pr-1"><span class="font-bold ${qtyClass} shrink-0 mr-1">${qty}x</span><span class="${nameClass}">${escapeHtml(name)}</span></span>
                        ${retBadge}
                    </div>
                    ${note ? `<div class="text-yellow-400 text-[9px] leading-snug truncate">— ${escapeHtml(note)}</div>` : ''}
                </div>`;
            }).join('');

            const chkLabel = isSymphony && order.check_number ? `Chk #${order.check_number}` : (isSymphony ? '' : `Chk #${order.id}`);
            // Mutfak karti duzeni: Chk meta satirinda tek basina, garson adi altinda kendi satirinda
            const waiterLine = isSymphony && order.waiter_name ? `<div class="text-[9px] text-gray-400 truncate mt-0.5"><i class="fas fa-user mr-0.5 text-gray-500"></i>${escapeHtml(order.waiter_name)}</div>` : '';

            let footer;
            if (isSymphony) {
                footer = `<div class="px-1 py-0.5 border-t border-gray-700">
                    <div class="w-full py-0.5 bg-blue-600/30 border border-blue-500 rounded text-[9px] font-bold text-center text-blue-200 flex items-center justify-center gap-1">
                        <i class="fas fa-server"></i> POS'ta
                    </div>
                </div>`;
            } else {
                const priceLine = order.total_price ? `<div class="px-1 py-0.5 border-t border-gray-700 flex justify-between items-center">
                    <span class="font-bold text-gold text-[11px]">${parseFloat(order.total_price).toFixed(2)} TL</span>
                </div>` : '';
                let btn;
                if (isNew && inSym) {
                    // Onay butonu kalkti: Symphony'ye giren siparis otomatik sag kolona duser
                    btn = `<div class="w-full py-0.5 bg-blue-600/30 border border-blue-500 rounded text-[9px] font-bold text-center text-blue-200 flex items-center justify-center gap-1">
                            <i class="fas fa-server"></i> POS'a alındı
                        </div>`;
                } else if (isNew && !inSym) {
                    btn = `<div class="flex gap-1">
                            <div class="flex-1 py-0.5 bg-gray-700 text-gray-400 rounded text-[9px] font-bold flex items-center justify-center gap-1">
                                <i class="fas fa-hourglass-half animate-pulse"></i> POS bekleniyor
                            </div>
                            <button onclick="cancelOrder(${order.id})" class="px-1 py-0.5 bg-red-700 hover:bg-red-600 text-white rounded text-[9px] font-bold transition" title="Siparişi iptal et">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>`;
                } else {
                    btn = `<div class="w-full py-0.5 bg-blue-600/40 border border-blue-500 rounded text-[9px] font-bold text-center flex items-center justify-center gap-1 text-blue-100">
                            <i class="fas fa-utensils"></i> Mutfakta hazirlaniyor
                        </div>`;
                }
                footer = `${priceLine}<div class="px-1 py-0.5 border-t border-gray-700">${btn}</div>`;
            }

            return `
            <div class="bg-gray-800 rounded-lg border-2 ${borderClass} overflow-hidden text-[11px]" data-order-id="${order.id}">
                <div class="px-1 py-0.5 bg-gray-750 border-b border-gray-700">
                    <div class="flex items-start justify-between gap-1">
                        <div class="min-w-0 leading-tight">${locLabel(order)}</div>
                        ${sourceBadge}
                    </div>
                    <div class="flex items-center justify-between gap-1 mt-0.5">
                        <span class="text-[9px] text-gray-400 truncate">${chkLabel}</span>
                        <span class="bar-elapsed px-1 py-0.5 rounded text-[10px] font-bold ${timeBg} shrink-0" data-order-time="${startTime.replace(/['"<>&]/g, '')}" data-is-symphony="${isSymphony ? '1' : '0'}">${timeStr}</span>
                    </div>
                    ${waiterLine}
                </div>
                <div class="px-1 py-0.5">
                    ${itemsHtml || '<div class="text-gray-400 text-center py-0.5">Urun yok</div>'}
                    ${order.order_note ? `<div class="mt-0.5 p-1 bg-yellow-900/30 rounded text-yellow-300 text-[10px]"><i class="fas fa-sticky-note mr-1"></i>${escapeHtml(order.order_note)}</div>` : ''}
                </div>
                ${footer}
            </div>`;
        }

        function renderBoards(orders, readyOrders) {
            // Kolon ayrimi: POS'a girmemis yeni QR siparisleri solda,
            // gerisi (Symphony, POS'a giren QR, mutfakta olanlar) sagda.
            const incoming = [], preparing = [];
            orders.forEach(order => {
                const isSymphony = order.source === 'symphony';
                // Hazir QR siparisler ayri "hazir" karti olarak en uste cikar
                if (!isSymphony && order.kitchen_status === 'ready') return;
                // Ickeler gizliyken yalnizca ickeden olusan QR siparisler sol kolonda kalir
                // MenuController QR siparisleri 'waiting' ile baslar; 'preparing'/'ready' olunca sag kolona duser
                const toRight = isSymphony || (order.in_symphony === true && order.has_food !== false)
                    || order.bar_status !== 'new'
                    || order.kitchen_status === 'preparing' || order.kitchen_status === 'ready';
                (toRight ? preparing : incoming).push(order);
            });

            // Filtreler (Admin > Ekran): butun ogeleri gizli olan kartlar gosterilmez
            const readyShown     = (readyOrders || []).filter(hasVisibleItems);
            const preparingShown = preparing.filter(hasVisibleItems);

            const readyHtml     = readyShown.map(renderReadyCard);
            const preparingHtml = preparingShown.map(o => renderOrderCard(o, true));
            const incomingHtml  = incoming.map(o => renderOrderCard(o));

            // Sol kolon — cagri kartlari ile yeni siparisler ayni griddedeyken yan yana durur.
            // _lastWaiterIds anahtara dahil: incoming bosken ('' == '') cagri kartlarinin
            // hic yazilmamasi hatasi boylece cozulur.
            const inKey = _lastWaiterIds + '|' + incoming.map(orderSig).join(',');
            if (inKey !== lastIncomingKeys) {
                lastIncomingKeys = inKey;
                document.getElementById('incoming-grid').innerHTML = _waiterCards.join('') + incomingHtml.join('');
            }
            document.getElementById('incoming-count').textContent = incomingHtml.length;

            // Sag kolon — hazir kartlar en ustte
            const prepKey = [...readyShown, ...preparingShown].map(orderSig).join(',');
            if (prepKey !== lastPreparingKeys) {
                lastPreparingKeys = prepKey;
                document.getElementById('preparing-grid').innerHTML = [...readyHtml, ...preparingHtml].join('');
            }
            document.getElementById('preparing-count').textContent = readyHtml.length + preparingHtml.length;
            document.getElementById('header-ready-count').textContent = readyHtml.length;

            const empty = _waiterCards.length === 0 && incomingHtml.length === 0 && preparingHtml.length === 0 && readyHtml.length === 0;
            document.getElementById('boards').classList.toggle('hidden', empty);
            document.getElementById('no-orders').classList.toggle('hidden', !empty);
        }

        function renderCompletedOrders(completedOrders, limit, attendedCalls) {
            const prefixEl = document.getElementById('completed-prefix');
            const ticker   = document.getElementById('completed-ticker');
            if (!prefixEl || !ticker) return;

            prefixEl.innerHTML = `<i class="fas fa-check-double"></i> SON ${limit ? limit : ''}:`;

            const hasCompleted = completedOrders && completedOrders.length > 0;
            const hasAttended  = attendedCalls && attendedCalls.length > 0;

            if (!hasCompleted && !hasAttended) {
                if (_lastCompletedKey !== 'empty') {
                    _lastCompletedKey = 'empty';
                    ticker.innerHTML = `<div class="strip-group"><span class="text-gray-500 text-xs">Henüz tamamlanan yok.</span></div>`;
                    ticker.style.animation = 'none';
                }
                return;
            }

            const orderChips = (completedOrders || []).map(order => {
                let items = [];
                try { items = Array.isArray(order.items) ? order.items : JSON.parse(order.items); }
                catch(e) { items = []; }
                const summary = items.map(i => {
                    const nm = i.name || (i.id ? getProductName(i.id) : '');
                    return `${i.quantity || 1}x ${escapeHtml(nm)}`;
                }).join(', ');
                const isSymphony = order.source === 'symphony';
                const srcBadge = isSymphony
                    ? `<span class="bg-blue-800 text-blue-200 text-[9px] px-1 rounded font-bold shrink-0">SYM</span>`
                    : `<span class="bg-orange-800 text-orange-200 text-[9px] px-1 rounded font-bold shrink-0">QR</span>`;
                const tableLabel = order.table_no ? 'TBL ' + escapeHtml(order.table_no) : (order.room_no ? 'RM ' + escapeHtml(order.room_no) : 'Pkt');
                const isCancelled = order.status === 'cancelled' || order.bar_status === 'cancelled';
                if (isCancelled) {
                    return `<span class="inline-flex items-center gap-1 bg-gray-800 border border-red-900 rounded px-1 py-0.5 text-xs text-red-400 max-w-[210px] shrink-0">
                        <i class="fas fa-ban text-red-600 shrink-0"></i>
                        <span class="font-bold shrink-0">${tableLabel}</span>${srcBadge}<span class="text-gray-400 truncate line-through">${escapeHtml(summary) || '—'}</span>
                    </span>`;
                }
                return `<span class="inline-flex items-center gap-1 bg-gray-800 border border-emerald-900 rounded px-1 py-0.5 text-xs text-emerald-300 max-w-[210px] shrink-0">
                    <i class="fas fa-check text-emerald-600 shrink-0"></i>
                    <span class="font-bold shrink-0">${tableLabel}</span>${srcBadge}<span class="text-gray-400 truncate">${escapeHtml(summary) || '—'}</span>
                </span>`;
            });

            const callChips = (attendedCalls || []).map(call => {
                const tableLabel = [call.table_no ? 'M' + call.table_no : '', call.room_no ? 'ROOM' + call.room_no : ''].filter(Boolean).join(' ') || 'Gen';
                return `<span class="inline-flex items-center gap-1 bg-gray-800 border border-green-900 rounded px-1 py-0.5 text-xs text-green-300 max-w-[170px] shrink-0">
                    <i class="fas fa-bell-slash text-green-600 shrink-0"></i>
                    <span class="font-bold shrink-0">${escapeHtml(tableLabel)}</span><span class="text-gray-400 truncate">${escapeHtml(call.note) || 'Çağrı'}</span>
                </span>`;
            });

            const chips = [...orderChips, ...callChips].join('');
            if (chips === _lastCompletedKey) return; // içerik aynıysa DOM'a dokunma — marquee baştan başlamasın
            _lastCompletedKey = chips;

            ticker.innerHTML = `<div class="strip-group">${chips}</div><div class="strip-group">${chips}</div>`;

            const mask = ticker.parentElement;
            const groupWidth = ticker.scrollWidth / 2;
            if (groupWidth <= mask.clientWidth) {
                ticker.style.animation = 'none'; // sığıyor — kaydırmaya gerek yok
            } else {
                ticker.style.animation = '';
                ticker.style.animationDuration = Math.max(30, Math.round(groupWidth / 60)) + 's';
            }
        }

        function renderWaiterCalls(calls) {
            // Cagri listesi degistiyse sol kolonu yeniden cizmeye zorla
            const ids = calls.map(c => c.id).join(',');
            if (ids !== _lastWaiterIds) {
                _lastWaiterIds = ids;
                lastIncomingKeys = '';
            }
            if (calls.length === 0) {
                _waiterCards = [];
                refreshTopBar();
                return;
            }
            _waiterCards = calls.map(call => {
                const minTotal = Math.floor(call.seconds_ago / 60);
                const timeBg = timerBg(minTotal, TIMER.waiter);
                const timeStr = String(Math.floor(call.seconds_ago / 3600)).padStart(2,'0') + ':' + String(Math.floor((call.seconds_ago % 3600) / 60)).padStart(2,'0') + ':' + String(call.seconds_ago % 60).padStart(2,'0');
                const callLabel = [call.table_no ? 'Masa ' + call.table_no : '', call.room_no ? 'ROOM ' + call.room_no : ''].filter(Boolean).join(' ') || 'Genel';
                return `<div class="bg-red-950 rounded-lg p-1 border border-red-800">
                    <div class="font-bold text-red-200 text-[12px] flex items-center gap-1"><i class="fas fa-bell text-red-400 waiter-alert text-[10px] shrink-0"></i><span class="truncate">${escapeHtml(callLabel)}</span></div>
                    <div class="mt-0.5"><span class="waiter-elapsed px-1 py-0.5 rounded text-[10px] ${timeBg} text-white font-bold" data-order-time="${(call.order_time || '').replace(/['"<>&]/g, '')}">${timeStr}</span></div>
                    ${call.note ? `<p class="text-red-300 text-[10px] mt-0.5 truncate">${escapeHtml(call.note)}</p>` : ''}
                    <button onclick="attendWaiterCall(${call.id})" class="mt-1 w-full py-0.5 bg-green-700 hover:bg-green-600 rounded text-[10px] font-bold transition"><i class="fas fa-check mr-0.5"></i>İlgilendi</button>
                </div>`;
            });
            refreshTopBar();
        }

        function fetchData() {
            Promise.all([
                fetch('/bar/api/orders').then(r => r.json()).catch(() => null),
                fetch('/bar/api/symphony').then(r => r.json()).catch(() => null),
            ]).then(([data, sym]) => {
                if (!data) return;

                const sn = data.server_now || (sym && sym.server_now);
                if (sn) {
                    const parsed = Date.parse(sn);
                    if (!isNaN(parsed)) serverClockOffsetMs = parsed - Date.now();
                }

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
                // Hazir bildirimi icin stabil anahtar (Symphony'de id=0 oldugundan group_key kullan)
                // Gizli kategoriler elendikten sonra kalan hazir kartlar ses tetikler
                const readyShown = (data.ready_orders || []).filter(hasVisibleItems);
                const currentReadyKeys = readyShown.map(o => o.group_key ? 'S:' + o.group_key : 'Q:' + o.id);

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

                if (!isFirstLoad) {
                    const newReady = currentReadyKeys.filter(k => !previousReadyIds.includes(k));
                    if (newReady.length > 0) playReadySound();
                }

                previousOrderIds  = currentOrderIds;
                previousWaiterIds = currentWaiterIds;
                previousReadyIds  = currentReadyKeys;
                isFirstLoad = false;

                // Hazir QR siparisler ayri "hazir" karti olarak gosterilir
                const activeOrders = allOrders.filter(o => !(o.source !== 'symphony' && o.kitchen_status === 'ready'));

                // Ayni odadan/masadan gelen aktif siparisler: #sira/toplam rozeti (en eski = #1)
                const locKey = o => o.table_no ? 'T' + o.table_no : (o.room_no ? 'R' + o.room_no : '');
                const groups = {};
                [...activeOrders, ...readyShown].forEach(o => {
                    const k = locKey(o);
                    if (!k) return;
                    (groups[k] = groups[k] || []).push(o);
                });
                Object.values(groups).forEach(list => {
                    if (list.length < 2) return;
                    list.sort((a, b) => (b.seconds_ago || 0) - (a.seconds_ago || 0));
                    list.forEach((o, i) => { o._seq = i + 1; o._seqTotal = list.length; });
                });

                renderWaiterCalls(data.waiter_calls || []);
                renderBoards(activeOrders, readyShown);
                renderCompletedOrders(data.completed_orders || [], data.completed_orders_limit || null, data.attended_calls || []);
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
