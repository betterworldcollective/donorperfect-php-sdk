<?php

namespace DonorPerfect\Data;

use DonorPerfect\Contracts\Data;

abstract class BaseData implements Data
{
    public static function from(array $data): static
    {
        return new static(...$data);
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
