# Project Overview

## What The System Does (Business Purpose)
This is a paperless antimicrobial prescription workflow for a hospital. Physicians create antimicrobial prescriptions, which are then reviewed by IPCC/ICC before the pharmacist can dispense, and a nurse completes the final receiving/acknowledgement step. The system enforces antimicrobial stewardship rules (restricted/monitored types), tracks approvals and rejections, and keeps auditable logs of the prescription lifecycle, dispensing, and returns.

## Main Modules / Features
- Authentication and role-based access (Doctor, ICC/IPCC, Nurse, Pharmacist, Admin variants).
- Prescription lifecycle (create, update, approve, reject, delete/end cycle).
- Review queues for Nurse, ICC, Pharmacy, Rendered, Returned.
- Pharmacy dispensing (rendering quantities) and issuance summaries.
- Patient management, including disposition history.
- Medicine master maintenance.
- Antimicrobial stewardship summaries (AMS, monitored summary, concurrent therapy).
- Returned meds tracking and receiving.
- Audit logging (prescription logs) and error logs.
- Real-time notifications/alerts via Firebase (front-end).

## Tech Stack
- Backend: PHP 7.3, Laravel 8 (Eloquent + Query Builder).
- Database: MySQL/MariaDB (multiple schemas/DBs: `paperless_prescription`, `tdh_user`, `homis`, `hospital_dbo`).
- Frontend: Blade templates, Laravel Mix/Webpack, Axios, Toastr, Moment.js, Firebase Realtime Database.

## Folder Architecture (Key Areas)
- `app/Http/Controllers`: Request handling and workflow logic.
- `app/Http/Middleware`: Role-based authorization for pages and APIs.
- `app/Models`: Core domain models (Prescription, Reviews, Patients, Medicines, etc.).
- `routes/web.php`: UI routes + authenticated API endpoints.
- `database/migrations`: Core schema definitions for prescriptions and related entities.
- `resources/views`: Blade pages, JS snippets, and UI partials (modals, layout).

## Important Workflows
- Login and routing: Successful login sets `access_rights` and routes users to role-specific pages.
- Prescription creation: Doctor submits prescription, patient history, meds, and AMS-related data.
- Routing after creation: Depending on prescription type and `for_extension`, it either goes straight to Pharmacy or to ICC review first.
- Audit logging: New prescriptions write to `prescription_logs`.
- ICC approval: Creates concurrent therapy records and forwards to Pharmacy.
- ICC rejection: Returns to Doctor and creates a `returned_prescriptions` record.
- Pharmacy rendering: Pharmacist sets `quantity_rendered` and forwards to Nurse; blocked if any medication quantity is 0.
- Nurse approval: Ends the cycle and records a rendered prescription.
- Returned prescriptions: Doctor updates and resubmits; it re-enters ICC/Pharmacy queues based on type.
- Returned meds: Track returned quantities and pharmacist receiving.

## Database-Related Core Entities
- `prescriptions`: Main order header (patient and clinical data, type, urgency).
- `prescribed_medicines`: Line items + rendered/returned quantities.
- `patient_history`: Snapshot of patient demographics per prescription.
- `patients` + `patient_disposition_history`: Patient master + disposition changes.
- `medicines`: Master list of meds with type/status.
- Review queues: `icc_reviews`, `nurse_reviews`, `pharmacy_reviews`, `rendered_prescriptions`, `returned_prescriptions`.
- Stewardship/clinical data: `concurrent_therapy`, `microbiological_results`, `indication_use`.
- Auditing: `prescription_logs`, `error_logs`.
- Operations: `returned_meds`, `nurse_stations`.

## External Integrations
- `tdh_user` database for users and privileges (`tdh_user.users`, `tdh_user.user_priv`).
- `homis` / `hospital_dbo` databases for patient lookup and admission data.
- Firebase Realtime Database for real-time notifications (front-end JS).

## Known Missing Documentation / Assumptions
- Several models are referenced but not present in `app/Models` or migrations: `CultureSent`, `DysfunctionCriteria`, `FocusInfection`, `Immunocompromising`, `IndicationUseDetails`, `InfectiousDiagnosis`, `SepsisCriteria`. These may exist in another repo, were removed, or are generated elsewhere.
- Prescription type codes are numeric and only inferred from logic: Types `2`, `5`, `8` appear to map to Restricted/Monitored/Non-Monitored; Types `4` and `7` seem related to intervention/switch logic; Type `6` triggers a multi-queue (Nurse + ICC + Pharmacy) flow.
- Some admission/disposition checks are commented out; current enforcement is unclear.
- Cross-database schema expectations are implicit (hard-coded schema names in queries).
- The exact IPCC vs ICC role naming is used interchangeably; access rules should be clarified with stakeholders.
