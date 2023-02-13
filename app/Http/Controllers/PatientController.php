<?php

namespace App\Http\Controllers;

use App\Models\ErrorLog;
use App\Models\HomisPatient;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PatientController extends Controller
{
    public function searchHomisPatient($hospital_id)
    {
        try {
            $patient = HomisPatient::where('hpatcode', 'LIKE', '%' . $hospital_id . '%')
                ->get(['hpatcode', 'patfirst', 'patmiddle', 'patlast', 'patsuffix', 'patbdate', 'patsex'])
                ->first();
            return response(['code' => 200, 'result' => $patient]);
        } catch (\Exception $exception) {
            return response(['code' => 500, 'message' => $exception->getMessage()]);
        }
    }

    public function getPatient(Request $request)
    {
        try {
            $search_filters = [];
            if ($request->firstname != "") {
                $search_filters[] = ['fname', 'LIKE', "%" . $request->firstname . "%"];
            }

            if ($request->lastname != "") {
                $search_filters[] = ['lname', 'LIKE', "%" . $request->lastname . "%"];
            }

            $patient = Patient::where($search_filters)
                ->take(10)
                ->get(['id', 'hospital_id', 'fname', 'mname', 'lname', 'suffix', 'birthdate', 'sex']);

            return response(['code' => 200, 'results' => $patient]);
        } catch (\Exception $exception) {
            return response(['code' => 500, 'message' => $exception->getMessage()]);
        }
    }

    public function getPatientDetails($id)
    {
        try {
            $patient = Patient::where('hpercode', $id)->get(['patbdate', 'patsex'])->first();
            return response(['code' => 200, 'result' => $patient]);
        } catch (\Exception $exception) {
            return response(['code' => 500, 'message' => $exception->getMessage()]);
        }
    }

    public function store(Request $request)
    {
        try {
            $message = "Patient information updated";
            $patient = Patient::where('hospital_id', $request->hospital_id)->first();
            if (!$patient) {
                $message = "New patient added";
                $patient = new Patient();
                $patient->hospital_id = $request->hospital_id;
            }

            $patient->fname = $request->fname;
            $patient->mname = $request->mname;
            $patient->lname = $request->lname;
            $patient->suffix = $request->suffix;
            $patient->birthdate = $request->birthdate;
            $patient->sex = $request->sex;
            $patient->save();
            return response(['code' => 200, 'message' => $message]);
        } catch (\Exception $exception) {
            self::insertErrorLogs(Auth::id(), 'insertPatient', $request, $exception->getMessage());
            return response(['code' => 500, 'message' => $exception->getMessage()]);
        }
    }

    public function insertErrorLogs($action_by, $action, $params, $details)
    {
        $log = new ErrorLog;
        $log->action_by = $action_by;
        $log->action = $action;
        $log->params = $params;
        $log->details = $details;
        $log->save();
    }
}


