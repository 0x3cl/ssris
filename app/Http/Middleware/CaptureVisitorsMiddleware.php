<?php

namespace App\Http\Middleware;

use App\Jobs\StoreVisitor;
use Closure;
use Illuminate\Http\Request;

class CaptureVisitorsMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        StoreVisitor::dispatch(
            $request->ip(),
            $request->userAgent()
        );

        return $next($request);
    }
}
