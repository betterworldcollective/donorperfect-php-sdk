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

    protected Method $method = Method::POST;

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
        // Build the params string with @ prefix and proper formatting
        $params = [];
        foreach ($this->properties as $key => $value) {
            if ($value === null) {
                $params[] = "@{$key}=null";
            } elseif (is_numeric($value)) {
                $params[] = "@{$key}={$value}";
            } else {
                $params[] = "@{$key}='{$value}'";
            }
        }
        
        return [
            'action' => 'dp_savedonor',
            'params' => implode(', ', $params),
        ];
    }

    protected function defaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];
    }
}
