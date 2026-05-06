<?php

use DonorPerfect\DonorPerfect;
use DonorPerfect\Exceptions\InvalidDataException;
use DonorPerfect\Requests\CallSqlRequest;
use DonorPerfect\Resources\CodeResource;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

it('exposes a codes() accessor on the connector', function () {
    $connector = new DonorPerfect('test-api-key');

    expect($connector->codes())->toBeInstanceOf(CodeResource::class);
});

it('builds a dpcodes SELECT for a valid field_name and hits /xmlrequest.asp', function () {
    $mockClient = new MockClient([
        CallSqlRequest::class => MockResponse::make(
            <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <result>
                <record>
                    <field name="CODE" value="GENERAL"/>
                    <field name="DESCRIPTION" value="General Fund"/>
                    <field name="INACTIVE" value="0"/>
                </record>
                <record>
                    <field name="CODE" value="LEGACY"/>
                    <field name="DESCRIPTION" value="Legacy Fund"/>
                    <field name="INACTIVE" value="1"/>
                </record>
            </result>
            XML
        ),
    ]);

    $connector = new DonorPerfect('test-api-key');
    $connector->withMockClient($mockClient);

    $rows = $connector->codes()->list('CAMPAIGN');

    $mockClient->assertSent(function (CallSqlRequest $request): bool {
        $sql = $request->query()->get('action');

        return $request->resolveEndpoint() === '/xmlrequest.asp'
            && $sql === "SELECT CODE, DESCRIPTION, INACTIVE FROM dpcodes WHERE FIELD_NAME = 'CAMPAIGN'";
    });

    expect($rows)->toHaveCount(2)
        ->and($rows[0])->toMatchArray([
            'code' => 'GENERAL',
            'description' => 'General Fund',
            'inactive' => false,
        ])
        ->and($rows[1])->toMatchArray([
            'code' => 'LEGACY',
            'description' => 'Legacy Fund',
            'inactive' => true,
        ]);
});

it('returns an empty array when DP returns no records', function () {
    $mockClient = new MockClient([
        CallSqlRequest::class => MockResponse::make(
            '<?xml version="1.0" encoding="UTF-8"?><result/>'
        ),
    ]);

    $connector = new DonorPerfect('test-api-key');
    $connector->withMockClient($mockClient);

    expect($connector->codes()->list('GL_CODE'))->toBe([]);
});

it('throws InvalidDataException for an unknown field_name', function () {
    $connector = new DonorPerfect('test-api-key');

    $connector->codes()->list("CAMPAIGN'; DROP TABLE dpcodes; --");
})->throws(InvalidDataException::class);
