<?php

namespace App\Http\Controllers;

use App\Models\ErrorLog;
use App\Models\HomisPatient;
use App\Models\Patient;
use App\Models\PatientDisposition;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PatientController extends Controller
{
    public function getAuthorization()
    {
        return "02778c432af8fac59c050a297e56f1adde9ac11f1baa6bd965edb4e330de889d4fe4ed1d";
    }

    private function getMedixToken()
    {
        $token = Cache::get('medix_access_token');
        if ($token) {
            return $token;
        }

        // Medix's server presents IIS's auto-generated self-signed default cert
        // (CN WMSvc-SHA2-LENOVO-LA0X1192), not one issued for their domain — a
        // vendor-side misconfiguration, not fixable via a local CA bundle update.
        $response = Http::withOptions(['verify' => false])->asForm()->post(config('services.medix.token_url'), [
            'grant_type' => 'client_credentials',
            'client_id' => config('services.medix.client_id'),
            'client_secret' => config('services.medix.client_secret'),
        ]);

        if (!$response->successful()) {
            return null;
        }

        $access_token = $response->json('accessToken');
        $expires_in = $response->json('expiresIn');
        $ttl = $expires_in ? max($expires_in - 60, 60) : 300;

        Cache::put('medix_access_token', $access_token, $ttl);

        return $access_token;
    }

    public function searchMedixPatient(Request $request)
    {
        try {
            if (empty($request->lastname)) {
                return response(['code' => 500, 'message' => 'Lastname is required']);
            }

            $token = self::getMedixToken();
            if (!$token) {
                return response(['code' => 500, 'message' => 'Unable to authenticate with MEDIX']);
            }

            // See the TLS comment in getMedixToken() above.
            $response = Http::withOptions(['verify' => false])->withToken($token)->get(config('services.medix.base_url') . '/GetPatientInformation', [
                'LastName' => $request->lastname,
            ]);

            if (!$response->successful()) {
                return response(['code' => 500, 'message' => 'MEDIX request failed']);
            }

            return response(['code' => 200, 'results' => $response->json()]);
        } catch (\Exception $exception) {
            return response(['code' => 500, 'message' => $exception->getMessage()]);
        }
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

            $patient = HomisPatient::where(function ($query) use ($firstname, $lastname, $patid) {
                if ($firstname != "") {
                    $query->where('patfirst', 'LIKE', '%' . $firstname . '%');
                }
                if ($lastname != "") {
                    $query->where('patlast', 'LIKE', '%' . $lastname . '%');
                }
                if ($patid != "") {
                    $query->where('hpatcode', 'LIKE', '%' . $patid);
                }
            })
                ->leftJoin('hospital_dbo.hadmlog as ha', 'hpatcode', '=', 'ha.hpercode')
                ->orderBy('ha.admdate', 'DESC')
                ->take(1)
                ->get(['hpatcode', 'patfirst', 'patmiddle', 'patlast', 'patsuffix', 'patbdate', 'patsex', 'ha.admdate', 'natcode', 'relcode', 'patempstat', 'patemaddr', 'pattelno', 'patcstat'])
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

    public function getPatientList(Request $request)
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
                ->take(11)
                ->skip($request->page * 10)
                ->get(['id', 'hospital_id', 'fname', 'mname', 'lname', 'suffix', 'birthdate', 'sex','disposition_status']);

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

    public function setDispositionStatus(Request $request)
    {
        try {
            $patient = Patient::find($request->id);
            if (!$patient) {
                return response()->json(['code' => 500, 'message' => 'Hospital ID dont exists']);
            }

            $patient->disposition_status = $request->disposition_status;
            $patient->save();
            //INSERT DISPOSITION HISTORY
            $patient_disposition = new PatientDisposition;
            $patient_disposition->patient_id = $request->id;
            $patient_disposition->hospital_id = $request->hospital_id;
            $patient_disposition->disposition_status = $request->disposition_status;
            $patient_disposition->disposition_date = $request->disposition_date;
            $patient_disposition->user_id = Auth::id();
            $patient_disposition->created_at = Carbon::now();
            $patient_disposition->save();
            return response(['code' => 200, 'message' => "Disposition status updated"]);
        } catch (\Exception $exception) {
            self::insertErrorLogs(Auth::id(), 'setDispositionStatus', $request, $exception->getMessage());
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
