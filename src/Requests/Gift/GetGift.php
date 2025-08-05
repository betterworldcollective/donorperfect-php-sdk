<?php

namespace DonorPerfect\Requests\Gift;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;

class GetGift extends Request
{
    use AlwaysThrowOnErrors;

    protected Method $method = Method::GET;

    public function __construct(protected int $giftId) {}

    public function resolveEndpoint(): string
    {
        return "/gift/v1/gifts/{$this->giftId}";
    }
}
