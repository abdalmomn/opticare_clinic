<?php

namespace App\Modules\Appointments\Helpers;

class AppointmentHelper
{

    public static function format($appointment): array
    {
        if (! $appointment) {
            return [];
        }

        return [
            'id' => $appointment->id,
            'patient_id' => $appointment->patient_id,
            'patient' => $appointment->patient ? [
                'id' => $appointment->patient->id,
                'full_name' => $appointment->patient->full_name ?? $appointment->patient->name,
                'medical_file_number' => $appointment->patient->medical_file_number,
                'identity_number' => $appointment->patient->identity_number,
                'national_id' => $appointment->patient->national_id,
                'passport_id' => $appointment->patient->passport_id,
                'phone' => $appointment->patient->phone,
            ] : null,
            'doctor_id' => $appointment->doctor_id,
            'doctor' => $appointment->doctor ? [
                'id' => $appointment->doctor->id,
                'name' => $appointment->doctor->name,
            ] : null,
            'appointment_at' => $appointment->appointment_at,
            'appointment_date' => $appointment->appointment_date,
            'appointment_time' => $appointment->appointment_time,
            'type' => $appointment->type,
            'status' => $appointment->status,
            'queue_number' => $appointment->queue_number,
            'reason' => $appointment->reason,
            'notes' => $appointment->notes,
            'cancel_reason' => $appointment->cancel_reason,
            'completion_notes' => $appointment->completion_notes,
            'confirmed_at' => $appointment->confirmed_at,
            'cancelled_at' => $appointment->cancelled_at,
            'checked_in_at' => $appointment->checked_in_at,
            'started_at' => $appointment->started_at,
            'completed_at' => $appointment->completed_at,
            'created_at' => $appointment->created_at,
            'updated_at' => $appointment->updated_at,
        ];
    }


    public static function formatPaginated($paginator): array
    {
        return [
            'items' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ];
    }
}
