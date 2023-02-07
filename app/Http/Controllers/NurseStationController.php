<?php

namespace App\Http\Controllers;

use App\Models\NurseStation;
use Illuminate\Http\Request;

class NurseStationController extends Controller
{
    public function insertNurseStation(Request $request)
    {
        try {
            $nurse_station = NurseStation::where('userid', $request->id)->first();
            if (!$nurse_station) {
                $nurse_station = new NurseStation;
                $nurse_station->userid = $request->id;
            }
            $nurse_station->stations = $request->stations;
            $nurse_station->save();
            return response(['code' => 200, 'message' => 'Nurse station added']);
        } catch (\Exception $exception) {
            return response(['code' => 500, 'message' => $exception->getMessage()]);
        }
    }
}
