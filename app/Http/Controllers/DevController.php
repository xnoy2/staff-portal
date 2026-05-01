<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class DevController extends Controller
{
    // ── Auth ──────────────────────────────────────────────────────────────────

    public function showLogin(): View|RedirectResponse
    {
        return session('dev_authenticated')
            ? redirect('/dev')
            : view('dev.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $password = env('DEV_PASSWORD', 'bcfdev2024');

        if ($request->input('password') === $password) {
            $request->session()->put('dev_authenticated', true);
            return redirect('/dev');
        }

        return back()->with('error', 'Incorrect password.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('dev_authenticated');
        return redirect('/dev/login');
    }

    // ── Dashboard ─────────────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $tab = $request->input('tab', 'overview');

        return view('dev.dashboard', [
            'tab'      => $tab,
            'overview' => $tab === 'overview' ? $this->overview()            : null,
            'logs'     => $tab === 'logs'     ? $this->parseLogs($request)   : null,
            'activity' => $tab === 'activity' ? $this->activityLog($request) : null,
            'database' => $tab === 'database' ? $this->databaseStats()       : null,
            'users'    => $tab === 'users'    ? $this->users($request)       : null,
        ]);
    }

    // ── Data ──────────────────────────────────────────────────────────────────

    private function overview(): array
    {
        $logFile      = storage_path('logs/laravel.log');
        $recentErrors = 0;

        if (File::exists($logFile)) {
            $tail         = $this->readTail($logFile, 100 * 1024);
            $recentErrors = (int) preg_match_all('/\]\s+\w+\.ERROR:/i', $tail);
        }

        $diskFree = @disk_free_space(base_path());

        return [
            'php_version'     => PHP_VERSION,
            'laravel_version' => app()->version(),
            'env'             => app()->environment(),
            'debug'           => (bool) config('app.debug'),
            'db_driver'       => config('database.default'),
            'cache_driver'    => config('cache.default'),
            'queue_driver'    => config('queue.default'),
            'timezone'        => config('app.timezone'),
            'user_count'      => User::count(),
            'active_users'    => User::where('is_active', true)->count(),
            'activity_count'  => Activity::count(),
            'recent_errors'   => $recentErrors,
            'log_size'        => File::exists($logFile) ? $this->fmtBytes(filesize($logFile)) : 'N/A',
            'disk_free'       => $diskFree !== false ? $this->fmtBytes((int) $diskFree) : 'N/A',
            'memory_usage'    => $this->fmtBytes(memory_get_usage(true)),
        ];
    }

    private function parseLogs(Request $request): array
    {
        $logFile     = storage_path('logs/laravel.log');
        $filterLevel = $request->input('level', '');
        $search      = $request->input('search', '');
        $entries     = [];

        if (! File::exists($logFile)) {
            return compact('entries', 'filterLevel', 'search');
        }

        $content = $this->readTail($logFile, 512 * 1024);

        preg_match_all(
            '/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+)\.(\w+): (.+?)(?=^\[|\z)/ms',
            $content,
            $matches,
            PREG_SET_ORDER
        );

        foreach (array_reverse($matches) as $m) {
            $lvl = strtolower($m[3]);
            $msg = trim($m[4]);

            if ($filterLevel && $lvl !== $filterLevel) continue;
            if ($search && stripos($msg, $search) === false) continue;

            $entries[] = [
                'datetime' => $m[1],
                'env'      => $m[2],
                'level'    => $lvl,
                'message'  => $msg,
                'short'    => strlen($msg) > 280 ? substr($msg, 0, 280) : null,
            ];

            if (count($entries) >= 200) break;
        }

        return compact('entries', 'filterLevel', 'search');
    }

    private function activityLog(Request $request): \Illuminate\Pagination\LengthAwarePaginator
    {
        return Activity::with('causer')
            ->when($request->filled('event'),     fn ($q) => $q->where('event',     $request->input('event')))
            ->when($request->filled('causer_id'), fn ($q) => $q->where('causer_id', $request->input('causer_id')))
            ->latest()
            ->paginate(50)
            ->withQueryString();
    }

    private function users(Request $request): \Illuminate\Pagination\LengthAwarePaginator
    {
        $sort = in_array($request->input('sort'), ['last_login_at', 'created_at', 'name', 'hire_date'])
            ? $request->input('sort')
            : 'last_login_at';
        $dir = $request->input('dir', 'desc') === 'asc' ? 'asc' : 'desc';

        return User::with('roles')
            ->withCount('timeEntries')
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where(function ($q) use ($request) {
                    $q->where('name',  'like', '%' . $request->input('search') . '%')
                      ->orWhere('email', 'like', '%' . $request->input('search') . '%');
                });
            })
            ->when($request->filled('role'), fn ($q) => $q->role($request->input('role')))
            ->when($request->input('status') === 'active',   fn ($q) => $q->where('is_active', true))
            ->when($request->input('status') === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderByRaw("{$sort} IS NULL ASC")
            ->orderBy($sort, $dir)
            ->paginate(30)
            ->withQueryString();
    }

    private function databaseStats(): array
    {
        try {
            $driver = config('database.default');
            $dbName = DB::connection()->getDatabaseName();
            $tables = [];

            if (in_array($driver, ['mysql', 'mariadb'])) {
                foreach (DB::select('SHOW TABLE STATUS') as $t) {
                    $tables[] = [
                        'name'      => $t->Name,
                        'rows'      => (int) $t->Rows,
                        'size'      => $this->fmtBytes((int) (($t->Data_length ?? 0) + ($t->Index_length ?? 0))),
                        'engine'    => $t->Engine   ?? '—',
                        'collation' => $t->Collation ?? '—',
                    ];
                }
            } else {
                foreach (DB::select("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name") as $t) {
                    $tables[] = [
                        'name'      => $t->name,
                        'rows'      => DB::table($t->name)->count(),
                        'size'      => '—',
                        'engine'    => 'SQLite',
                        'collation' => '—',
                    ];
                }
            }

            usort($tables, fn ($a, $b) => $b['rows'] - $a['rows']);

            return ['tables' => $tables, 'db_name' => $dbName, 'driver' => $driver];
        } catch (\Throwable $e) {
            return [
                'tables'  => [],
                'db_name' => '—',
                'driver'  => config('database.default'),
                'error'   => $e->getMessage(),
            ];
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function readTail(string $path, int $maxBytes): string
    {
        $size = filesize($path);
        $fh   = fopen($path, 'r');

        if ($size > $maxBytes) {
            fseek($fh, $size - $maxBytes);
            fgets($fh); // skip partial line
        }

        $content = stream_get_contents($fh);
        fclose($fh);
        return $content;
    }

    private function fmtBytes(int $bytes): string
    {
        if ($bytes < 1024)           return $bytes . ' B';
        if ($bytes < 1_048_576)      return round($bytes / 1024, 1) . ' KB';
        if ($bytes < 1_073_741_824)  return round($bytes / 1_048_576, 1) . ' MB';
        return round($bytes / 1_073_741_824, 2) . ' GB';
    }
}
