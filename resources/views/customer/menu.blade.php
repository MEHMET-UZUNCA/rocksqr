<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>{{ \App\Models\Setting::get('site_title', 'QR Menu') }}{{ $tableNo ? ' - Masa ' . $tableNo : '' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { font-family: 'Poppins', sans-serif; box-sizing: border-box; }
        body {
            background: #0f0f0f;
            color: #e8d5b7;
            padding-bottom: 70px;
        }
        /* Header */
        .app-header {
            background: linear-gradient(90deg, #0a0a0a 0%, #181818 50%, #0a0a0a 100%);
            border-bottom: 2px solid #d4af37;
            box-shadow: 0 2px 12px rgba(212,175,55,0.2);
        }
        .rocks-logo {
            font-size: 1.4rem;
            font-weight: 800;
            letter-spacing: 3px;
            background: linear-gradient(90deg, #d4af37, #e8d5b7, #d4af37);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        /* Category tabs */
        .cat-nav {
            background: #111;
            border-bottom: 1px solid #222;
            overflow-x: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        .cat-nav::-webkit-scrollbar { display: none; }
        .cat-tab {
            white-space: nowrap;
            padding: 10px 18px;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            color: #666;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
            flex-shrink: 0;
            background: transparent;
            border-top: none;
            border-left: none;
            border-right: none;
        }
        .cat-tab.active, .cat-tab:hover {
            color: #d4af37;
            border-bottom-color: #d4af37;
        }
        /* Product card */
        .card-product {
            background: linear-gradient(160deg, #1c1c1c 0%, #151515 100%);
            border: 1px solid #2a2a2a;
            border-radius: 12px;
            overflow: hidden;
            transition: border-color 0.2s, box-shadow 0.2s;
            display: flex;
            flex-direction: column;
        }
        .card-product:hover {
            border-color: #d4af37;
            box-shadow: 0 4px 18px rgba(212,175,55,0.15);
        }
        .card-img {
            width: 100%;
            height: 100px;
            object-fit: cover;
            background: #1a1a1a;
            display: block;
        }
        .card-img.placeholder {
            object-fit: contain;
            padding: 8px;
        }
        @media (min-width: 768px) {
            .card-img { height: 160px; }
        }
        /* Buttons */
        .btn-gold {
            background: linear-gradient(90deg, #d4af37, #e8d5b7);
            color: #0f0f0f;
            border: none;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-gold:hover, .btn-gold:active {
            background: linear-gradient(90deg, #c9a42e, #d4af37);
            box-shadow: 0 3px 12px rgba(212,175,55,0.4);
        }
        .btn-outline-gold {
            background: rgba(212,175,55,0.06);
            border: 1.5px solid #d4af37;
            color: #d4af37;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-outline-gold:hover, .btn-outline-gold:active {
            background: rgba(212,175,55,0.15);
        }
        /* Cart badge */
        .badge {
            background: #d4af37;
            color: #0f0f0f;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.65rem;
            font-weight: 800;
            flex-shrink: 0;
        }
        /* Bottom bar */
        .bottom-bar {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            background: linear-gradient(90deg, #0a0a0a 0%, #181818 50%, #0a0a0a 100%);
            border-top: 2px solid #d4af37;
            padding: 10px 16px;
            z-index: 40;
            box-shadow: 0 -4px 20px rgba(212,175,55,0.1);
        }
        /* Cart drawer */
        .cart-drawer {
            background: #111;
            border-left: 2px solid #d4af37;
        }
        /* Modal */
        #waiter-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.75);
            backdrop-filter: blur(4px);
            z-index: 60;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .modal-box {
            background: linear-gradient(160deg, #1a1a1a 0%, #0f0f0f 100%);
            border: 2px solid #d4af37;
            border-radius: 16px;
            width: 100%;
            max-width: 360px;
        }
        /* Section title */
        .section-heading {
            color: #d4af37;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            padding-bottom: 10px;
            border-bottom: 1px solid #222;
        }
        /* Price */
        .price {
            color: #d4af37;
            font-weight: 700;
        }
        /* Toast */
        .toast {
            position: fixed;
            bottom: 80px;
            left: 50%;
            transform: translateX(-50%);
            background: #1a1a1a;
            border: 1px solid #d4af37;
            color: #e8d5b7;
            padding: 10px 18px;
            border-radius: 10px;
            font-size: 0.82rem;
            z-index: 9999;
            box-shadow: 0 4px 20px rgba(0,0,0,0.6);
            white-space: nowrap;
            animation: fadeInUp 0.25s ease;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateX(-50%) translateY(10px); }
            to   { opacity: 1; transform: translateX(-50%) translateY(0); }
        }
    </style>
</head>
<body>

    <!-- ===== HEADER ===== -->
    <header class="app-header sticky top-0 z-40">
        <div class="max-w-6xl mx-auto px-4 py-3 flex justify-between items-center">
            <!-- Logo + Title -->
            <div class="flex items-center gap-2.5">
                @if(\App\Models\Setting::get('logo_svg'))
                    <div class="h-7 w-auto [&>svg]:max-h-full [&>svg]:w-auto">{!! \App\Models\Setting::get('logo_svg') !!}</div>
                @else
                    <div class="rocks-logo">ROCKS</div>
                @endif
                <div>
                    <div class="text-xs font-bold" style="color:#d4af37;letter-spacing:2px;line-height:1.1;">QR MENU</div>
                    @if($tableNo)
                        <div class="text-xs" style="color:#666;">Masa {{ $tableNo }}</div>
                    @endif
                </div>
            </div>
            <!-- Actions -->
            <div class="flex items-center gap-2">
                <button onclick="showWaiterNote()"
                        class="btn-outline-gold flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold">
                    <i class="fas fa-bell text-xs"></i>
                    <span>Garson</span>
                </button>
                <button onclick="document.getElementById('cart-drawer').classList.toggle('translate-x-full')"
                        class="btn-gold flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold">
                    <i class="fas fa-shopping-cart text-xs"></i>
                    <span class="badge" id="cart-count">0</span>
                </button>
            </div>
        </div>
    </header>

    <!-- ===== CATEGORY TABS ===== -->
    <div class="cat-nav sticky z-30" style="top:57px;">
        <div class="max-w-6xl mx-auto flex">
            @foreach($categories as $category)
                <button class="cat-tab {{ $loop->first ? 'active' : '' }}"
                        onclick="scrollToCategory('cat-{{ $loop->index }}', this)">
                    {{ $category->name }}
                </button>
            @endforeach
        </div>
    </div>

    <!-- ===== MAIN CONTENT ===== -->
    <main class="max-w-6xl mx-auto px-4 pt-4 pb-2">
        @foreach($categories as $category)
            <section id="cat-{{ $loop->index }}" class="mb-8">
                <h2 class="section-heading mb-3">{{ $category->name }}</h2>

                @if($category->activeProducts->isEmpty())
                    <p class="text-xs text-center py-6" style="color:#555;">Bu kategoride ürün bulunmamaktadır.</p>
                @else
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                        @foreach($category->activeProducts as $product)
                            <div class="card-product">
                                <!-- Image -->
                                <div style="background:#1a1a1a;height:100px;overflow:hidden;flex-shrink:0;">
                                    <img src="{{ $product->photo_url }}"
                                         alt="{{ $product->name }}"
                                         class="card-img {{ $product->has_photo ? '' : 'placeholder' }}"
                                         loading="lazy"
                                         onerror="this.src='{{ asset('images/product-placeholder.svg') }}';this.classList.add('placeholder');">
                                </div>
                                <!-- Info -->
                                <div class="p-2.5 flex flex-col flex-1">
                                    <h3 class="text-xs font-semibold leading-snug mb-1" style="color:#e8d5b7;">{{ $product->name }}</h3>
                                    @if($product->description)
                                        <p class="text-xs mb-2 leading-snug" style="color:#555;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">{{ $product->description }}</p>
                                    @endif
                                    <div class="mt-auto flex justify-between items-center gap-1">
                                        <span class="price text-xs">{{ number_format($product->price, 2) }} ₺</span>
                                        <button onclick="addToCart({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $product->price }}, this)"
                                                class="btn-gold w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold flex-shrink-0">
                                            <i class="fas fa-plus" style="font-size:0.65rem;"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        @endforeach
    </main>

    <!-- ===== BOTTOM BAR ===== -->
    <div class="bottom-bar">
        <div class="max-w-6xl mx-auto flex gap-2.5">
            <button onclick="showWaiterNote()"
                    class="btn-outline-gold flex-1 py-2.5 rounded-xl text-xs font-semibold flex items-center justify-center gap-2">
                <i class="fas fa-bell"></i> Garson Çağır
            </button>
            <button onclick="document.getElementById('cart-drawer').classList.toggle('translate-x-full')"
                    class="btn-gold flex-1 py-2.5 rounded-xl text-xs font-semibold flex items-center justify-center gap-2">
                <i class="fas fa-shopping-cart"></i>
                <span>Sepetim</span>
                <span class="badge" id="cart-count-bottom">0</span>
            </button>
        </div>
    </div>

    <!-- ===== CART DRAWER ===== -->
    <div id="cart-drawer" class="cart-drawer fixed right-0 top-0 h-full w-full sm:w-96 z-50 translate-x-full transition-transform duration-300 flex flex-col">
        <div style="background:#1a1a1a;border-bottom:2px solid #d4af37;" class="px-4 py-3.5 flex justify-between items-center flex-shrink-0">
            <h2 class="font-bold text-base" style="color:#d4af37;">
                <i class="fas fa-shopping-cart mr-2 text-sm"></i>Sepetim
            </h2>
            <button onclick="document.getElementById('cart-drawer').classList.add('translate-x-full')"
                    class="w-8 h-8 rounded-full flex items-center justify-center text-lg"
                    style="background:#2a2a2a;color:#d4af37;">&times;</button>
        </div>

        <div class="flex-1 overflow-y-auto px-3 py-3" id="cart-items">
            <p class="text-center py-10 text-sm" style="color:#555;">Sepetiniz boş</p>
        </div>

        <div style="border-top:2px solid #2a2a2a;" class="p-3 space-y-2.5 flex-shrink-0">
            <div class="flex justify-between items-center font-bold">
                <span class="text-sm" style="color:#aaa;">Toplam</span>
                <span class="price text-base" id="cart-total">0.00 ₺</span>
            </div>
            <textarea id="order-note" placeholder="Sipariş notu..." rows="2"
                      class="w-full rounded-xl px-3 py-2 text-xs focus:outline-none resize-none"
                      style="background:#0a0a0a;border:1px solid #2a2a2a;color:#e8d5b7;"></textarea>
            <button onclick="checkout()" class="w-full py-3 btn-gold rounded-xl font-bold text-sm">
                <i class="fas fa-check mr-2"></i>Siparişi Tamamla
            </button>
        </div>
    </div>

    <!-- ===== WAITER MODAL ===== -->
    <div id="waiter-modal">
        <div class="modal-box p-5">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0"
                     style="background:rgba(212,175,55,0.12);">
                    <i class="fas fa-bell text-sm" style="color:#d4af37;"></i>
                </div>
                <div>
                    <h3 class="font-bold text-sm" style="color:#d4af37;">Garson Çağır</h3>
                    @if($tableNo)
                        <p class="text-xs" style="color:#666;">Masa {{ $tableNo }}</p>
                    @endif
                </div>
            </div>
            <textarea id="waiter-note" placeholder="Not eklemek ister misiniz? (isteğe bağlı)"
                      rows="3"
                      class="w-full rounded-xl px-3 py-2 mb-3 focus:outline-none text-xs resize-none"
                      style="background:#0a0a0a;border:1px solid #333;color:#e8d5b7;"></textarea>
            <div class="flex gap-2.5">
                <button onclick="closeWaiterModal()"
                        class="flex-1 py-2.5 rounded-xl text-xs font-semibold"
                        style="background:#1a1a1a;color:#666;border:1px solid #2a2a2a;">
                    İptal
                </button>
                <button onclick="submitWaiterCall()"
                        class="flex-1 py-2.5 btn-gold rounded-xl font-bold text-xs">
                    <i class="fas fa-bell mr-1"></i> Çağır
                </button>
            </div>
        </div>
    </div>

    <script>
        let cart = {};
        let clickLock = false;

        /* ---- Category tab scroll ---- */
        function scrollToCategory(sectionId, tabEl) {
            document.querySelectorAll('.cat-tab').forEach(t => t.classList.remove('active'));
            tabEl.classList.add('active');
            tabEl.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            const el = document.getElementById(sectionId);
            if (!el) return;
            const headerH = document.querySelector('.app-header').offsetHeight;
            const navH = document.querySelector('.cat-nav').offsetHeight;
            const top = el.getBoundingClientRect().top + window.scrollY - headerH - navH - 8;
            clickLock = true;
            window.scrollTo({ top, behavior: 'smooth' });
            setTimeout(() => { clickLock = false; }, 900);
        }

        /* ---- Active tab on scroll ---- */
        const sections = document.querySelectorAll('section[id^="cat-"]');
        const tabs = document.querySelectorAll('.cat-tab');
        const observer = new IntersectionObserver((entries) => {
            if (clickLock) return;
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const idx = Array.from(sections).indexOf(entry.target);
                    tabs.forEach(t => t.classList.remove('active'));
                    if (tabs[idx]) {
                        tabs[idx].classList.add('active');
                        tabs[idx].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                    }
                }
            });
        }, { rootMargin: '-25% 0px -65% 0px' });
        sections.forEach(s => observer.observe(s));

        /* ---- Cart ---- */
        function addToCart(productId, name, price, btn) {
            if (cart[productId]) {
                cart[productId].quantity++;
            } else {
                cart[productId] = { name, price, quantity: 1 };
            }
            updateCart();
            if (btn) {
                btn.style.transform = 'scale(0.8)';
                setTimeout(() => btn.style.transform = '', 180);
            }
        }

        function removeFromCart(productId) {
            delete cart[productId];
            updateCart();
        }

        function updateQuantity(productId, quantity) {
            if (quantity <= 0) { removeFromCart(productId); }
            else { cart[productId].quantity = quantity; updateCart(); }
        }

        function updateCart() {
            const count = Object.values(cart).reduce((s, i) => s + i.quantity, 0);
            document.getElementById('cart-count').textContent = count;
            document.getElementById('cart-count-bottom').textContent = count;

            const html = Object.entries(cart).map(([id, item]) => `
                <div class="flex items-center gap-2 rounded-xl p-2.5 mb-2" style="background:#1a1a1a;border:1px solid #222;">
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold truncate" style="color:#e8d5b7;">${item.name}</p>
                        <p class="text-xs" style="color:#888;">${(item.price * item.quantity).toFixed(2)} ₺</p>
                    </div>
                    <div class="flex items-center gap-1 flex-shrink-0">
                        <button onclick="updateQuantity(${id},${item.quantity-1})"
                                class="w-6 h-6 rounded-lg text-xs font-bold flex items-center justify-center"
                                style="background:#222;color:#d4af37;">−</button>
                        <span class="w-5 text-center text-xs font-bold" style="color:#d4af37;">${item.quantity}</span>
                        <button onclick="updateQuantity(${id},${item.quantity+1})"
                                class="w-6 h-6 rounded-lg text-xs font-bold flex items-center justify-center"
                                style="background:#222;color:#d4af37;">+</button>
                        <button onclick="removeFromCart(${id})"
                                class="w-6 h-6 rounded-lg text-xs flex items-center justify-center ml-0.5"
                                style="background:rgba(239,68,68,0.12);color:#f87171;">✕</button>
                    </div>
                </div>
            `).join('');

            document.getElementById('cart-items').innerHTML = count === 0
                ? '<p class="text-center py-10 text-sm" style="color:#555;">Sepetiniz boş</p>'
                : html;

            const total = Object.values(cart).reduce((s, i) => s + i.price * i.quantity, 0);
            document.getElementById('cart-total').textContent = total.toFixed(2) + ' ₺';
        }

        function checkout() {
            if (Object.keys(cart).length === 0) {
                showToast('Sepetiniz boş.');
                return;
            }
            const items = Object.entries(cart).map(([id, item]) => ({ id: parseInt(id), quantity: item.quantity }));
            const total = Object.values(cart).reduce((s, i) => s + i.price * i.quantity, 0);
            const note = document.getElementById('order-note').value;
            const form = document.createElement('form');
            form.method = 'POST';
            @if($tableNo)
                form.action = '{{ route("order.place", ["tableNo" => $tableNo]) }}';
            @else
                form.action = '{{ route("order.place.public") }}';
            @endif
            form.innerHTML = `@csrf
                <input type="hidden" name="items" value='${JSON.stringify(items)}'>
                <input type="hidden" name="total_price" value="${total}">
                <input type="hidden" name="order_note" value="${note}">`;
            document.body.appendChild(form);
            form.submit();
        }

        /* ---- Waiter ---- */
        function showWaiterNote() {
            document.getElementById('waiter-modal').style.display = 'flex';
        }
        function closeWaiterModal() {
            document.getElementById('waiter-modal').style.display = 'none';
            document.getElementById('waiter-note').value = '';
        }
        function submitWaiterCall() {
            const note = document.getElementById('waiter-note').value;
            @if($tableNo)
                const url = `{{ route('waiter.call', ['tableNo' => $tableNo]) }}`;
            @else
                const url = `{{ route('waiter.call.public') }}`;
            @endif
            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ note })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    closeWaiterModal();
                    showToast('Garson çağrıldı! En kısa sürede yanınızda olacak.');
                }
            })
            .catch(err => console.error(err));
        }

        /* ---- Toast ---- */
        function showToast(msg) {
            const t = document.createElement('div');
            t.className = 'toast';
            t.textContent = msg;
            document.body.appendChild(t);
            setTimeout(() => t.remove(), 3000);
        }

        /* ---- Close cart on backdrop click ---- */
        document.addEventListener('click', (e) => {
            const drawer = document.getElementById('cart-drawer');
            if (!drawer.classList.contains('translate-x-full') &&
                !drawer.contains(e.target) &&
                !e.target.closest('[onclick*="cart-drawer"]')) {
                drawer.classList.add('translate-x-full');
            }
        });
    </script>
</body>
</html>
