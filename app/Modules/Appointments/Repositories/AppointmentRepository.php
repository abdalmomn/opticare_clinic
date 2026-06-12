<?php

namespace App\Modules\Appointments\Repositories;

use App\Modules\Core\Repositories\BaseRepository;
use App\Modules\Appointments\Models\Appointment;
use Illuminate\Pagination\LengthAwarePaginator;
class AppointmentRepository extends BaseRepository
{
    public function __construct(Appointment $model)
    {
        parent::__construct($model);
    }

    public function search(array $filters): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->with(['patient', 'doctor']);

        if (! empty($filters['date'])) {
            $date = $filters['date'];
            $query->whereDate('appointment_at', $date);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('appointment_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('appointment_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['status'])) {
            $status = $filters['status'];
            if (is_array($status)) {
                $query->whereIn('status', $status);
            } else {
                $query->where('status', $status);
            }
        }

        if (! empty($filters['type'])) {
            $type = $filters['type'];
            if (is_array($type)) {
                $query->whereIn('type', $type);
            } else {
                $query->where('type', $type);
            }
        }

        if (! empty($filters['patient_id'])) {
            $query->where('patient_id', $filters['patient_id']);
        }

        if (! empty($filters['doctor_id'])) {
            $query->where('doctor_id', $filters['doctor_id']);
        }

        if (! empty($filters['keyword'])) {
            $keyword = trim((string) $filters['keyword']);

            $query->where(function ($q) use ($keyword) {
                $q->whereHas('patient', function ($pq) use ($keyword) {
                    $pq->where('full_name', 'like', "%{$keyword}%")
                        ->orWhere('name', 'like', "%{$keyword}%")
                        ->orWhere('first_name', 'like', "%{$keyword}%")
                        ->orWhere('father_name', 'like', "%{$keyword}%")
                        ->orWhere('last_name', 'like', "%{$keyword}%")
                        ->orWhere('phone', 'like', "%{$keyword}%")
                        ->orWhere('identity_number', 'like', "%{$keyword}%")
                        ->orWhere('national_id', 'like', "%{$keyword}%")
                        ->orWhere('passport_id', 'like', "%{$keyword}%")
                        ->orWhere('medical_file_number', 'like', "%{$keyword}%");
                })->orWhere('reason', 'like', "%{$keyword}%")
                ->orWhere('notes', 'like', "%{$keyword}%");
            });
        }

        $perPage = isset($filters['per_page'])
            ? min(max((int) $filters['per_page'], 1), 100)
            : 15;

        return $query->orderBy('appointment_at', 'desc')->paginate($perPage);
    }

    public function findAppointmentById(int $id): ?Appointment
    {
        return $this->model->newQuery()
            ->with([
                'patient',
                'doctor',
                'createdBy',
                'updatedBy',
                'confirmedBy',
                'cancelledBy',
                'checkedInBy',
                'startedBy',
                'completedBy',
            ])
            ->find($id);
    }

    public function createAppointment(array $data): Appointment
    {
        return $this->model->create($data);
    }

    public function updateAppointment(Appointment $appointment, array $data): Appointment
    {
        $appointment->update($data);

        return $this->findAppointmentById($appointment->id);
    }

    public function nextQueueNumberForDate(string $date): int
    {
        $maxQueue = $this->model->newQuery()
            ->whereDate('appointment_date', $date)
            ->where('status', '!=', Appointment::STATUS_CANCELLED)
            ->max('queue_number');

        return ($maxQueue ?? 0) + 1;
    }

    public function todayAppointments(array $filters = []): LengthAwarePaginator
    {
        $filters['date'] = now()->toDateString();

        return $this->search($filters);
    }

    public function queue(array $filters = []): LengthAwarePaginator
    {
        $date = $filters['date'] ?? now()->toDateString();

        $query = $this->model->newQuery()
            ->with(['patient', 'doctor'])
            ->whereDate('appointment_date', $date)
            ->whereIn('status', [
                Appointment::STATUS_WAITING,
                Appointment::STATUS_IN_PROGRESS,
            ])
            ->whereNotNull('queue_number')
            ->orderBy('queue_number', 'asc');

        $perPage = isset($filters['per_page'])
            ? min(max((int) $filters['per_page'], 1), 100)
            : 100;

        return $query->paginate($perPage);
    }

    public function doctorTodayAppointments(int $doctorId, array $filters = []): LengthAwarePaginator
    {
        $filters['date'] = $filters['date'] ?? now()->toDateString();
        $filters['doctor_id'] = $doctorId;

        $query = $this->model->newQuery()
            ->with(['patient', 'doctor'])
            ->where('doctor_id', $doctorId)
            ->whereDate('appointment_date', $filters['date'])
            ->whereIn('status', [
                Appointment::STATUS_CONFIRMED,
                Appointment::STATUS_WAITING,
                Appointment::STATUS_IN_PROGRESS,
            ]);

        $perPage = isset($filters['per_page'])
            ? min(max((int) $filters['per_page'], 1), 100)
            : 15;

        return $query->orderBy('appointment_at', 'asc')->paginate($perPage);
    }
}
