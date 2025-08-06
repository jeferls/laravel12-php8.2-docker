<?php

declare(strict_types=1);

namespace Domain\Shared\Repositories;

use Domain\Shared\DTOs\BasePaginationDTO;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Domain\Shared\Contracts\BaseRepositoryContract;

class BaseRepository implements BaseRepositoryContract
{
    /**
     * @var Model
     */
    private $modelObject;
    protected string $modelClass;

    public function __construct()
    {
        $this->modelClass = "\\$this->modelClass";
        $this->modelObject = app($this->modelClass);
    }

    /**
     * getModel
     *
     * @return Model
     */
    protected function getModel()
    {
        return $this->modelObject;
    }

    /**
     * @return Builder
     */
    public function getQueryBuilder()
    {
        return $this->modelObject->newQuery();
    }

     /**
     * Search for values ​​in the columns that should not be returned
     * Can send an array with ['column', 'value'] and ['column', '=', 'value'].
     *
     * @param  array $filter
     * @return array|null
     */
    public function getNotIn(array $filter) : ?array
    {
        $query = $this->getQueryBuilder();

        if (!empty($filter)) {
            foreach ($filter as $key => $condition) {
                if (is_array($condition) && count($condition) === 1) {
                    $query->whereNot(key($condition), $condition[key($condition)]);
                } elseif (is_array($condition) && count($condition) === 3) {
                    $query->whereNot($condition[0], $condition[1], $condition[2]);
                } elseif(is_string($key)){
                    $query->whereNot($key,$condition);
                }
            }
        }

        return $query->get()->toArray() ?? null;
    }

    /**
     * Retrieves all records from the database
     *
     * @param array|Builder|null $filter
     * @param int|null $take
     * @param int $page
     * @return BasePaginationDTO
     */
    public function findAll($filter = [], ?int $take = 15, int $page = 1): BasePaginationDTO
    {
        if ($filter instanceof Builder) {
            $query = $filter;
        } elseif (is_array($filter)) {
            $query = $this->getQueryBuilder();
            foreach ($filter as $key => $value) {
                if (is_array($value)) {
                    $query->whereIn($key, $value);
                } else {
                    $query->where($key, $value);
                }
            }
        }

        if (!isset($query)) {
            $query = $this->getQueryBuilder();
        }

        $paginated = $query->paginate(
            $take,
            ['*'],
            'page',
            $page
        );

        return new BasePaginationDTO(
            $paginated->items(),
            $paginated->total(),
            $paginated->currentPage(),
            $paginated->perPage(),
            $paginated->currentPage() > 1
                ? $paginated->currentPage() - 1
                : null,
            $paginated->currentPage() < $paginated->lastPage()
                ? $paginated->currentPage() + 1
                : null,
            1,
            $paginated->lastPage(),
        );
    }

    /**
     * Retrieves single record by generic filter
     *
     * @param array $filter
     * @param array $fields
     * @param bool $withoutGlobalScopes
     * @return array|null
     */
    public function findOne(array $filter = [], ?array $fields = null, ?array $relations = [],?bool $withoutGlobalScopes = false): ?array
    {
        $query = $this->modelObject;
        
        if($withoutGlobalScopes){
            $query = $query->withoutGlobalScopes();
        }

        $query = $query->newQuery();
        foreach ($filter as $key => $value) {
            if (is_array($value)) {
                $query->whereIn($key, $value);
            } else {
                $query->where($key, $value);
            }
        }
        $result = !empty($relations) ? $query->with($relations) : $query;

        if($fields){
            $result = $query->first($fields);
        }else {
            $result = $query->first();
        }

        return $result ? $result->toArray() : null;
    }

    /**
     * Retrieves a record by its id
     * If parameter $fail is set to true, when something goes wrong
     * the method will fire a ModelNotFoundException.
     *
     * @param int $id *
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        return $this->findOne(['id' => $id]);
    }


    /**
     * Checks if a record with the given data exists
     *
     * @param array $data
     * @return bool
     */
    public function exist(array $data, bool $withoutGlobalScopes = false): bool
    {
        $query = $this->modelObject;

        if($withoutGlobalScopes){
            $query = $query->withoutGlobalScopes();
        }
        $query = $query->newQuery();
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $query->whereIn($key, $value);
            } else {
                $query->where($key, $value);
            }
        }

        return $query->exists();
    }

    /**
     * Retrieves all records from the database without pagination.
     *
     * @param array|Builder|null $filter
     * @return array
     */
    public function fetchAll($filter = null): array
    {
        if ($filter instanceof Builder) {
            $query = $filter;
        } elseif (is_array($filter)) {
            $query = $this->getQueryBuilder();
            foreach ($filter as $key => $value) {
                if (is_array($value)) {
                    $query->whereIn($key, $value);
                } else {
                    $query->where($key, $value);
                }
            }
        } else {
            $query = $this->getQueryBuilder();
        }

        return $query->get()->toArray();
    }

    /**
     * @param array $data
     * @param bool $withoutGlobalScopes
     * @return array
     */
    public function create(array $data,bool $withoutGlobalScopes = false): array
    {
        $model = new $this->modelClass;

        if($withoutGlobalScopes){
            $model->withoutGlobalScopes();
        }

        $entity = $model->create($data);

        return $entity->toArray();
    }

    /**
     * @param int $id
     * @param array $data
     * @param bool $withoutGlobalScopes
     * @return array
     */
    public function update(int $id, array $data,?bool $withoutGlobalScopes = false): array
    {
        $entity = $this->modelObject;

        if($withoutGlobalScopes){
            $entity = $entity->withoutGlobalScopes();
        }

        $entity = $entity->findOrFail($id);

        foreach ($data as $key => $value) {
            $entity->{$key} = $value;
        }

        $entity->save();

        return $entity->toArray();
    }

    /**
     * @param array $attributes
     * @param array $values
     * @param bool $withoutGlobalScopes
     * @return array
     */
    public function updateOrCreate(array $attributes, array $values, bool $withoutGlobalScopes = false): array
    {
        if($withoutGlobalScopes) {
            $entity = $this->modelObject->withoutGlobalScopes()->where($attributes)->first();
        } else {
            $entity = $this->modelObject->where($attributes)->first();
        }

        if ($entity) {
            $entity->fill($values)->save();
        } else {
            $entity = $this->modelObject->create(array_merge($attributes, $values));
        }

        return $entity->toArray();
    }

    /**
     * @param int $id
     * @return bool
     */
    public function destroy(int $id): bool
    {
        $entity = $this->modelObject->findOrFail($id);

        return $entity->delete();
    }
}
