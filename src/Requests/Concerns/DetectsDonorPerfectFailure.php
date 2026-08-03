<?php

namespace DonorPerfect\Requests\Concerns;

use DonorPerfect\Exceptions\DonorPerfectException;
use DonorPerfect\Exceptions\UnauthorizedException;
use Saloon\Http\Response;
use SimpleXMLElement;
use Throwable;

/**
 * Failure detection for DonorPerfect's write procedures (dp_savedonor, dp_savegift,
 * dp_saveflag_xml, dp_save_udf_xml).
 *
 * DP answers HTTP 200 for everything, so `AlwaysThrowOnErrors` alone never fires.
 * There are three response shapes, and only the first is a success:
 *
 *   success   <result><record><field value="297"/></record></result>
 *   envelope  <result><field name="success" value="false" reason="…"/><error>…</error></result>
 *   in-record <result><record><field value="…this field does not exist"/></record></result>
 *
 * The `reason` on the envelope is almost always the misleading
 * "user not authorized for this api call." — DP's blanket mask for any SQL-level
 * failure (duplicate key, FK violation, truncation, bad param signature). Per the DP
 * XML API manual (p.11) API keys carry no per-procedure permissions at all, so that
 * text is never evidence of a permissions problem. A genuinely bad key answers with
 * `Invalid token.` or `login failed` and no `<error>` element — that, and only that,
 * raises UnauthorizedException.
 */
trait DetectsDonorPerfectFailure
{
    public function hasRequestFailed(Response $response): ?bool
    {
        $xml = $this->donorPerfectXml($response);

        if (! $xml instanceof SimpleXMLElement) {
            return true;
        }

        if ($this->isErrorEnvelope($xml)) {
            return true;
        }

        if (! isset($xml->record)) {
            return true;
        }

        // Write procs echo the affected row id. Anything non-numeric in that slot is
        // DP reporting a problem inside a well-formed <record>, and an empty value is
        // a silent no-op (e.g. @donor_id=null) — neither is a success.
        return ! is_numeric($this->recordValue($xml));
    }

    public function getRequestException(Response $response, ?Throwable $senderException): ?Throwable
    {
        $xml = $this->donorPerfectXml($response);
        $action = $this->donorPerfectAction();

        if (! $xml instanceof SimpleXMLElement) {
            return new DonorPerfectException(
                "DonorPerfect returned an unreadable response for {$action}: ".$response->body(),
                previous: $senderException
            );
        }

        $reason = trim((string) ($xml->field['reason'] ?? ''));
        $detail = trim((string) ($xml->error ?? ''));

        if ($this->isAuthenticationFailure($reason, $detail)) {
            return new UnauthorizedException(
                "DonorPerfect rejected the API key for {$action}: {$reason}"
            );
        }

        if ($this->isErrorEnvelope($xml)) {
            $summary = trim($detail.($reason !== '' ? " — {$reason}" : '')) ?: 'unknown error';

            return new DonorPerfectException(
                "DonorPerfect rejected {$action} ({$summary}). This is a SQL-level failure "
                .'— a duplicate row, a missing or misnamed parameter, an unknown id, or an '
                .'oversized value — not an API key permission problem.'
            );
        }

        $recordValue = $this->recordValue($xml);

        if ($recordValue === '') {
            return new DonorPerfectException(
                "DonorPerfect accepted {$action} but changed nothing (empty id in response). "
                .'Check that every required parameter has a non-null value.'
            );
        }

        return new DonorPerfectException("DonorPerfect rejected {$action}: {$recordValue}");
    }

    /**
     * The DP procedure name, read off the request's own `action` query parameter.
     */
    protected function donorPerfectAction(): string
    {
        $query = $this->query()->all();

        return is_string($query['action'] ?? null) ? $query['action'] : static::class;
    }

    private function donorPerfectXml(Response $response): SimpleXMLElement|false|null
    {
        try {
            $xml = $response->xml();
        } catch (Throwable) {
            return null;
        }

        return $xml instanceof SimpleXMLElement ? $xml : null;
    }

    private function isErrorEnvelope(SimpleXMLElement $xml): bool
    {
        return isset($xml->field['value']) && (string) $xml->field['value'] === 'false';
    }

    /**
     * Genuine credential rejections carry no `<error>` element — DP never got as far
     * as running SQL.
     */
    private function isAuthenticationFailure(string $reason, string $detail): bool
    {
        if ($detail !== '') {
            return false;
        }

        $reason = strtolower($reason);

        return str_contains($reason, 'invalid token') || str_contains($reason, 'login failed');
    }

    private function recordValue(SimpleXMLElement $xml): string
    {
        return trim((string) ($xml->record->field['value'] ?? ''));
    }
}
