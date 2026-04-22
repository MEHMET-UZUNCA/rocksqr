<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sipariş Onaylandı</title>
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
        @keyframes checkmark {
            0% { transform: scale(0) rotate(-45deg); opacity: 0; }
            50% { transform: scale(1.2) rotate(0deg); opacity: 1; }
            100% { transform: scale(1) rotate(0deg); opacity: 1; }
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .check-anim { animation: checkmark 0.6s ease-out forwards; }
        .fade-up { animation: fadeUp 0.5s ease-out forwards; }
        .fade-up-1 { animation-delay: 0.3s; opacity: 0; }
        .fade-up-2 { animation-delay: 0.5s; opacity: 0; }
        .fade-up-3 { animation-delay: 0.7s; opacity: 0; }
    </style>
</head>
<body class="bg-primary font-poppins text-white min-h-screen flex items-center justify-center p-4">
    <div class="max-w-sm w-full text-center">
        <!-- Success Icon -->
        <div class="check-anim mb-6">
            <div class="w-24 h-24 mx-auto bg-green-500 rounded-full flex items-center justify-center shadow-lg shadow-green-500/30">
                <i class="fas fa-check text-white text-4xl"></i>
            </div>
        </div>

        <!-- Title -->
        <h1 class="text-2xl font-bold text-white mb-2 fade-up fade-up-1">Siparişiniz Onaylandı!</h1>
        <p class="text-gray-400 text-sm mb-8 fade-up fade-up-1">Siparişiniz mutfağa iletildi</p>

        <!-- Total -->
        <div class="bg-light-primary border border-gold/30 rounded-2xl p-6 mb-8 fade-up fade-up-2">
            <p class="text-gray-400 text-sm mb-1">Toplam Tutar</p>
            <p class="text-4xl font-bold text-gold">{{ number_format($order->total_price, 2) }} ₺</p>
        </div>

        <!-- Back Button -->
        <div class="fade-up fade-up-3">
            @if($order->table_no)
            <a href="{{ route('menu.table', ['tableNo' => $order->table_no]) }}" 
               class="w-full inline-block py-3 bg-gold text-primary font-bold rounded-xl hover:bg-gold/90 transition text-center">
                <i class="fas fa-utensils mr-2"></i>Menüye Dön
            </a>
            @else
            <a href="{{ route('menu.index') }}" 
               class="w-full inline-block py-3 bg-gold text-primary font-bold rounded-xl hover:bg-gold/90 transition text-center">
                <i class="fas fa-utensils mr-2"></i>Menüye Dön
            </a>
            @endif
        </div>
    </div>
</body>
</html>