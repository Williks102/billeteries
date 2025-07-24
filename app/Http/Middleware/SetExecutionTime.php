<?php

namespace App\Http\Middleware;

use Closure;

class SetExecutionTime
{
    public function handle($request, Closure $next)
    {
        ini_set('max_execution_time', 120); // 120 secondes
        return $next($request);
    }
}
