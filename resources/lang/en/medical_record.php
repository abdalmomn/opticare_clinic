<?php

return [

    /*
    |--------------------------------------------------------------------------
    | MedicalRecords Module – English Translations
    |--------------------------------------------------------------------------
    */

    'messages' => [
        'unified_record_fetched' => 'Patient unified record fetched successfully.',
        'visit_session_fetched' => 'Visit session fetched successfully.',
        'visit_session_created' => 'Visit session created successfully.',
        'visit_session_saved' => 'Visit session saved successfully.',
        'visit_finalized' => 'Visit session finalized successfully.',
        'visits_timeline_fetched' => 'Visits timeline fetched successfully.',
        'reports_timeline_fetched' => 'Reports timeline fetched successfully.',
        'prescriptions_timeline_fetched' => 'Prescriptions timeline fetched successfully.',
        'measurements_timeline_fetched' => 'Measurements timeline fetched successfully.',
        'diagnoses_timeline_fetched' => 'Diagnoses timeline fetched successfully.',
        'private_notes_timeline_fetched' => 'Private notes timeline fetched successfully.',
        'private_note_fetched' => 'Private note fetched successfully.',
        'diagnosis_codes_fetched' => 'Diagnosis codes fetched successfully.',
        'image_folders_fetched' => 'Image folders fetched successfully.',
        'folder_files_fetched' => 'Image folder files fetched successfully.',
        'image_comparison_fetched' => 'Image comparison fetched successfully.',
        'image_types_fetched' => 'Image types fetched successfully.',
        'imaging_file_fetched' => 'Image fetched successfully.',
        'image_note_saved' => 'Image note saved successfully.',
        'report_images_attached' => 'Report images attached successfully.',
    ],

    'errors' => [
        'not_allowed_view_record' => 'You are not allowed to view this medical record.',
        'not_allowed_view_timeline' => 'You are not allowed to view this timeline.',
        'not_allowed_create_visit' => 'You are not allowed to create or open a visit session.',
        'not_allowed_save_session' => 'You are not allowed to save this visit session.',
        'not_allowed_finalize' => 'You are not allowed to finalize this visit session.',
        'not_allowed_create_measurement' => 'You are not allowed to record eye measurements.',
        'not_allowed_create_report' => 'You are not allowed to create medical reports.',
        'not_allowed_create_prescription' => 'You are not allowed to create prescriptions.',
        'not_allowed_create_diagnosis' => 'You are not allowed to create diagnoses.',
        'not_allowed_add_disease_classification' => 'You are not allowed to add disease classification codes.',
        'not_allowed_create_note' => 'You are not allowed to create private notes.',
        'not_allowed_view_diagnosis_codes' => 'You are not allowed to view diagnosis codes.',
        'not_allowed_view_notes' => 'You are not allowed to view private notes.',
        'private_note_not_found' => 'Private note not found.',

        'patient_not_found' => 'Patient not found.',
        'appointment_not_found' => 'Appointment not found.',
        'visit_not_found' => 'Visit session not found.',

        'visit_already_finalized' => 'This visit session is already finalized and can no longer be edited.',
        'visit_cancelled' => 'This visit session is cancelled and can no longer be edited.',

        'appointment_not_in_progress' => 'A visit session can only be opened for an in-progress appointment.',
        'appointment_no_doctor' => 'The appointment has no assigned doctor.',
        'appointment_doctor_mismatch' => 'You are not the doctor assigned to this appointment.',
        'visit_doctor_mismatch' => 'You are not the doctor who owns this visit session.',
        'invalid_diagnosis_codes' => 'One or more diagnosis codes are invalid or inactive.',
        'invalid_imaging_reference' => 'One or more imaging references are invalid.',

        'not_allowed_view_image_folders' => 'You are not allowed to view image folders.',
        'not_allowed_compare_images' => 'You are not allowed to compare images.',
        'not_allowed_save_image_note' => 'You are not allowed to save image notes.',
        'not_allowed_attach_images' => 'You are not allowed to attach images to reports.',
        'folder_not_found' => 'Image folder not found.',
        'folder_not_for_patient' => 'The image folder does not belong to this patient.',
        'folder_missing_type' => 'The folder does not contain images of the requested type.',
        'file_not_found' => 'Image not found.',
        'file_not_for_patient' => 'The image does not belong to this patient.',
        'files_type_mismatch' => 'The two images must be of the same image type.',
        'report_not_found' => 'Medical report not found.',
        'image_not_for_patient' => 'One or more selected images do not belong to the report patient.',
    ],

    'validation' => [
        'visit_type_invalid' => 'The visit type is invalid.',
        'chief_complaint_max' => 'Chief complaint may not be greater than 2000 characters.',
        'symptoms_max' => 'Symptoms may not be greater than 2000 characters.',
        'examination_notes_max' => 'Examination notes may not be greater than 5000 characters.',
        'diagnosis_max' => 'Diagnosis may not be greater than 5000 characters.',
        'treatment_plan_max' => 'Treatment plan may not be greater than 5000 characters.',
        'notes_max' => 'Notes may not be greater than 5000 characters.',

        'measured_at_invalid' => 'Measured at must be a valid date.',
        'iop_invalid' => 'Intraocular pressure must be a valid number.',
        'visual_acuity_max' => 'Visual acuity may not be greater than 50 characters.',

        'report_title_max' => 'Report title may not be greater than 255 characters.',
        'report_text_max' => 'Report text may not be greater than 20000 characters.',
        'imaging_request_invalid' => 'Imaging request is invalid or does not exist.',
        'imaging_file_invalid' => 'Imaging file is invalid or does not exist.',

        'prescription_text_max' => 'Prescription text may not be greater than 20000 characters.',
        'medicine_name_required' => 'Medicine name is required for each prescription item.',
        'medicine_name_max' => 'Medicine name may not be greater than 255 characters.',

        'diagnosis_codes_array' => 'Diagnosis codes must be a list of identifiers.',
        'diagnosis_code_invalid' => 'A selected diagnosis code is invalid or does not exist.',

        'note_max' => 'Private note may not be greater than 5000 characters.',
        'visibility_invalid' => 'The note visibility value is invalid.',

        'search_max' => 'Search keyword may not be greater than 255 characters.',
        'per_page_max' => 'Per page may not be greater than 100.',
        'date_invalid' => 'Date must be a valid date.',
        'status_invalid' => 'The status value is invalid.',
        'left_folder_required' => 'The left folder is required.',
        'right_folder_required' => 'The right folder is required.',
        'folders_must_differ' => 'The two folders to compare must be different.',
        'folder_invalid' => 'The selected image folder is invalid or does not exist.',
        'eye_invalid' => 'The eye value must be OD, OS or OU.',
        'image_type_required' => 'The image type is required.',
        'left_file_required' => 'The left image is required.',
        'right_file_required' => 'The right image is required.',
        'files_must_differ' => 'The two images to compare must be different.',
        'file_invalid' => 'The selected image is invalid or does not exist.',
        'visit_invalid' => 'The selected visit is invalid or does not exist.',
        'mode_invalid' => 'The mode must be either append or replace.',
        'attach_images_required' => 'Provide at least one image or folder to attach.',
    ],
];
