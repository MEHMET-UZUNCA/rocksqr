<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isAdmin()) {
            return redirect()
                ->route('admin.dashboard')
                ->with('error', 'Bu bölüm için yönetici yetkisi gerekir.');
        }

        return $next($request);
    }
}
