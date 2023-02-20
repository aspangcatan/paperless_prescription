<?php

namespace App\Http\Controllers;

use App\Models\PrescribedMedicine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrescribedMedicineController extends Controller
{
    public function getIssuancesSummary(Request $request)
    {
        $search_filters = [['quantity_rendered', '>', 0]];
        if ($request->filter_description != "") {
            $search_filters[] = ['description', 'LIKE', '%' . $request->filter_description . '%'];
        }
        if ($request->filter_date != "") {
            $search_filters[] = ['date_rendered', 'LIKE', '%' . $request->filter_date . '%'];
        }

        try {
            $issuances = PrescribedMedicine::where($search_filters)
                ->select(DB::raw('description,MAX(unit) as unit,SUM(quantity_rendered) as quantity_rendered,SUM(quantity_returned) as quantity_returned'))
                ->groupBy(['description'])
                ->orderBy('description', 'ASC')
                ->take(10)
                ->get();
            return response(['code' => 200, 'results' => $issuances]);
        } catch (\Exception $exception) {
            return response(['code' => 500, 'message' => $exception->getMessage()]);
        }
    }
}
