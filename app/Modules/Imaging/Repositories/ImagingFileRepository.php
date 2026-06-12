<?php

namespace App\Modules\Imaging\Repositories;

use App\Modules\Core\Repositories\BaseRepository;
use App\Modules\Imaging\Models\ImagingFile;

class ImagingFileRepository extends BaseRepository
{
    public function __construct(ImagingFile $model)
    {
        parent::__construct($model);
    }

    public function findWithRelations(int $id): ?ImagingFile
    {
        return $this->query()
            ->with([
                'request',
                'item',
                'device:id,name,device_identifier,device_type',
                'uploader:id,name',
            ])
            ->find($id);
    }

    public function countActiveForRequest(int $requestId): int
    {
        return $this->query()
            ->where('imaging_request_id', $requestId)
            ->count();
    }

    public function nextBatchId(): int
    {
        return (int) now()->getTimestampMs();
    }
}
