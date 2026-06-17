<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ตรวจสิทธิ์การเข้าถึงต่อเมนู+action
 * ใช้งาน: ->middleware('menu:kpi.indicator,edit')   (action ไม่ระบุ = view)
 */
class EnsureMenuPermission
{
    public function handle(Request $request, Closure $next, string $menuCode, string $action = 'view'): Response
    {
        $user = $request->user();

        if (! $user || ! $user->canMenu($menuCode, $action)) {
            abort(403, 'คุณไม่มีสิทธิ์ใช้งานส่วนนี้');
        }

        return $next($request);
    }
}
