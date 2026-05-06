<?php

namespace DonorPerfect\Requests\Flag;

use DonorPerfect\Support\ActionParams;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;

class SaveFlag extends Request
{
    use AlwaysThrowOnErrors;

    protected Method $method = Method::POST;

    /**
     * @param  array<string, mixed>  $properties  DP @-prefixed param keys (matching_id, flag, optional flag_date)
     */
    public function __construct(protected array $properties) {}

    public function resolveEndpoint(): string
    {
        return '/xmlrequest.asp';
    }

    /**
     * @return array<string, string>
     */
    protected function defaultQuery(): array
    {
        return [
            'action' => 'dp_saveflag_xml',
            'params' => ActionParams::serialize($this->properties),
        ];
    }
}
