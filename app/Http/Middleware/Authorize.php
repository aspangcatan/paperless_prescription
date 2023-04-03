<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class Authorize
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
            #PRESCRIBER
            case "home":
            case "returned_prescription":
                if (session('access_rights') == "Doctor" || session('access_rights') == "ICC")
                    return $next($request);
                else
                    return redirect('/home');
            case "nurse_review":
                if (session('access_rights') == "Nurse" || session('access_rights') == "Nurse Admin")
                    return $next($request);
                else
                    return redirect('/nurse_review');
            case "pharmacy_review":
                if (session('access_rights') == "Pharmacist" || session('access_rights') == "Pharmacist Admin")
                    return $next($request);
                else
                    return redirect('/pharmacy_review');
            default:
                return $next($request);
        }
    }
}
