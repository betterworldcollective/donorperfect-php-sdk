<?php

namespace DonorPerfect;

use DonorPerfect\Authentications\DonorPerfectToken;
use DonorPerfect\Exceptions\DonorPerfectException;
use DonorPerfect\Requests\CallSqlRequest;
use DonorPerfect\Requests\Donor\SaveDonor;
use DonorPerfect\Requests\Gift\SaveGift;
use DonorPerfect\Requests\TestConnection;
use DonorPerfect\Resources\CodeResource;
use DonorPerfect\Resources\FlagResource;
use DonorPerfect\Resources\UdfMetadataResource;
use DonorPerfect\Resources\UdfResource;
use DonorPerfect\Responses\DonorPerfectResponse;
use Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
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
     * Logger used to surface non-throwing failures (e.g. testConnection).
     */
    private LoggerInterface $logger;

    /**
     * Define the custom response
     *
     * @var class-string<Response>|null
     */
    protected ?string $response = DonorPerfectResponse::class;

    /**
     * Constructor
     */
    public function __construct(string $apiKey, ?LoggerInterface $logger = null)
    {
        // URL decode the API key to prevent double-encoding when used as query parameter
        $this->apiKey = urldecode($apiKey);
        $this->logger = $logger ?? new NullLogger;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
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
            $this->logger->error('DonorPerfect testConnection failed: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return false;
        }
    }

    /**
     * Read dpcodes rows for a given field_name (CAMPAIGN, GL_CODE, etc.).
     */
    public function codes(): CodeResource
    {
        return new CodeResource($this);
    }

    /**
     * Write a UDF value to a donor or gift via dp_save_udf_xml.
     */
    public function udfs(): UdfResource
    {
        return new UdfResource($this);
    }

    /**
     * Apply a flag to a donor via dp_saveflag_xml (additive only).
     */
    public function flags(): FlagResource
    {
        return new FlagResource($this);
    }

    /**
     * List the org's user-defined fields (UDFs) for a given UDF table (DPUDF or DPGIFTUDF).
     *
     * Replaces the misnamed `metadata()` accessor (removed in 0.3.4): the previous
     * implementation queried `DPFIELDS` which only contains built-in field metadata,
     * never UDFs. UDFs live as columns on `DPUDF`/`DPGIFTUDF` themselves.
     */
    public function udfMetadata(): UdfMetadataResource
    {
        return new UdfMetadataResource($this);
    }

    /**
     * Execute a raw SQL query against DonorPerfect's `dp_callsql` endpoint.
     * `CallSqlRequest::hasRequestFailed` detects DP's error envelope and throws.
     *
     * @return array<string, mixed>
     *
     * @throws DonorPerfectException when DP returns an error envelope
     */
    public function executeSql(string $sql): array
    {
        return $this->send(new CallSqlRequest($sql))->xmlArray();
    }

    /**
     * Save donor using saveDonor
     *
     * @param  array<string, mixed>  $data
     *
     * @throws DonorPerfectException when DP returns an error or malformed response
     */
    public function saveDonor(array $data): int
    {
        $request = new SaveDonor($data);
        $response = $this->send($request);
        $body = $response->body();
        $xml = $response->xml();

        if (! $xml instanceof SimpleXMLElement || ! isset($xml->record)) {
            throw new DonorPerfectException('DonorPerfect rejected SaveDonor: '.$body);
        }

        $record = $xml->record;
        if (! isset($record->field) || ! isset($record->field['value'])) {
            throw new DonorPerfectException('DonorPerfect SaveDonor returned unexpected response: '.$body);
        }

        return (int) $record->field['value'];
    }

    /**
     * Save gift using saveGift
     *
     * @param  array<string, mixed>  $data
     *
     * @throws DonorPerfectException when DP returns an error or malformed response
     */
    public function saveGift(array $data): int
    {
        $request = new SaveGift($data);
        $response = $this->send($request);
        $body = $response->body();
        $xml = $response->xml();

        if (! $xml instanceof SimpleXMLElement || ! isset($xml->record)) {
            throw new DonorPerfectException('DonorPerfect rejected SaveGift: '.$body);
        }

        $record = $xml->record;
        if (! isset($record->field) || ! isset($record->field['value'])) {
            throw new DonorPerfectException('DonorPerfect SaveGift returned unexpected response: '.$body);
        }

        return (int) $record->field['value'];
    }
}
