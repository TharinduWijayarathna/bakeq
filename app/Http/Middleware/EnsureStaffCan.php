<?php

namespace App\Http\Middleware;

use App\Support\StaffPermissions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffCan
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        abort_unless(StaffPermissions::allows($request->user(), $ability), 403);

        return $next($request);
    }
}
