<?php

declare(strict_types=1);

namespace Domain\Shared\Helpers;

use InvalidArgumentException;
use JsonSerializable;
use Domain\Shared\Contracts\BaseDtoContract;

class BaseDTO implements BaseDTOContract, JsonSerializable
{
    /**
     * Method values
     */
    public function values(): array
    {
        return get_object_vars($this);
    }

    /**
     * Method get
     *
     *
     * @return mixed
     */
    public function get(string $property)
    {
        $getter = 'get'.ucfirst($property);

        if (method_exists($this, $getter)) {
            return $this->{$getter}();
        }

        if (! property_exists($this, $property)) {
            throw new InvalidArgumentException(sprintf(
                "The property '%s' doesn't exists in '%s' DTO Class",
                $property,
                get_class()
            ));
        }

        return $this->{$property};
    }

    /**
     * Method jsonSerialize
     *
     * @return mixed
     */
    public function jsonSerialize()
    {
        return $this->values();
    }

    /**
     * __get
     *
     * @param  string  $name
     * @return mixed
     */
    public function __get(string $property)
    {
        if (! property_exists($this, $property)) {
            throw new InvalidArgumentException(sprintf(
                "The property '%s' doesn't exists in '%s' DTO Class",
                $property,
                get_class()
            ));
        }

        return $this->{$property};
    }

    /**
     * Method __set
     *
     * @param  mixed  $value
     */
    public function __set(string $name, $value): void
    {
        throw new InvalidArgumentException(
            sprintf("The property '%s' is read-only", $name)
        );
    }

    /**
     * Method __isset
     */
    public function __isset(string $name): bool
    {
        return property_exists($this, $name);
    }
}
