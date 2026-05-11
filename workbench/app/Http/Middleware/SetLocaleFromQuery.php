<?php

namespace Workbench\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocaleFromQuery
{
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->query('locale');
        if (in_array($locale, ['en', 'es'], true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
