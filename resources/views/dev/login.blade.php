<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dev Console — Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; }
        body { background: #080b14; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }

        .mono { font-family: 'Consolas', 'Courier New', 'SF Mono', monospace; }

        /* Subtle grid background */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(99,102,241,0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(99,102,241,0.04) 1px, transparent 1px);
            background-size: 48px 48px;
            pointer-events: none;
        }

        /* Ambient glow */
        .glow {
            position: fixed;
            border-radius: 50%;
            filter: blur(100px);
            pointer-events: none;
            opacity: 0.12;
        }

        .field {
            width: 100%;
            background: #080b14;
            border: 1px solid #1e2440;
            border-radius: 10px;
            padding: 12px 16px;
            color: #e2e8f0;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .field::placeholder { color: #374260; }
        .field:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99,102,241,0.12);
        }
    </style>
</head>
<body>
    <!-- Ambient orbs -->
    <div class="glow" style="width:400px;height:400px;top:-100px;left:50%;transform:translateX(-50%);background:#6366f1;"></div>
    <div class="glow" style="width:300px;height:300px;bottom:0;right:10%;background:#818cf8;"></div>

    <div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;position:relative;">
        <div style="width:100%;max-width:360px;">

            <!-- Logo -->
            <div style="text-align:center;margin-bottom:32px;">
                <div style="display:inline-flex;align-items:center;justify-content:center;width:56px;height:56px;background:rgba(99,102,241,0.1);border:1px solid rgba(99,102,241,0.25);border-radius:16px;margin-bottom:16px;">
                    <svg width="26" height="26" fill="none" stroke="#818cf8" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h1 style="color:#f1f5f9;font-size:20px;font-weight:700;margin:0 0 6px;">Dev Console</h1>
                <p class="mono" style="color:#475569;font-size:12px;margin:0;">{{ config('app.name') }} · {{ app()->environment() }} · v{{ app()->version() }}</p>
            </div>

            <!-- Error -->
            @if(session('error'))
            <div style="margin-bottom:16px;padding:12px 16px;background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);border-radius:10px;color:#f87171;font-size:13px;">
                {{ session('error') }}
            </div>
            @endif

            <!-- Card -->
            <div style="background:#0d1020;border:1px solid #1e2440;border-radius:16px;padding:28px;">
                <form method="POST" action="/dev/login">
                    @csrf
                    <div style="margin-bottom:20px;">
                        <label class="mono" style="display:block;font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:8px;">
                            Access Password
                        </label>
                        <input
                            class="field"
                            type="password"
                            name="password"
                            autofocus
                            autocomplete="current-password"
                            placeholder="Enter password"
                        />
                    </div>
                    <button
                        type="submit"
                        style="width:100%;background:#6366f1;color:#fff;font-size:14px;font-weight:600;padding:12px;border-radius:10px;border:none;cursor:pointer;transition:background 0.15s;"
                        onmouseover="this.style.background='#4f52d9'"
                        onmouseout="this.style.background='#6366f1'"
                    >
                        Enter Console →
                    </button>
                </form>
            </div>

            <!-- Hint -->
            <p class="mono" style="text-align:center;color:#374260;font-size:11px;margin-top:20px;">
                Set <span style="color:#64748b;">DEV_PASSWORD</span> in .env to configure access
            </p>
        </div>
    </div>
</body>
</html>
