<?php

namespace DonorPerfect\Contracts;

interface Data
{
    /**
     * @param  array<mixed, mixed>  $data
     */
    public static function from(array $data): self;
}
