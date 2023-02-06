<?php

namespace App\Http\Controllers;

use App\Models\UserPrivelege;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function authenticate(Request $request)
    {
        $credentials = [
            'username' => $request->username,
            'password' => $request->password
        ];

        if (Auth::attempt($credentials)) {
            $user_priv = UserPrivelege::where([['user_id', Auth::id()], ['syscode', 'paperless']])->get()->first();
            if ($user_priv == null) {
                Auth::logout();
                return back()->with(['message' => 'Access denied']);
            }

            return redirect()->intended('/home');
        }

        return back()->with(['message' => 'The provided credentials do not match our records.']);
    }

    public function getDoctors()
    {
        try {
            $doctors = DB::table('tdh_user.users as u')
                ->join('tdh_user.user_priv as p', 'u.id', '=', 'p.user_id')
                ->where('p.syscode', '=', 'paperless')
                ->where('p.level', '=', 'doctor')
                ->get(['u.id', 'u.fname', 'u.mname', 'u.lname', 'u.suffix']);
            return response(['code' => 200, 'results' => $doctors]);
        } catch (\Exception $exception) {
            return response(['code' => 500, 'message' => $exception->getMessage()]);
        }
    }

    public function getNurses(){
        try {
            $nurses = DB::table('tdh_user.users as u')
                ->join('tdh_user.user_priv as p', 'u.id', '=', 'p.user_id')
                ->where('p.syscode', '=', 'paperless')
                ->where('p.level', '=', 'nurses')
                ->get(['u.id', 'u.fname', 'u.mname', 'u.lname', 'u.suffix']);
            return response(['code' => 200, 'results' => $nurses]);
        } catch (\Exception $exception) {
            return response(['code' => 500, 'message' => $exception->getMessage()]);
        }
    }
}
