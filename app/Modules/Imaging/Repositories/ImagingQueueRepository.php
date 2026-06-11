<?php

namespace App\Modules\Imaging\Repositories;

use App\Modules\Core\Repositories\BaseRepository;
use App\Modules\Imaging\Models\ImagingQueue;

class ImagingQueueRepository extends BaseRepository
{
    public function __construct(ImagingQueue $model)
    {
        parent::__construct($model);
    }

    public function findByRequest(int $requestId): ?ImagingQueue
    {
        return $this->query()
            ->where('imaging_request_id', $requestId)
            ->first();
    }

    public function nextQueueNumber(): int
    {
        $max = $this->query()
            ->whereIn('status', [
                ImagingQueue::STATUS_WAITING,
                ImagingQueue::STATUS_DISPATCHED,
                ImagingQueue::STATUS_IN_PROGRESS,
            ])
            ->max('queue_number');

        return (int) $max + 1;
    }

    public function upsertForRequest(int $requestId, array $attributes): ImagingQueue
    {
        return $this->query()->updateOrCreate(
            ['imaging_request_id' => $requestId],
            $attributes
        );
    }
}
