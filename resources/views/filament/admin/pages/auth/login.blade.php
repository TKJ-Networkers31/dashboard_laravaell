<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Smart Lab IOT SMKN 2 Baleendah</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Sora', sans-serif;
            min-height: 100vh;
            display: flex;
            overflow: hidden;
        }

        /* ===== LEFT: FORM PANEL ===== */
        .form-side {
            width: 45%;
            min-width: 400px;
            background: #0a0f1e;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 56px 48px;
            position: relative;
            z-index: 2;
        }

        .form-side::after {
            content: '';
            position: absolute;
            right: 0; top: 0; bottom: 0;
            width: 1px;
            background: linear-gradient(180deg, transparent, rgba(0,180,255,0.4) 30%, rgba(0,180,255,0.4) 70%, transparent);
        }

        .form-inner {
            width: 100%;
            max-width: 360px;
        }

        /* Logo */
        .logo-block {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 40px;
        }
        .logo-img {
            width: 64px;
            height: 64px;
            object-fit: contain;
            border-radius: 50%;
            border: 2px solid rgba(0,180,255,0.25);
            padding: 4px;
            background: rgba(0,180,255,0.05);
            flex-shrink: 0;
        }
        .logo-text .school-abbr {
            font-family: 'Space Mono', monospace;
            font-size: 15px;
            font-weight: 700;
            color: #00b4ff;
            letter-spacing: 2px;
            display: block;
        }
        .logo-text .school-full {
            font-size: 10px;
            color: rgba(150,190,230,0.45);
            letter-spacing: 0.5px;
            text-transform: uppercase;
            display: block;
            margin-top: 2px;
        }

        /* Heading */
        .form-heading {
            margin-bottom: 32px;
        }
        .form-badge {
            font-family: 'Space Mono', monospace;
            font-size: 10px;
            color: #00b4ff;
            letter-spacing: 2px;
            background: rgba(0,180,255,0.07);
            border: 1px solid rgba(0,180,255,0.18);
            padding: 4px 12px;
            border-radius: 4px;
            display: inline-block;
            margin-bottom: 14px;
        }
        .form-heading h1 {
            font-size: 26px;
            font-weight: 700;
            color: #eaf4ff;
            line-height: 1.3;
            margin-bottom: 6px;
        }
        .form-heading p {
            font-size: 13px;
            color: rgba(140,180,220,0.5);
        }

        /* Error */
        .error-box {
            background: rgba(220,50,50,0.1);
            border: 1px solid rgba(220,50,50,0.3);
            border-radius: 8px;
            padding: 12px 14px;
            font-size: 12px;
            color: #ff8080;
            margin-bottom: 18px;
        }

        /* Fields */
        .field { margin-bottom: 20px; }
        .field label {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 10px;
            color: rgba(0,180,255,0.6);
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 8px;
            font-family: 'Space Mono', monospace;
        }
        .field input {
            width: 100%;
            background: rgba(0,30,70,0.5);
            border: 1px solid rgba(0,180,255,0.14);
            border-radius: 8px;
            padding: 12px 16px;
            color: #c8e0f8;
            font-size: 14px;
            font-family: 'Sora', sans-serif;
            outline: none;
            transition: all 0.2s;
        }
        .field input::placeholder { color: rgba(100,150,200,0.3); }
        .field input:focus {
            border-color: rgba(0,180,255,0.45);
            background: rgba(0,40,90,0.55);
            box-shadow: 0 0 0 3px rgba(0,180,255,0.07);
        }

        /* Remember me */
        .remember-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 24px;
        }
        .remember-row input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #00b4ff;
            cursor: pointer;
            flex-shrink: 0;
        }
        .remember-row label {
            font-size: 13px;
            color: rgba(150,190,230,0.6);
            cursor: pointer;
        }

        /* Button */
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #0055e0, #00a8f0);
            border: none;
            border-radius: 8px;
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            font-family: 'Sora', sans-serif;
            letter-spacing: 2px;
            text-transform: uppercase;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(0,140,240,0.3);
        }
        .btn-login:active { transform: translateY(0); }

        .footer-text {
            margin-top: 24px;
            text-align: center;
            font-family: 'Space Mono', monospace;
            font-size: 10px;
            color: rgba(0,180,255,0.18);
            letter-spacing: 1px;
        }

        /* ===== RIGHT: BG PANEL ===== */
        .bg-side {
            flex: 1;
            position: relative;
            overflow: hidden;
        }
        .bg-side img.bg-img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: brightness(0.35) saturate(0.8);
        }
        .bg-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg,
                rgba(4,12,30,0.85) 0%,
                rgba(0,30,80,0.6) 50%,
                rgba(0,60,120,0.4) 100%);
        }

        /* Grid lines on bg */
        .bg-grid {
            position: absolute; inset: 0;
            background-image:
                linear-gradient(rgba(0,180,255,0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0,180,255,0.05) 1px, transparent 1px);
            background-size: 50px 50px;
        }

        /* Scan line */
        .scan-line {
            position: absolute; top: -2px; left: 0;
            width: 100%; height: 2px;
            background: linear-gradient(90deg, transparent, rgba(0,180,255,0.4), transparent);
            animation: scan 5s linear infinite;
        }
        @keyframes scan { 0% { top: -2px; } 100% { top: 100%; } }

        /* Floating nodes */
        .node {
            position: absolute;
            width: 6px; height: 6px;
            background: #00b4ff; border-radius: 50%;
            box-shadow: 0 0 10px #00b4ff;
            animation: pulse-node 3s ease-in-out infinite;
        }
        @keyframes pulse-node {
            0%, 100% { opacity: 0.2; transform: scale(1); }
            50% { opacity: 0.9; transform: scale(1.6); }
        }

        /* Content on right */
        .bg-content {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 52px;
        }
        .bg-content .welcome-tag {
            font-family: 'Space Mono', monospace;
            font-size: 10px;
            color: rgba(0,180,255,0.7);
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 16px;
        }
        .bg-content h2 {
            font-size: 36px;
            font-weight: 700;
            color: #eaf4ff;
            line-height: 1.25;
            margin-bottom: 16px;
        }
        .bg-content h2 span { color: #00b4ff; }
        .bg-content p {
            font-size: 14px;
            color: rgba(160,200,240,0.6);
            line-height: 1.8;
            max-width: 400px;
            margin-bottom: 32px;
        }

        /* Stats row */
        .stats-row {
            display: flex;
            gap: 16px;
        }
        .stat-pill {
            background: rgba(0,180,255,0.07);
            border: 1px solid rgba(0,180,255,0.15);
            border-radius: 8px;
            padding: 12px 20px;
            text-align: center;
        }
        .stat-pill .num {
            font-family: 'Space Mono', monospace;
            font-size: 18px;
            color: #00b4ff;
            display: block;
        }
        .stat-pill .lbl {
            font-size: 9px;
            color: rgba(140,180,220,0.4);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Status bar */
        .status-bar {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 24px;
        }
        .dot-live {
            width: 7px; height: 7px;
            background: #00ff88; border-radius: 50%;
            box-shadow: 0 0 8px #00ff88;
            animation: pulse-node 2.5s ease-in-out infinite;
        }
        .status-text {
            font-family: 'Space Mono', monospace;
            font-size: 10px;
            color: rgba(0,255,136,0.6);
            letter-spacing: 1px;
        }

        /* corner decorations */
        .corner { position: absolute; width: 14px; height: 14px; border-color: rgba(0,180,255,0.25); }
        .tl { top: 12px; left: 12px; border-top: 1.5px solid; border-left: 1.5px solid; }
        .tr { top: 12px; right: 12px; border-top: 1.5px solid; border-right: 1.5px solid; }
        .bl { bottom: 12px; left: 12px; border-bottom: 1.5px solid; border-left: 1.5px solid; }
        .br { bottom: 12px; right: 12px; border-bottom: 1.5px solid; border-right: 1.5px solid; }

        /* Responsive */
        @media (max-width: 768px) {
            body { flex-direction: column; }
            .form-side { width: 100%; min-width: unset; padding: 40px 24px; }
            .form-side::after { display: none; }
            .bg-side { display: none; }
        }
    </style>
</head>
<body>

    {{-- LEFT: FORM --}}
    <div class="form-side">
        <div class="corner tl"></div><div class="corner tr"></div>
        <div class="corner bl"></div><div class="corner br"></div>

        <div class="form-inner">

            <div class="logo-block">
                <img class="logo-img" src="{{ asset('images/logo_smk2.png') }}" alt="Logo SMKN 2 Baleendah">
                <div class="logo-text">
                    <span class="school-abbr">SMKN 2 BALEENDAH</span>
                    <span class="school-full">Sekolah Menengah Kejuruan Negeri</span>
                </div>
            </div>

            <div class="form-heading">
                <div class="form-badge">// AUTH ACCESS</div>
                <h1>Selamat Datang</h1>
                <p>Masuk untuk mengakses Smart Lab IOT Dashboard</p>
            </div>

            @if (->any())
                <div class="error-box">
                    @foreach (->all() as )
                        <div>{{  }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('filament.admin.auth.login') }}">
                @csrf

                <div class="field">
                    <label>
                        <svg width="12" height="12" viewBox="0 0 16 16" fill="none">
                            <rect x="2" y="7" width="12" height="8" rx="1.5" stroke="currentColor" stroke-width="1.4"/>
                            <path d="M5 7V5a3 3 0 016 0v2" stroke="currentColor" stroke-width="1.4"/>
                        </svg>
                        Email
                    </label>
                    <input type="email" name="email" value="{{ old('email') }}"
                           placeholder="admin@smkn2baleendah.sch.id" required autofocus>
                </div>

                <div class="field">
                    <label>
                        <svg width="12" height="12" viewBox="0 0 16 16" fill="none">
                            <circle cx="8" cy="8" r="5.5" stroke="currentColor" stroke-width="1.4"/>
                            <circle cx="8" cy="8" r="2" fill="currentColor"/>
                        </svg>
                        Password
                    </label>
                    <input type="password" name="password" placeholder="••••••••••••" required>
                </div>

                <div class="remember-row">
                    <input type="checkbox" id="remember" name="remember" value="1"
                           {{ old('remember') ? 'checked' : '' }}>
                    <label for="remember">Ingat saya di perangkat ini</label>
                </div>

                <button type="submit" class="btn-login">MASUK &rarr;</button>
            </form>

            <div class="footer-text">SMKN 2 BALEENDAH &middot; SMART LAB IOT</div>
        </div>
    </div>

    {{-- RIGHT: BG IMAGE --}}
    <div class="bg-side">
        <img class="bg-img" src="{{ asset('images/bg_login.jpg') }}" alt="SMKN 2 Baleendah">
        <div class="bg-overlay"></div>
        <div class="bg-grid"></div>
        <div class="scan-line"></div>

        {{-- floating nodes --}}
        <div class="node" style="top:15%;left:20%;animation-delay:0s"></div>
        <div class="node" style="top:35%;left:75%;animation-delay:1s"></div>
        <div class="node" style="top:60%;left:15%;animation-delay:2s"></div>
        <div class="node" style="top:80%;left:80%;animation-delay:0.5s"></div>
        <div class="node" style="top:25%;left:50%;animation-delay:1.5s"></div>

        <div class="bg-content">
            <div class="welcome-tag">// SISTEM AKTIF</div>
            <h2>Smart Lab<br><span>IOT Dashboard</span></h2>
            <p>Monitoring dan kontrol laboratorium berbasis Internet of Things — pantau kondisi ruang, sensor, dan akses perangkat secara real-time.</p>

            <div class="stats-row">
                <div class="stat-pill">
                    <span class="num">24/7</span>
                    <div class="lbl">Monitoring</div>
                </div>
                <div class="stat-pill">
                    <span class="num">ESP32</span>
                    <div class="lbl">Device</div>
                </div>
                <div class="stat-pill">
                    <span class="num">MQTT</span>
                    <div class="lbl">Protocol</div>
                </div>
            </div>

            <div class="status-bar">
                <div class="dot-live"></div>
                <span class="status-text">SYSTEM ONLINE — ALL SENSORS ACTIVE</span>
            </div>
        </div>
    </div>

</body>
</html>
