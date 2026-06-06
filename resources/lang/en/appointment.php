<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Appointments Module – English Translations
    |--------------------------------------------------------------------------
    */

    'messages' => [
        'appointments_fetched' => 'Appointments fetched successfully.',
        'appointment_fetched' => 'Appointment fetched successfully.',
        'appointment_created' => 'Appointment created successfully.',
        'appointment_updated' => 'Appointment updated successfully.',
        'appointment_confirmed' => 'Appointment confirmed successfully.',
        'appointment_cancelled' => 'Appointment cancelled successfully.',
        'appointment_checked_in' => 'Appointment checked in successfully.',
        'doctor_assigned' => 'Doctor assigned to appointment successfully.',
        'appointment_started' => 'Appointment examination started successfully.',
        'appointment_completed' => 'Appointment completed successfully.',
        'queue_fetched' => 'Queue list fetched successfully.',
        'today_appointments_fetched' => 'Today\'s appointments fetched successfully.',
        'doctor_today_appointments_fetched' => 'Doctor\'s today appointments fetched successfully.',
    ],

    'errors' => [
        'not_allowed_view' => 'You are not allowed to view appointments.',
        'not_allowed_create' => 'You are not allowed to create appointments.',
        'not_allowed_update' => 'You are not allowed to update appointments.',
        'not_allowed_confirm' => 'You are not allowed to confirm appointments.',
        'not_allowed_cancel' => 'You are not allowed to cancel appointments.',
        'not_allowed_manage_status' => 'You are not allowed to manage appointment status.',
        'not_allowed_assign_doctor' => 'You are not allowed to assign doctors to appointments.',
        'appointment_not_found' => 'Appointment not found.',
        'patient_not_found' => 'Patient not found.',
        'patient_inactive' => 'Patient account is inactive. Cannot create appointment.',
        'patient_archived' => 'Patient file is archived. Cannot create appointment.',
        'patient_deceased' => 'Patient is marked as deceased. Cannot create appointment.',
        'doctor_not_found' => 'Doctor not found.',
        'invalid_status_transition' => 'Invalid status transition for this appointment.',
        'appointment_already_cancelled' => 'Appointment is already cancelled.',
        'appointment_already_completed' => 'Appointment is already completed.',
        'cannot_cancel_appointment' => 'Cannot cancel an appointment that is in progress or already completed.',
        'cannot_update_appointment' => 'Cannot update a cancelled or completed appointment.',
        'cannot_start_without_doctor' => 'Cannot start appointment without a assigned doctor.',
        'cannot_assign_doctor' => 'Cannot assign doctor to a cancelled or completed appointment.',
        'selected_staff_is_not_doctor' => 'The selected staff member is not a doctor.',
    ],

    'validation' => [
        'patient_id_required' => 'Patient ID is required.',
        'patient_id_invalid' => 'Patient ID is invalid or patient does not exist.',
        'doctor_id_required' => 'Doctor ID is required.',
        'doctor_id_invalid' => 'Doctor ID is invalid or doctor does not exist.',
        'appointment_at_required' => 'Appointment date and time is required.',
        'appointment_at_invalid' => 'Appointment date and time must be a valid date.',
        'appointment_at_past' => 'Appointment date and time must be in the future.',
        'type_required' => 'Appointment type is required.',
        'type_invalid' => 'Appointment type is invalid.',
        'reason_max' => 'Reason may not be greater than 1000 characters.',
        'notes_max' => 'Notes may not be greater than 2000 characters.',
        'cancel_reason_required' => 'Cancel reason is required.',
        'cancel_reason_max' => 'Cancel reason may not be greater than 1000 characters.',
        'completion_notes_max' => 'Completion notes may not be greater than 2000 characters.',
        'date_invalid' => 'Date must be a valid date.',
        'date_from_invalid' => 'Date from must be a valid date.',
        'date_to_invalid' => 'Date to must be a valid date.',
        'date_to_before_from' => 'Date to must be after or equal to date from.',
        'status_invalid' => 'Status is invalid.',
        'keyword_max' => 'Keyword may not be greater than 255 characters.',
        'per_page_max' => 'Per page may not be greater than 100.',
    ],
];
