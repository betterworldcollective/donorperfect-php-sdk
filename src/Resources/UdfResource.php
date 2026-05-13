<?php

namespace DonorPerfect\Resources;

use DonorPerfect\DonorPerfect;
use DonorPerfect\Exceptions\DonorPerfectException;
use DonorPerfect\Exceptions\InvalidDataException;
use DonorPerfect\Requests\Udf\SaveUdf;
use Saloon\Http\BaseResource;
use SimpleXMLElement;

class UdfResource extends BaseResource
{
    /**
     * Per DonorPerfect XML API docs (dp_save_udf_xml, p.39): only C/D/N are valid.
     * `M` (money) was previously allowed in error — money UDFs map to `N` (numeric)
     * and route through the @number_value parameter. Removed in 0.3.4.
     */
    private const ALLOWED_DATA_TYPES = ['C', 'D', 'N'];

    /**
     * Write a single UDF value to a DonorPerfect record (donor or gift).
     *
     * $matchingId is donor_id when the UDF lives on the DP table, or gift_id
     * when on DPGIFT — DP routes by data_type + the UDF's own table.
     *
     * @return array<string, mixed>
     *
     * @throws InvalidDataException when $dataType is not one of C/D/N
     * @throws DonorPerfectException when DP returns an empty or malformed response
     */
    public function save(int $matchingId, string $fieldName, string $dataType, ?string $value): array
    {
        if (! in_array($dataType, self::ALLOWED_DATA_TYPES, true)) {
            throw new InvalidDataException("Unsupported dp_save_udf_xml data_type: {$dataType}");
        }

        $connector = $this->connector;
        assert($connector instanceof DonorPerfect);

        $response = $connector->send(new SaveUdf([
            'matching_id' => $matchingId,
            'field_name' => $fieldName,
            'data_type' => $dataType,
            'field_value' => $value,
        ]));

        $body = $response->body();
        $xml = $response->xml();

        // dp_save_udf_xml success returns <result><record><field … value="<id>"/></record></result>.
        // DP rejections (e.g. permissions) come back as <result><field name="success" value="false" reason="…"/></result>
        // — same wrapper, no <record>. Treat any response missing <record> as a rejection.
        if (! $xml instanceof SimpleXMLElement || ! isset($xml->record)) {
            throw new DonorPerfectException('DonorPerfect rejected SaveUdf: '.$body);
        }

        return $response->xmlArray();
    }
}
