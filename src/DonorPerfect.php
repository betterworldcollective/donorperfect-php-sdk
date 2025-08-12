<?php

namespace DonorPerfect;

use DonorPerfect\Authentications\DonorPerfectToken;
use DonorPerfect\Requests\CallSqlRequest;
use DonorPerfect\Requests\Donor\SaveDonor;
use DonorPerfect\Requests\Gift\SaveGift;
use DonorPerfect\Requests\TestConnection;
use DonorPerfect\Responses\DonorPerfectResponse;
use Exception;
use Saloon\Contracts\Authenticator;
use Saloon\Http\Connector;
use Saloon\Http\Response;
use SimpleXMLElement;

class DonorPerfect extends Connector
{
    /**
     * SDK name constant
     */
    public const SDK_NAME = 'DonorPerfect PHP SDK';

    /**
     * API base URL constant
     */
    public const API_BASE_URL = 'https://www.donorperfect.net/prod';

    /**
     * API key header constant
     */
    public const API_KEY_HEADER = 'DP-API-Key';

    /**
     * Default content type constant
     */
    public const CONTENT_TYPE = 'application/xml';

    /**
     * Default accept header constant
     */
    public const ACCEPT_HEADER = 'application/xml';

    /**
     * API key for authentication
     */
    public string $apiKey;

    /**
     * Define the custom response
     *
     * @var class-string<Response>|null
     */
    protected ?string $response = DonorPerfectResponse::class;

    /**
     * Constructor
     */
    public function __construct(string $apiKey)
    {
        // URL decode the API key to prevent double-encoding when used as query parameter
        $this->apiKey = urldecode($apiKey);
    }

    /**
     * Resolve the base URL of the service.
     */
    public function resolveBaseUrl(): string
    {
        return self::API_BASE_URL;
    }

    /**
     * Define default headers
     *
     * @return string[]
     */
    protected function defaultHeaders(): array
    {
        return [
            'Accept' => self::ACCEPT_HEADER,
            'Content-Type' => self::CONTENT_TYPE,
        ];
    }

    /**
     * Get default query parameters
     *
     * @return array<string, mixed>
     */
    protected function defaultQuery(): array
    {
        return [
            'apikey' => $this->apiKey,
        ];
    }

    /**
     * Get the authenticator for the request
     */
    public function getAuthenticator(): ?Authenticator
    {
        return new DonorPerfectToken($this->apiKey);
    }

    /**
     * Test connection using SQL query
     */
    public function testConnection(): bool
    {
        try {
            $request = new TestConnection;
            $response = $this->send($request);

            // Check if response contains success
            $body = $response->body();
            if (str_contains($body, 'success') && str_contains($body, 'false')) {
                return false;
            }
            // If we get a valid XML response with records, it's successful
            if (str_contains($body, '<record>')) {
                return true;
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Execute SQL query
     */
    public function executeSql(string $sql): mixed
    {
        $request = new CallSqlRequest($sql);
        $response = $this->send($request);

        return $response->xmlArray();
    }

    /**
     * Save donor using saveDonor
     */
    /**
     * @param  array<string, mixed>  $data
     */
    public function saveDonor(array $data): int
    {
        $request = new SaveDonor($data);
        $response = $this->send($request);
        $xml = $response->xml();
        if ($xml instanceof SimpleXMLElement && isset($xml->record)) {
            // Extract the donor_id from the record field
            $record = $xml->record;
            if (isset($record->field) && isset($record->field['value'])) {
                return (int) $record->field['value'];
            }
        }

        return 0;
    }

    /**
     * Save gift using saveGift
     */
    /**
     * @param  array<string, mixed>  $data
     */
    public function saveGift(array $data): int
    {
        $request = new SaveGift($data);
        $response = $this->send($request);
        $xml = $response->xml();
        if ($xml instanceof SimpleXMLElement && isset($xml->record)) {
            // Extract the gift_id from the record field
            $record = $xml->record;
            if (isset($record->field) && isset($record->field['value'])) {
                return (int) $record->field['value'];
            }
        }

        return 0;
    }
}
