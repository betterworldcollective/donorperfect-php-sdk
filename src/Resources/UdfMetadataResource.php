<?php

namespace DonorPerfect\Resources;

use DonorPerfect\DonorPerfect;
use DonorPerfect\Exceptions\InvalidDataException;
use DonorPerfect\Requests\CallSqlRequest;
use Saloon\Http\BaseResource;
use SimpleXMLElement;

class UdfMetadataResource extends BaseResource
{
    private const ALLOWED_TABLE_NAMES = ['DPUDF', 'DPGIFTUDF'];

    /**
     * Columns that are FK joins back to the parent table — never themselves UDFs.
     */
    private const NON_UDF_COLUMNS = ['donor_id', 'gift_id'];

    /**
     * Map SQL Server data types to the C/D/N values dp_save_udf_xml accepts.
     *
     * Per DonorPerfect XML API docs (dp_save_udf_xml, p.39): only C (character),
     * D (date), N (numeric) are valid @data_type values.
     */
    private const SQL_TYPE_TO_DP_TYPE = [
        'char' => 'C',
        'varchar' => 'C',
        'nvarchar' => 'C',
        'text' => 'C',
        'ntext' => 'C',
        'date' => 'D',
        'datetime' => 'D',
        'datetime2' => 'D',
        'smalldatetime' => 'D',
        'int' => 'N',
        'bigint' => 'N',
        'smallint' => 'N',
        'tinyint' => 'N',
        'numeric' => 'N',
        'decimal' => 'N',
        'money' => 'N',
        'smallmoney' => 'N',
        'float' => 'N',
        'real' => 'N',
        'bit' => 'N',
    ];

    /**
     * List the org's user-defined fields for the given UDF table.
     *
     * UDFs live as columns in `DPUDF` (donor UDFs) and `DPGIFTUDF` (gift UDFs) —
     * NOT in the `DPFIELDS` metadata table, which only describes built-in fields.
     * We discover them via `INFORMATION_SCHEMA.COLUMNS` and map SQL types to
     * the C/D/N codes `dp_save_udf_xml` accepts.
     *
     * @return array<int, array{field_name: string, data_type: string}>
     *
     * @throws InvalidDataException when $tableName is not in the allowlist
     */
    public function list(string $tableName): array
    {
        if (! in_array($tableName, self::ALLOWED_TABLE_NAMES, true)) {
            throw new InvalidDataException("Unsupported UDF table_name: {$tableName}");
        }

        $connector = $this->connector;
        assert($connector instanceof DonorPerfect);

        $sql = "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '{$tableName}'";

        $response = $connector->send(new CallSqlRequest($sql));
        $xml = $response->xml();

        if (! $xml instanceof SimpleXMLElement || ! isset($xml->record)) {
            return [];
        }

        $rows = [];
        foreach ($xml->record as $record) {
            $row = $this->normalizeRecord($record);

            if ($row === null) {
                continue;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @return array{field_name: string, data_type: string}|null
     */
    private function normalizeRecord(SimpleXMLElement $record): ?array
    {
        $columnName = null;
        $sqlType = null;

        foreach ($record->field ?? [] as $field) {
            $name = (string) ($field['name'] ?? '');
            $value = (string) ($field['value'] ?? '');

            if ($name === 'COLUMN_NAME') {
                $columnName = $value;
            } elseif ($name === 'DATA_TYPE') {
                $sqlType = strtolower($value);
            }
        }

        if ($columnName === null || $sqlType === null) {
            return null;
        }

        if (in_array($columnName, self::NON_UDF_COLUMNS, true)) {
            return null;
        }

        $dpType = self::SQL_TYPE_TO_DP_TYPE[$sqlType] ?? null;
        if ($dpType === null) {
            return null;
        }

        return [
            'field_name' => $columnName,
            'data_type' => $dpType,
        ];
    }
}
