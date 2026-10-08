<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>{{ \App\Models\Setting::get('site_title', 'QR Menu') }}{{ $tableNo ? ' - Masa ' . $tableNo : '' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --ivory: #FAF7F2;
            --card: #FFFFFF;
            --line: #E9E1D5;
            --espresso: #2D2420;
            --muted: #8A7E72;
            --bronze: #A67C52;
            --bronze-dark: #8C6242;
            --bronze-soft: #F3EBE1;
        }
        * { font-family: 'Poppins', sans-serif; box-sizing: border-box; }
        body {
            background: var(--ivory);
            color: var(--espresso);
            padding-bottom: 110px;
        }
        .font-serif-display { font-family: 'Playfair Display', Georgia, serif; }
        .text-muted { color: var(--muted); }
        .text-bronze { color: var(--bronze); }

        /* Header */
        .app-header {
            background: var(--ivory);
            border-bottom: 1px solid var(--line);
        }
        .logo-wrap { height: 34px; display: flex; align-items: center; }
        .logo-wrap > svg { max-height: 100%; max-width: 180px; width: auto; height: auto; }
        .brand-title {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 1.35rem;
            font-weight: 700;
            letter-spacing: 3px;
            color: var(--espresso);
            line-height: 1.1;
        }
        .brand-sub {
            font-size: 0.62rem;
            font-weight: 600;
            letter-spacing: 3px;
            color: var(--bronze);
            text-transform: uppercase;
            margin-top: 2px;
        }
        .chip-table {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: var(--bronze-soft);
            color: var(--bronze-dark);
            border: 1px solid #E3D5C3;
            font-size: 0.68rem;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 999px;
            white-space: nowrap;
        }
        .icon-btn {
            width: 38px; height: 38px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            cursor: pointer;
        }
        .icon-btn-bell { background: var(--card); border: 1px solid var(--line); color: var(--bronze); }
        .icon-btn-bell:hover { background: var(--bronze-soft); }
        .icon-btn-cart { background: var(--bronze); color: #fff; position: relative; }
        .icon-btn-cart:hover { background: var(--bronze-dark); }
        .badge {
            position: absolute;
            top: -4px; right: -4px;
            background: var(--espresso);
            color: #fff;
            border-radius: 999px;
            min-width: 17px; height: 17px;
            padding: 0 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.6rem;
            font-weight: 700;
            border: 2px solid var(--ivory);
        }

        /* Category tabs */
        .cat-nav {
            background: var(--ivory);
            border-bottom: 1px solid var(--line);
            overflow-x: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        .cat-nav::-webkit-scrollbar { display: none; }
        .cat-tab {
            white-space: nowrap;
            padding: 11px 16px;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.3px;
            color: var(--muted);
            cursor: pointer;
            border: none;
            border-bottom: 2px solid transparent;
            background: transparent;
            transition: all 0.2s;
            flex-shrink: 0;
        }
        .cat-tab.active { color: var(--bronze-dark); border-bottom-color: var(--bronze); }
        .cat-tab:hover { color: var(--bronze-dark); }

        /* Section heading */
        .section-heading {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 1.15rem;
            font-weight: 600;
            color: var(--espresso);
            display: flex;
            align-items: baseline;
            gap: 10px;
        }
        .section-heading::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--line);
        }

        /* Menu rows */
        .menu-row {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .menu-row:hover { border-color: #DCCDB8; box-shadow: 0 3px 14px rgba(45,36,32,0.06); }
        .row-img {
            width: 84px; height: 84px;
            border-radius: 10px;
            object-fit: cover;
            background: var(--bronze-soft);
            flex-shrink: 0;
        }
        .row-img.placeholder { object-fit: contain; padding: 6px; }
        .row-desc {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Hero card */
        .hero-card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(45,36,32,0.05);
        }
        .hero-img-wrap { height: 190px; overflow: hidden; background: var(--bronze-soft); }
        @media (min-width: 640px) { .hero-img-wrap { height: 250px; } }
        .hero-img { width: 100%; height: 100%; object-fit: cover; }
        .hero-img.placeholder { object-fit: contain; padding: 16px; }

        /* Add button */
        .btn-add {
            background: var(--bronze);
            color: #fff;
            border: none;
            cursor: pointer;
            transition: all 0.15s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .btn-add:hover { background: var(--bronze-dark); }
        .btn-add:active { transform: scale(0.85); }
        .btn-add-round { width: 34px; height: 34px; border-radius: 999px; }
        .btn-add-wide {
            padding: 9px 18px;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 600;
            gap: 7px;
        }
        .price { color: var(--bronze-dark); font-weight: 700; }
        .price-serif { font-family: 'Playfair Display', Georgia, serif; }

        /* Floating cart bar */
        #cart-fab {
            position: fixed;
            bottom: 14px; left: 14px; right: 14px;
            max-width: 480px;
            margin: 0 auto;
            background: var(--espresso);
            color: #fff;
            border-radius: 999px;
            padding: 8px 8px 8px 18px;
            display: none;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            z-index: 45;
            box-shadow: 0 8px 24px rgba(45,36,32,0.35);
            cursor: pointer;
            border: none;
            width: calc(100% - 28px);
        }
        #cart-fab.show { display: flex; animation: fabIn 0.25s ease; }
        @keyframes fabIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        #cart-fab .fab-btn {
            background: var(--bronze);
            color: #fff;
            border-radius: 999px;
            padding: 9px 18px;
            font-size: 0.8rem;
            font-weight: 600;
            white-space: nowrap;
        }

        /* Cart bottom sheet */
        #cart-sheet {
            position: fixed;
            inset: 0;
            z-index: 60;
            visibility: hidden;
        }
        #cart-sheet.open { visibility: visible; }
        .sheet-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(45,36,32,0.5);
            opacity: 0;
            transition: opacity 0.25s;
        }
        #cart-sheet.open .sheet-backdrop { opacity: 1; }
        .sheet-panel {
            position: absolute;
            bottom: 0; left: 0; right: 0;
            max-width: 520px;
            margin: 0 auto;
            background: var(--ivory);
            border-radius: 22px 22px 0 0;
            max-height: 88vh;
            display: flex;
            flex-direction: column;
            transform: translateY(100%);
            transition: transform 0.3s ease;
            box-shadow: 0 -10px 40px rgba(45,36,32,0.25);
        }
        #cart-sheet.open .sheet-panel { transform: translateY(0); }
        .sheet-grip { width: 42px; height: 4px; border-radius: 999px; background: #DCCDB8; margin: 10px auto 0; }

        /* Cart item rows */
        .cart-row {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 10px 12px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .stepper-btn {
            width: 27px; height: 27px;
            border-radius: 8px;
            background: var(--bronze-soft);
            color: var(--bronze-dark);
            font-weight: 700;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            flex-shrink: 0;
        }
        .stepper-btn:hover { background: #EADFD0; }
        .remove-btn {
            width: 27px; height: 27px;
            border-radius: 8px;
            background: #FBEAE7;
            color: #C0392B;
            font-size: 0.7rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            flex-shrink: 0;
        }

        /* Inputs */
        .field-input {
            width: 100%;
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 11px 14px;
            font-size: 0.85rem;
            color: var(--espresso);
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .field-input::placeholder { color: #B8AB9C; }
        .field-input:focus { border-color: var(--bronze); box-shadow: 0 0 0 3px rgba(166,124,82,0.15); }
        .field-error { border-color: #C0392B !important; box-shadow: 0 0 0 3px rgba(192,57,43,0.12) !important; }

        /* Buttons */
        .btn-primary {
            background: var(--bronze);
            color: #fff;
            border: none;
            font-weight: 700;
            border-radius: 12px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-primary:hover { background: var(--bronze-dark); }
        .btn-primary:disabled { opacity: 0.6; cursor: wait; }
        .btn-ghost {
            background: transparent;
            border: 1px solid var(--line);
            color: var(--muted);
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-ghost:hover { background: var(--card); color: var(--espresso); }

        /* Modal (waiter) */
        #waiter-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(45,36,32,0.5);
            backdrop-filter: blur(3px);
            z-index: 70;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .modal-box {
            background: var(--ivory);
            border: 1px solid var(--line);
            border-radius: 18px;
            width: 100%;
            max-width: 370px;
            box-shadow: 0 20px 60px rgba(45,36,32,0.3);
        }

        /* Toast */
        .toast {
            position: fixed;
            bottom: 96px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--espresso);
            color: #F5EFE7;
            padding: 11px 20px;
            border-radius: 12px;
            font-size: 0.82rem;
            z-index: 9999;
            box-shadow: 0 8px 24px rgba(45,36,32,0.4);
            white-space: nowrap;
            max-width: calc(100vw - 32px);
            overflow: hidden;
            text-overflow: ellipsis;
            animation: fadeInUp 0.25s ease;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateX(-50%) translateY(10px); }
            to   { opacity: 1; transform: translateX(-50%) translateY(0); }
        }
    </style>
</head>
<body>
    @php
        $qrBg        = \App\Models\Setting::get('screen_bg_image_qr', '');
        $qrBgOpacity = (int) \App\Models\Setting::get('screen_bg_opacity_qr', 30);
        $qrBgSize    = (int) \App\Models\Setting::get('screen_bg_size_qr', 60);
    @endphp
    @if($qrBg)
    <div style="position:fixed;inset:0;display:flex;align-items:center;justify-content:center;pointer-events:none;user-select:none">
        <img src="{{ asset('images/' . $qrBg) }}?v={{ @filemtime(public_path('images/' . $qrBg)) }}" alt=""
             style="max-height:{{ $qrBgSize }}vh;max-width:{{ $qrBgSize }}vw;object-fit:contain;opacity:{{ round($qrBgOpacity / 100, 2) }};filter:brightness(0.45)">
    </div>
    @endif

    <!-- ===== HEADER ===== -->
    <header class="app-header sticky top-0 z-40">
        <div class="max-w-3xl mx-auto px-4 py-3 flex justify-between items-center gap-3">
            <!-- Logo + Title -->
            <div class="flex items-center gap-3 min-w-0">
                @if(\App\Models\Setting::get('logo_svg'))
                    <div class="logo-wrap">{!! \App\Models\Setting::get('logo_svg') !!}</div>
                    <div class="border-l border-[#E3D5C3] pl-3">
                        <div class="brand-sub">Rocks Services QR</div>
                    </div>
                @else
                    <div>
                        <div class="brand-title">ROCKS HOTEL</div>
                        <div class="brand-sub">Rocks Services QR</div>
                    </div>
                @endif
            </div>
            <!-- Actions -->
            <div class="flex items-center gap-2 flex-shrink-0">
                @if($tableNo)
                    <span class="chip-table"><i class="fas fa-chair text-[0.6rem]"></i> Masa {{ $tableNo }}</span>
                @endif
                <button onclick="showWaiterNote()" class="icon-btn icon-btn-bell" aria-label="Garson çağır">
                    <i class="fas fa-bell text-sm"></i>
                </button>
                <button onclick="openCart()" class="icon-btn icon-btn-cart" aria-label="Sepetim">
                    <i class="fas fa-basket-shopping text-sm"></i>
                    <span class="badge" id="cart-count">0</span>
                </button>
            </div>
        </div>
    </header>

    <!-- ===== CATEGORY TABS ===== -->
    <div class="cat-nav sticky z-30" id="cat-nav">
        <div class="max-w-3xl mx-auto flex">
            @foreach($categories as $category)
                <button class="cat-tab {{ $loop->first ? 'active' : '' }}"
                        onclick="scrollToCategory('cat-{{ $loop->index }}', this)">
                    {{ $category->name }}
                </button>
            @endforeach
        </div>
    </div>

    <!-- ===== MAIN CONTENT ===== -->
    <main class="max-w-3xl mx-auto px-4 pt-5 pb-2">
        @php $heroRendered = false; @endphp
        @foreach($categories as $category)
            <section id="cat-{{ $loop->index }}" class="mb-9">
                <h2 class="section-heading mb-4">{{ $category->name }}</h2>

                @if($category->activeProducts->isEmpty())
                    <p class="text-xs text-center py-6 text-muted">Bu kategoride ürün bulunmamaktadır.</p>
                @else
                    <div class="space-y-3">
                        @foreach($category->activeProducts as $product)
                            @php $isHero = ! $heroRendered; @endphp

                            @if($isHero && $product->has_photo)
                            <!-- Hero product -->
                            <div class="hero-card">
                                <div class="hero-img-wrap">
                                    <img src="{{ $product->photo_url }}"
                                         alt="{{ $product->name }}"
                                         class="hero-img"
                                         onerror="this.src='{{ asset('images/product-placeholder.svg') }}';this.classList.add('placeholder');">
                                </div>
                                <div class="p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <h3 class="font-serif-display text-lg font-semibold leading-snug">{{ $product->name }}</h3>
                                            @if($product->description)
                                                <p class="text-xs text-muted mt-1 leading-relaxed">{{ $product->description }}</p>
                                            @endif
                                        </div>
                                        <span class="price price-serif text-xl whitespace-nowrap">{{ number_format($product->price, 2, ',', '.') }} ₺</span>
                                    </div>
                                    <button onclick="addToCart({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $product->price }}, this)"
                                            class="btn-add btn-add-wide mt-3">
                                        <i class="fas fa-plus text-[0.7rem]"></i> Sepete Ekle
                                    </button>
                                </div>
                            </div>
                            @php $heroRendered = true; @endphp
                            @else
                            <!-- List row -->
                            <div class="menu-row p-3 flex gap-3">
                                <img src="{{ $product->photo_url }}"
                                     alt="{{ $product->name }}"
                                     class="row-img"
                                     loading="lazy"
                                     onerror="this.src='{{ asset('images/product-placeholder.svg') }}';this.classList.add('placeholder');">
                                <div class="flex-1 min-w-0 flex flex-col">
                                    <h3 class="text-sm font-semibold leading-snug">{{ $product->name }}</h3>
                                    @if($product->description)
                                        <p class="row-desc text-xs text-muted mt-0.5 leading-snug">{{ $product->description }}</p>
                                    @endif
                                    <div class="mt-auto flex justify-between items-center gap-2 pt-1.5">
                                        <span class="price text-sm">{{ number_format($product->price, 2, ',', '.') }} ₺</span>
                                        <button onclick="addToCart({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $product->price }}, this)"
                                                class="btn-add btn-add-round" aria-label="Sepete ekle">
                                            <i class="fas fa-plus text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </section>
        @endforeach
    </main>

    <!-- ===== FLOATING CART BAR ===== -->
    <button id="cart-fab" onclick="openCart()">
        <span class="text-sm font-medium" id="fab-info">0 ürün</span>
        <span class="fab-btn">Sepetim <i class="fas fa-arrow-right ml-1.5 text-xs"></i></span>
    </button>

    <!-- ===== CART BOTTOM SHEET ===== -->
    <div id="cart-sheet">
        <div class="sheet-backdrop" onclick="closeCart()"></div>
        <div class="sheet-panel">
            <div class="sheet-grip"></div>
            <div class="px-5 py-3 flex justify-between items-center flex-shrink-0">
                <h2 class="font-serif-display text-xl font-semibold">Sepetim</h2>
                <button onclick="closeCart()"
                        class="w-8 h-8 rounded-full flex items-center justify-center text-lg"
                        style="background:var(--bronze-soft);color:var(--bronze-dark);">&times;</button>
            </div>

            <div class="flex-1 overflow-y-auto px-4 py-2 space-y-2" id="cart-items">
                <p class="text-center py-10 text-sm text-muted">Sepetiniz boş</p>
            </div>

            <div style="border-top:1px solid var(--line);" class="p-4 space-y-3 flex-shrink-0">
                @if(!empty($roomList))
                <!-- Room number -->
                <div id="room-block">
                    <label class="block text-[0.68rem] font-semibold uppercase tracking-widest text-muted mb-1.5">Oda Numaranız{{ $roomRequiredOrder ? '' : ' (isteğe bağlı)' }}</label>
                    <div id="room-saved" class="hidden items-center justify-between field-input !py-2.5">
                        <span class="flex items-center gap-2 text-sm font-semibold">
                            <i class="fas fa-door-open text-bronze"></i>
                            Oda <span id="room-saved-no"></span>
                        </span>
                        <button type="button" onclick="changeRoom()"
                                class="text-xs font-semibold text-bronze underline underline-offset-2 hover:text-bronze-dark">Değiştir</button>
                    </div>
                    <div id="room-input-wrap" class="hidden relative">
                        <i class="fas fa-door-open absolute left-3.5 top-1/2 -translate-y-1/2 text-sm" style="color:#B8AB9C;"></i>
                        <input id="room-no" type="text" inputmode="numeric" maxlength="16" placeholder="Örn: 101" autocomplete="off"
                               class="field-input !pl-10">
                    </div>
                    <p id="room-error" class="hidden mt-1.5 text-xs font-medium items-center gap-1.5" style="color:#C0392B;">
                        <i class="fas fa-circle-exclamation"></i>
                        Oda numarası hatalı, lütfen kontrol edin.
                    </p>
                </div>
                @endif

                <textarea id="order-note" placeholder="Sipariş notu (isteğe bağlı)..." rows="2"
                          class="field-input resize-none"></textarea>

                <div class="flex justify-between items-center pt-1">
                    <span class="text-sm text-muted font-medium">Toplam</span>
                    <span class="price price-serif text-xl" id="cart-total">0,00 ₺</span>
                </div>
                <button onclick="checkout()" id="checkout-btn" class="w-full py-3.5 btn-primary font-bold text-sm">
                    <i class="fas fa-check mr-2"></i>Siparişi Tamamla
                </button>
            </div>
        </div>
    </div>

    <!-- ===== WAITER MODAL ===== -->
    <div id="waiter-modal">
        <div class="modal-box p-5">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
                     style="background:var(--bronze-soft);">
                    <i class="fas fa-bell text-sm text-bronze"></i>
                </div>
                <div>
                    <h3 class="font-serif-display font-semibold text-base">Garson Çağır</h3>
                    @if($tableNo)
                        <p class="text-xs text-muted">Masa {{ $tableNo }}</p>
                    @endif
                </div>
            </div>
            <textarea id="waiter-note" placeholder="Not eklemek ister misiniz? (isteğe bağlı)"
                      rows="3"
                      class="field-input resize-none mb-3"></textarea>
            @if(!empty($roomList))
            <div id="waiter-room-block" class="mb-3">
                <label class="block text-[0.68rem] font-semibold uppercase tracking-widest text-muted mb-1.5">Oda Numaranız{{ $roomRequiredWaiter ? '' : ' (isteğe bağlı)' }}</label>
                <div id="waiter-room-saved" class="hidden items-center justify-between field-input !py-2.5">
                    <span class="flex items-center gap-2 text-sm font-semibold">
                        <i class="fas fa-door-open text-bronze"></i>
                        Oda <span id="waiter-room-saved-no"></span>
                    </span>
                    <button type="button" onclick="changeRoom('waiter')"
                            class="text-xs font-semibold text-bronze underline underline-offset-2 hover:text-bronze-dark">Değiştir</button>
                </div>
                <div id="waiter-room-input-wrap" class="hidden relative">
                    <i class="fas fa-door-open absolute left-3.5 top-1/2 -translate-y-1/2 text-sm" style="color:#B8AB9C;"></i>
                    <input id="waiter-room-no" type="text" inputmode="numeric" maxlength="16" placeholder="Örn: 101" autocomplete="off"
                           class="field-input !pl-10">
                </div>
                <p id="waiter-room-error" class="hidden mt-1.5 text-xs font-medium items-center gap-1.5" style="color:#C0392B;">
                    <i class="fas fa-circle-exclamation"></i>
                    Oda numarası hatalı, lütfen kontrol edin.
                </p>
            </div>
            @endif
            <div class="flex gap-2.5">
                <button onclick="closeWaiterModal()" class="flex-1 py-2.5 btn-ghost text-xs">İptal</button>
                <button onclick="submitWaiterCall()" id="waiter-submit"
                        class="flex-1 py-2.5 btn-primary font-bold text-xs">
                    <i class="fas fa-bell mr-1"></i> Çağır
                </button>
            </div>
        </div>
    </div>

    <script>
        let cart = {};
        let clickLock = false;
        const MAX_QTY = {{ \App\Http\Controllers\MenuController::MAX_ITEM_QUANTITY }};
        const ROOM_LIST = @js($roomList);
        const ROOM_REQ = { cart: @js($roomRequiredOrder), waiter: @js($roomRequiredWaiter) };
        const ROOM_KEY = 'rocksqr_room_no';
        let savedRoom = localStorage.getItem(ROOM_KEY) || '';

        /* ---- Helpers ---- */
        function fmt(n) {
            return n.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        /* ---- Sticky nav offset ---- */
        function syncNavOffset() {
            const header = document.querySelector('.app-header');
            const nav = document.getElementById('cat-nav');
            if (header && nav) nav.style.top = header.offsetHeight + 'px';
        }
        window.addEventListener('load', syncNavOffset);
        window.addEventListener('resize', syncNavOffset);

        /* ---- Category tab scroll ---- */
        function scrollToCategory(sectionId, tabEl) {
            document.querySelectorAll('.cat-tab').forEach(t => t.classList.remove('active'));
            tabEl.classList.add('active');
            tabEl.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            const el = document.getElementById(sectionId);
            if (!el) return;
            const headerH = document.querySelector('.app-header').offsetHeight;
            const navH = document.getElementById('cat-nav').offsetHeight;
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
                if (cart[productId].quantity >= MAX_QTY) {
                    showToast('En fazla sipariş limitine ulaşıldı.');
                    return;
                }
                cart[productId].quantity++;
            } else {
                cart[productId] = { name, price, quantity: 1 };
            }
            updateCart();
            if (btn) {
                btn.style.transform = 'scale(0.85)';
                setTimeout(() => btn.style.transform = '', 180);
            }
        }

        function removeFromCart(productId) {
            delete cart[productId];
            updateCart();
        }

        function updateQuantity(productId, quantity) {
            if (quantity <= 0) { removeFromCart(productId); return; }
            if (quantity > MAX_QTY) { showToast('En fazla sipariş limitine ulaşıldı.'); return; }
            cart[productId].quantity = quantity;
            updateCart();
        }

        function updateCart() {
            const entries = Object.entries(cart);
            const count = entries.reduce((s, [, i]) => s + i.quantity, 0);
            document.getElementById('cart-count').textContent = count;

            const fab = document.getElementById('cart-fab');
            if (count > 0) {
                const total = entries.reduce((s, [, i]) => s + i.price * i.quantity, 0);
                document.getElementById('fab-info').textContent =
                    count + ' ürün · ' + fmt(total) + ' ₺';
                fab.classList.add('show');
            } else {
                fab.classList.remove('show');
            }

            const html = entries.map(([id, item]) => `
                <div class="cart-row">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold truncate">${item.name}</p>
                        <p class="text-xs text-muted mt-0.5">${fmt(item.price * item.quantity)} ₺</p>
                    </div>
                    <div class="flex items-center gap-1.5 flex-shrink-0">
                        <button onclick="updateQuantity(${id},${item.quantity-1})" class="stepper-btn">−</button>
                        <span class="w-6 text-center text-sm font-bold">${item.quantity}</span>
                        <button onclick="updateQuantity(${id},${item.quantity+1})" class="stepper-btn">+</button>
                        <button onclick="removeFromCart(${id})" class="remove-btn ml-0.5"><i class="fas fa-trash-can"></i></button>
                    </div>
                </div>
            `).join('');

            document.getElementById('cart-items').innerHTML = count === 0
                ? '<p class="text-center py-10 text-sm text-muted">Sepetiniz boş</p>'
                : html;

            const total = entries.reduce((s, [, i]) => s + i.price * i.quantity, 0);
            document.getElementById('cart-total').textContent = fmt(total) + ' ₺';
        }

        /* ---- Cart sheet ---- */
        function openCart() {
            document.getElementById('cart-sheet').classList.add('open');
            document.body.style.overflow = 'hidden';
        }
        function closeCart() {
            document.getElementById('cart-sheet').classList.remove('open');
            document.body.style.overflow = '';
        }

        /* ---- Room number (sepet + garson modali ayni oda durumunu paylasir) ---- */
        const ROOM_UI = {
            cart:   { saved: 'room-saved',        savedNo: 'room-saved-no',        wrap: 'room-input-wrap',        input: 'room-no',        error: 'room-error' },
            waiter: { saved: 'waiter-room-saved', savedNo: 'waiter-room-saved-no', wrap: 'waiter-room-input-wrap', input: 'waiter-room-no', error: 'waiter-room-error' }
        };

        function initRoomUI() {
            const valid = !!savedRoom && ROOM_LIST.includes(savedRoom);
            if (!valid) savedRoom = '';
            Object.values(ROOM_UI).forEach(ui => {
                const saved = document.getElementById(ui.saved);
                if (!saved) return;
                const wrap = document.getElementById(ui.wrap);
                if (valid) {
                    document.getElementById(ui.savedNo).textContent = savedRoom;
                    saved.classList.remove('hidden');
                    saved.classList.add('flex');
                    wrap.classList.add('hidden');
                } else {
                    saved.classList.add('hidden');
                    saved.classList.remove('flex');
                    wrap.classList.remove('hidden');
                }
            });
        }

        function changeRoom(which) {
            savedRoom = '';
            localStorage.removeItem(ROOM_KEY);
            initRoomUI();
            setRoomError(false, 'cart');
            setRoomError(false, 'waiter');
            const input = document.getElementById((ROOM_UI[which] || ROOM_UI.cart).input);
            if (input) input.focus();
        }

        function setRoomError(show, which) {
            const ui = ROOM_UI[which] || ROOM_UI.cart;
            const err = document.getElementById(ui.error);
            if (!err) return false;
            err.classList.toggle('hidden', !show);
            err.classList.toggle('flex', show);
            const input = document.getElementById(ui.input);
            const saved = document.getElementById(ui.saved);
            if (input) input.classList.toggle('field-error', show && !input.closest('.hidden'));
            if (saved) saved.classList.toggle('field-error', show && saved.classList.contains('flex'));
            return show;
        }

        // Oda secimini dogrula; zorunlu akista hatali/boş girişi engeller, opsiyonel akista boş gecmeye izin verir
        function resolveRoom(which) {
            if (ROOM_LIST.length === 0) return { ok: true, room: '' };
            const ctx = ROOM_UI[which] ? which : 'cart';
            if (savedRoom && ROOM_LIST.includes(savedRoom)) return { ok: true, room: savedRoom };
            const input = document.getElementById(ROOM_UI[ctx].input);
            const room = (input ? input.value : '').trim();
            if (ROOM_LIST.includes(room)) return { ok: true, room };
            // Zorunlu degilse gecersiz/boş oda engellemez; oda bilgisi olmadan devam edilir
            if (!ROOM_REQ[ctx]) return { ok: true, room: '' };
            setRoomError(true, ctx);
            if (input) {
                input.focus();
                input.animate(
                    [{ transform: 'translateX(0)' }, { transform: 'translateX(-5px)' }, { transform: 'translateX(5px)' }, { transform: 'translateX(0)' }],
                    { duration: 250, iterations: 2 }
                );
            }
            return { ok: false, room: '' };
        }

        // Gecerli oda secildiginde kaydet ve tum oda bolumlerini guncelle
        function saveRoom(room) {
            savedRoom = room;
            localStorage.setItem(ROOM_KEY, room);
            setRoomError(false, 'cart');
            setRoomError(false, 'waiter');
            initRoomUI();
        }

        /* ---- Checkout ---- */
        function checkout() {
            if (Object.keys(cart).length === 0) {
                showToast('Sepetiniz boş.');
                return;
            }

            const rr = resolveRoom('cart');
            if (!rr.ok) return;
            const room = rr.room;
            if (room) saveRoom(room);

            const btn = document.getElementById('checkout-btn');
            btn.disabled = true;

            const items = Object.entries(cart).map(([id, item]) => ({ id: parseInt(id), quantity: item.quantity }));
            const note = document.getElementById('order-note').value;
            const form = document.createElement('form');
            form.method = 'POST';
            @if($tableNo)
                form.action = '{{ route("order.place", ["tableNo" => $tableNo]) }}';
            @else
                form.action = '{{ route("order.place.public") }}';
            @endif
            const fields = {
                _token: '{{ csrf_token() }}',
                items: JSON.stringify(items),
                order_note: note,
                room_no: room,
            };
            Object.entries(fields).forEach(([name, value]) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value;
                form.appendChild(input);
            });
            document.body.appendChild(form);
            form.submit();
        }

        /* ---- Waiter ---- */
        function showWaiterNote() {
            initRoomUI();
            setRoomError(false, 'waiter');
            document.getElementById('waiter-modal').style.display = 'flex';
        }
        function closeWaiterModal() {
            document.getElementById('waiter-modal').style.display = 'none';
            document.getElementById('waiter-note').value = '';
        }
        function submitWaiterCall() {
            const rr = resolveRoom('waiter');
            if (!rr.ok) return;
            if (rr.room) saveRoom(rr.room);
            const btn = document.getElementById('waiter-submit');
            const original = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Gönderiliyor';
            const note = document.getElementById('waiter-note').value;
            @if($tableNo)
                const url = `{{ route('waiter.call', ['tableNo' => $tableNo]) }}`;
            @else
                const url = `{{ route('waiter.call.public') }}`;
            @endif
            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ note, room_no: rr.room })
            })
            .then(r => {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(() => {
                closeWaiterModal();
                showToast('Garson çağrıldı! En kısa sürede yanınızda olacak.');
            })
            .catch(() => showToast('Çağrı gönderilemedi. Lütfen tekrar deneyin.'))
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = original;
            });
        }

        /* ---- Toast ---- */
        function showToast(msg) {
            const t = document.createElement('div');
            t.className = 'toast';
            t.textContent = msg;
            document.body.appendChild(t);
            setTimeout(() => t.remove(), 3000);
        }

        @if($errors->any())
            document.addEventListener('DOMContentLoaded', () => showToast(@js($errors->first())));
        @endif

        /* ---- Init ---- */
        document.addEventListener('DOMContentLoaded', () => {
            initRoomUI();
            syncNavOffset();
            Object.values(ROOM_UI).forEach(ui => {
                const input = document.getElementById(ui.input);
                if (!input) return;
                input.addEventListener('input', () => {
                    input.classList.remove('field-error');
                    const err = document.getElementById(ui.error);
                    if (err) { err.classList.add('hidden'); err.classList.remove('flex'); }
                });
            });
        });
    </script>
</body>
</html>
