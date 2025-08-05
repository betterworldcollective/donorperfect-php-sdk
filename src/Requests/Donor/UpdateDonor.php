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

    /**
     * @param array<string, mixed> $properties
     */
    public function __construct(protected array $properties) {}

    public function resolveEndpoint(): string
    {
        return '/donors/' . $this->properties['donor_id'];
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultBody(): array
    {
        return $this->properties;
    }
}
