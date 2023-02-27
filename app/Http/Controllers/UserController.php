<?php

namespace App\Http\Controllers;

use App\Models\NurseStation;
use App\Models\UserPrivelege;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function authenticate(Request $request)
    {
        $credentials = ['username' => $request->username, 'password' => $request->password];

        if (Auth::attempt($credentials)) {
            $user_priv = UserPrivelege::where([['user_id', Auth::id()], ['syscode', 'paperless']])->get()->first();
            if ($user_priv == null) {
                Auth::logout();
                return back()->with(['message' => 'Access denied']);
            }

            $nurse_station = NurseStation::where('userid', Auth::id())->get(['stations'])->first();
            if (!$nurse_station) {
                session(['station' => ""]);
            } else {
                session(['station' => $nurse_station->stations]);
            }

            session(['access_rights' => $user_priv->level]);
            switch ($user_priv->level) {
                case "Doctor":
                    return redirect()->intended('/home');
                case "ICC":
                    return redirect()->intended('/icc_review');
                case "Nurse":
                case "Nurse Admin":
                    return redirect()->intended('/nurse_review');
                case "Pharmacist":
                case "Pharmacist Admin":
                    return redirect()->intended('/pharmacy_review');
            }
        }
        return back()->with(['message' => 'The provided credentials do not match our records.']);
    }

    public function getDoctors()
    {
        try {
            $doctors = DB::table('tdh_user.users as u')
                ->join('tdh_user.user_priv as p', 'u.id', '=', 'p.user_id')
                ->where('p.syscode', '=', 'paperless')
                ->where('p.level', '=', 'Doctor')
                ->get(['u.id', 'u.fname', 'u.mname', 'u.lname', 'u.suffix']);
            return response(['code' => 200, 'results' => $doctors]);
        } catch (\Exception $exception) {
            return response(['code' => 500, 'message' => $exception->getMessage()]);
        }
    }

    public function getNurses(Request $request)
    {
        try {
            $nurses = DB::table('tdh_user.users as u')
                ->join('tdh_user.user_priv as p', 'u.id', '=', 'p.user_id')
                ->leftJoin('paperless_prescription.nurse_stations as n', 'u.id', '=', 'n.userid')
                ->where('p.syscode', '=', 'paperless')
                ->where('p.level', '=', 'Nurse')
                ->get(['u.id', 'u.fname', 'u.mname', 'u.lname', 'u.suffix', 'n.stations']);
            return response(['code' => 200, 'results' => $nurses]);
        } catch (\Exception $exception) {
            return response(['code' => 500, 'message' => $exception->getMessage()]);
        }
    }
}
