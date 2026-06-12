<?php

namespace App\Modules\Imaging\Services;

use App\Modules\Authentication\Models\Staff;
use App\Modules\Appointments\Repositories\AppointmentRepository;
use App\Modules\Imaging\Models\ImagingActivityLog;
use App\Modules\Imaging\Models\ImagingFile;
use App\Modules\Imaging\Models\ImagingQueue;
use App\Modules\Imaging\Models\ImagingRequest;
use App\Modules\Imaging\Models\ImagingRequestItem;
use App\Modules\Imaging\Repositories\ImagingQueueRepository;
use App\Modules\Imaging\Repositories\ImagingRequestRepository;
use App\Modules\MedicalRecords\Repositories\VisitRecordRepository;
use App\Modules\Patients\Repositories\ClinicPatientRepository;
use App\Modules\RolesPermissions\Constants\PermissionList;
use App\Modules\RolesPermissions\Helpers\AccessControlHelper;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ImagingRequestService
{
    public function __construct(
        protected ImagingRequestRepository $repository,
        protected ImagingQueueRepository $queueRepository,
        protected ClinicPatientRepository $patientRepository,
        protected VisitRecordRepository $visitRecordRepository,
        protected AppointmentRepository $appointmentRepository,
        protected ImagingActivityLogService $activityLog
    ) {}

    public function create(array $data, Staff $actor): array
    {
        Gate::forUser($actor)->authorize('create', ImagingRequest::class);

        $patient = $this->patientRepository->findPatientById($data['patient_id']);

        if (! $patient) {
            throw new HttpException(
                Response::HTTP_NOT_FOUND,
                __('imaging.errors.patient_not_found')
            );
        }

        $visitRecord = null;
        if (! empty($data['visit_record_id'])) {
            $visitRecord = $this->visitRecordRepository->findSession((int) $data['visit_record_id']);

            if (! $visitRecord) {
                throw new HttpException(
                    Response::HTTP_NOT_FOUND,
                    __('imaging.errors.visit_record_not_found')
                );
            }
        }

        $appointment = null;
        if (! empty($data['appointment_id'])) {
            $appointment = $this->appointmentRepository->findById((int) $data['appointment_id']);

            if (! $appointment) {
                throw new HttpException(
                    Response::HTTP_NOT_FOUND,
                    __('imaging.errors.appointment_not_found')
                );
            }
        }

        $this->ensurePatientVisitAppointmentConsistency(
            $patient->id,
            $visitRecord,
            $appointment
        );

        $source = $this->resolveSource($actor, $data);
        $requestedBy = $this->resolveRequestedBy($actor, $data['requested_by'] ?? null);

        $payload = [
            'patient_id' => $patient->id,
            'visit_record_id' => $visitRecord?->id,
            'appointment_id' => $appointment?->id,
            'requested_by' => $requestedBy,
            'room_id' => $data['room_id'] ?? null,
            'request_type' => $this->buildRequestTypeSummary($data['requested_types']),
            'source' => $source,
            'notes' => $data['notes'] ?? null,
            'status' => ImagingRequest::STATUS_PENDING_PAYMENT,
            'payment_status' => ImagingRequest::PAYMENT_STATUS_PENDING,
            'priority' => $data['priority'] ?? 'normal',
            'created_by' => $actor->id,
        ];

        $imagingRequest = $this->repository->createWithItems($payload, $data['requested_types']);

        $this->activityLog->record(
            ImagingActivityLog::ACTION_REQUEST_CREATED,
            imagingRequestId: $imagingRequest->id,
            actorId: $actor->id,
            toStatus: ImagingRequest::STATUS_PENDING_PAYMENT
        );

        return [
            'request' => $this->formatRequest($imagingRequest, $actor),
        ];
    }

    public function list(array $filters, Staff $actor): array
    {
        Gate::forUser($actor)->authorize('viewAny', ImagingRequest::class);

        $paginator = $this->repository->paginateForActor($actor, $filters);

        return [
            'items' => $paginator->getCollection()
                ->map(fn (ImagingRequest $request) => $this->formatRequest($request, $actor))
                ->all(),
            'pagination' => $this->formatPagination($paginator),
        ];
    }

    public function show($request, Staff $actor): array
    {
        $imagingRequest = $this->resolveRequest($request);

        if (! $imagingRequest) {
            throw new HttpException(
                Response::HTTP_NOT_FOUND,
                __('imaging.errors.not_found')
            );
        }

        Gate::forUser($actor)->authorize('view', $imagingRequest);

        return ['request' => $this->formatRequest($imagingRequest, $actor)];
    }

    public function cancel($request, array $data, Staff $actor): array
    {
        $imagingRequest = $this->resolveRequest($request);

        if (! $imagingRequest) {
            throw new HttpException(
                Response::HTTP_NOT_FOUND,
                __('imaging.errors.not_found')
            );
        }


        if (in_array($imagingRequest->status, [
            ImagingRequest::STATUS_IN_PROGRESS,
            ImagingRequest::STATUS_COMPLETED,
            ImagingRequest::STATUS_CANCELLED,
            ImagingRequest::LEGACY_STATUS_CANCELED,
        ], true)) {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                __('imaging.errors.cannot_cancel')
            );
        }

        $previousStatus = ImagingRequest::normalizeStatus($imagingRequest->status ?? '');

        $payload = [
            'status' => ImagingRequest::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancel_reason' => $data['reason'] ?? null,
            'updated_by' => $actor->id,
        ];

        $updatedRequest = $this->repository->cancel($imagingRequest, $payload);

        $this->activityLog->record(
            ImagingActivityLog::ACTION_REQUEST_CANCELLED,
            imagingRequestId: $updatedRequest->id,
            actorId: $actor->id,
            fromStatus: $previousStatus,
            toStatus: ImagingRequest::STATUS_CANCELLED
        );

        return ['request' => $this->formatRequest($updatedRequest, $actor)];
    }

    public function confirmPayment($request, array $data, Staff $actor): array
    {
        return DB::transaction(function () use ($request, $data, $actor) {
            $imagingRequest = $this->repository->lockForUpdate($this->requestId($request));

            if (! $imagingRequest) {
                throw new HttpException(
                    Response::HTTP_NOT_FOUND,
                    __('imaging.errors.not_found')
                );
            }

            $status = ImagingRequest::normalizeStatus($imagingRequest->status ?? '');

            if ($status !== ImagingRequest::STATUS_PENDING_PAYMENT) {
                throw new HttpException(
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                    __('imaging.errors.cannot_confirm_payment')
                );
            }

            $waive = (bool) ($data['waive'] ?? false);

            $payload = [
                'payment_status' => $waive
                    ? ImagingRequest::PAYMENT_STATUS_WAIVED
                    : ImagingRequest::PAYMENT_STATUS_CONFIRMED,
                'status' => ImagingRequest::STATUS_PAYMENT_CONFIRMED,
                'confirmed_by' => $actor->id,
                'payment_confirmed_at' => now(),
                'updated_by' => $actor->id,
            ];

            if (! empty($data['invoice_item_id'])) {
                $payload['invoice_item_id'] = (int) $data['invoice_item_id'];
            }

            $imagingRequest->update($payload);

            $this->activityLog->record(
                $waive
                    ? ImagingActivityLog::ACTION_PAYMENT_WAIVED
                    : ImagingActivityLog::ACTION_PAYMENT_CONFIRMED,
                imagingRequestId: $imagingRequest->id,
                actorId: $actor->id,
                fromStatus: ImagingRequest::STATUS_PENDING_PAYMENT,
                toStatus: ImagingRequest::STATUS_PAYMENT_CONFIRMED
            );

            return [
                'request' => $this->formatRequest(
                    $this->repository->findDetailed($imagingRequest->id),
                    $actor
                ),
            ];
        });
    }

    public function sendToTechnician($request, array $data, Staff $actor): array
    {
        return DB::transaction(function () use ($request, $data, $actor) {
            $imagingRequest = $this->repository->lockForUpdate($this->requestId($request));

            if (! $imagingRequest) {
                throw new HttpException(
                    Response::HTTP_NOT_FOUND,
                    __('imaging.errors.not_found')
                );
            }

            $status = ImagingRequest::normalizeStatus($imagingRequest->status ?? '');

            if (! in_array($status, [
                ImagingRequest::STATUS_PAYMENT_CONFIRMED,
                ImagingRequest::STATUS_READY_FOR_IMAGING,
            ], true)) {
                throw new HttpException(
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                    __('imaging.errors.cannot_dispatch')
                );
            }

            if (! in_array($imagingRequest->payment_status, [
                ImagingRequest::PAYMENT_STATUS_CONFIRMED,
                ImagingRequest::PAYMENT_STATUS_WAIVED,
            ], true)) {
                throw new HttpException(
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                    __('imaging.errors.payment_not_confirmed')
                );
            }

            $technicianId = $this->resolveTechnician($data['technician_id'] ?? null);
            $roomId = $data['room_id'] ?? $imagingRequest->room_id;

            $payload = [
                'status' => ImagingRequest::STATUS_READY_FOR_IMAGING,
                'sent_to_technician_by' => $actor->id,
                'sent_to_technician_at' => now(),
                'updated_by' => $actor->id,
            ];

            if ($technicianId !== null) {
                $payload['technician_id'] = $technicianId;
            }

            if (! empty($data['room_id'])) {
                $payload['room_id'] = (int) $data['room_id'];
            }

            if (! empty($data['priority'])) {
                $payload['priority'] = $data['priority'];
            }

            $imagingRequest->update($payload);

            $existingQueue = $this->queueRepository->findByRequest($imagingRequest->id);
            $queueNumber = $existingQueue?->queue_number ?? $this->queueRepository->nextQueueNumber();

            $this->queueRepository->upsertForRequest($imagingRequest->id, [
                'room_id' => $roomId,
                'technician_id' => $technicianId,
                'queue_number' => $queueNumber,
                'status' => ImagingQueue::STATUS_DISPATCHED,
                'dispatched_at' => now(),
            ]);

            $this->activityLog->record(
                ImagingActivityLog::ACTION_SENT_TO_TECHNICIAN,
                imagingRequestId: $imagingRequest->id,
                actorId: $actor->id,
                fromStatus: $status,
                toStatus: ImagingRequest::STATUS_READY_FOR_IMAGING,
                metadata: $technicianId !== null ? ['technician_id' => $technicianId] : null
            );

            return [
                'request' => $this->formatRequest(
                    $this->repository->findDetailed($imagingRequest->id),
                    $actor
                ),
            ];
        });
    }

    public function technicianQueue(array $filters, Staff $actor): array
    {
        Gate::forUser($actor)->authorize('viewQueue', ImagingRequest::class);

        $currentRequest = $this->repository->findActiveForTechnician($actor->id);
        $paginator = $this->repository->paginateTechnicianQueue($actor, $filters);

        return [
            'current_request' => $currentRequest
                ? $this->formatRequest($currentRequest, $actor)
                : null,
            'items' => $paginator->getCollection()
                ->map(fn (ImagingRequest $request) => $this->formatRequest($request, $actor))
                ->all(),
        ];
    }

    public function start($request, Staff $actor): array
    {
        return DB::transaction(function () use ($request, $actor) {
            $imagingRequest = $this->repository->lockForUpdate($this->requestId($request));

            if (! $imagingRequest) {
                throw new HttpException(
                    Response::HTTP_NOT_FOUND,
                    __('imaging.errors.not_found')
                );
            }

            Gate::forUser($actor)->authorize('start', $imagingRequest);

            $status = ImagingRequest::normalizeStatus($imagingRequest->status ?? '');

            if ($status !== ImagingRequest::STATUS_READY_FOR_IMAGING) {
                throw new HttpException(
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                    __('imaging.errors.cannot_start')
                );
            }

            if (config('opticare.imaging_one_at_a_time', true)
                && $this->repository->technicianHasActive($actor->id)
            ) {
                throw new HttpException(
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                    __('imaging.errors.technician_busy')
                );
            }

            $imagingRequest->update([
                'status' => ImagingRequest::STATUS_IN_PROGRESS,
                'technician_id' => $imagingRequest->technician_id ?? $actor->id,
                'started_at' => now(),
                'updated_by' => $actor->id,
            ]);

            $existingQueue = $this->queueRepository->findByRequest($imagingRequest->id);

            $this->queueRepository->upsertForRequest($imagingRequest->id, [
                'technician_id' => $imagingRequest->technician_id,
                'queue_number' => $existingQueue?->queue_number ?? $this->queueRepository->nextQueueNumber(),
                'status' => ImagingQueue::STATUS_IN_PROGRESS,
                'started_at' => now(),
            ]);

            $this->activityLog->record(
                ImagingActivityLog::ACTION_STARTED,
                imagingRequestId: $imagingRequest->id,
                actorId: $actor->id,
                fromStatus: ImagingRequest::STATUS_READY_FOR_IMAGING,
                toStatus: ImagingRequest::STATUS_IN_PROGRESS
            );

            return [
                'request' => $this->formatRequest(
                    $this->repository->findDetailed($imagingRequest->id),
                    $actor
                ),
            ];
        });
    }

    public function complete($request, Staff $actor): array
    {
        return DB::transaction(function () use ($request, $actor) {
            $imagingRequest = $this->repository->lockForUpdate($this->requestId($request));

            if (! $imagingRequest) {
                throw new HttpException(
                    Response::HTTP_NOT_FOUND,
                    __('imaging.errors.not_found')
                );
            }

            Gate::forUser($actor)->authorize('complete', $imagingRequest);

            $status = ImagingRequest::normalizeStatus($imagingRequest->status ?? '');

            if ($status !== ImagingRequest::STATUS_IN_PROGRESS) {
                throw new HttpException(
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                    __('imaging.errors.cannot_complete')
                );
            }

            if (! $imagingRequest->files()->exists()) {
                throw new HttpException(
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                    __('imaging.errors.no_files_uploaded')
                );
            }

            $imagingRequest->update([
                'status' => ImagingRequest::STATUS_COMPLETED,
                'completed_at' => now(),
                'updated_by' => $actor->id,
            ]);

            $this->queueRepository->upsertForRequest($imagingRequest->id, [
                'status' => ImagingQueue::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);

            $this->activityLog->record(
                ImagingActivityLog::ACTION_COMPLETED,
                imagingRequestId: $imagingRequest->id,
                actorId: $actor->id,
                fromStatus: ImagingRequest::STATUS_IN_PROGRESS,
                toStatus: ImagingRequest::STATUS_COMPLETED
            );

            return [
                'request' => $this->formatRequest(
                    $this->repository->findDetailed($imagingRequest->id),
                    $actor
                ),
            ];
        });
    }

    private function requestId($request): int
    {
        return $request instanceof ImagingRequest
            ? (int) $request->id
            : (int) $request;
    }

    private function resolveTechnician(?int $technicianId): ?int
    {
        if ($technicianId === null) {
            return null;
        }

        $staff = Staff::find($technicianId);

        if (! $staff) {
            throw new HttpException(
                Response::HTTP_NOT_FOUND,
                __('imaging.errors.technician_not_found')
            );
        }

        if (! AccessControlHelper::staffHasPermission($staff, PermissionList::VIEW_IMAGING_QUEUE)) {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                __('imaging.errors.not_a_technician')
            );
        }

        return $staff->id;
    }

    private function resolveRequest($request): ?ImagingRequest
    {
        if ($request instanceof ImagingRequest) {
            return $this->repository->findDetailed($request->id);
        }

        return $this->repository->findDetailed($request);
    }

    private function ensurePatientVisitAppointmentConsistency(
        int $patientId,
        ?\App\Modules\MedicalRecords\Models\VisitRecord $visitRecord,
        ?\App\Modules\Appointments\Models\Appointment $appointment
    ): void {
        if ($visitRecord && (int) $visitRecord->patient_id !== $patientId) {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                __('imaging.errors.visit_patient_mismatch')
            );
        }

        if ($appointment && (int) $appointment->patient_id !== $patientId) {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                __('imaging.errors.appointment_patient_mismatch')
            );
        }

        if ($visitRecord && $appointment && $visitRecord->appointment_id !== null && $visitRecord->appointment_id !== $appointment->id) {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                __('imaging.errors.visit_appointment_conflict')
            );
        }
    }

    private function resolveSource(Staff $actor, array $data): string
    {
        $hasSecretaryPermission = AccessControlHelper::staffHasPermission(
            $actor,
            PermissionList::CREATE_IMAGING_REQUEST_FOR_PATIENT
        );

        $hasDoctorPermission = AccessControlHelper::staffHasPermission(
            $actor,
            PermissionList::CREATE_IMAGING_REQUEST
        );

        if ($hasSecretaryPermission && ! $hasDoctorPermission) {
            return ImagingRequest::SOURCE_SECRETARY_REQUEST;
        }

        return ImagingRequest::SOURCE_DOCTOR_REQUEST;
    }

    private function resolveRequestedBy(Staff $actor, ?int $requestedBy): ?int
    {
        $isSecretary = AccessControlHelper::staffHasPermission(
            $actor,
            PermissionList::CREATE_IMAGING_REQUEST_FOR_PATIENT
        ) && ! AccessControlHelper::staffHasPermission($actor, PermissionList::CREATE_IMAGING_REQUEST);

        if (! $isSecretary) {
            return $actor->id;
        }

        if ($requestedBy === null) {
            return null;
        }

        $staff = Staff::find($requestedBy);

        if (! $staff) {
            throw new HttpException(
                Response::HTTP_NOT_FOUND,
                __('imaging.errors.requested_by_not_found')
            );
        }

        return $staff->id;
    }

    public function formatRequest(ImagingRequest $request, Staff $actor): array
    {
        $status = ImagingRequest::normalizeStatus($request->status ?? '');

        $formatted = [
            'id' => $request->id,
            'status' => $status,
            'status_label' => $this->formatStatusLabel($status),
            'payment_status' => $request->payment_status ?? ImagingRequest::PAYMENT_STATUS_PENDING,
            'payment_status_label' => $this->formatPaymentStatusLabel($request->payment_status ?? ImagingRequest::PAYMENT_STATUS_PENDING),
            'priority' => $request->priority,
            'request_type' => $request->request_type,
            'source' => $request->source,
            'patient' => $this->formatPatientSummary($request->patient),
            'requested_by' => $this->formatStaffSummary($request->requestedBy),
            'room' => $this->formatRoomSummary($request->room),
            'visit_record_id' => $request->visit_record_id,
            'appointment_id' => $request->appointment_id,
            'requested_types' => $this->formatRequestedTypes($request->items),
            'payment' => $this->formatPayment($request),
            'technician' => $this->formatStaffSummary($request->technician),
            'timestamps' => $this->formatTimestamps($request),
            'notes' => $request->notes,
            'cancel_reason' => $request->cancel_reason,
        ];

        if ($request->relationLoaded('files')) {
            $formatted['files'] = $request->files
                ->map(fn (ImagingFile $file) => $this->formatFile($file))
                ->all();
            $formatted['files_count'] = count($formatted['files']);
        }

        return $formatted;
    }

    public function formatFile(ImagingFile $file): array
    {
        return [
            'id' => $file->id,
            'label' => $this->formatFileLabel($file),
            'image_type' => $file->image_type !== null && $file->image_type !== ''
                ? $file->image_type
                : ($file->modality !== null && $file->modality !== '' ? $file->modality : null),
            'modality' => $file->modality,
            'eye' => $file->eye,
            'region' => $file->region,
            'file_name' => $file->file_name,
            'file_url' => $this->formatFileUrl($file->file_path),
            'thumbnail_url' => $file->thumbnail_path
                ? $this->formatFileUrl($file->thumbnail_path)
                : null,
            'file_size' => $file->file_size,
            'mime_type' => $file->mime_type,
            'captured_at' => $this->formatDate($file->captured_at),
            'uploaded_at' => $this->formatDate($file->uploaded_at),
            'source' => $file->source,
            'is_primary' => (bool) $file->is_primary,
            'imaging_request_item_id' => $file->imaging_request_item_id,
            'device' => $this->formatDeviceSummary($file),
            'uploaded_by' => $file->relationLoaded('uploader')
                ? $this->formatStaffSummary($file->uploader)
                : null,
        ];
    }

    private function formatDeviceSummary(ImagingFile $file): ?array
    {
        if ($file->relationLoaded('device') && $file->device) {
            return [
                'id' => $file->device->id,
                'name' => $file->device->name,
                'device_identifier' => $file->device->device_identifier,
                'type' => $file->device->device_type,
            ];
        }

        if (! empty($file->device_name)) {
            return [
                'id' => $file->device_id,
                'name' => $file->device_name,
                'device_identifier' => null,
                'type' => null,
            ];
        }

        return null;
    }

    private function formatFileLabel(ImagingFile $file): ?string
    {
        if (! empty($file->image_label)) {
            return $file->image_label;
        }

        if (! empty($file->region) && ! empty($file->eye)) {
            return $file->region.' '.$file->eye;
        }

        if (! empty($file->modality)) {
            return $file->modality;
        }

        return $file->file_name
            ? pathinfo($file->file_name, PATHINFO_FILENAME)
            : null;
    }

    private function formatFileUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset('storage/'.ltrim($path, '/'));
    }

    private function formatPatientSummary(?\App\Modules\Patients\Models\ClinicPatient $patient): ?array
    {
        if (! $patient) {
            return null;
        }

        return [
            'id' => $patient->id,
            'full_name' => $patient->full_name,
            'birth_date' => $patient->birth_date
                            ? Carbon::parse($patient->birth_date)->format('Y-m-d')
                            : null,
            'medical_file_number' => $patient->medical_file_number,
        ];
    }

    private function formatStaffSummary(?Staff $staff): ?array
    {
        if (! $staff) {
            return null;
        }

        return [
            'id' => $staff->id,
            'name' => $staff->name,
        ];
    }

    private function formatRoomSummary(?\App\Modules\Clinic\Models\Room $room): ?array
    {
        if (! $room) {
            return null;
        }

        return [
            'id' => $room->id,
            'name' => $room->name,
        ];
    }

    private function formatRequestedTypes($items): array
    {
        return collect($items)
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'image_type' => $item->image_type,
                    'eye' => $item->eye,
                    'region' => $item->region,
                    'notes' => $item->notes,
                    'status' => $item->status,
                    'status_label' => $this->formatItemStatusLabel($item->status),
                ];
            })
            ->all();
    }

    private function formatPayment(ImagingRequest $request): array
    {
        return [
            'status' => $request->payment_status ?? ImagingRequest::PAYMENT_STATUS_PENDING,
            'status_label' => $this->formatPaymentStatusLabel($request->payment_status ?? ImagingRequest::PAYMENT_STATUS_PENDING),
            'confirmed_at' => $this->formatDate($request->payment_confirmed_at),
            'confirmed_by' => $this->formatStaffSummary($request->confirmedBy),
        ];
    }

    private function formatTimestamps(ImagingRequest $request): array
    {
        return [
            'created_at' => $this->formatDate($request->created_at),
            'payment_confirmed_at' => $this->formatDate($request->payment_confirmed_at),
            'sent_to_technician_at' => $this->formatDate($request->sent_to_technician_at),
            'started_at' => $this->formatDate($request->started_at),
            'completed_at' => $this->formatDate($request->completed_at),
            'cancelled_at' => $this->formatDate($request->cancelled_at),
        ];
    }

    private function formatActions(ImagingRequest $request, Staff $actor): array
    {
        $status = ImagingRequest::normalizeStatus($request->status ?? '');

        return [
            'can_cancel' => Gate::forUser($actor)->allows('cancel', $request),
            'can_confirm_payment' => Gate::forUser($actor)->allows('confirmPayment', $request)
                && in_array($status, [ImagingRequest::STATUS_PENDING_PAYMENT, ImagingRequest::LEGACY_STATUS_PENDING], true),
            'can_send_to_technician' => Gate::forUser($actor)->allows('sendToTechnician', $request)
                && in_array($status, [ImagingRequest::STATUS_PAYMENT_CONFIRMED, ImagingRequest::STATUS_READY_FOR_IMAGING], true),
            'can_start' => Gate::forUser($actor)->allows('start', $request),
            'can_upload' => Gate::forUser($actor)->allows('uploadFiles', $request),
            'can_complete' => Gate::forUser($actor)->allows('complete', $request),
        ];
    }

    private function formatPagination($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'has_more' => $paginator->hasMorePages(),
        ];
    }

    private function formatDate($value): ?string
    {
        if (! $value) {
            return null;
        }

        return Carbon::parse($value)->toISOString();
    }

    private function formatStatusLabel(string $status): string
    {
        return match ($status) {
            ImagingRequest::STATUS_REQUESTED => 'Requested',
            ImagingRequest::STATUS_PENDING_PAYMENT => 'Pending Payment',
            ImagingRequest::STATUS_PAYMENT_CONFIRMED => 'Payment Confirmed',
            ImagingRequest::STATUS_READY_FOR_IMAGING => 'Ready For Imaging',
            ImagingRequest::STATUS_IN_PROGRESS => 'In Progress',
            ImagingRequest::STATUS_COMPLETED => 'Completed',
            ImagingRequest::STATUS_CANCELLED => 'Cancelled',
            ImagingRequest::LEGACY_STATUS_PENDING => 'Pending Payment',
            ImagingRequest::LEGACY_STATUS_CANCELED => 'Cancelled',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    private function formatPaymentStatusLabel(string $status): string
    {
        return match ($status) {
            ImagingRequest::PAYMENT_STATUS_PENDING => 'Pending',
            ImagingRequest::PAYMENT_STATUS_CONFIRMED => 'Confirmed',
            ImagingRequest::PAYMENT_STATUS_WAIVED => 'Waived',
            ImagingRequest::PAYMENT_STATUS_REFUNDED => 'Refunded',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    private function formatItemStatusLabel(?string $status): string
    {
        return match ($status) {
            ImagingRequestItem::STATUS_REQUESTED => 'Requested',
            ImagingRequestItem::STATUS_CAPTURED => 'Captured',
            ImagingRequestItem::STATUS_SKIPPED => 'Skipped',
            default => ucfirst(str_replace('_', ' ', $status ?? 'requested')),
        };
    }

    private function buildRequestTypeSummary(array $requestedTypes): string
    {
        $labels = array_map(function (array $requestedType) {
            $labelParts = [trim($requestedType['image_type'])];

            if (! empty($requestedType['region'])) {
                $labelParts[] = trim($requestedType['region']);
            }

            if (! empty($requestedType['eye'])) {
                $labelParts[] = trim($requestedType['eye']);
            }

            return trim(implode(' ', array_filter($labelParts)));
        }, $requestedTypes);

        return count($labels) === 1
            ? $labels[0]
            : implode(' + ', $labels);
    }
}
