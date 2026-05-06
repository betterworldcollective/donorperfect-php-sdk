<?php

namespace DonorPerfect\Resources;

use DonorPerfect\DonorPerfect;
use DonorPerfect\Exceptions\InvalidDataException;
use DonorPerfect\Requests\CallSqlRequest;
use Saloon\Http\BaseResource;
use SimpleXMLElement;

class MetadataResource extends BaseResource
{
    private const ALLOWED_TABLE_NAMES = ['DP', 'DPGIFT', 'PLEDGE'];

    /**
     * Read DPFIELDS rows describing the org's UDFs.
     *
     * @param  list<string>  $tableNames  defaults to donor + gift UDFs
     * @return array<int, array{table_name: string, field_name: string, prompt: ?string, data_type: string}>
     *
     * @throws InvalidDataException when any $tableNames entry is not in the allowlist
     */
    public function list(array $tableNames = ['DP', 'DPGIFT']): array
    {
        if ($tableNames === []) {
            throw new InvalidDataException('At least one DPFIELDS TABLE_NAME is required');
        }

        foreach ($tableNames as $tableName) {
            if (! in_array($tableName, self::ALLOWED_TABLE_NAMES, true)) {
                throw new InvalidDataException("Unsupported DPFIELDS table_name: {$tableName}");
            }
        }

        $connector = $this->connector;
        assert($connector instanceof DonorPerfect);

        $inList = "'".implode("','", $tableNames)."'";
        $sql = "SELECT TABLE_NAME, FIELD_NAME, PROMPT, DATA_TYPE FROM DPFIELDS WHERE TABLE_NAME IN ({$inList})";

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
     * @return array{table_name: string, field_name: string, prompt: ?string, data_type: string}
     */
    private function normalizeRecord(SimpleXMLElement $record): array
    {
        $row = ['table_name' => '', 'field_name' => '', 'prompt' => null, 'data_type' => ''];

        foreach ($record->field ?? [] as $field) {
            $name = (string) ($field['name'] ?? '');
            $value = (string) ($field['value'] ?? '');

            switch ($name) {
                case 'TABLE_NAME':
                    $row['table_name'] = $value;
                    break;
                case 'FIELD_NAME':
                    $row['field_name'] = $value;
                    break;
                case 'PROMPT':
                    $row['prompt'] = $value === '' ? null : $value;
                    break;
                case 'DATA_TYPE':
                    $row['data_type'] = $value;
                    break;
            }
        }

        return $row;
    }
}
