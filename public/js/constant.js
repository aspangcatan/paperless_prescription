const FREQUENCIES = ["IMMEDIATELY", "1X/DAY", "2X/DAY", "3X/DAY", "4X/DAY", "EVERY HOUR", "EVERY 2 HOURS", "EVERY 2 HOURS AS NEEDED", "EVERY 4 HOURS", "EVERY 4 HOURS AS NEEDED", "EVERY 6 HOURS", "EVERY 6 HOURS AS NEEDED", "EVERY 8 HOURS", "EVERY 8 HOURS AS NEEDED", "EVERY 12 HOURS", "EVERY 12 HOURS AS NEEDED", "EVERY 24 HOURS", "EVERY 24 HOURS AS NEEDED", "EVERY 48 HOURS", "EVERY 48 HOURS AS NEEDED", "EVERY 72 HOURS", "EVERY 72 HOURS AS NEEDED", "EVERY NIGHT AT BEDTIME", "EVERY OTHER DAY", "1X/WEEK", "2X/WEEK", "3X/WEEK", "1X/MONTH", "LOADING DOSE"];
const STRENGTH_UNIT = ["", "g", "mg", "mcg", "units", "liters", "mL", "mEq"];
const INDICATION_USE_ARRAY = ["Prophylactic", "Empiric", "Definitive", "Empiric escalation", "Empiric de-escalation", "Empiric then definitive"];
const FOCUS_INFECTION_ARRAY = ["HENT", "OCULAR", "Skin/Skin structure", "Bones/Joints", "Respiratory", "Cardiovascular", "GI/Intraabdominal", "Genito-urinary", "CNS"];
const CULTURE_SENT_ARRAY = ["Blood", "Respiratory", "Urine", "CSF", "Wound", "Others"];
const IMMUNO_ARRAY = ["None", "Chronic pulmonary", "Chronic liver disease", "Chronic kidney disease", "Diabetes mellitus, poorly controlled",
    "Malignancy", "Immunocompromised state", "Connective tissue disease", "Chronic steroid use", "Others"];
const INFECTIOUS_DIAGNOSIS_ARRAY = ["Deep-seated infections (e.g. abscess, empyema)", "Staphylococcus aureus bacteremia", "Meningitis or encephalitis",
    "Septic arthritis", "Osteomyelitis", "Endocarditis", "Necrotizing soft tissue infections", "Infected implants or prosthesis", "Febrile neutropenia", "Others"];
const SEPSIS_CRITERIA_ARRAY = ["HR >90/min", "RR >20min or pCO2 <32 MMG", "Temperature ≥38°C or ≤36°C", "WBC >12,000 or <4,000 or >10% immature bands", "N/A"];
const DYSFUNCTION_CRITERIA_ARRAY = ["SBP <90 or droppped >40 mmHg from baseline", "New onset jaundice", "Platelet <100,000/uL or INR >1.5 not on anticoagulation",
    "Upper or Lower GI bleeding", "Respiratory failure requiring ventilation; hypoxemiaL saO2 <90% air", "Urine Output <0.5 mL/kg/hr for > 6hr or azotemia (creatinine 2x baseline or >1.3 mg/dL)", "N/A"];
const STATIONS = ["INTERMEDIATE CARE UNIT", "LABOR ROOM", "LABOR ROOM NEWBORN", "MEDICAL WARD (STATION 1)", "MEDICAL WARD (STATION 2)",
    "NEWBORN WARD", "NICU", "OB HIGH RISK", "OB LOW RISK", "PEDIA WARD", "PICU", "SURGICAL WARD"];
