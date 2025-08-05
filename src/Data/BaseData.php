<?php

namespace DonorPerfect\Data;

use DonorPerfect\Contracts\Data;

abstract class BaseData implements Data
{
    /**
     * @param array<string, mixed> $data
     * @return static
     */
    public static function from(array $data): static
    {
        /** @var static $instance */
        $instance = new static(...$data);
        return $instance;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
