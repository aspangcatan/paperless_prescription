<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
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
                ->take(20)
                ->orderBy('description', 'ASC')
                ->get(['id', 'description', 'unit']);

            return response(['code' => 200, 'results' => $medicines]);
        } catch (\Exception $exception) {
            return response(['code' => 500, 'message' => $exception->getMessage()]);
        }
    }
}
