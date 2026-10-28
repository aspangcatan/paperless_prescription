<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class Authorize
{
    /**
     * Roles allowed to open each page, matching what the sidebar offers them.
     * Unlisted pages (medicine dictionary, track prescription, disposition) are open to every signed-in user.
     */
    const ALLOWED_ROLES = [
        #PRESCRIBER
        'home' => ['Doctor', 'ICC'],
        'returned_prescription' => ['Doctor', 'ICC'],
        #IPCC
        'icc_review' => ['ICC'],
        'monitored_summary' => ['ICC'],
        'ams_summary' => ['ICC', 'Pharmacist', 'Pharmacist Admin'],
        #NURSE
        'nurse_review' => ['Nurse', 'Nurse Admin'],
        'rendered_prescription' => ['Nurse', 'Nurse Admin'],
        'nurse_admin' => ['Nurse Admin'],
        #PHARMACY
        'pharmacy_review' => ['Pharmacist', 'Pharmacist Admin'],
        'returned_meds' => ['Pharmacist', 'Pharmacist Admin'],
        'issuances' => ['Pharmacist', 'Pharmacist Admin'],
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
            return redirect()->route('login');
        }

        return $next($request);
    }
}
