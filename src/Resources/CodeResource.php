<?php

namespace DonorPerfect\Resources;

use DonorPerfect\DonorPerfect;
use DonorPerfect\Exceptions\InvalidDataException;
use DonorPerfect\Requests\CallSqlRequest;
use Saloon\Http\BaseResource;
use SimpleXMLElement;

class CodeResource extends BaseResource
{
    private const ALLOWED_FIELD_NAMES = [
        'CAMPAIGN',
        'GL_CODE',
        'SOLICIT',
        'EMAIL_TYPE',
        'PHONE_TYPE',
        'ADDRESS_TYPE',
        'FLAG',
        'FLAGS',
    ];

    /**
     * Fetch dpcodes rows for the given DP field_name.
     *
     * Allowed field names mirror the BB-parity dropdowns; the allowlist defends
     * against SQL injection even though the value is internal.
     *
     * @return array<int, array{code: string, description: ?string, inactive: bool}>
     *
     * @throws InvalidDataException when $fieldName is not in the allowlist
     */
    public function list(string $fieldName): array
    {
        if (! in_array($fieldName, self::ALLOWED_FIELD_NAMES, true)) {
            throw new InvalidDataException("Unsupported dpcodes field_name: {$fieldName}");
        }

        $connector = $this->connector;
        assert($connector instanceof DonorPerfect);

        $sql = "SELECT CODE, DESCRIPTION, INACTIVE FROM dpcodes WHERE FIELD_NAME = '{$fieldName}'";
        $response = $connector->send(new CallSqlRequest($sql));

        $xml = $response->xml();

        if (! $xml instanceof SimpleXMLElement || ! isset($xml->record)) {
            return [];
        }

        $rows = [];
        foreach ($xml->record as $record) {
            $rows[] = $this->normalizeRecord($record);
        }

        return $rows;
    }

    /**
     * @return array{code: string, description: ?string, inactive: bool}
     */
    private function normalizeRecord(SimpleXMLElement $record): array
    {
        $row = ['code' => '', 'description' => null, 'inactive' => false];

        foreach ($record->field ?? [] as $field) {
            $name = (string) ($field['name'] ?? '');
            $value = (string) ($field['value'] ?? '');

            switch ($name) {
                case 'CODE':
                    $row['code'] = $value;
                    break;
                case 'DESCRIPTION':
                    $row['description'] = $value === '' ? null : $value;
                    break;
                case 'INACTIVE':
                    $row['inactive'] = $value === '1' || $value == 'Y' || strcasecmp($value, 'true') === 0;
                    break;
            }
        }

        return $row;
    }
}
