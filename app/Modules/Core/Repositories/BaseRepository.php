<?php

namespace App\Modules\Core\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

abstract class BaseRepository
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /*
    |--------------------------------------------------------------------------
    | Base Query
    |--------------------------------------------------------------------------
    */

    public function query(): Builder
    {
        return $this->model->newQuery();
    }

    /*
    |--------------------------------------------------------------------------
    | Get Records
    |--------------------------------------------------------------------------
    */

    public function all(array $columns = ['*'], array $relations = []): EloquentCollection
    {
        return $this->query()
            ->with($relations)
            ->get($columns);
    }

    public function paginate(
        int $perPage = 15,
        array $conditions = [],
        array $relations = [],
        array $columns = ['*'],
        string $orderBy = 'id',
        string $direction = 'desc'
    ): LengthAwarePaginator {
        return $this->applyConditions(
                $this->query()->with($relations),
                $conditions
            )
            ->orderBy($orderBy, $direction)
            ->paginate($perPage, $columns);
    }

    public function getWhere(
        array $conditions,
        array $columns = ['*'],
        array $relations = [],
        string $orderBy = 'id',
        string $direction = 'desc'
    ): EloquentCollection {
        return $this->applyConditions(
                $this->query()->with($relations),
                $conditions
            )
            ->orderBy($orderBy, $direction)
            ->get($columns);
    }

    /*
    |--------------------------------------------------------------------------
    | Find One Record
    |--------------------------------------------------------------------------
    */

    public function findById(
        int|string $id,
        array $columns = ['*'],
        array $relations = []
    ): ?Model {
        return $this->query()
            ->with($relations)
            ->find($id, $columns);
    }

    public function findOrFail(
        int|string $id,
        array $columns = ['*'],
        array $relations = []
    ): Model {
        return $this->query()
            ->with($relations)
            ->findOrFail($id, $columns);
    }

    public function firstWhere(
        string $column,
        mixed $value,
        array $columns = ['*'],
        array $relations = []
    ): ?Model {
        return $this->query()
            ->with($relations)
            ->where($column, $value)
            ->first($columns);
    }

    public function firstByConditions(
        array $conditions,
        array $columns = ['*'],
        array $relations = []
    ): ?Model {
        return $this->applyConditions(
                $this->query()->with($relations),
                $conditions
            )
            ->first($columns);
    }

    /*
    |--------------------------------------------------------------------------
    | Create / Update
    |--------------------------------------------------------------------------
    */

    public function create(array $data): Model
    {
        return $this->query()->create($data);
    }

    public function createMany(array $items): EloquentCollection
    {
        $created = collect();

        foreach ($items as $item) {
            $created->push($this->create($item));
        }

        return new EloquentCollection($created);
    }

    public function updateById(int|string $id, array $data): Model
    {
        $record = $this->findOrFail($id);

        $record->fill($data);
        $record->save();

        return $record->refresh();
    }

    public function updateWhere(array $conditions, array $data): int
    {
        return $this->applyConditions($this->query(), $conditions)
            ->update($data);
    }

    public function updateOrCreate(array $conditions, array $data): Model
    {
        return $this->query()->updateOrCreate($conditions, $data);
    }

    /*
    |--------------------------------------------------------------------------
    | Delete
    |--------------------------------------------------------------------------
    */

    public function deleteById(int|string $id): bool
    {
        $record = $this->findOrFail($id);

        return (bool) $record->delete();
    }

    public function deleteWhere(array $conditions): int
    {
        return $this->applyConditions($this->query(), $conditions)
            ->delete();
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function exists(array $conditions): bool
    {
        return $this->applyConditions($this->query(), $conditions)
            ->exists();
    }

    public function count(array $conditions = []): int
    {
        return $this->applyConditions($this->query(), $conditions)
            ->count();
    }

    protected function applyConditions(Builder $query, array $conditions): Builder
    {
        foreach ($conditions as $column => $value) {
            if (is_array($value)) {
                $operator = $value[0] ?? '=';
                $conditionValue = $value[1] ?? null;

                $query->where($column, $operator, $conditionValue);
                continue;
            }

            $query->where($column, $value);
        }

        return $query;
    }
}
