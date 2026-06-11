<?php

namespace App\Modules\Imaging\Repositories;

use App\Modules\Core\Repositories\BaseRepository;
use App\Modules\Imaging\Models\ImagingRequestItem;

class ImagingRequestItemRepository extends BaseRepository
{
    public function __construct(ImagingRequestItem $model)
    {
        parent::__construct($model);
    }

    public function createItems(array $items)
    {
        return $this->createMany($items);
    }
}
