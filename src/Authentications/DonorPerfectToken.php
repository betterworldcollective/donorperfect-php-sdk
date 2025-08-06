<?php

namespace DonorPerfect\Authentications;

use Saloon\Contracts\Authenticator;
use Saloon\Http\PendingRequest;
use Saloon\Traits\Auth\AuthenticatesRequests;

class DonorPerfectToken implements Authenticator
{
    use AuthenticatesRequests;

    public string $apiKey;

    public function __construct(string $apiKey)
    {
        // URL decode the API key to prevent double-encoding when used as query parameter
        $this->apiKey = urldecode($apiKey);
    }

    public function set(PendingRequest $pendingRequest): void
    {
        // API key is passed as query parameter, not header
        $pendingRequest->query()->add('apikey', $this->apiKey);
    }
}
