# OptiCare Clinic - Claude Code Instructions

You are working inside Laravel project `opticare_clinic`.

Do not modify `opticare_central`.
Do not modify unrelated modules unless required for integration.

Follow the existing module architecture:
- Controllers return ApiResponse.
- Services return arrays/data only, never JsonResponse.
- Repositories extend BaseRepository.
- Requests handle validation.
- Use translations, never hardcode API messages.
- Use Sanctum auth.
- Use the existing RolesPermissions system.
- Follow existing patterns from Authentication, RolesPermissions, Patients, Appointments, and Core.

Important namespaces:
- Patient module: App\Modules\Patients
- Patient model: App\Modules\Patients\Models\ClinicPatient
- Appointments module: App\Modules\Appointments
- Appointment model: App\Modules\Appointments\Models\Appointment
- MedicalRecords module: App\Modules\MedicalRecords

Never use PatientManagement.
Never use clinic_admin.

Patients module is already completed.
Appointments module is already completed.

Current task scope:
Implement only MedicalRecords APIs.

Do not implement:
- PDF generation
- frontend
- payments
- surgeries
- image upload
- image comparison processing
- central patient booking
- unrelated refactoring

Relevant tables already exist:
- medical_records
- visit_records
- vital_signs
- eye_measurements
- prescriptions
- prescription_items
- medical_reports
- medical_report_images
- diagnosis_codes
- visit_diagnosis_codes
- doctor_private_notes
- appointments
- clinic_patients
- imaging_requests
- imaging_files

Before editing:
- Inspect only relevant files.
- Do not read the whole repository.
- Use targeted search.
- Return a short plan first.
- Wait for my approval before implementing.

After implementation, return only:
- files created
- files modified
- routes added
- assumptions
- manual test steps
- possible risks