<?php

namespace DonorPerfect\Responses;

use Saloon\Http\Response;

class DonorPerfectResponse extends Response
{
    /**
     * Get the response as XML
     */
    public function xml(mixed ...$arguments): \SimpleXMLElement|bool
    {
        $body = $this->body();
        if (empty($body)) {
            return false;
        }

        // libxml emits PHP warnings on malformed XML by default. Surface the failure
        // via the bool return value instead so callers (and tests) stay clean.
        $previous = libxml_use_internal_errors(true);
        try {
            $xml = simplexml_load_string($body);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        return $xml;
    }

    /**
     * Get the response as array from XML
     *
     * @return array<string, mixed>
     */
    public function xmlArray(): array
    {
        $xml = $this->xml();
        if (! $xml) {
            return [];
        }

        $jsonString = json_encode($xml);
        if ($jsonString === false) {
            return [];
        }

        $result = json_decode($jsonString, true);
        if (! is_array($result)) {
            return [];
        }

        /** @var array<string, mixed> $result */
        return $result;
    }
}
