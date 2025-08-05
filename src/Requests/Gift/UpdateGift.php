<?php

namespace DonorPerfect\Requests\Gift;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;

class UpdateGift extends Request implements HasBody
{
    use AlwaysThrowOnErrors, HasJsonBody;

    protected Method $method = Method::PUT;

    public function __construct(
        protected int $giftId,
        protected array $properties
    ) {}

    public function resolveEndpoint(): string
    {
        return "/gift/v1/gifts/{$this->giftId}";
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultBody(): array
    {
        return $this->properties;
    }
}
