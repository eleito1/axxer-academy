@props(['title' => null, 'showErrors' => true])
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'AXXER Academy' }}</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f4f7fb;
            --surface: #ffffff;
            --surface-soft: #eef4ff;
            --surface-strong: #101d3a;
            --text: #14213d;
            --muted: #667085;
            --muted-strong: #475467;
            --line: #dfe7f3;
            --brand: #1559e8;
            --brand-strong: #0f3fba;
            --brand-soft: #e9f1ff;
            --ok: #168457;
            --ok-soft: #def5e8;
            --warn: #99601c;
            --warn-soft: #fff1d6;
            --danger: #a82331;
            --danger-soft: #fff0f1;
            --shadow: 0 18px 50px rgba(16, 32, 68, .10);
            --radius: 8px;
            --radius-lg: 12px;
            --container: 1180px;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                color-scheme: dark;
                --bg: #09111f;
                --surface: #101a2c;
                --surface-soft: #15243d;
                --surface-strong: #050b16;
                --text: #eef4ff;
                --muted: #b7c3d6;
                --muted-strong: #d5deee;
                --line: #243653;
                --brand-soft: #102a5d;
                --shadow: 0 18px 50px rgba(0, 0, 0, .26);
            }
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            min-width: 320px;
            background:
                radial-gradient(circle at 10% 0, rgba(21, 89, 232, .10), transparent 28rem),
                linear-gradient(180deg, var(--bg), #fff 72rem);
            color: var(--text);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 16px;
            line-height: 1.5;
        }
        @media (prefers-color-scheme: dark) {
            body { background: linear-gradient(180deg, var(--bg), #07101d 72rem); }
        }
        a { color: inherit; }
        img { max-width: 100%; display: block; }
        h1, h2, h3, p { margin-top: 0; }
        h1 { margin-bottom: 10px; font-size: clamp(30px, 8vw, 54px); line-height: 1.02; }
        h2 { margin-bottom: 10px; font-size: clamp(22px, 5vw, 34px); line-height: 1.12; }
        h3 { margin-bottom: 8px; font-size: 18px; line-height: 1.2; }
        p { color: var(--muted); }
        button, input, select, textarea { font: inherit; }
        :focus-visible { outline: 3px solid rgba(21, 89, 232, .28); outline-offset: 3px; }

        .skip-link {
            position: absolute;
            left: 18px;
            top: 12px;
            z-index: 20;
            transform: translateY(-160%);
            border-radius: 6px;
            background: var(--surface);
            padding: 10px 14px;
            color: var(--brand);
            font-weight: 800;
            box-shadow: var(--shadow);
        }
        .skip-link:focus { transform: translateY(0); }

        .top {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            min-height: 64px;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            border-bottom: 1px solid rgba(223, 231, 243, .78);
            background: rgba(255, 255, 255, .86);
            padding: 12px max(18px, 5vw);
            backdrop-filter: blur(18px);
        }
        @media (prefers-color-scheme: dark) {
            .top { background: rgba(16, 26, 44, .86); }
        }
        .brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: var(--text);
            font-weight: 900;
            letter-spacing: .02em;
            text-decoration: none;
        }
        .brand-mark {
            display: grid;
            width: 34px;
            height: 34px;
            place-items: center;
            border-radius: 8px;
            background: var(--surface-strong);
            color: #fff;
            font-size: 13px;
            letter-spacing: 0;
        }
        .brand span:last-child { color: var(--brand); }
        .nav {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .nav a, .nav button {
            min-height: 38px;
            border: 0;
            border-radius: 8px;
            background: transparent;
            color: var(--muted-strong);
            cursor: pointer;
            padding: 9px 11px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 800;
        }
        .nav a:hover, .nav button:hover { background: var(--brand-soft); color: var(--brand); }
        .nav form { margin: 0; }
        main {
            width: min(var(--container), 100%);
            margin: 0 auto;
            padding: 24px 18px 72px;
        }

        .stack { display: grid; gap: 18px; }
        .cluster { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
        .spread { display: flex; gap: 16px; align-items: center; justify-content: space-between; }
        .grid { display: grid; grid-template-columns: 1fr; gap: 16px; }
        .grid.cards { align-items: stretch; }
        @media (min-width: 720px) {
            main { padding: 36px 28px 86px; }
            .grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .grid.three { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        @media (min-width: 1120px) {
            .grid.auto { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }

        .card {
            border: 1px solid var(--line);
            border-radius: var(--radius-lg);
            background: var(--surface);
            padding: 22px;
            box-shadow: var(--shadow);
        }
        .soft-card {
            border: 1px solid var(--line);
            border-radius: var(--radius);
            background: color-mix(in srgb, var(--surface) 82%, var(--surface-soft));
            padding: 18px;
        }
        .auth { max-width: 520px; margin: 22px auto; }
        .hero-panel {
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, .18);
            border-radius: var(--radius-lg);
            background:
                linear-gradient(135deg, rgba(21, 89, 232, .94), rgba(16, 29, 58, .98)),
                var(--surface-strong);
            color: #fff;
            padding: clamp(24px, 8vw, 48px);
            box-shadow: var(--shadow);
        }
        .hero-panel p, .hero-panel .muted { color: #d7e4ff; }
        .kicker {
            margin-bottom: 8px;
            color: var(--brand);
            font-size: 12px;
            font-weight: 900;
            letter-spacing: .08em;
            text-transform: uppercase;
        }
        .muted { color: var(--muted); font-size: 13px; }
        .eyebrow { color: var(--muted); font-size: 12px; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; }
        .btn {
            display: inline-flex;
            min-height: 46px;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 0;
            border-radius: 8px;
            background: var(--brand);
            color: #fff;
            cursor: pointer;
            padding: 12px 18px;
            font-weight: 900;
            text-decoration: none;
            transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
        }
        .btn:hover { background: var(--brand-strong); box-shadow: 0 12px 24px rgba(21, 89, 232, .18); transform: translateY(-1px); }
        .btn.secondary { background: var(--brand-soft); color: var(--brand); }
        .btn.secondary:hover { background: #dbe9ff; color: var(--brand-strong); }
        .btn.ghost { background: transparent; color: var(--muted-strong); box-shadow: none; }
        .btn.full { width: 100%; }
        .btn[disabled], .disabled { opacity: .45; pointer-events: none; }
        .badge, .chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            background: var(--warn-soft);
            color: var(--warn);
            padding: 6px 10px;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: .04em;
            text-transform: uppercase;
        }
        .badge.aprovado, .chip.ok { background: var(--ok-soft); color: var(--ok); }
        .badge.bloqueado, .chip.danger { background: var(--danger-soft); color: var(--danger); }
        .progress-track {
            height: 9px;
            overflow: hidden;
            border-radius: 999px;
            background: #e6edf8;
        }
        .progress-fill {
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, var(--brand), #4da3ff);
            transition: width .28s ease;
        }
        .course-card {
            display: grid;
            gap: 16px;
            height: 100%;
            padding: 0;
            overflow: hidden;
            text-decoration: none;
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        }
        .course-card:hover { transform: translateY(-3px); border-color: rgba(21, 89, 232, .38); }
        .course-cover, .course-art {
            aspect-ratio: 16 / 9;
            width: 100%;
            object-fit: cover;
            background:
                linear-gradient(135deg, rgba(21, 89, 232, .9), rgba(16, 29, 58, .96)),
                var(--surface-strong);
        }
        .course-art {
            display: grid;
            place-items: center;
            color: #fff;
            font-size: 42px;
            font-weight: 900;
        }
        .course-body { display: grid; gap: 12px; padding: 0 18px 18px; }
        .empty-state {
            display: grid;
            gap: 10px;
            place-items: center;
            border: 1px dashed var(--line);
            border-radius: var(--radius-lg);
            background: var(--surface);
            padding: 30px 20px;
            text-align: center;
        }
        .empty-icon {
            display: grid;
            width: 48px;
            height: 48px;
            place-items: center;
            border-radius: 8px;
            background: var(--brand-soft);
            color: var(--brand);
            font-weight: 900;
        }
        .field { margin: 15px 0; }
        .field label { display: block; margin-bottom: 6px; font-size: 13px; font-weight: 800; }
        .field input, .field select, .field textarea, select {
            width: 100%;
            min-height: 44px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface);
            color: var(--text);
            padding: 11px;
        }
        .field input[aria-invalid="true"], .field select[aria-invalid="true"] { border-color: var(--danger); }
        .field-help, .field-error { display: block; margin-top: 5px; font-size: 12px; line-height: 1.35; }
        .field-help { color: var(--muted); }
        .field-error { color: var(--danger); }
        .errors, .success {
            border-radius: var(--radius);
            margin-bottom: 18px;
            padding: 14px 16px;
            font-size: 14px;
            font-weight: 700;
        }
        .errors { background: var(--danger-soft); color: var(--danger); }
        .success { background: var(--ok-soft); color: var(--ok); }
        .links { margin-top: 18px; font-size: 14px; }
        .links a { color: var(--brand); font-weight: 800; }
        .user { border-top: 1px solid var(--line); padding: 18px 0; }
        .user:first-of-type { border-top: 0; }
        .actions { display: flex; gap: 10px; align-items: end; flex-wrap: wrap; }
        .actions form { display: flex; gap: 8px; align-items: end; flex-wrap: wrap; }
        .actions select { min-width: 150px; }
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
        .skeleton {
            position: relative;
            overflow: hidden;
            background: #e8eef7;
        }
        .skeleton::after {
            content: "";
            position: absolute;
            inset: 0;
            transform: translateX(-100%);
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .46), transparent);
            animation: shimmer 1.4s infinite;
        }
        @keyframes shimmer { 100% { transform: translateX(100%); } }

        @media (max-width: 620px) {
            .top { align-items: flex-start; flex-direction: column; }
            .nav { width: 100%; justify-content: flex-start; }
            .nav a, .nav button { padding-inline: 9px; }
            .spread { align-items: flex-start; flex-direction: column; }
            .btn.full-mobile { width: 100%; }
            .card { padding: 18px; }
        }
    </style>
</head>
<body>
    <a class="skip-link" href="#conteudo">Ir para o conteudo</a>
    <header class="top">
        <a class="brand" href="{{ auth()->check() ? route('dashboard') : route('login') }}" aria-label="AXXER Academy">
            <span class="brand-mark">AX</span>
            <span>AXXER</span> <span>ACADEMY</span>
        </a>
        @auth
            <nav class="nav" aria-label="Navegacao principal">
                <a href="{{ route('dashboard') }}">Academia</a>
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}">Admin</a>
                    <a href="{{ route('admin.products.index') }}">Produtos</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">Sair</button>
                </form>
            </nav>
        @endauth
    </header>
    <main id="conteudo">
        @if(session('success'))
            <div class="success" role="status">{{ session('success') }}</div>
        @endif
        @if($showErrors && $errors->any())
            <div class="errors" role="alert">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        {{ $slot }}
    </main>
</body>
</html>
