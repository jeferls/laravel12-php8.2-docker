<?php

declare(strict_types=1);

namespace Domain\Shared\DTOs;

use Domain\Shared\Contracts\BasePaginationDtoContract;
use Domain\Shared\Helpers\BaseDTO;

/**
 * @property array $items
 * @property int $total
 * @property int $page
 * @property int $perPage
 * @property int|null $previousPage
 * @property int|null $nextPage
 * @property int $firstPage
 * @property int $lastPage
 */
class BasePaginationDTO extends BaseDTO
{
    protected array $items;
    protected int $total;
    protected int $page;
    protected int $perPage;
    protected ?int $previousPage;
    protected ?int $nextPage;
    protected int $firstPage;
    protected int $lastPage;

    public function __construct(
        array $items,
        int $total,
        int $page,
        int $perPage,
        ?int $previousPage,
        ?int $nextPage,
        int $firstPage,
        int $lastPage
    ) {

        $this->items = $items;
        $this->total = $total;
        $this->page = $page;
        $this->perPage = $perPage;
        $this->previousPage = $previousPage;
        $this->nextPage = $nextPage;
        $this->firstPage = $firstPage;
        $this->lastPage = $lastPage;
    }
}
