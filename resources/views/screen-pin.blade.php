<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $screenMeta['label'] }} — Erişim</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        html, body { height: 100%; }
        body {
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, #0b1120 0%, #1e293b 100%);
            display: flex; align-items: center; justify-content: center;
            color: #f9fafb; padding: 16px; user-select: none;
        }
        .gate-card {
            position: relative;
            width: 100%; max-width: 360px; background: #111827;
            border: 1px solid #374151; border-radius: 20px;
            padding: 30px 24px 26px; text-align: center;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.55);
        }
        .gate-icon {
            width: 60px; height: 60px; margin: 0 auto 14px; border-radius: 18px;
            background: rgba(212, 175, 55, 0.12); border: 1px solid rgba(212, 175, 55, 0.35);
            display: flex; align-items: center; justify-content: center;
        }
        .gate-title { font-size: 20px; font-weight: 700; color: #d4af37; }
        .gate-sub { font-size: 13px; color: #9ca3af; margin-top: 4px; }
        .pin-dots { display: flex; gap: 10px; justify-content: center; margin: 22px 0 6px; }
        .pin-dot {
            width: 14px; height: 14px; border-radius: 50%;
            border: 2px solid #4b5563; transition: all 0.12s ease;
        }
        .pin-dot.filled { background: #d4af37; border-color: #d4af37; transform: scale(1.15); }
        .gate-error {
            display: none; margin: 10px auto 0; max-width: 280px;
            padding: 8px 10px; border-radius: 10px; font-size: 13px;
            background: rgba(220, 38, 38, 0.15); border: 1px solid rgba(220, 38, 38, 0.45);
            color: #fca5a5;
        }
        .gate-error.visible { display: block; }
        .keypad { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 18px; }
        .key {
            height: 62px; border: 1px solid #374151; border-radius: 14px;
            background: #1f2937; color: #f9fafb; font-size: 22px; font-weight: 700;
            cursor: pointer; transition: background 0.1s ease, transform 0.05s ease;
        }
        .key:active { background: #374151; transform: scale(0.96); }
        .key.util { font-size: 18px; color: #9ca3af; }
        .key.enter {
            grid-column: span 3; height: 56px; margin-top: 2px;
            background: #d4af37; border-color: #d4af37; color: #111827; font-size: 18px;
        }
        .key.enter:disabled { background: #374151; border-color: #374151; color: #6b7280; cursor: not-allowed; }
        .shake { animation: shake 0.4s ease; }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20% { transform: translateX(-8px); }
            40% { transform: translateX(8px); }
            60% { transform: translateX(-5px); }
            80% { transform: translateX(5px); }
        }
    </style>
</head>
<body>
    @php
        $screenBg        = \App\Models\Setting::get('screen_bg_image_pin', '');
        $screenBgOpacity = (int) \App\Models\Setting::get('screen_bg_opacity_pin', 30);
        $screenBgSize    = (int) \App\Models\Setting::get('screen_bg_size_pin', 60);
    @endphp
    @if($screenBg)
    <div style="position:fixed;inset:0;display:flex;align-items:center;justify-content:center;pointer-events:none;user-select:none">
        <img src="{{ asset('images/' . $screenBg) }}?v={{ @filemtime(public_path('images/' . $screenBg)) }}" alt=""
             style="max-height:{{ $screenBgSize }}vh;max-width:{{ $screenBgSize }}vw;object-fit:contain;opacity:{{ round($screenBgOpacity / 100, 2) }}">
    </div>
    @endif
    <div class="gate-card" id="gate-card">
        <div class="gate-icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#d4af37" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </div>
        <h1 class="gate-title">{{ $screenMeta['label'] }}</h1>
        <p class="gate-sub">Ekrana girmek için PIN girin</p>

        <div class="pin-dots" id="pin-dots">
            @for($i = 0; $i < 6; $i++)<span class="pin-dot"></span>@endfor
        </div>

        <div class="gate-error {{ $errors->any() ? 'visible' : '' }}" id="gate-error">
            {{ $errors->first() ?: 'PIN hatalı. Tekrar deneyin.' }}
        </div>

        <form method="POST" action="{{ route('screen.pin.unlock', $screen) }}" id="pin-form">
            @csrf
            <input type="hidden" name="pin" id="pin-input" value="">
        </form>

        <div class="keypad">
            <button type="button" class="key" data-key="1">1</button>
            <button type="button" class="key" data-key="2">2</button>
            <button type="button" class="key" data-key="3">3</button>
            <button type="button" class="key" data-key="4">4</button>
            <button type="button" class="key" data-key="5">5</button>
            <button type="button" class="key" data-key="6">6</button>
            <button type="button" class="key" data-key="7">7</button>
            <button type="button" class="key" data-key="8">8</button>
            <button type="button" class="key" data-key="9">9</button>
            <button type="button" class="key util" id="clear-btn">C</button>
            <button type="button" class="key" data-key="0">0</button>
            <button type="button" class="key util" id="back-btn">&times;</button>
            <button type="button" class="key enter" id="enter-btn" disabled>GİRİŞ</button>
        </div>
    </div>

    <script>
        const MIN = 4, MAX = 6;
        let buf = '';
        const dots = document.querySelectorAll('.pin-dot');
        const input = document.getElementById('pin-input');
        const enterBtn = document.getElementById('enter-btn');
        const errorBox = document.getElementById('gate-error');

        function render() {
            dots.forEach((d, i) => d.classList.toggle('filled', i < buf.length));
            enterBtn.disabled = buf.length < MIN;
        }
        function press(d) { if (buf.length < MAX) { buf += d; render(); } }
        function backspace() { buf = buf.slice(0, -1); render(); }
        function clearBuf() { buf = ''; render(); }
        function submit() {
            if (buf.length < MIN) return;
            input.value = buf;
            document.getElementById('pin-form').submit();
        }

        document.querySelectorAll('[data-key]').forEach(b =>
            b.addEventListener('click', () => { errorBox.classList.remove('visible'); press(b.dataset.key); })
        );
        document.getElementById('clear-btn').addEventListener('click', clearBuf);
        document.getElementById('back-btn').addEventListener('click', backspace);
        enterBtn.addEventListener('click', submit);
        document.addEventListener('keydown', e => {
            if (/^[0-9]$/.test(e.key)) press(e.key);
            else if (e.key === 'Backspace') backspace();
            else if (e.key === 'Enter') submit();
        });

        @if($errors->any())
        document.getElementById('gate-card').classList.add('shake');
        @endif
    </script>
</body>
</html>
