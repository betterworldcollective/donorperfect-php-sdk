<?php

namespace DonorPerfect\Requests\Donor;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasXmlBody;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;

class SaveDonor extends Request implements HasBody
{
    use AlwaysThrowOnErrors, HasXmlBody;

    protected Method $method = Method::GET;

    /**
     * @param array<string, mixed> $properties
     */
    public function __construct(protected array $properties) {}

    public function resolveEndpoint(): string
    {
        return '/xmlrequest.asp';
    }

    protected function defaultQuery(): array
    {
        $action = 'dp_savedonor(' . implode(',', array_map(fn($key, $value) => "{$key}='{$value}'", array_keys($this->properties), $this->properties)) . ')';
        
        return [
            'action' => $action,
        ];
    }
}
