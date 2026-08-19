<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class AuthorizeApi
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        switch (Route::getCurrentRoute()->uri) {
            case "insert_patient":
            case "insert_prescription":
                if (session('access_rights') == "Doctor" || session('access_rights') == "ICC")
                    return $next($request);
                else
                    return response(['code' => 500, 'message' => 'Permission denied']);
            case "approve_prescription":
                if (session('access_rights') == "ICC" || session('access_rights') == "Nurse")
                    return $next($request);
                else
                    return response(['code' => 500, 'message' => 'Permission denied']);
            case "reject_prescription":
                if (session('access_rights') == "ICC" || session('access_rights') == "Nurse" || session('access_rights') == "Pharmacist")
                    return $next($request);
                else
                    return response(['code' => 500, 'message' => 'Permission denied']);
            case "render_prescription":
                if (session('access_rights') == "Pharmacist")
                    return $next($request);
                else
                    return response(['code' => 500, 'message' => 'Permission denied']);
            case "update_prescription":
            case "prescription/delete":
                if (session('access_rights') == "ICC" || session('access_rights') == "Doctor")
                    return $next($request);
                else
                    return response(['code' => 500, 'message' => 'Permission denied']);
            default:
                return $next($request);
        }

    }
}
