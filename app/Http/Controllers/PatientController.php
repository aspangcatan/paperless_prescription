<?php

namespace App\Http\Controllers;

use App\Models\ErrorLog;
use App\Models\HomisPatient;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PatientController extends Controller
{
    public function getAuthorization()
    {
        return "02778c432af8fac59c050a297e56f1adde9ac11f1baa6bd965edb4e330de889d4fe4ed1d";
    }

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

    public function searchApiPatient(Request $request)
    {
        try {
            $header = $request->header('Authorization');
            if (empty($header)) {
                return response(['code' => 500, 'message' => 'Something went wrong.']);
            }

            if ($header != self::getAuthorization()) {
                return response(['code' => 500, 'message' => 'Something went wrong..']);
            }

            $firstname = "";
            $lastname = "";
            $patid = $request->patid;

//            $patient = DB::table('hospital_dbo.hperson as hp')
//                        ->join('hospital_dbo.hadmlog as ha','hp.hpatcode','=','ha.hpercode')
//                        ->where(function ($query) use ($firstname, $lastname, $patid) {
//                            if ($firstname != "") {
//                                $query->where('patfirst', 'LIKE', '%' . $firstname . '%');
//                            }
//                            if ($lastname != "") {
//                                $query->where('patlast', 'LIKE', '%' . $lastname . '%');
//                            }
//                            if ($patid != "") {
//                                $query->where('hpatcode', 'LIKE', '%' . $patid . '%');
//                            }
//                        })
//                ->get(['hp.hpatcode', 'hp.patfirst', 'hp.patmiddle', 'hp.patlast', 'hp.patsuffix', 'hp.patbdate', 'hp.patsex'])
//                ->take(10);

            $patient = HomisPatient::where(function ($query) use ($firstname, $lastname, $patid) {
                if ($firstname != "") {
                    $query->where('patfirst', 'LIKE', '%' . $firstname . '%');
                }
                if ($lastname != "") {
                    $query->where('patlast', 'LIKE', '%' . $lastname . '%');
                }
                if ($patid != "") {
                    $query->where('hpatcode', 'LIKE', '%' . $patid . '%');
                }
            })
                ->join('hospital_dbo.hadmlog as ha', 'hpatcode', '=', 'ha.hpercode')
                ->take(1)
                ->get(['hpatcode', 'patfirst', 'patmiddle', 'patlast', 'patsuffix', 'patbdate', 'patsex','ha.admdate','natcode','relcode','patempstat','patemaddr','pattelno'])
                ->last();

            return response(['code' => 200, 'result' => $patient]);
        } catch (\Exception $exception) {
            return response(['code' => 500, 'message' => 'Ooops something went wrong']);
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


