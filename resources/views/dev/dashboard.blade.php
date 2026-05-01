<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dev Console — {{ ucfirst($tab) }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; overflow: hidden; }
        body { background: #080b14; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: #cbd5e1; }

        .mono { font-family: 'Consolas', 'Courier New', 'SF Mono', monospace; }

        /* Scrollbars */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #1e2440; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #2d3560; }

        /* Sidebar nav */
        .nav-item {
            display: flex; align-items: center; gap: 10px;
            padding: 8px 12px; border-radius: 8px;
            font-size: 13px; font-weight: 500; color: #64748b;
            text-decoration: none; transition: background 0.15s, color 0.15s;
            cursor: pointer;
        }
        .nav-item:hover  { background: rgba(255,255,255,0.04); color: #cbd5e1; }
        .nav-item.active { background: rgba(99,102,241,0.12); color: #818cf8; }

        /* Cards */
        .card {
            background: #0d1020;
            border: 1px solid #1e2440;
            border-radius: 12px;
        }

        /* Log level badges */
        .badge { display:inline-block; padding: 1px 7px; border-radius: 5px; font-size: 10px; font-weight: 700; font-family: 'Consolas', monospace; border: 1px solid; white-space: nowrap; flex-shrink: 0; }
        .badge-error, .badge-critical, .badge-emergency, .badge-alert { background: rgba(239,68,68,0.1); color: #f87171; border-color: rgba(239,68,68,0.25); }
        .badge-warning { background: rgba(245,158,11,0.1); color: #fbbf24; border-color: rgba(245,158,11,0.25); }
        .badge-notice, .badge-info { background: rgba(59,130,246,0.1); color: #60a5fa; border-color: rgba(59,130,246,0.25); }
        .badge-debug { background: rgba(100,116,139,0.1); color: #94a3b8; border-color: rgba(100,116,139,0.2); }

        /* Log message colors */
        .msg-error, .msg-critical, .msg-emergency, .msg-alert { color: #fca5a5; }
        .msg-warning { color: #fde68a; }
        .msg-notice, .msg-info { color: #93c5fd; }
        .msg-debug { color: #94a3b8; }
        .msg-default { color: #cbd5e1; }

        /* Details/summary */
        details > summary { list-style: none; }
        details > summary::-webkit-details-marker { display: none; }

        /* Field inputs */
        .field {
            background: #080b14; border: 1px solid #1e2440; border-radius: 8px;
            padding: 7px 12px; color: #e2e8f0; font-size: 13px; outline: none;
            transition: border-color 0.15s;
        }
        .field:focus { border-color: #6366f1; }
        .field::placeholder { color: #374260; }

        /* Table */
        .data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .data-table th { text-align: left; padding: 10px 16px; font-size: 10px; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.06em; border-bottom: 1px solid #1e2440; }
        .data-table td { padding: 10px 16px; border-bottom: 1px solid #111827; vertical-align: top; }
        .data-table tr:last-child td { border-bottom: none; }
        .data-table tbody tr:hover td { background: rgba(255,255,255,0.015); }

        /* Stat cards */
        .stat-card {
            background: #0d1020; border: 1px solid #1e2440; border-radius: 12px;
            padding: 16px 20px;
        }
        .stat-label { font-size: 11px; color: #475569; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.05em; }
        .stat-value { font-size: 22px; font-weight: 700; color: #f1f5f9; }
        .stat-sub   { font-size: 11px; color: #334155; margin-top: 2px; }

        /* Info card */
        .info-card {
            background: #0d1020; border: 1px solid #1e2440; border-radius: 10px;
            padding: 14px 16px;
        }
        .info-label { font-size: 10px; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px; }
        .info-value { font-size: 13px; font-weight: 600; }

        /* Mini bar */
        .mini-bar-track { flex: 1; max-width: 80px; background: #1e2440; border-radius: 999px; height: 3px; }
        .mini-bar-fill  { background: #6366f1; height: 3px; border-radius: 999px; min-width: 2px; }

        /* Btn */
        .btn-primary {
            background: #6366f1; color: #fff; font-size: 13px; font-weight: 600;
            padding: 7px 16px; border-radius: 8px; border: none; cursor: pointer;
            transition: background 0.15s;
        }
        .btn-primary:hover { background: #4f52d9; }

        /* Event badges */
        .ev-created { background: rgba(16,185,129,0.1); color: #34d399; }
        .ev-updated { background: rgba(59,130,246,0.1); color: #60a5fa; }
        .ev-deleted { background: rgba(239,68,68,0.1); color: #f87171; }
        .ev-default { background: rgba(100,116,139,0.1); color: #94a3b8; }
        .ev-badge { display:inline-block; padding:1px 8px; border-radius:5px; font-size:11px; font-weight:600; font-family:monospace; }
    </style>
</head>
<body>
<div style="display:flex;height:100vh;overflow:hidden;">

    {{-- ──────────────────────────────── SIDEBAR ──────────────────────────────── --}}
    <aside style="width:220px;flex-shrink:0;background:#0a0d18;border-right:1px solid #1e2440;display:flex;flex-direction:column;">

        {{-- Brand --}}
        <div style="padding:20px 16px 16px;border-bottom:1px solid #1e2440;">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:32px;height:32px;background:rgba(99,102,241,0.12);border:1px solid rgba(99,102,241,0.25);border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="16" height="16" fill="none" stroke="#818cf8" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <div style="color:#f1f5f9;font-size:13px;font-weight:700;line-height:1.2;">Dev Console</div>
                    <div class="mono" style="color:#334155;font-size:10px;">{{ config('app.name') }}</div>
                </div>
            </div>
        </div>

        {{-- Nav --}}
        <nav style="flex:1;padding:12px 8px;display:flex;flex-direction:column;gap:2px;">
            @php
            $navItems = [
                [
                    'key'   => 'overview',
                    'label' => 'Overview',
                    'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>',
                ],
                [
                    'key'   => 'logs',
                    'label' => 'App Logs',
                    'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
                ],
                [
                    'key'   => 'activity',
                    'label' => 'User Activity',
                    'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                ],
                [
                    'key'   => 'database',
                    'label' => 'Database',
                    'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>',
                ],
                [
                    'key'   => 'users',
                    'label' => 'Users',
                    'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>',
                ],
            ];
            @endphp

            @foreach($navItems as $item)
            <a href="/dev?tab={{ $item['key'] }}" class="nav-item {{ $tab === $item['key'] ? 'active' : '' }}">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0;">{!! $item['icon'] !!}</svg>
                {{ $item['label'] }}
            </a>
            @endforeach
        </nav>

        {{-- Footer --}}
        <div style="padding:12px 8px;border-top:1px solid #1e2440;">
            <div class="mono" style="display:flex;align-items:center;gap:8px;padding:8px 10px;background:#080b14;border-radius:8px;margin-bottom:6px;">
                <span style="width:7px;height:7px;border-radius:50%;background:{{ app()->isProduction() ? '#f87171' : '#34d399' }};flex-shrink:0;{{ app()->isProduction() ? '' : '' }}"></span>
                <span style="font-size:11px;color:#64748b;flex:1;">{{ app()->environment() }}</span>
                @if(config('app.debug'))
                <span style="font-size:10px;font-weight:700;color:#fbbf24;">DEBUG</span>
                @endif
            </div>
            <form method="POST" action="/dev/logout">
                @csrf
                <button type="submit" class="nav-item" style="width:100%;border:none;background:transparent;text-align:left;">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Logout
                </button>
            </form>
        </div>
    </aside>

    {{-- ──────────────────────────────── MAIN ──────────────────────────────── --}}
    <main style="flex:1;display:flex;flex-direction:column;overflow:hidden;">

        {{-- Top bar --}}
        <header style="background:rgba(8,11,20,0.9);backdrop-filter:blur(12px);border-bottom:1px solid #1e2440;padding:14px 24px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
            <div>
                <h1 style="color:#f1f5f9;font-size:14px;font-weight:700;margin:0 0 2px;">
                    @if($tab === 'overview')  System Overview
                    @elseif($tab === 'logs')  Application Logs
                    @elseif($tab === 'activity') User Activity
                    @elseif($tab === 'users') Users
                    @else Database
                    @endif
                </h1>
                <p class="mono" style="font-size:11px;color:#334155;margin:0;">PHP {{ PHP_VERSION }} &middot; Laravel {{ app()->version() }}</p>
            </div>
            <div class="mono" style="font-size:11px;color:#334155;">{{ now()->format('D d M Y · H:i:s') }}</div>
        </header>

        {{-- Scrollable content --}}
        <div style="flex:1;overflow-y:auto;padding:24px;">

            {{-- ════════════════════════ OVERVIEW ════════════════════════ --}}
            @if($tab === 'overview' && $overview)

            <div style="display:flex;flex-direction:column;gap:24px;">

                {{-- Alert banner if recent errors --}}
                @if($overview['recent_errors'] > 0)
                <div style="background:rgba(239,68,68,0.07);border:1px solid rgba(239,68,68,0.2);border-radius:10px;padding:12px 16px;display:flex;align-items:center;gap:10px;">
                    <svg width="16" height="16" fill="none" stroke="#f87171" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span style="font-size:13px;color:#f87171;font-weight:500;">{{ $overview['recent_errors'] }} ERROR {{ $overview['recent_errors'] === 1 ? 'entry' : 'entries' }} detected in the last 100 KB of logs.</span>
                    <a href="/dev?tab=logs&level=error" style="margin-left:auto;font-size:12px;color:#f87171;text-decoration:underline;">View logs →</a>
                </div>
                @endif

                {{-- System info --}}
                <div>
                    <div style="font-size:10px;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:12px;">System</div>
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px;">
                        @php
                        $sysCards = [
                            ['label'=>'PHP Version',    'value'=>$overview['php_version'],     'color'=>'#818cf8', 'mono'=>true ],
                            ['label'=>'Laravel',        'value'=>$overview['laravel_version'],  'color'=>'#818cf8', 'mono'=>true ],
                            ['label'=>'Environment',    'value'=>$overview['env'],              'color'=> app()->isProduction() ? '#f87171' : '#34d399', 'mono'=>true ],
                            ['label'=>'Debug Mode',     'value'=>$overview['debug'] ? 'ON' : 'OFF', 'color'=>$overview['debug'] ? '#fbbf24' : '#64748b', 'mono'=>true ],
                            ['label'=>'Database',       'value'=>$overview['db_driver'],        'color'=>'#60a5fa', 'mono'=>true ],
                            ['label'=>'Cache Driver',   'value'=>$overview['cache_driver'],     'color'=>'#94a3b8', 'mono'=>true ],
                            ['label'=>'Queue Driver',   'value'=>$overview['queue_driver'],     'color'=>'#94a3b8', 'mono'=>true ],
                            ['label'=>'Timezone',       'value'=>$overview['timezone'],         'color'=>'#94a3b8', 'mono'=>false],
                        ];
                        @endphp
                        @foreach($sysCards as $sc)
                        <div class="info-card">
                            <div class="info-label">{{ $sc['label'] }}</div>
                            <div class="info-value {{ $sc['mono'] ? 'mono' : '' }}" style="color:{{ $sc['color'] }};">{{ $sc['value'] }}</div>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Stats --}}
                <div>
                    <div style="font-size:10px;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:12px;">Application</div>
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px;">
                        <div class="stat-card">
                            <div class="stat-label">Users</div>
                            <div class="stat-value">{{ number_format($overview['user_count']) }}</div>
                            <div class="stat-sub">{{ $overview['active_users'] }} active</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">Activity Entries</div>
                            <div class="stat-value">{{ number_format($overview['activity_count']) }}</div>
                            <div class="stat-sub">total audit records</div>
                        </div>
                        <div class="stat-card" style="{{ $overview['recent_errors'] > 0 ? 'border-color:rgba(239,68,68,0.3);' : '' }}">
                            <div class="stat-label">Recent Errors</div>
                            <div class="stat-value" style="color:{{ $overview['recent_errors'] > 0 ? '#f87171' : '#f1f5f9' }};">{{ $overview['recent_errors'] }}</div>
                            <div class="stat-sub">in last 100 KB of log</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">Log File Size</div>
                            <div class="stat-value mono" style="font-size:18px;">{{ $overview['log_size'] }}</div>
                            <div class="stat-sub">storage/logs/laravel.log</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">Memory Usage</div>
                            <div class="stat-value mono" style="font-size:18px;">{{ $overview['memory_usage'] }}</div>
                            <div class="stat-sub">current request</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">Disk Free</div>
                            <div class="stat-value mono" style="font-size:18px;">{{ $overview['disk_free'] }}</div>
                            <div class="stat-sub">available on disk</div>
                        </div>
                    </div>
                </div>

            </div>

            @endif {{-- /overview --}}


            {{-- ════════════════════════ LOGS ════════════════════════ --}}
            @if($tab === 'logs' && $logs !== null)
            @php $lvl = $logs['filterLevel']; $srch = $logs['search']; @endphp

            <div style="display:flex;flex-direction:column;gap:16px;">

                {{-- Filter bar --}}
                <form method="GET" action="/dev" style="display:flex;flex-wrap:wrap;align-items:center;gap:10px;background:#0d1020;border:1px solid #1e2440;border-radius:12px;padding:12px 16px;">
                    <input type="hidden" name="tab" value="logs">
                    <select name="level" class="field mono" style="width:140px;">
                        <option value=""          {{ $lvl === ''          ? 'selected' : '' }}>All levels</option>
                        <option value="error"     {{ $lvl === 'error'     ? 'selected' : '' }}>ERROR</option>
                        <option value="warning"   {{ $lvl === 'warning'   ? 'selected' : '' }}>WARNING</option>
                        <option value="info"      {{ $lvl === 'info'      ? 'selected' : '' }}>INFO</option>
                        <option value="notice"    {{ $lvl === 'notice'    ? 'selected' : '' }}>NOTICE</option>
                        <option value="debug"     {{ $lvl === 'debug'     ? 'selected' : '' }}>DEBUG</option>
                        <option value="critical"  {{ $lvl === 'critical'  ? 'selected' : '' }}>CRITICAL</option>
                        <option value="emergency" {{ $lvl === 'emergency' ? 'selected' : '' }}>EMERGENCY</option>
                    </select>
                    <input
                        type="text"
                        name="search"
                        value="{{ $srch }}"
                        placeholder="Search message text…"
                        class="field"
                        style="flex:1;min-width:200px;"
                    />
                    <button type="submit" class="btn-primary">Filter</button>
                    @if($lvl || $srch)
                    <a href="/dev?tab=logs" style="font-size:13px;color:#64748b;text-decoration:none;">Clear</a>
                    @endif
                    <span style="margin-left:auto;font-size:11px;color:#334155;">
                        {{ count($logs['entries']) }} entries &nbsp;·&nbsp; reading last 512 KB
                    </span>
                </form>

                {{-- Log list --}}
                <div class="card" style="overflow:hidden;">
                    @forelse($logs['entries'] as $entry)
                    @php
                    $l = $entry['level'];
                    $msgClass = in_array($l, ['error','critical','emergency','alert']) ? 'msg-error' :
                                ($l === 'warning' ? 'msg-warning' :
                                (in_array($l, ['info','notice']) ? 'msg-info' :
                                ($l === 'debug' ? 'msg-debug' : 'msg-default')));
                    @endphp

                    @if($entry['short'])
                    {{-- Long entry: collapsible --}}
                    <details style="border-bottom:1px solid #111827;">
                        <summary style="display:flex;align-items:flex-start;gap:10px;padding:10px 16px;cursor:pointer;user-select:none;list-style:none;">
                            <span class="badge badge-{{ $l }}" style="margin-top:2px;">{{ strtoupper($l) }}</span>
                            <span class="mono" style="font-size:11px;color:#334155;margin-top:3px;white-space:nowrap;flex-shrink:0;">{{ $entry['datetime'] }}</span>
                            <span class="{{ $msgClass }} mono" style="font-size:12px;flex:1;min-width:0;word-break:break-word;line-height:1.5;">{{ $entry['short'] }}<span style="color:#475569;">…</span></span>
                            <svg width="12" height="12" fill="none" stroke="#334155" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:4px;transition:transform 0.2s;" class="chevron">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </summary>
                        <div style="padding:0 16px 12px 16px;">
                            <pre class="mono" style="font-size:11px;color:#94a3b8;background:#080b14;border:1px solid #1e2440;border-radius:8px;padding:12px 14px;overflow-x:auto;max-height:320px;overflow-y:auto;white-space:pre-wrap;word-break:break-all;margin:0;">{{ $entry['message'] }}</pre>
                        </div>
                    </details>
                    @else
                    {{-- Short entry: plain row --}}
                    <div style="display:flex;align-items:flex-start;gap:10px;padding:9px 16px;border-bottom:1px solid #111827;">
                        <span class="badge badge-{{ $l }}" style="margin-top:2px;">{{ strtoupper($l) }}</span>
                        <span class="mono" style="font-size:11px;color:#334155;margin-top:3px;white-space:nowrap;flex-shrink:0;">{{ $entry['datetime'] }}</span>
                        <span class="{{ $msgClass }} mono" style="font-size:12px;flex:1;min-width:0;word-break:break-word;line-height:1.5;">{{ $entry['message'] }}</span>
                    </div>
                    @endif

                    @empty
                    <div style="padding:48px 24px;text-align:center;color:#334155;font-size:14px;">No log entries found.</div>
                    @endforelse
                </div>

            </div>

            @endif {{-- /logs --}}


            {{-- ════════════════════════ ACTIVITY ════════════════════════ --}}
            @if($tab === 'activity' && $activity !== null)

            <div style="display:flex;flex-direction:column;gap:16px;">

                {{-- Filter bar --}}
                <form method="GET" action="/dev" style="display:flex;flex-wrap:wrap;align-items:center;gap:10px;background:#0d1020;border:1px solid #1e2440;border-radius:12px;padding:12px 16px;">
                    <input type="hidden" name="tab" value="activity">
                    <select name="event" class="field mono" style="width:150px;">
                        <option value="">All events</option>
                        <option value="created" {{ request('event') === 'created' ? 'selected' : '' }}>created</option>
                        <option value="updated" {{ request('event') === 'updated' ? 'selected' : '' }}>updated</option>
                        <option value="deleted" {{ request('event') === 'deleted' ? 'selected' : '' }}>deleted</option>
                    </select>
                    <button type="submit" class="btn-primary">Filter</button>
                    @if(request('event') || request('causer_id'))
                    <a href="/dev?tab=activity" style="font-size:13px;color:#64748b;text-decoration:none;">Clear</a>
                    @endif
                    <span style="margin-left:auto;font-size:11px;color:#334155;">{{ number_format($activity->total()) }} total entries</span>
                </form>

                {{-- Table --}}
                <div class="card" style="overflow:hidden;">
                    <div style="overflow-x:auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Actor</th>
                                    <th>Event</th>
                                    <th>Description</th>
                                    <th>Subject</th>
                                    <th>Changes</th>
                                    <th>When</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($activity as $log)
                                @php
                                $evClass = match($log->event) {
                                    'created' => 'ev-created',
                                    'updated' => 'ev-updated',
                                    'deleted' => 'ev-deleted',
                                    default   => 'ev-default',
                                };
                                $subject = $log->subject_type ? class_basename($log->subject_type) : null;
                                $props   = $log->properties instanceof \Illuminate\Support\Collection ? $log->properties->toArray() : [];
                                @endphp
                                <tr>
                                    <td style="white-space:nowrap;">
                                        @if($log->causer)
                                            <span style="color:#e2e8f0;font-weight:500;">{{ $log->causer->name ?? '—' }}</span>
                                        @else
                                            <span style="color:#334155;">System</span>
                                        @endif
                                    </td>
                                    <td style="white-space:nowrap;">
                                        @if($log->event)
                                        <span class="ev-badge {{ $evClass }}">{{ $log->event }}</span>
                                        @else
                                        <span style="color:#334155;">—</span>
                                        @endif
                                    </td>
                                    <td style="max-width:280px;">
                                        <span style="color:#94a3b8;font-size:12px;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $log->description }}">{{ $log->description }}</span>
                                    </td>
                                    <td style="white-space:nowrap;">
                                        @if($subject)
                                        <span class="mono" style="font-size:11px;color:#64748b;">{{ $subject }}</span>
                                        @if($log->subject_id)
                                        <span style="color:#334155;font-size:11px;"> #{{ substr($log->subject_id, 0, 8) }}</span>
                                        @endif
                                        @else
                                        <span style="color:#334155;">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(!empty($props))
                                        <details>
                                            <summary style="font-size:11px;color:#6366f1;cursor:pointer;list-style:none;font-family:monospace;">
                                                {{ count($props) }} field(s)
                                            </summary>
                                            <pre class="mono" style="font-size:10px;color:#64748b;background:#080b14;border:1px solid #1e2440;border-radius:6px;padding:8px 10px;max-width:280px;max-height:160px;overflow:auto;margin:4px 0 0;white-space:pre-wrap;word-break:break-all;">{{ json_encode($props, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                        </details>
                                        @else
                                        <span style="color:#334155;">—</span>
                                        @endif
                                    </td>
                                    <td class="mono" style="font-size:11px;color:#475569;white-space:nowrap;">{{ $log->created_at->format('d M Y H:i:s') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" style="text-align:center;padding:48px;color:#334155;">No activity recorded yet.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    @if($activity->hasPages())
                    <div style="border-top:1px solid #1e2440;padding:12px 16px;display:flex;align-items:center;justify-content:space-between;">
                        <span style="font-size:12px;color:#475569;">
                            @if($activity->count())
                            {{ $activity->firstItem() }}–{{ $activity->lastItem() }} of {{ number_format($activity->total()) }}
                            @endif
                        </span>
                        <div style="display:flex;align-items:center;gap:6px;">
                            @if(!$activity->onFirstPage())
                            <a href="{{ $activity->previousPageUrl() }}" style="padding:5px 14px;font-size:12px;color:#94a3b8;background:#1e2440;border-radius:7px;text-decoration:none;">← Prev</a>
                            @endif
                            <span class="mono" style="font-size:12px;color:#475569;padding:0 8px;">{{ $activity->currentPage() }} / {{ $activity->lastPage() }}</span>
                            @if($activity->hasMorePages())
                            <a href="{{ $activity->nextPageUrl() }}" style="padding:5px 14px;font-size:12px;color:#94a3b8;background:#1e2440;border-radius:7px;text-decoration:none;">Next →</a>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>

            </div>

            @endif {{-- /activity --}}


            {{-- ════════════════════════ DATABASE ════════════════════════ --}}
            @if($tab === 'database' && $database !== null)

            <div style="display:flex;flex-direction:column;gap:16px;">

                {{-- Connection info --}}
                <div class="card" style="padding:16px 20px;display:flex;flex-wrap:wrap;align-items:center;gap:24px;">
                    <div>
                        <div style="font-size:10px;color:#475569;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:3px;">Driver</div>
                        <div class="mono" style="font-size:14px;font-weight:700;color:#818cf8;">{{ $database['driver'] }}</div>
                    </div>
                    <div style="width:1px;height:32px;background:#1e2440;"></div>
                    <div>
                        <div style="font-size:10px;color:#475569;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:3px;">Database</div>
                        <div class="mono" style="font-size:14px;font-weight:700;color:#e2e8f0;">{{ basename($database['db_name']) }}</div>
                    </div>
                    <div style="width:1px;height:32px;background:#1e2440;"></div>
                    <div>
                        <div style="font-size:10px;color:#475569;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:3px;">Tables</div>
                        <div style="font-size:14px;font-weight:700;color:#e2e8f0;">{{ count($database['tables']) }}</div>
                    </div>
                    <div style="width:1px;height:32px;background:#1e2440;"></div>
                    <div>
                        <div style="font-size:10px;color:#475569;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:3px;">Total Rows</div>
                        <div style="font-size:14px;font-weight:700;color:#e2e8f0;">{{ number_format(array_sum(array_column($database['tables'], 'rows'))) }}</div>
                    </div>
                    @if(isset($database['error']))
                    <div style="margin-left:auto;">
                        <span class="mono" style="font-size:11px;color:#f87171;">Error: {{ $database['error'] }}</span>
                    </div>
                    @endif
                </div>

                {{-- Table list --}}
                <div class="card" style="overflow:hidden;">
                    @php $maxRows = count($database['tables']) ? max(array_column($database['tables'], 'rows')) : 1; @endphp
                    <div style="overflow-x:auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Table</th>
                                    <th>Rows</th>
                                    <th>Size</th>
                                    <th>Engine</th>
                                    <th>Collation</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($database['tables'] as $tbl)
                                <tr>
                                    <td class="mono" style="color:#e2e8f0;font-weight:500;">{{ $tbl['name'] }}</td>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:10px;">
                                            <span style="color:#f1f5f9;font-weight:600;min-width:48px;">{{ number_format($tbl['rows']) }}</span>
                                            @if($tbl['rows'] > 0)
                                            <div class="mini-bar-track">
                                                <div class="mini-bar-fill" style="width:{{ $maxRows > 0 ? min(100, ($tbl['rows'] / $maxRows) * 100) : 0 }}%;"></div>
                                            </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="mono" style="font-size:12px;color:#64748b;">{{ $tbl['size'] }}</td>
                                    <td style="font-size:12px;color:#64748b;">{{ $tbl['engine'] }}</td>
                                    <td class="mono" style="font-size:11px;color:#475569;">
                                        @php $col = $tbl['collation']; @endphp
                                        {{ $col === '—' ? '—' : (strstr($col, '_', true) ?: $col) }}
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" style="text-align:center;padding:48px;color:#334155;">No tables found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            @endif {{-- /database --}}

            {{-- ════════════════════════ USERS ════════════════════════ --}}
            @if($tab === 'users' && $users !== null)
            @php
            $sort    = request('sort', 'last_login_at');
            $dir     = request('dir',  'desc');
            $flipDir = $dir === 'desc' ? 'asc' : 'desc';

            $roleColors = [
                'admin'     => 'background:rgba(239,68,68,0.12);color:#f87171;',
                'manager'   => 'background:rgba(245,158,11,0.12);color:#fbbf24;',
                'site_head' => 'background:rgba(168,85,247,0.12);color:#c084fc;',
                'staff'     => 'background:rgba(100,116,139,0.12);color:#94a3b8;',
            ];

            function sortUrl($col, $currentSort, $currentDir) {
                $newDir = ($currentSort === $col && $currentDir === 'desc') ? 'asc' : 'desc';
                $params = array_merge(request()->except(['sort','dir','page']), ['tab'=>'users','sort'=>$col,'dir'=>$newDir]);
                return '/dev?' . http_build_query($params);
            }
            @endphp

            <div style="display:flex;flex-direction:column;gap:16px;">

                {{-- Filter bar --}}
                <form method="GET" action="/dev" style="display:flex;flex-wrap:wrap;align-items:center;gap:10px;background:#0d1020;border:1px solid #1e2440;border-radius:12px;padding:12px 16px;">
                    <input type="hidden" name="tab" value="users">
                    <input type="hidden" name="sort" value="{{ $sort }}">
                    <input type="hidden" name="dir"  value="{{ $dir }}">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search name or email…"
                        class="field"
                        style="width:240px;"
                    />
                    <select name="role" class="field mono" style="width:140px;">
                        <option value="">All roles</option>
                        <option value="admin"     {{ request('role') === 'admin'     ? 'selected' : '' }}>admin</option>
                        <option value="manager"   {{ request('role') === 'manager'   ? 'selected' : '' }}>manager</option>
                        <option value="site_head" {{ request('role') === 'site_head' ? 'selected' : '' }}>site_head</option>
                        <option value="staff"     {{ request('role') === 'staff'     ? 'selected' : '' }}>staff</option>
                    </select>
                    <select name="status" class="field" style="width:130px;">
                        <option value="">All statuses</option>
                        <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    <button type="submit" class="btn-primary">Filter</button>
                    @if(request('search') || request('role') || request('status'))
                    <a href="/dev?tab=users" style="font-size:13px;color:#64748b;text-decoration:none;">Clear</a>
                    @endif
                    <span style="margin-left:auto;font-size:11px;color:#334155;">{{ number_format($users->total()) }} users</span>
                </form>

                {{-- Table --}}
                <div class="card" style="overflow:hidden;">
                    <div style="overflow-x:auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="min-width:240px;">
                                        <a href="{{ sortUrl('name', $sort, $dir) }}" style="color:inherit;text-decoration:none;display:flex;align-items:center;gap:4px;">
                                            User
                                            @if($sort === 'name') <span style="color:#6366f1;">{{ $dir === 'desc' ? '↓' : '↑' }}</span> @endif
                                        </a>
                                    </th>
                                    <th>Roles</th>
                                    <th>Status</th>
                                    <th style="min-width:180px;">
                                        <a href="{{ sortUrl('last_login_at', $sort, $dir) }}" style="color:inherit;text-decoration:none;display:flex;align-items:center;gap:4px;">
                                            Last Login
                                            @if($sort === 'last_login_at') <span style="color:#6366f1;">{{ $dir === 'desc' ? '↓' : '↑' }}</span> @endif
                                        </a>
                                    </th>
                                    <th>Time Entries</th>
                                    <th style="min-width:120px;">
                                        <a href="{{ sortUrl('hire_date', $sort, $dir) }}" style="color:inherit;text-decoration:none;display:flex;align-items:center;gap:4px;">
                                            Hire Date
                                            @if($sort === 'hire_date') <span style="color:#6366f1;">{{ $dir === 'desc' ? '↓' : '↑' }}</span> @endif
                                        </a>
                                    </th>
                                    <th style="min-width:120px;">
                                        <a href="{{ sortUrl('created_at', $sort, $dir) }}" style="color:inherit;text-decoration:none;display:flex;align-items:center;gap:4px;">
                                            Member Since
                                            @if($sort === 'created_at') <span style="color:#6366f1;">{{ $dir === 'desc' ? '↓' : '↑' }}</span> @endif
                                        </a>
                                    </th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $user)
                                <tr>
                                    {{-- User --}}
                                    <td>
                                        <div style="display:flex;align-items:center;gap:10px;">
                                            <img
                                                src="{{ $user->avatar_url }}"
                                                alt="{{ $user->name }}"
                                                style="width:32px;height:32px;border-radius:50%;object-fit:cover;flex-shrink:0;border:1px solid #1e2440;"
                                            />
                                            <div style="min-width:0;">
                                                <div style="color:#e2e8f0;font-weight:500;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:180px;" title="{{ $user->name }}">
                                                    {{ $user->name }}
                                                </div>
                                                <div class="mono" style="font-size:11px;color:#475569;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:180px;" title="{{ $user->email }}">
                                                    {{ $user->email }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Roles --}}
                                    <td>
                                        <div style="display:flex;flex-wrap:wrap;gap:4px;">
                                            @forelse($user->roles as $role)
                                            <span class="mono" style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:5px;{{ $roleColors[$role->name] ?? 'background:rgba(100,116,139,0.12);color:#94a3b8;' }}">
                                                {{ $role->name }}
                                            </span>
                                            @empty
                                            <span style="font-size:11px;color:#334155;">—</span>
                                            @endforelse
                                        </div>
                                    </td>

                                    {{-- Status --}}
                                    <td>
                                        <div style="display:flex;align-items:center;gap:6px;">
                                            <span style="width:7px;height:7px;border-radius:50%;background:{{ $user->is_active ? '#34d399' : '#ef4444' }};flex-shrink:0;"></span>
                                            <span style="font-size:12px;color:{{ $user->is_active ? '#34d399' : '#f87171' }};">{{ $user->is_active ? 'Active' : 'Inactive' }}</span>
                                        </div>
                                        @if($user->must_change_password)
                                        <div style="font-size:10px;color:#fbbf24;margin-top:2px;">⚠ pwd reset required</div>
                                        @endif
                                    </td>

                                    {{-- Last Login --}}
                                    <td>
                                        @if($user->last_login_at)
                                        <div style="color:#e2e8f0;font-size:12px;">{{ $user->last_login_at->diffForHumans() }}</div>
                                        <div class="mono" style="font-size:10px;color:#475569;">{{ $user->last_login_at->format('d M Y · H:i') }}</div>
                                        @else
                                        <span style="font-size:12px;color:#334155;">Never</span>
                                        @endif
                                    </td>

                                    {{-- Time Entries --}}
                                    <td>
                                        <span style="font-size:13px;font-weight:600;color:{{ $user->time_entries_count > 0 ? '#e2e8f0' : '#334155' }};">
                                            {{ number_format($user->time_entries_count) }}
                                        </span>
                                    </td>

                                    {{-- Hire Date --}}
                                    <td class="mono" style="font-size:12px;color:#64748b;">
                                        {{ $user->hire_date ? $user->hire_date->format('d M Y') : '—' }}
                                    </td>

                                    {{-- Member Since --}}
                                    <td class="mono" style="font-size:12px;color:#64748b;">
                                        {{ $user->created_at->format('d M Y') }}
                                    </td>

                                    {{-- Activity link --}}
                                    <td>
                                        <a
                                            href="/dev?tab=activity&causer_id={{ $user->id }}"
                                            style="font-size:11px;color:#6366f1;text-decoration:none;white-space:nowrap;"
                                        >Activity →</a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" style="text-align:center;padding:48px;color:#334155;">No users found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    @if($users->hasPages())
                    <div style="border-top:1px solid #1e2440;padding:12px 16px;display:flex;align-items:center;justify-content:space-between;">
                        <span style="font-size:12px;color:#475569;">
                            @if($users->count())
                            {{ $users->firstItem() }}–{{ $users->lastItem() }} of {{ number_format($users->total()) }}
                            @endif
                        </span>
                        <div style="display:flex;align-items:center;gap:6px;">
                            @if(!$users->onFirstPage())
                            <a href="{{ $users->previousPageUrl() }}" style="padding:5px 14px;font-size:12px;color:#94a3b8;background:#1e2440;border-radius:7px;text-decoration:none;">← Prev</a>
                            @endif
                            <span class="mono" style="font-size:12px;color:#475569;padding:0 8px;">{{ $users->currentPage() }} / {{ $users->lastPage() }}</span>
                            @if($users->hasMorePages())
                            <a href="{{ $users->nextPageUrl() }}" style="padding:5px 14px;font-size:12px;color:#94a3b8;background:#1e2440;border-radius:7px;text-decoration:none;">Next →</a>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>

            </div>
            @endif {{-- /users --}}

        </div>{{-- /scrollable --}}
    </main>

</div>

<script>
// Rotate chevron in open details elements
document.addEventListener('toggle', function(e) {
    if (e.target.tagName === 'DETAILS') {
        const chevron = e.target.querySelector('summary .chevron');
        if (chevron) chevron.style.transform = e.target.open ? 'rotate(180deg)' : '';
    }
}, true);

// Auto-refresh overview every 30s
@if($tab === 'overview')
setTimeout(function() { location.reload(); }, 30000);
@endif
</script>
</body>
</html>
