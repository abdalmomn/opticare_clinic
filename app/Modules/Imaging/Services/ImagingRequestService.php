<?php

namespace App\Modules\Imaging\Services;

use App\Modules\Authentication\Models\Staff;
use App\Modules\Appointments\Repositories\AppointmentRepository;
use App\Modules\Imaging\Helpers\ImagingRequestHelper;
use App\Modules\Imaging\Models\ImagingActivityLog;
use App\Modules\Imaging\Models\ImagingQueue;
use App\Modules\Imaging\Models\ImagingRequest;
use App\Modules\Imaging\Repositories\ImagingQueueRepository;
use App\Modules\Imaging\Repositories\ImagingRequestRepository;
use App\Modules\MedicalRecords\Repositories\VisitRecordRepository;
use App\Modules\Patients\Repositories\ClinicPatientRepository;
use Illuminate\Http\Response;
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

        ImagingRequestHelper::ensurePatientVisitAppointmentConsistency(
            $patient->id,
            $visitRecord,
            $appointment
        );

        $source = ImagingRequestHelper::resolveSource($actor, $data);
        $requestedBy = ImagingRequestHelper::resolveRequestedBy($actor, $data['requested_by'] ?? null);

        $payload = [
            'patient_id' => $patient->id,
            'visit_record_id' => $visitRecord?->id,
            'appointment_id' => $appointment?->id,
            'requested_by' => $requestedBy,
            'room_id' => $data['room_id'] ?? null,
            'request_type' => ImagingRequestHelper::buildRequestTypeSummary($data['requested_types']),
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
            'request' => ImagingRequestHelper::formatRequest($imagingRequest, $actor),
        ];
    }

    public function list(array $filters, Staff $actor): array
    {
        Gate::forUser($actor)->authorize('viewAny', ImagingRequest::class);

        $paginator = $this->repository->paginateForActor($actor, $filters);

        return [
            'items' => $paginator->getCollection()
                ->map(fn (ImagingRequest $request) => ImagingRequestHelper::formatRequest($request, $actor))
                ->all(),
            'pagination' => ImagingRequestHelper::formatPagination($paginator),
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

        return ['request' => ImagingRequestHelper::formatRequest($imagingRequest, $actor)];
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

        return ['request' => ImagingRequestHelper::formatRequest($updatedRequest, $actor)];
    }

    public function confirmPayment($request, array $data, Staff $actor): array
    {
        return DB::transaction(function () use ($request, $data, $actor) {
            $imagingRequest = $this->repository->lockForUpdate(ImagingRequestHelper::requestId($request));

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
                'request' => ImagingRequestHelper::formatRequest(
                    $this->repository->findDetailed($imagingRequest->id),
                    $actor
                ),
            ];
        });
    }

    public function sendToTechnician($request, array $data, Staff $actor): array
    {
        return DB::transaction(function () use ($request, $data, $actor) {
            $imagingRequest = $this->repository->lockForUpdate(ImagingRequestHelper::requestId($request));

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

            $technicianId = ImagingRequestHelper::resolveTechnician($data['technician_id'] ?? null);
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
                'request' => ImagingRequestHelper::formatRequest(
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
                ? ImagingRequestHelper::formatRequest($currentRequest, $actor)
                : null,
            'items' => $paginator->getCollection()
                ->map(fn (ImagingRequest $request) => ImagingRequestHelper::formatRequest($request, $actor))
                ->all(),
        ];
    }

    public function start($request, Staff $actor): array
    {
        return DB::transaction(function () use ($request, $actor) {
            $imagingRequest = $this->repository->lockForUpdate(ImagingRequestHelper::requestId($request));

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
                'request' => ImagingRequestHelper::formatRequest(
                    $this->repository->findDetailed($imagingRequest->id),
                    $actor
                ),
            ];
        });
    }

    public function complete($request, Staff $actor): array
    {
        return DB::transaction(function () use ($request, $actor) {
            $imagingRequest = $this->repository->lockForUpdate(ImagingRequestHelper::requestId($request));

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
                'request' => ImagingRequestHelper::formatRequest(
                    $this->repository->findDetailed($imagingRequest->id),
                    $actor
                ),
            ];
        });
    }

    private function resolveRequest($request): ?ImagingRequest
    {
        if ($request instanceof ImagingRequest) {
            return $this->repository->findDetailed($request->id);
        }

        return $this->repository->findDetailed($request);
    }
}
