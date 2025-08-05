<?php

namespace DonorPerfect\Requests\Donor;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;

class SaveDonor extends Request
{
    use AlwaysThrowOnErrors;

    protected Method $method = Method::GET;

    public function __construct(protected array $properties) {}

    public function resolveEndpoint(): string
    {
        return '/xmlrequest.asp';
    }

    protected function defaultQuery(): array
    {
        // Format properties as comma-separated string for dp_savedonor
        $formattedProps = [];
        foreach ($this->properties as $key => $value) {
            $formattedProps[] = "$key=$value";
        }
        $propsString = implode(',', $formattedProps);
        
        return [
            'action' => "dp_savedonor($propsString)",
        ];
    }
}
