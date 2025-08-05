<?php

namespace DonorPerfect;

use DonorPerfect\Requests\CallSqlRequest;
use DonorPerfect\Requests\Donor\SaveDonor;
use DonorPerfect\Requests\Gift\SaveGift;
use DonorPerfect\Requests\TestConnection;
use DonorPerfect\Responses\DonorPerfectResponse;
use Exception;
use Saloon\Http\Connector;
use Saloon\Http\Response;

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
        $this->apiKey = $apiKey;
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
     * Test connection using SQL query
     */
    public function testConnection(): bool
    {
        try {
            $request = new TestConnection;
            $response = $this->send($request);

            // Check if response contains success
            $body = $response->body();
            if (strpos($body, 'success') !== false && strpos($body, 'false') !== false) {
                return false;
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

        return $response->xml();
    }

    /**
     * Save donor using saveDonor
     */
        /**
     * @param array<string, mixed> $data
     */
    public function saveDonor(array $data): int
    {
        $request = new SaveDonor($data);
        $response = $this->send($request);

        return (int) $response->xml()->donor_id ?? 0;
    }

    /**
     * Save gift using saveGift
     */
        /**
     * @param array<string, mixed> $data
     */
    public function saveGift(array $data): int
    {
        $request = new SaveGift($data);
        $response = $this->send($request);

        return (int) $response->xml()->gift_id ?? 0;
    }
}
