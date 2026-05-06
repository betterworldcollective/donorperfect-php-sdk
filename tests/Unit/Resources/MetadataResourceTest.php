<?php

use DonorPerfect\DonorPerfect;
use DonorPerfect\Exceptions\InvalidDataException;
use DonorPerfect\Requests\CallSqlRequest;
use DonorPerfect\Resources\MetadataResource;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

it('exposes a metadata() accessor on the connector', function () {
    expect((new DonorPerfect('k'))->metadata())->toBeInstanceOf(MetadataResource::class);
});

it('reads DPFIELDS for DP and DPGIFT by default and parses each row', function () {
    $mockClient = new MockClient([
        CallSqlRequest::class => MockResponse::make(<<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <result>
                <record>
                    <field name="TABLE_NAME" value="DP"/>
                    <field name="FIELD_NAME" value="PRONOUN_UDF"/>
                    <field name="PROMPT" value="Pronoun"/>
                    <field name="DATA_TYPE" value="C"/>
                </record>
                <record>
                    <field name="TABLE_NAME" value="DPGIFT"/>
                    <field name="FIELD_NAME" value="SOURCE_UDF"/>
                    <field name="PROMPT" value="Campaign Source"/>
                    <field name="DATA_TYPE" value="C"/>
                </record>
            </result>
            XML
        ),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    $rows = $connector->metadata()->list();

    $mockClient->assertSent(function (CallSqlRequest $request): bool {
        $sql = $request->query()->get('action');

        return str_starts_with($sql, 'SELECT TABLE_NAME, FIELD_NAME, PROMPT, DATA_TYPE FROM DPFIELDS')
            && str_contains($sql, "TABLE_NAME IN ('DP','DPGIFT')");
    });

    expect($rows)->toHaveCount(2)
        ->and($rows[0])->toMatchArray([
            'table_name' => 'DP',
            'field_name' => 'PRONOUN_UDF',
            'prompt' => 'Pronoun',
            'data_type' => 'C',
        ])
        ->and($rows[1]['table_name'])->toBe('DPGIFT');
});

it('lets the caller scope to a single table', function () {
    $mockClient = new MockClient([
        CallSqlRequest::class => MockResponse::make('<?xml version="1.0"?><result/>'),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    $connector->metadata()->list(['DPGIFT']);

    $mockClient->assertSent(function (CallSqlRequest $request): bool {
        return str_contains($request->query()->get('action'), "TABLE_NAME IN ('DPGIFT')");
    });
});

it('rejects unknown table names', function () {
    (new DonorPerfect('k'))->metadata()->list(['DROP_TABLE']);
})->throws(InvalidDataException::class);
