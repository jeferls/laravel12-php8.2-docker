<?php

namespace Domain\Shared\Contracts;

use Domain\Shared\DTOs\BasePaginationDTO;
use Illuminate\Database\Eloquent\Builder;

interface BaseRepositoryContract
{
    public function getQueryBuilder();

    /**
     * Retrieves all records by generic filter
     *
     * @param array|null $filter
     * @param int|null $take
     * @param int $page
     * @return BasePaginationDTO
     */
    public function findAll(?array $filter = null, ?int $take = 15, int $page = 1): BasePaginationDTO;

    /**
     * Search for values ​​in the columns that should not be returned
     * Can send an array with ['column', 'value'] and ['column', '=', 'value'].
     *
     * @param  array $filter
     * @return array|null
    */
    public function getNotIn(array $filter) : ?array;


    /**
     * Retrieves single record by generic filter
     *
     * @param array $filter
     * @param array $fields
     * @param bool $withoutGlobalScopes
     * @return array|null
     */
    public function findOne(array $filter = [], ?array $fields = null, ?array $relations = [],?bool $withoutGlobalScopes = false): ?array;

    /**
     * Retrieves a record by its id
     *
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array;

    /**
     * @param array $data
     * @param bool $withoutGlobalScopes
     * @return array|null
     */
    public function create(array $data,bool $withoutGlobalScopes = false): array;

    /**
     * @param int $id
     * @param array $data
     * @return array
     */
    public function update(int $id, array $data, ?bool $withoutGlobalScopes = false): array;

    /**
     * @param array $attributes
     * @param array $values
     * @param bool $withoutGlobalScopes
     * @return array
     */
    public function updateOrCreate(array $attributes, array $values, bool $withoutGlobalScopes = false): array;

    /**
     * @param int $id
     * @return bool
     */
    public function destroy(int $id): bool;

    /**
     * Checks if a record with the given data exists
     *
     * @param array $data
     * @return bool
     */
    public function exist(array $data, bool $withoutGlobalScopes = false): bool;

    /**
     * Retrieves all records from the database without pagination.
     *
     * @param array|Builder|null $filter
     * @return array
     */
    public function fetchAll($filter = null): array;
}
