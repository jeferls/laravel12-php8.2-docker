<?php

namespace Domain\Shared\Contracts;

interface BaseDtoContract
{
    public function values(): array;

    /**
     * @return mixed
     */
    public function get(string $property);
}
