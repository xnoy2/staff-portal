<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HelpController extends Controller
{
    public function guide(Request $request): Response
    {
        $user = $request->user()->load('roles');

        return Inertia::render('Help/Guide', [
            'userRole' => $user->getRoleNames()->first() ?? 'staff',
        ]);
    }
}
