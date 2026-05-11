<?php

namespace DonorPerfect\Resources;

use DonorPerfect\DonorPerfect;
use DonorPerfect\Exceptions\DonorPerfectException;
use DonorPerfect\Exceptions\InvalidDataException;
use DonorPerfect\Requests\Udf\SaveUdf;
use Saloon\Http\BaseResource;

class UdfResource extends BaseResource
{
    private const ALLOWED_DATA_TYPES = ['C', 'D', 'M', 'N'];

    /**
     * Write a single UDF value to a DonorPerfect record (donor or gift).
     *
     * $matchingId is donor_id when the UDF lives on the DP table, or gift_id
     * when on DPGIFT — DP routes by data_type + the UDF's own table.
     *
     * @return array<string, mixed>
     *
     * @throws InvalidDataException when $dataType is not one of C/D/M/N
     * @throws DonorPerfectException when DP returns an empty or malformed response
     */
    public function save(int $matchingId, string $fieldName, string $dataType, ?string $value): array
    {
        if (! in_array($dataType, self::ALLOWED_DATA_TYPES, true)) {
            throw new InvalidDataException("Unsupported DPFIELDS data_type: {$dataType}");
        }

        $connector = $this->connector;
        assert($connector instanceof DonorPerfect);

        $response = $connector->send(new SaveUdf([
            'matching_id' => $matchingId,
            'field_name' => $fieldName,
            'data_type' => $dataType,
            'field_value' => $value,
        ]));

        if ($response->xml() === false) {
            throw new DonorPerfectException('DonorPerfect rejected SaveUdf: '.$response->body());
        }

        return $response->xmlArray();
    }
}
