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

        return simplexml_load_string($body);
    }

    /**
     * Get the response as array from XML
     */
    public function xmlArray(): array
    {
        $xml = $this->xml();
        if (! $xml) {
            return [];
        }

        return json_decode(json_encode($xml), true);
    }
}
