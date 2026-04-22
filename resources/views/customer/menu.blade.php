<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ \App\Models\Setting::get('site_title', 'QR Menu') }}{{ $tableNo ? ' - Masa ' . $tableNo : '' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #0f0f0f;
            --secondary: #1a1a1a;
            --light: #2a2a2a;
            --gold: #d4af37;
            --silver: #c0c0c0;
            --accent: #e8d5b7;
        }
        * { font-family: 'Poppins', sans-serif; }
        body { 
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 100%);
            color: #e8d5b7;
        }
        .rocks-header {
            background: linear-gradient(90deg, #0f0f0f 0%, #1a1a1a 50%, #0f0f0f 100%);
            border-bottom: 3px solid #d4af37;
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
        }
        .rocks-logo {
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: 3px;
            background: linear-gradient(90deg, #d4af37, #e8d5b7, #d4af37);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 0 10px rgba(212, 175, 55, 0.5);
        }
        .card-product {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            border: 2px solid #d4af37;
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.2);
        }
        .card-product:hover {
            border-color: #e8d5b7;
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.4);
            transform: translateY(-5px);
        }
        .btn-primary {
            background: linear-gradient(90deg, #d4af37, #e8d5b7);
            color: #0f0f0f;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
        }
        .btn-primary:hover {
            background: linear-gradient(90deg, #e8d5b7, #d4af37);
            box-shadow: 0 6px 20px rgba(212, 175, 55, 0.5);
            transform: scale(1.05);
        }
        .section-title {
            color: #d4af37;
            font-size: 1.75rem;
            font-weight: 700;
            border-bottom: 2px solid #d4af37;
            padding-bottom: 10px;
            text-shadow: 0 2px 8px rgba(212, 175, 55, 0.3);
        }
        .price-tag {
            color: #d4af37;
            font-size: 1.25rem;
            font-weight: 700;
        }
        .cart-badge {
            background: #d4af37;
            color: #0f0f0f;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }
        .modal {
            background: rgba(15, 15, 15, 0.95);
            border: 2px solid #d4af37;
            border-radius: 8px;
        }
        @keyframes shimmer {
            0% { opacity: 0.6; }
            50% { opacity: 1; }
            100% { opacity: 0.6; }
        }
    </style>
</head>
<body class="bg-primary text-white font-sans">
    <div class="min-h-screen flex flex-col">
        <!-- Header -->
        <header class="rocks-header sticky top-0 z-40">
            <div class="max-w-6xl mx-auto px-4 py-6 flex justify-between items-center">
                <div>
                    @if(\App\Models\Setting::get('logo_svg'))
                        <div class="h-10 w-auto [&>svg]:max-h-full [&>svg]:w-auto">{!! \App\Models\Setting::get('logo_svg') !!}</div>
                    @else
                        <div class="rocks-logo">ROCKS</div>
                    @endif
                    <p class="text-sm" style="color: #d4af37;">QR MENU</p>
                    @if($tableNo)
                        <p class="text-xs" style="color: #c0c0c0;">Mesa {{ $tableNo }}</p>
                    @endif
                </div>
                <button onclick="document.getElementById('cart-drawer').classList.toggle('translate-x-full')" 
                        class="flex items-center gap-2 px-6 py-3 btn-primary">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-badge" id="cart-count">0</span>
                </button>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 max-w-6xl mx-auto w-full px-4 py-8">
            <!-- Menu Items by Category -->
            @foreach($categories as $category)
                <section class="mb-12">
                    <h2 class="section-title mb-6">{{ $category->name }}</h2>
                    
                    @if($category->activeProducts->isEmpty())
                        <p style="color: #c0c0c0;">Bu kategoride ürün bulunmamaktadır</p>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach($category->activeProducts as $product)
                                <div class="card-product">
                                    @if($product->photo_path)
                                        <div class="relative h-40 overflow-hidden" style="background: #2a2a2a;">
                                            <img src="{{ $product->photo_url }}" 
                                                 alt="{{ $product->name }}"
                                                 class="w-full h-full object-cover" loading="lazy">
                                        </div>
                                    @else
                                        <div class="relative h-40 flex items-center justify-center" style="background: linear-gradient(135deg, #2a2a2a, #1a1a1a);">
                                            <i class="fas fa-utensils" style="font-size: 3rem; color: #d4af37; opacity: 0.5;"></i>
                                        </div>
                                    @endif
                                    <div class="p-4">
                                        <h3 class="font-bold text-lg mb-2" style="color: #e8d5b7;">{{ $product->name }}</h3>
                                        @if($product->description)
                                            <p class="text-sm mb-3" style="color: #c0c0c0;">{{ $product->description }}</p>
                                        @endif
                                        <div class="flex justify-between items-center">
                                            <span class="price-tag">{{ number_format($product->price, 2) }} ₺</span>
                                            <button onclick="addToCart({{ $product->id }}, '{{ $product->name }}', {{ $product->price }})" 
                                                    class="btn-primary px-4 py-2">
                                                <i class="fas fa-plus"></i>
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

        <!-- Call Waiter Button -->
        <footer style="background: #1a1a1a; border-top: 2px solid #d4af37; padding: 1rem;">
            <div class="max-w-6xl mx-auto flex gap-3">
                <button onclick="showWaiterNote()" class="flex-1 py-3 btn-primary">
                    <i class="fas fa-bell"></i> Garson Çağır
                </button>
            </div>
        </footer>
    </div>

    <!-- Cart Drawer -->
    <div id="cart-drawer" class="fixed right-0 top-0 h-full w-full sm:w-96 rounded-lg z-50 translate-x-full transition-transform duration-300 flex flex-col overflow-hidden modal">
        <div style="background: #2a2a2a; border-bottom: 2px solid #d4af37;" class="px-4 py-4 flex justify-between items-center">
            <h2 class="text-xl font-bold" style="color: #d4af37;">Sepetim</h2>
            <button onclick="document.getElementById('cart-drawer').classList.add('translate-x-full')" 
                    class="text-2xl" style="color: #d4af37;">&times;</button>
        </div>

        <div class="flex-1 overflow-y-auto px-4 py-4" id="cart-items">
            <p class="text-center py-8" style="color: #c0c0c0;">Sepetiniz boş</p>
        </div>

        <div style="border-top: 2px solid #d4af37;" class="p-4 space-y-4">
            <div class="flex justify-between items-center text-lg font-bold">
                <span>Toplam:</span>
                <span class="price-tag" id="cart-total">0.00 ₺</span>
            </div>

            <textarea id="order-note" placeholder="Siparişiniz için özel not ekleyin..." 
                      class="w-full rounded px-3 py-2 text-sm focus:outline-none" 
                      style="background: #1a1a1a; border: 2px solid #d4af37; color: #e8d5b7;">
            </textarea>

            <button onclick="checkout()" class="w-full py-3 btn-primary font-bold">
                Siparişi Tamamla
            </button>
        </div>
    </div>

    <!-- Waiter Note Modal -->
    <div id="waiter-modal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
        <div class="modal p-6 w-full max-w-sm">
            <h3 class="text-xl font-bold mb-4" style="color: #d4af37;">Garson Çağır</h3>
            
            <textarea id="waiter-note" placeholder="Size nasıl yardımcı olabiliriz?" 
                      class="w-full rounded px-3 py-2 mb-4 focus:outline-none text-sm"
                      style="background: #1a1a1a; border: 2px solid #d4af37; color: #e8d5b7;"></textarea>

            <div class="flex gap-3">
                <button onclick="closeWaiterModal()" class="flex-1 py-2 rounded transition"
                        style="background: #2a2a2a; color: #c0c0c0; border: 2px solid #c0c0c0;">
                    İptal Et
                </button>
                <button onclick="submitWaiterCall({{ $tableNo }})" class="flex-1 py-2 btn-primary font-bold">
                    Çağır
                </button>
            </div>
        </div>
    </div>

    <script>
        let cart = {};

        function addToCart(productId, name, price) {
            if (cart[productId]) {
                cart[productId].quantity++;
            } else {
                cart[productId] = { name, price, quantity: 1 };
            }
            updateCart();
        }

        function removeFromCart(productId) {
            delete cart[productId];
            updateCart();
        }

        function updateQuantity(productId, quantity) {
            if (quantity <= 0) {
                removeFromCart(productId);
            } else {
                cart[productId].quantity = quantity;
                updateCart();
            }
        }

        function updateCart() {
            const cartCount = Object.keys(cart).length;
            document.getElementById('cart-count').textContent = cartCount;

            const cartItemsHtml = Object.entries(cart).map(([id, item]) => `
                <div class="flex items-center justify-between bg-primary border border-gold/20 rounded p-3 mb-3">
                    <div class="flex-1">
                        <p class="font-semibold text-gold">${item.name}</p>
                        <p class="text-sm text-gray-400">${item.price.toFixed(2)} ₺ adet</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button onclick="updateQuantity(${id}, ${item.quantity - 1})" class="px-2 py-1 bg-gold/20 hover:bg-gold/30 text-gold rounded text-sm">−</button>
                        <span class="w-6 text-center text-gold font-bold">${item.quantity}</span>
                        <button onclick="updateQuantity(${id}, ${item.quantity + 1})" class="px-2 py-1 bg-gold/20 hover:bg-gold/30 text-gold rounded text-sm">+</button>
                        <button onclick="removeFromCart(${id})" class="ml-2 px-2 py-1 bg-red-600/20 hover:bg-red-600/30 text-red-400 rounded text-sm">✕</button>
                    </div>
                </div>
            `).join('');

            if (cartCount === 0) {
                document.getElementById('cart-items').innerHTML = '<p class="text-gray-400 text-center py-8">Sepetiniz boş</p>';
            } else {
                document.getElementById('cart-items').innerHTML = cartItemsHtml;
            }

            const total = Object.values(cart).reduce((sum, item) => sum + (item.price * item.quantity), 0);
            document.getElementById('cart-total').textContent = `${total.toFixed(2)} ₺`;
        }

        function checkout() {
            if (Object.keys(cart).length === 0) {
                alert('Sepetiniz boş');
                return;
            }

            const items = Object.entries(cart).map(([id, item]) => ({
                id: parseInt(id),
                quantity: item.quantity
            }));

            const total = Object.values(cart).reduce((sum, item) => sum + (item.price * item.quantity), 0);
            const note = document.getElementById('order-note').value;

            const form = document.createElement('form');
            form.method = 'POST';
            @if($tableNo)
                form.action = '{{ route("order.place", ["tableNo" => $tableNo]) }}';
            @else
                form.action = '{{ route("order.place.public") }}';
            @endif
            form.innerHTML = `
                @csrf
                <input type="hidden" name="items" value='${JSON.stringify(items)}'>
                <input type="hidden" name="total_price" value="${total}">
                <input type="hidden" name="order_note" value="${note}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        function showWaiterNote() {
            document.getElementById('waiter-modal').classList.remove('hidden');
        }

        function closeWaiterModal() {
            document.getElementById('waiter-modal').classList.add('hidden');
            document.getElementById('waiter-note').value = '';
        }

        function submitWaiterCall(tableNo) {
            const note = document.getElementById('waiter-note').value;

            let url;
            @if($tableNo)
                url = `{{ route('waiter.call', ['tableNo' => $tableNo]) }}`;
            @else
                url = `{{ route('waiter.call.public') }}`;
            @endif

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ note })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Garson çağrıldı! En kısa sürede yanınızda olacak.');
                    closeWaiterModal();
                }
            })
            .catch(error => console.error('Error:', error));
        }
    </script>
</body>
</html>