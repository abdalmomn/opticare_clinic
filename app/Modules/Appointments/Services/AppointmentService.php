<?php

namespace App\Modules\Appointments\Services;

use App\Modules\Authentication\Models\Staff;
use App\Modules\Appointments\Models\Appointment;
use App\Modules\Appointments\Repositories\AppointmentRepository;
use App\Modules\Patients\Repositories\ClinicPatientRepository;
use App\Modules\RolesPermissions\Constants\PermissionList;
use App\Modules\RolesPermissions\Helpers\AccessControlHelper;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\RolesPermissions\Enums\RoleEnum;

class AppointmentService
{
    public function __construct(
        protected AppointmentRepository $repository,
        protected ClinicPatientRepository $patientRepository
    ) {}

    /**
     * Check if staff has permission
     */
    private function authorize(Staff $actor, string $permission, string $messageKey): void
    {
        if (! AccessControlHelper::staffHasPermission($actor, $permission)) {
            throw new HttpException(
                Response::HTTP_FORBIDDEN,
                __($messageKey)
            );
        }
    }

    /**
     * List appointments with filters
     */
    public function listAppointments(array $filters, Staff $actor): array
    {
        $this->authorize($actor, PermissionList::VIEW_APPOINTMENTS, 'appointment.errors.not_allowed_view');

        $paginator = $this->repository->search($filters);

        return $this->formatPaginated($paginator);
    }

    /**
     * Get today's appointments
     */
    public function todayAppointments(array $filters, Staff $actor): array
    {
        $this->authorize($actor, PermissionList::VIEW_APPOINTMENTS, 'appointment.errors.not_allowed_view');

        $paginator = $this->repository->todayAppointments($filters);

        return $this->formatPaginated($paginator);
    }

    /**
     * Get queue appointments
     */
    public function queue(array $filters, Staff $actor): array
    {
        $this->authorize($actor, PermissionList::VIEW_APPOINTMENTS, 'appointment.errors.not_allowed_view');

        $paginator = $this->repository->queue($filters);

        return $this->formatPaginated($paginator);
    }

    /**
     * Get doctor's today appointments
     */
    public function doctorTodayAppointments(Staff $actor, array $filters = []): array
    {
        $this->authorize($actor, PermissionList::VIEW_APPOINTMENTS, 'appointment.errors.not_allowed_view');

        $paginator = $this->repository->doctorTodayAppointments($actor->id, $filters);

        return $this->formatPaginated($paginator);
    }

    /**
     * Show single appointment
     */
    public function showAppointment(int $id, Staff $actor): array
    {
        $this->authorize($actor, PermissionList::VIEW_APPOINTMENTS, 'appointment.errors.not_allowed_view');

        $appointment = $this->repository->findAppointmentById($id);

        if (! $appointment) {
            throw new HttpException(
                Response::HTTP_NOT_FOUND,
                __('appointment.errors.appointment_not_found')
            );
        }

        return ['appointment' => $appointment];
    }

    /**
     * Create appointment
     */
    public function createAppointment(array $data, Staff $actor): array
    {
        $this->authorize($actor, PermissionList::CREATE_APPOINTMENT, 'appointment.errors.not_allowed_create');

        // Check patient exists
        $patient = $this->patientRepository->findPatientById($data['patient_id']);

        if (! $patient) {
            throw new HttpException(
                Response::HTTP_NOT_FOUND,
                __('appointment.errors.patient_not_found')
            );
        }

        // Check patient is not archived or deceased
        if ($patient->status === 'deceased') {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                __('appointment.errors.patient_deceased')
            );
        }

