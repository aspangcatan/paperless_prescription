<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class AuthorizeApi
{
    /**
     * Roles allowed to call each write endpoint. Controllers also derive the workflow stage from
     * the role, so a permitted user still can't act as another stage. Unlisted routes are read-only.
     */
    const ALLOWED_ROLES = [
        'insert_patient' => ['Doctor', 'ICC'],
        'insert_prescription' => ['Doctor', 'ICC'],
        'update_prescription' => ['Doctor', 'ICC'],
        'prescription/delete' => ['Doctor', 'ICC'],
        'icc/decide_prescription' => ['ICC'],
        'approve_prescription' => ['Nurse', 'Nurse Admin'],
        'reject_prescription' => ['ICC', 'Nurse', 'Nurse Admin', 'Pharmacist', 'Pharmacist Admin'],
        'render_prescription' => ['Pharmacist', 'Pharmacist Admin'],
        'return_meds' => ['Nurse', 'Nurse Admin'],
        'accept_meds' => ['Pharmacist', 'Pharmacist Admin'],
        'insert_medicine' => ['Pharmacist Admin'],
        'update_nurse_station' => ['Nurse Admin'],
    ];

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $roles = self::ALLOWED_ROLES[Route::getCurrentRoute()->uri] ?? null;
        if ($roles !== null && !in_array(session('access_rights'), $roles, true)) {
            return response(['code' => 500, 'message' => 'Permission denied']);
        }

        return $next($request);
    }
}
