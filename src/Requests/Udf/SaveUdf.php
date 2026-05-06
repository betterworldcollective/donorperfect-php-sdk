<?php

namespace DonorPerfect\Requests\Udf;

use DonorPerfect\Support\ActionParams;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;

class SaveUdf extends Request
{
    use AlwaysThrowOnErrors;

    protected Method $method = Method::POST;

    /**
     * @param  array<string, mixed>  $properties  DP @-prefixed param keys (matching_id, field_name, data_type, field_value)
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
            'action' => 'dp_save_udf_xml',
            'params' => ActionParams::serialize($this->properties),
        ];
    }
}