        if ($patient->status === 'archived') {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                __('appointment.errors.patient_archived')
            );
        }

        if (! $patient->is_active) {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                __('appointment.errors.patient_inactive')
            );
        }

        if (! $patient->is_active) {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                __('appointment.errors.patient_inactive')
            );
        }

        // If doctor_id provided, validate doctor exists
        if (! empty($data['doctor_id'])) {
            $doctor = Staff::find($data['doctor_id']);

            if (! $doctor->hasRole(RoleEnum::DOCTOR->value, 'api')) {
                throw new HttpException(
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                    __('appointment.errors.selected_staff_is_not_doctor')
                );
            }

            if (! $doctor) {
                throw new HttpException(
                    Response::HTTP_NOT_FOUND,
                    __('appointment.errors.doctor_not_found')
                );
            }
        }
        $appointmentAt = \Carbon\Carbon::parse($data['appointment_at']);

        $payload = [
            'patient_id' => $data['patient_id'],
            'doctor_id' => $data['doctor_id'] ?? null,
            'appointment_at' => $appointmentAt,
            'appointment_date' => $appointmentAt->toDateString(),
            'appointment_time' => $appointmentAt->format('H:i:s'),
            'type' => $data['type'] ?? Appointment::TYPE_CONSULTATION,
            'status' => Appointment::STATUS_BOOKED,
            'reason' => $data['reason'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $actor->id,
        ];

        $appointment = $this->repository->createAppointment($payload);

        return ['appointment' => $appointment];
    }

    /**
     * Update appointment
     */
    public function updateAppointment(int $id, array $data, Staff $actor): array
    {
        $this->authorize($actor, PermissionList::CREATE_APPOINTMENT, 'appointment.errors.not_allowed_update');

        $appointment = $this->repository->findAppointmentById($id);

        if (! $appointment) {
            throw new HttpException(
                Response::HTTP_NOT_FOUND,
                __('appointment.errors.appointment_not_found')
            );
        }

        // Cannot update cancelled or completed appointments
        if (in_array($appointment->status, [Appointment::STATUS_CANCELLED, Appointment::STATUS_COMPLETED])) {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                __('appointment.errors.cannot_update_appointment')
            );
        }

        $payload = [
            'updated_by' => $actor->id,
        ];

        if (isset($data['doctor_id'])) {
            if ($data['doctor_id'] !== null) {
                $doctor = Staff::find($data['doctor_id']);

                if (! $doctor->hasRole(RoleEnum::DOCTOR->value, 'api')) {
                    throw new HttpException(
                        Response::HTTP_UNPROCESSABLE_ENTITY,
                        __('appointment.errors.selected_staff_is_not_doctor')
                    );
                }

                if (! $doctor) {
                    throw new HttpException(
                        Response::HTTP_NOT_FOUND,
                        __('appointment.errors.doctor_not_found')
                    );
                }
            }

            $payload['doctor_id'] = $data['doctor_id'];
        }

        if (isset($data['appointment_at'])) {
            $appointmentAt = \Carbon\Carbon::parse($data['appointment_at']);

            $payload['appointment_at'] = $appointmentAt;
            $payload['appointment_date'] = $appointmentAt->toDateString();
            $payload['appointment_time'] = $appointmentAt->format('H:i:s');
        }

        if (isset($data['type'])) {
            $payload['type'] = $data['type'];
        }

        if (isset($data['reason'])) {
            $payload['reason'] = $data['reason'];
        }

        if (isset($data['notes'])) {
            $payload['notes'] = $data['notes'];
        }

        $appointment = $this->repository->updateAppointment($appointment, $payload);

        return ['appointment' => $appointment];
    }

    /**
     * Confirm appointment
     */
    public function confirmAppointment(int $id, Staff $actor): array
    {
        $this->authorize($actor, PermissionList::CONFIRM_APPOINTMENT, 'appointment.errors.not_allowed_confirm');

        $appointment = $this->repository->findAppointmentById($id);

        if (! $appointment) {
            throw new HttpException(
                Response::HTTP_NOT_FOUND,
                __('appointment.errors.appointment_not_found')
            );
        }

        // Can only confirm from booked status
        if ($appointment->status !== Appointment::STATUS_BOOKED) {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                __('appointment.errors.invalid_status_transition')
            );
        }

        $payload = [
            'status' => Appointment::STATUS_CONFIRMED,
            'confirmed_at' => now(),
            'confirmed_by' => $actor->id,
            'updated_by' => $actor->id,
        ];

        $appointment = $this->repository->updateAppointment($appointment, $payload);

        return ['appointment' => $appointment];
    }

    /**
     * Cancel appointment
     */
    public function cancelAppointment(int $id, array $data, Staff $actor): array
    {
        $this->authorize($actor, PermissionList::CANCEL_APPOINTMENT, 'appointment.errors.not_allowed_cancel');

        $appointment = $this->repository->findAppointmentById($id);

        if (! $appointment) {
            throw new HttpException(
                Response::HTTP_NOT_FOUND,
                __('appointment.errors.appointment_not_found')
            );
        }

        // Can only cancel from booked, confirmed, or waiting
        $allowedStatuses = [
            Appointment::STATUS_BOOKED,
            Appointment::STATUS_CONFIRMED,
            Appointment::STATUS_WAITING,
        ];

        if (! in_array($appointment->status, $allowedStatuses)) {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                __('appointment.errors.cannot_cancel_appointment')
            );
        }

        $payload = [
            'status' => Appointment::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancelled_by' => $actor->id,
            'cancel_reason' => $data['cancel_reason'] ?? null,
            'updated_by' => $actor->id,
        ];

        $appointment = $this->repository->updateAppointment($appointment, $payload);

        return ['appointment' => $appointment];
    }

    /**
     * Check-in appointment
     */
    public function checkInAppointment(int $id, array $data, Staff $actor): array
    {
        $this->authorize($actor, PermissionList::MANAGE_PATIENT_STATUS, 'appointment.errors.not_allowed_manage_status');

        $appointment = $this->repository->findAppointmentById($id);

        if (! $appointment) {
            throw new HttpException(
                Response::HTTP_NOT_FOUND,
                __('appointment.errors.appointment_not_found')
            );
        }

        // Can only check-in from booked or confirmed
        $allowedStatuses = [
            Appointment::STATUS_BOOKED,
            Appointment::STATUS_CONFIRMED,
        ];

        if (! in_array($appointment->status, $allowedStatuses)) {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                __('appointment.errors.invalid_status_transition')
            );
        }

        // Generate queue number
        $queueNumber = $this->repository->nextQueueNumberForDate($appointment->appointment_date);

        $payload = [
            'status' => Appointment::STATUS_WAITING,
            'queue_number' => $queueNumber,
            'checked_in_at' => now(),
            'checked_in_by' => $actor->id,
            'updated_by' => $actor->id,
        ];

        if (isset($data['notes'])) {
            $payload['notes'] = $data['notes'];
        }

        $appointment = $this->repository->updateAppointment($appointment, $payload);

        return ['appointment' => $appointment];
    }

    /**
     * Assign doctor to appointment
     */
    public function assignDoctor(int $id, array $data, Staff $actor): array
    {
        $this->authorize($actor, PermissionList::ASSIGN_PATIENT_TO_DOCTOR, 'appointment.errors.not_allowed_assign_doctor');

        $appointment = $this->repository->findAppointmentById($id);

        if (! $appointment) {
            throw new HttpException(
                Response::HTTP_NOT_FOUND,
                __('appointment.errors.appointment_not_found')
            );
        }

        // Cannot assign to cancelled or completed
        if (in_array($appointment->status, [Appointment::STATUS_CANCELLED, Appointment::STATUS_COMPLETED])) {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                __('appointment.errors.cannot_assign_doctor')
            );
        }

        $doctor = Staff::find($data['doctor_id']);

        if (! $doctor->hasRole(RoleEnum::DOCTOR->value, 'api')) {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                __('appointment.errors.selected_staff_is_not_doctor')
            );
        }

        if (! $doctor) {
            throw new HttpException(
                Response::HTTP_NOT_FOUND,
                __('appointment.errors.doctor_not_found')
            );
        }

        $payload = [
            'doctor_id' => $data['doctor_id'],
            'updated_by' => $actor->id,
        ];

        $appointment = $this->repository->updateAppointment($appointment, $payload);

        return ['appointment' => $appointment];
    }

    /**
     * Start appointment examination
     */
    public function startAppointment(int $id, Staff $actor): array
    {
        $this->authorize($actor, PermissionList::MANAGE_PATIENT_STATUS, 'appointment.errors.not_allowed_manage_status');

        $appointment = $this->repository->findAppointmentById($id);

        if (! $appointment) {
            throw new HttpException(
                Response::HTTP_NOT_FOUND,
                __('appointment.errors.appointment_not_found')
            );
        }

        // Can only start from waiting
        if ($appointment->status !== Appointment::STATUS_WAITING) {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                __('appointment.errors.invalid_status_transition')
            );
        }

        // Must have doctor assigned
        if (! $appointment->doctor_id) {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                __('appointment.errors.cannot_start_without_doctor')
            );
        }

        $payload = [
            'status' => Appointment::STATUS_IN_PROGRESS,
            'started_at' => now(),
            'started_by' => $actor->id,
            'updated_by' => $actor->id,
        ];

        $appointment = $this->repository->updateAppointment($appointment, $payload);

        return ['appointment' => $appointment];
    }

    /**
     * Complete appointment
     */
    public function completeAppointment(int $id, array $data, Staff $actor): array
    {
        $this->authorize($actor, PermissionList::MANAGE_PATIENT_STATUS, 'appointment.errors.not_allowed_manage_status');

        $appointment = $this->repository->findAppointmentById($id);

        if (! $appointment) {
            throw new HttpException(
                Response::HTTP_NOT_FOUND,
                __('appointment.errors.appointment_not_found')
            );
        }

        // Can only complete from in_progress
        if ($appointment->status !== Appointment::STATUS_IN_PROGRESS) {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                __('appointment.errors.invalid_status_transition')
            );
        }

        $payload = [
            'status' => Appointment::STATUS_COMPLETED,
            'completed_at' => now(),
            'completed_by' => $actor->id,
            'updated_by' => $actor->id,
        ];

        if (isset($data['completion_notes'])) {
            $payload['completion_notes'] = $data['completion_notes'];
        }

        $appointment = $this->repository->updateAppointment($appointment, $payload);

        return ['appointment' => $appointment];
    }

    /**
     * Format paginated response
     */
    private function formatPaginated($paginator): array
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
