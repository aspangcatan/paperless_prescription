<?php

namespace Database\Seeders;

use App\Models\Medicine;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $medicines = [
            [
                'id' => 0,
                'description' => strtoupper('Amikacin 125mg/ml,2ml'),
                'unit' => strtoupper('amp'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('Amoxicillin 500mg'),
                'unit' => strtoupper('cap'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('Amoxicillin 100mg/ml, 15ml'),
                'unit' => strtoupper('bot'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('Amoxicillin 250mg/5ml,60ml'),
                'unit' => strtoupper('bot'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('Amoxicillin trihydrate 500mg'),
                'unit' => strtoupper('cap'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('Amphotericin 50mg/ml, 10ml'),
                'unit' => strtoupper('bot'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('Ampicillin + Sulbactam 750mg'),
                'unit' => strtoupper('vial'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('Ampicillin 1g'),
                'unit' => strtoupper('vial'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('Ampicillin 250mg'),
                'unit' => strtoupper('vial'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('Azithromycin 200mg/5ml, 15ml'),
                'unit' => strtoupper('bot'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('Azithromycin 500mg'),
                'unit' => strtoupper('vial'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('Aztreonam 1g'),
                'unit' => strtoupper('vial'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('Azithromycin dehydrate 500mg'),
                'unit' => strtoupper('tab'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('Benzathine Benzylpenicillin 1M units ( IM)'),
                'unit' => strtoupper('vial'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('Benzathine Benzylpenicillin 1.2Munits ( IM)'),
                'unit' => strtoupper('vial'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('Benzathine Benzylpenicllin 5M units (IM)'),
                'unit' => strtoupper('vial'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('Cefalexin 100mg/5ml 10ml'),
                'unit' => strtoupper('bot'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('Cefalexin 250mg/5ml 60ml'),
                'unit' => strtoupper('bot'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('Cefalexin 500mg'),
                'unit' => strtoupper('cap'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('Cefazolin 1g'),
                'unit' => strtoupper('vial'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('Cefepime 1g'),
                'unit' => strtoupper('vial'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('Cefexime 200mg'),
                'unit' => strtoupper('cap'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('Cefexime 400mg'),
                'unit' => strtoupper('cap'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('Cefixime 100mg/5ml, 60ml'),
                'unit' => strtoupper('bot'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('Cefotaxime 1g'),
                'unit' => strtoupper('vial'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('Cefotaxime 500mg'),
                'unit' => strtoupper('vial'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('Cefoxitin 1g'),
                'unit' => strtoupper('vial'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('ceftazidime 1g'),
                'unit' => strtoupper('vial'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('Ceftriaxone 1g'),
                'unit' => strtoupper('vial'),
                'type' => 2,
                'status' => 1
            ], [
                'id' => 0,
                'description' => strtoupper('Cefuroxime 250mg/5ml, 50ml'),
                'unit' => strtoupper('bot'),
                'type' => 2,
                'status' => 1
            ], [
                'id' => 0,
                'description' => strtoupper('Cefuroxime 500mg'),
                'unit' => strtoupper('tab'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('Cefuroxime 750mg'),
                'unit' => strtoupper('vial'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('Ciprofloxacin 2mg/ml, 100ml'),
                'unit' => strtoupper('vial'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('Ciprofloxacin Hydrochloride 500mg'),
                'unit' => strtoupper('tab'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('Clarithromycin 500mg'),
                'unit' => strtoupper('tab'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('Clarithromycin 125mg /5ml , 50ml'),
                'unit' => strtoupper('bot'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('Clindamycin 150mg/ml,2ml'),
                'unit' => strtoupper('amp'),
                'type' => 2,
                'status' => 1
            ], [
                'id' => 0,
                'description' => strtoupper('Clindamycin 150mg/ml,4ml'),
                'unit' => strtoupper('amp'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('Clindamycin 300mg'),
                'unit' => strtoupper('cap'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('Co-Amoxiclav 400mg/57mg 70ml'),
                'unit' => strtoupper('bot'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('Co-amoxiclav 500mg/125mg'),
                'unit' => strtoupper('tab'),
                'type' => 2,
                'status' => 1
            ], [
                'id' => 0,
                'description' => strtoupper('Cotrimoxazole 240mg/5ml, 70ml'),
                'unit' => strtoupper('bot'),
                'type' => 2,
                'status' => 1
            ], [
                'id' => 0,
                'description' => strtoupper('Cotrimoxazole 800mg/160mg'),
                'unit' => strtoupper('tab'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('Doxycycline 100mg'),
                'unit' => strtoupper('cap'),
                'type' => 2,
                'status' => 1
            ], [
                'id' => 0,
                'description' => strtoupper('Ertapenem 1g'),
                'unit' => strtoupper('vial'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('Erythromycin eye oint 0.5% 5g'),
                'unit' => strtoupper('tube'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('Gentamycin 80mg/ml, 2ml'),
                'unit' => strtoupper('amp'),
                'type' => 2,
                'status' => 1
            ], [
                'id' => 0,
                'description' => strtoupper('Levofloxacin 500mg'),
                'unit' => strtoupper('tab'),
                'type' => 2,
                'status' => 1
            ], [
                'id' => 0,
                'description' => strtoupper('Levofloxacin 500mg/100ml,100ml'),
                'unit' => strtoupper('vial'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('Meropenem 1g'),
                'unit' => strtoupper('vial'),
                'type' => 2,
                'status' => 1
            ], [
                'id' => 0,
                'description' => strtoupper('Metronidazole 125mg/5ml, 60ml'),
                'unit' => strtoupper('bot'),
                'type' => 2,
                'status' => 1
            ], [
                'id' => 0,
                'description' => strtoupper('Metronidazole 500mg'),
                'unit' => strtoupper('tab'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('Metronidazole 5mg/ml,100ml'),
                'unit' => strtoupper('vial'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('Mupirocin 20mg (2%), 5g'),
                'unit' => strtoupper('tube'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('Nystatin 100,000 units ,30ml'),
                'unit' => strtoupper('bot'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('Oxacillin 500mg'),
                'unit' => strtoupper('vial'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('Piperacillin + Tazobactam 4.5g'),
                'unit' => strtoupper('vial'),
                'type' => 2,
                'status' => 1
            ], [
                'id' => 0,
                'description' => strtoupper('Polymyxin B Sulfate 1ml'),
                'unit' => strtoupper('vial'),
                'type' => 2,
                'status' => 1
            ], [
                'id' => 0,
                'description' => strtoupper('Silver Sulfadiazine 10mg/g cream'),
                'unit' => strtoupper('tube'),
                'type' => 2,
                'status' => 1
            ],
            [
                'id' => 0,
                'description' => strtoupper('Tobramycin + Dexamethasone Eye drops'),
                'unit' => strtoupper('bot'),
                'type' => 2,
                'status' => 1
            ], [
                'id' => 0,
                'description' => strtoupper('Vancomycin 1g'),
                'unit' => strtoupper('vial'),
                'type' => 2,
                'status' => 1
            ]
        ];

        Medicine::insert($medicines);
    }
}
