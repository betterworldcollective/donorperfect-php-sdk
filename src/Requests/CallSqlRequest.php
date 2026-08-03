<?php

namespace DonorPerfect\Requests;

use DonorPerfect\Exceptions\DonorPerfectException;
use DonorPerfect\Exceptions\UnauthorizedException;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;
use SimpleXMLElement;
use Throwable;

class CallSqlRequest extends Request
{
    use AlwaysThrowOnErrors;

    protected Method $method = Method::GET;

    public function __construct(protected string $sql) {}

    public function resolveEndpoint(): string
    {
        return '/xmlrequest.asp';
    }

    protected function defaultQuery(): array
    {
        return [
            'action' => $this->sql,
        ];
    }

    /**
     * DP returns HTTP 200 with `<field name="success" value="false"/>` for
     * rejected queries — most commonly specific column projections (use
     * `SELECT *`), non-whitelisted tables, or syntax DP's parser doesn't
     * accept. The reason text ("user not authorized for this api call") is
     * misleading; the `<error>` tag has the real signal.
     */
    public function hasRequestFailed(Response $response): ?bool
    {
        try {
            $xml = $response->xml();
        } catch (Throwable) {
            return null;
        }

        if (! $xml instanceof SimpleXMLElement || ! isset($xml->field['value'])) {
            return null;
        }

        return (string) $xml->field['value'] === 'false';
    }

    public function getRequestException(Response $response, ?Throwable $senderException): ?Throwable
    {
        try {
            $xml = $response->xml();
        } catch (Throwable) {
            $xml = null;
        }

        $reason = $xml instanceof SimpleXMLElement ? trim((string) ($xml->field['reason'] ?? '')) : '';
        $detail = $xml instanceof SimpleXMLElement ? trim((string) ($xml->error ?? '')) : '';

        // A dead or wrong API key answers `Invalid token.` / `login failed` with no
        // <error> element — DP never reached the SQL. Everything else here is a query
        // DP refused to run, whatever the misleading `reason` text says.
        if ($detail === '' && (str_contains(strtolower($reason), 'invalid token') || str_contains(strtolower($reason), 'login failed'))) {
            return new UnauthorizedException("DonorPerfect rejected the API key: {$reason}");
        }

        $summary = trim($detail.($reason !== '' ? " — {$reason}" : '')) ?: 'unknown error';

        return new DonorPerfectException("DonorPerfect rejected SQL ({$summary}): {$this->sql}");
    }
}
