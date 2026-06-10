<?php

namespace App\Modules\MedicalRecords\Repositories;

use App\Modules\Core\Repositories\BaseRepository;
use App\Modules\MedicalRecords\Models\VisitRecord;
use Illuminate\Pagination\LengthAwarePaginator;

class VisitRecordRepository extends BaseRepository
{
    public function __construct(VisitRecord $model)
    {
        parent::__construct($model);
    }

    private function sessionRelations(?int $doctorId = null): array
    {
        return [
            'patient:id,medical_file_number,full_name,gender,birth_date',
            'doctor:id,name',
            'appointment:id,status,appointment_type,type,appointment_at,reason',
            'latestEyeMeasurement' => function ($query) {
                $query->select([
                    'eye_measurements.id',
                    'eye_measurements.visit_record_id',
                    'eye_measurements.measured_at',
                    'eye_measurements.visual_acuity_od',
                    'eye_measurements.visual_acuity_os',
                    'eye_measurements.iop_od',
                    'eye_measurements.iop_os',
                    'eye_measurements.notes',
                ]);
            },
            'latestMedicalReport' => function ($query) {
                $query
                    ->select([
                        'medical_reports.id',
                        'medical_reports.visit_record_id',
                        'medical_reports.title',
                        'medical_reports.report_text',
                        'medical_reports.status',
                    ])
                    ->with('images:id,medical_report_id,imaging_request_id,imaging_file_id,notes');
            },
            'latestPrescription' => function ($query) {
                $query
                    ->select([
                        'prescriptions.id',
                        'prescriptions.visit_record_id',
                        'prescriptions.prescription_text',
                        'prescriptions.status',
                        'prescriptions.notes',
                    ])
                    ->with('items:id,prescription_id,medicine_name,dosage,frequency,duration');
            },
            'diagnosisCodeLinks' => function ($query) {
                $query
                    ->select(['id', 'visit_record_id', 'diagnosis_code_id'])
                    ->with('diagnosisCode:id,code,name_en,name_ar');
            },
            'privateNotes' => function ($q) use ($doctorId) {
                $q->select(['id', 'visit_record_id', 'doctor_id', 'note', 'visibility']);

                if ($doctorId !== null) {
                    $q->where('doctor_id', $doctorId);
                }
            },
        ];
    }

    public function findSession(int $id, ?int $doctorId = null): ?VisitRecord
    {
        return $this->model->newQuery()
            ->select([
                'id',
                'patient_id',
                'appointment_id',
                'doctor_id',
                'status',
                'visit_type',
                'visit_at',
                'chief_complaint',
                'symptoms',
                'examination_notes',
                'diagnosis',
                'treatment_plan',
                'notes',
                'finalized_at',
            ])
            ->with($this->sessionRelations($doctorId))
            ->find($id);
    }

    public function findByAppointmentId(int $appointmentId): ?VisitRecord
    {
        return $this->model->newQuery()
            ->where('appointment_id', $appointmentId)
            ->first();
    }

    public function createVisit(array $data): VisitRecord
    {
        return $this->model->newQuery()->create($data);
    }

    public function updateVisit(VisitRecord $visit, array $data): VisitRecord
    {
        $visit->fill($data);
        $visit->save();

        return $visit->refresh();
    }

    public function visitsTimeline(int $patientId, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->select([
                'id',
                'appointment_id',
                'doctor_id',
                'status',
                'visit_type',
                'visit_at',
                'created_at',
            ])
            ->with([
                'doctor:id,name',
                'appointment:id,appointment_type,type,status',
            ])
            ->where('patient_id', $patientId);

        $this->applyDateRange($query, $filters);

        $perPage = $this->resolvePerPage($filters);

        return $query
            ->orderByRaw('COALESCE(visit_at, created_at) DESC')
            ->paginate($perPage);
    }

    public function diagnosesTimeline(int $patientId, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->select([
                'id',
                'patient_id',
                'appointment_id',
                'doctor_id',
                'status',
                'visit_type',
                'visit_at',
                'diagnosis',
                'finalized_at',
                'created_at',
            ])
            ->with([
                'doctor:id,name',
                'diagnosisCodeLinks' => function ($query) {
                    $query
                        ->select([
                            'id',
                            'visit_record_id',
                            'diagnosis_code_id',
                            'doctor_id',
                            'created_at',
                        ])
                        ->with([
                            'diagnosisCode:id,code,name_en,name_ar',
                        ]);
                },
            ])
            ->where('patient_id', $patientId)
            ->where(function ($query) {
                $query
                    ->where(function ($subQuery) {
                        $subQuery
                            ->whereNotNull('diagnosis')
                            ->where('diagnosis', '!=', '');
                    })
                    ->orWhereHas('diagnosisCodeLinks');
            });

        $status = $filters['status'] ?? 'finalized';

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if (! empty($filters['date_from'])) {
            $query->whereRaw(
                'DATE(COALESCE(visit_at, finalized_at, created_at)) >= ?',
                [$filters['date_from']]
            );
        }

        if (! empty($filters['date_to'])) {
            $query->whereRaw(
                'DATE(COALESCE(visit_at, finalized_at, created_at)) <= ?',
                [$filters['date_to']]
            );
        }

        $perPage = $this->resolvePerPage($filters);

        return $query
            ->orderByRaw('COALESCE(visit_at, finalized_at, created_at) DESC')
            ->paginate($perPage);
    }

    private function applyDateRange($query, array $filters): void
    {
        if (! empty($filters['date_from'])) {
            $query->whereDate('visit_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('visit_at', '<=', $filters['date_to']);
        }
    }

    private function resolvePerPage(array $filters): int
    {
        return isset($filters['per_page'])
            ? min(max((int) $filters['per_page'], 1), 100)
            : 15;
    }
}
