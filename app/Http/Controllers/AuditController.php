<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class AuditController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'manager']), 403);

        $query = Activity::with(['causer:id,name,avatar'])
            ->latest();

        if ($request->filled('causer_id')) {
            $query->where('causer_id', $request->input('causer_id'))
                  ->where('causer_type', User::class);
        }

        if ($request->filled('log_name')) {
            $query->where('log_name', $request->input('log_name'));
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }

        $logs = $query->paginate(50)->withQueryString()
            ->through(fn (Activity $a) => [
                'id'           => $a->id,
                'description'  => $a->description,
                'log_name'     => $a->log_name,
                'subject_type' => $a->subject_type ? class_basename($a->subject_type) : null,
                'subject_id'   => $a->subject_id,
                'causer'       => $a->causer ? [
                    'id'         => $a->causer->id,
                    'name'       => $a->causer->name,
                    'avatar_url' => $a->causer->avatar_url,
                ] : null,
                'properties'   => $a->properties,
                'created_at'   => $a->created_at->toIso8601String(),
            ]);

        $logNames = Activity::distinct()->orderBy('log_name')->pluck('log_name');

        $staffList = User::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Audit/Index', [
            'logs'      => $logs,
            'logNames'  => $logNames,
            'staffList' => $staffList,
            'filters'   => $request->only(['causer_id', 'log_name', 'from', 'to']),
        ]);
    }
}
