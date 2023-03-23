<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\Models\PrescribedMedicine;
use Illuminate\Http\Request;

class MedicineController extends Controller
{
    function getMedicine(Request $request)
    {
        try {
            $search_filters = [];
            if (!$request->medicine == "")
                $search_filters[] = ['description', 'like', "%$request->medicine%"];

            $medicines = Medicine::where($search_filters)
                ->take(10)
                ->orderBy('description', 'ASC')
                ->get(['id', 'description', 'unit', 'type', 'status']);

            return response(['code' => 200, 'results' => $medicines]);
        } catch (\Exception $exception) {
            return response(['code' => 500, 'message' => $exception->getMessage()]);
        }
    }

    function getMedicineList(Request $request)
    {
        try {
//            $type = $request->type;
//            $search_filters = [['status', '=', '1'], ['type', '=', $type]];
//            if ($type == 4)
                $search_filters = [['status', '=', '1']];
            if (!$request->medicine == "")
                $search_filters[] = ['description', 'like', "%$request->medicine%"];

            $medicines = Medicine::where($search_filters)
                ->take(10)
                ->orderBy('description', 'ASC')
                ->get(['id', 'description', 'unit', 'type', 'status']);

            return response(['code' => 200, 'results' => $medicines]);
        } catch (\Exception $exception) {
            return response(['code' => 500, 'message' => $exception->getMessage()]);
        }
    }

    function store(Request $request)
    {
        try {
            $message = "Medicine updated";
            $medicine = Medicine::find($request->id);

            $prescribed_drugs = PrescribedMedicine::where('description', $request->description)->get()->first();
            if ($prescribed_drugs) {
                return response(['code' => 500, 'message' => "Medicine is already used in prescription"]);
            }

            if (!$medicine) {
                $message = "New medicine added";
                $medicine = new Medicine;
            }

            $medicine->description = $request->description;
            $medicine->unit = $request->unit;
            $medicine->type = $request->type;
            $medicine->status = $request->status;
            $medicine->save();
            return response(['code' => 200, 'message' => $message]);
        } catch (\Exception $exception) {
            return response(['code' => 500, 'message' => $exception->getMessage()]);
        }
    }
}
