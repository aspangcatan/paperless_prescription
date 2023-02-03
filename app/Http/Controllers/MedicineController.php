<?php

namespace App\Http\Controllers;

use App\Models\Medicine;

class MedicineController extends Controller
{
    function getMedicine()
    {
        try {
            $medicines = Medicine::take(20)
                ->orderBy('description', 'ASC')
                ->get(['id', 'description', 'unit']);

            return response(['code' => 200, 'results' => $medicines]);
        } catch (\Exception $exception) {
            return response(['code' => 500, 'message' => $exception->getMessage()]);
        }
    }

    function searchMedicine($keyword)
    {
        try {
            $medicines = Medicine::where(function ($q) use ($keyword) {
                $q->orwhere('description', 'like', "%$keyword%");
            })
                ->take(20)
                ->orderBy('description', 'ASC')
                ->get(['id', 'description', 'unit']);

            return response(['code' => 200, 'results' => $medicines]);
        } catch (\Exception $exception) {
            return response(['code' => 500, 'message' => $exception->getMessage()]);
        }
    }
}
