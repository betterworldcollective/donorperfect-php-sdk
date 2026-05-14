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
    private const ALLOWED_DATA_TYPES = ['C', 'D', 'N'];

    /**
     * Write a single UDF value to a DonorPerfect record (donor or gift).
     *
     * Per DP XML API docs (dp_save_udf_xml, p.39): seven required params —
     * @matching_id, @field_name, @data_type, @char_value, @date_value,
     * @number_value, @user_id. The value routes by data_type:
     *   C → @char_value (others null), D → @date_value, N → @number_value.
     *
     * @return array<string, mixed>
     *
     * @throws InvalidDataException when $dataType is not one of C/D/N
     * @throws DonorPerfectException when DP returns an empty or malformed response
     */
    public function save(int $matchingId, string $fieldName, string $dataType, ?string $value, string $userId = 'BetterWorld'): array
    {
        if (! in_array($dataType, self::ALLOWED_DATA_TYPES, true)) {
            throw new InvalidDataException("Unsupported dp_save_udf_xml data_type: {$dataType}");
        }

        $connector = $this->connector;
        assert($connector instanceof DonorPerfect);

        $numberValue = null;
        if ($dataType === 'N' && $value !== null) {
            $numberValue = str_contains($value, '.') ? (float) $value : (int) $value;
        }

        $response = $connector->send(new SaveUdf([
            'matching_id' => $matchingId,
            'field_name' => $fieldName,
            'data_type' => $dataType,
            'char_value' => $dataType === 'C' ? $value : null,
            'date_value' => $dataType === 'D' ? $value : null,
            'number_value' => $numberValue,
            'user_id' => $userId,
        ]));

        $body = $response->body();
        $xml = $response->xml();

        if (! $xml instanceof SimpleXMLElement || ! isset($xml->record)) {
            throw new DonorPerfectException('DonorPerfect rejected SaveUdf: '.$body);
        }

        return $response->xmlArray();
    }
}
