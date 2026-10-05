<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sipariş Onaylandı</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
            --green: #3E7C4F;
        }
        * { font-family: 'Poppins', sans-serif; box-sizing: border-box; }
        body { background: var(--ivory); color: var(--espresso); }
        .font-serif-display { font-family: 'Playfair Display', Georgia, serif; }
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
        .total-card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 18px;
            box-shadow: 0 4px 20px rgba(45,36,32,0.05);
        }
        .loc-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--bronze-soft);
            color: var(--bronze-dark);
            border: 1px solid #E3D5C3;
            font-size: 0.8rem;
            font-weight: 600;
            padding: 7px 16px;
            border-radius: 999px;
        }
        .btn-bronze {
            display: block;
            width: 100%;
            background: var(--bronze);
            color: #fff;
            font-weight: 700;
            border-radius: 12px;
            padding: 13px 0;
            text-align: center;
            transition: background 0.2s;
        }
        .btn-bronze:hover { background: var(--bronze-dark); }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="max-w-sm w-full text-center">
        <!-- Success Icon -->
        <div class="check-anim mb-6">
            <div class="w-24 h-24 mx-auto rounded-full flex items-center justify-center"
                 style="background:var(--bronze-soft);box-shadow:0 8px 30px rgba(166,124,82,0.25);">
                <div class="w-16 h-16 rounded-full flex items-center justify-center" style="background:var(--green);">
                    <i class="fas fa-check text-white text-3xl"></i>
                </div>
            </div>
        </div>

        <!-- Title -->
        <h1 class="font-serif-display text-3xl font-semibold mb-2 fade-up fade-up-1">Siparişiniz Alındı</h1>
        <p class="text-sm mb-6 fade-up fade-up-1" style="color:var(--muted);">Siparişiniz mutfağa iletildi, en kısa sürede hazırlanacak</p>

        @if($order->room_no || $order->table_no)
        <div class="mb-6 fade-up fade-up-1">
            @if($order->room_no)
                <span class="loc-chip"><i class="fas fa-door-open"></i> Oda {{ $order->room_no }}</span>
            @elseif($order->table_no)
                <span class="loc-chip"><i class="fas fa-chair"></i> Masa {{ $order->table_no }}</span>
            @endif
        </div>
        @endif

        <!-- Total -->
        <div class="total-card p-6 mb-8 fade-up fade-up-2">
            <p class="text-sm mb-1" style="color:var(--muted);">Toplam Tutar</p>
            <p class="font-serif-display text-4xl font-semibold" style="color:var(--bronze-dark);">{{ number_format($order->total_price, 2, ',', '.') }} ₺</p>
        </div>

        <!-- Back Button -->
        <div class="fade-up fade-up-3">
            @if($order->table_no)
            <a href="{{ route('menu.table', ['tableNo' => $order->table_no]) }}" class="btn-bronze text-sm">
                <i class="fas fa-utensils mr-2"></i>Menüye Dön
            </a>
            @else
            <a href="{{ route('menu.index') }}" class="btn-bronze text-sm">
                <i class="fas fa-utensils mr-2"></i>Menüye Dön
            </a>
            @endif
        </div>
    </div>
</body>
</html>
