<?php

namespace DonorPerfect\Requests\Donor;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;

class UpdateDonor extends Request implements HasBody
{
    use AlwaysThrowOnErrors, HasJsonBody;

    protected Method $method = Method::PUT;

    public function __construct(
        protected int $donorId,
        protected array $properties
    ) {}

    public function resolveEndpoint(): string
    {
        return "/donor/v1/donors/{$this->donorId}";
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultBody(): array
    {
        return $this->properties;
    }
}
