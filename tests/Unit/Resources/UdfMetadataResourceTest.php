<?php

use DonorPerfect\DonorPerfect;
use DonorPerfect\Exceptions\InvalidDataException;
use DonorPerfect\Requests\CallSqlRequest;
use DonorPerfect\Resources\UdfMetadataResource;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

it('exposes a udfMetadata() accessor on the connector', function () {
    expect((new DonorPerfect('k'))->udfMetadata())->toBeInstanceOf(UdfMetadataResource::class);
});

it('queries INFORMATION_SCHEMA.COLUMNS for the requested UDF table and maps SQL types to DP types', function () {
    $mockClient = new MockClient([
        CallSqlRequest::class => MockResponse::make(<<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <result>
                <record>
                    <field name="COLUMN_NAME" value="donor_id"/>
                    <field name="DATA_TYPE" value="numeric"/>
                </record>
                <record>
                    <field name="COLUMN_NAME" value="MCAT"/>
                    <field name="DATA_TYPE" value="varchar"/>
                </record>
                <record>
                    <field name="COLUMN_NAME" value="BIRTHDATE"/>
                    <field name="DATA_TYPE" value="datetime"/>
                </record>
                <record>
                    <field name="COLUMN_NAME" value="LIFETIME_GIVING"/>
                    <field name="DATA_TYPE" value="money"/>
                </record>
                <record>
                    <field name="COLUMN_NAME" value="DNICK"/>
                    <field name="DATA_TYPE" value="nvarchar"/>
                </record>
            </result>
            XML
        ),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    $rows = $connector->udfMetadata()->list('DPUDF');

    $mockClient->assertSent(function (CallSqlRequest $request): bool {
        return $request->query()->get('action') === "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'DPUDF'";
    });

    // donor_id is filtered out as a non-UDF FK; the rest map varchar/nvarchar→C, datetime→D, money→N
    expect($rows)->toHaveCount(4)
        ->and($rows[0])->toMatchArray(['field_name' => 'MCAT', 'data_type' => 'C'])
        ->and($rows[1])->toMatchArray(['field_name' => 'BIRTHDATE', 'data_type' => 'D'])
        ->and($rows[2])->toMatchArray(['field_name' => 'LIFETIME_GIVING', 'data_type' => 'N'])
        ->and($rows[3])->toMatchArray(['field_name' => 'DNICK', 'data_type' => 'C']);
});

it('filters gift_id when querying DPGIFTUDF', function () {
    $mockClient = new MockClient([
        CallSqlRequest::class => MockResponse::make(<<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <result>
                <record>
                    <field name="COLUMN_NAME" value="gift_id"/>
                    <field name="DATA_TYPE" value="numeric"/>
                </record>
                <record>
                    <field name="COLUMN_NAME" value="EVATT"/>
                    <field name="DATA_TYPE" value="varchar"/>
                </record>
            </result>
            XML
        ),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    $rows = $connector->udfMetadata()->list('DPGIFTUDF');

    expect($rows)->toHaveCount(1)
        ->and($rows[0])->toMatchArray(['field_name' => 'EVATT', 'data_type' => 'C']);
});

it('skips columns whose SQL type cannot be mapped (defensive)', function () {
    $mockClient = new MockClient([
        CallSqlRequest::class => MockResponse::make(<<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <result>
                <record>
                    <field name="COLUMN_NAME" value="WEIRD_BLOB"/>
                    <field name="DATA_TYPE" value="image"/>
                </record>
                <record>
                    <field name="COLUMN_NAME" value="MCAT"/>
                    <field name="DATA_TYPE" value="varchar"/>
                </record>
            </result>
            XML
        ),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    $rows = $connector->udfMetadata()->list('DPUDF');

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['field_name'])->toBe('MCAT');
});

it('returns an empty array when DP returns no records', function () {
    $mockClient = new MockClient([
        CallSqlRequest::class => MockResponse::make('<?xml version="1.0"?><result/>'),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    expect($connector->udfMetadata()->list('DPUDF'))->toBe([]);
});

it('rejects unknown table names', function () {
    (new DonorPerfect('k'))->udfMetadata()->list('DPFIELDS');
})->throws(InvalidDataException::class);

it('rejects table names attempting SQL injection', function () {
    (new DonorPerfect('k'))->udfMetadata()->list("DPUDF'; DROP TABLE foo; --");
})->throws(InvalidDataException::class);
