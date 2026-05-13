<?php

namespace DonorPerfect\Resources;

use DateTimeInterface;
use DonorPerfect\DonorPerfect;
use DonorPerfect\Exceptions\DonorPerfectException;
use DonorPerfect\Requests\Flag\SaveFlag;
use Saloon\Http\BaseResource;
use SimpleXMLElement;

class FlagResource extends BaseResource
{
    /**
     * Apply a flag to a donor. Additive — never deletes existing flags.
     *
     * Use the additive `dp_saveflag_xml` action exclusively. The DP-side
     * `dp_delflags_xml` wipes ALL flags for a donor and is intentionally NOT
     * exposed by this SDK to prevent accidental data loss in sync listeners.
     *
     * @return array<string, mixed>
     *
     * @throws DonorPerfectException when DP returns an empty or malformed response
     */
    public function save(int $donorId, string $flagCode, ?DateTimeInterface $flagDate = null): array
    {
        $connector = $this->connector;
        assert($connector instanceof DonorPerfect);

        $properties = [
            'matching_id' => $donorId,
            'flag' => $flagCode,
        ];

        if ($flagDate !== null) {
            $properties['flag_date'] = $flagDate->format('Y-m-d');
        }

        $response = $connector->send(new SaveFlag($properties));

        $body = $response->body();
        $xml = $response->xml();

        // dp_saveflag_xml success returns <result><record>…</record></result>.
        // DP rejections (e.g. permissions) come back as <result><field name="success" value="false" reason="…"/></result>
        // — same wrapper, no <record>. Treat any response missing <record> as a rejection.
        if (! $xml instanceof SimpleXMLElement || ! isset($xml->record)) {
            throw new DonorPerfectException('DonorPerfect rejected SaveFlag: '.$body);
        }

        return $response->xmlArray();
    }
}
