<?php

namespace DonorPerfect\Requests\Gift;

use DonorPerfect\Support\ActionParams;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasXmlBody;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;

class SaveGift extends Request implements HasBody
{
    use AlwaysThrowOnErrors, HasXmlBody;

    protected Method $method = Method::POST;

    /**
     * @param  array<string, mixed>  $properties
     */
    public function __construct(protected array $properties) {}

    public function resolveEndpoint(): string
    {
        return '/xmlrequest.asp';
    }

    protected function defaultQuery(): array
    {
        return [
            'action' => 'dp_savegift',
            'params' => ActionParams::serialize($this->properties),
        ];
    }

    protected function defaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];
    }
}
