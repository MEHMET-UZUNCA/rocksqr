<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SubdomainRedirect
{
    /**
     * Configured subdomain → path mapping.
     * Keys are Setting keys, values are the target URL paths.
     */
    private const SCREEN_MAP = [
        'subdomain_bar'     => '/bar',
        'subdomain_kitchen' => '/kitchen-pos',
        'subdomain_ana'     => '/kitchen-ana',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost(); // e.g., poolbds.rockshotel.com
        $parts = explode('.', $host);

        // Need at least two parts to have a subdomain
        if (count($parts) < 2) {
            return $next($request);
        }

        $subdomain = strtolower($parts[0]);

        if ($subdomain === '') {
            return $next($request);
        }

        foreach (self::SCREEN_MAP as $settingKey => $targetPath) {
            $alias = strtolower(trim(Setting::get($settingKey, '')));
            if ($alias === '' || $alias !== $subdomain) {
                continue;
            }

            $currentPath = rtrim($request->getPathInfo(), '/') ?: '/';

            // If already on the target screen or one of its sub-paths (API, etc.), pass through
            if ($currentPath === $targetPath || str_starts_with($currentPath, $targetPath . '/')) {
                return $next($request);
            }

            // Redirect root (and any unrecognised path) to the screen
            if ($currentPath === '/') {
                return redirect($targetPath);
            }

            // For all other paths on this subdomain, pass through (API routes, etc.)
            return $next($request);
        }

        return $next($request);
    }
}
