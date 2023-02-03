<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
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
}
