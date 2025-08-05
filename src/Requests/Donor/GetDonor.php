<?php

namespace DonorPerfect\Requests\Donor;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;

class GetDonor extends Request
{
    use AlwaysThrowOnErrors;

    protected Method $method = Method::GET;

    public function __construct(protected int $donorId) {}

    public function resolveEndpoint(): string
    {
        return "/donor/v1/donors/{$this->donorId}";
    }
}
