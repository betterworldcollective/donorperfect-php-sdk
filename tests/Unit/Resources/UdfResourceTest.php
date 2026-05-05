<?php

use DonorPerfect\DonorPerfect;
use DonorPerfect\Exceptions\InvalidDataException;
use DonorPerfect\Requests\Udf\SaveUdf;
use DonorPerfect\Resources\UdfResource;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

it('exposes a udfs() accessor on the connector', function () {
    expect((new DonorPerfect('k'))->udfs())->toBeInstanceOf(UdfResource::class);
});

it('builds a dp_save_udf_xml call with @matching_id, @field_name, @data_type, @field_value', function () {
    $mockClient = new MockClient([
        SaveUdf::class => MockResponse::make('<?xml version="1.0"?><result><record><field name="success" value="true"/></record></result>'),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    $connector->udfs()->save(12345, 'PRONOUN_UDF', 'C', 'they/them');

    $mockClient->assertSent(function (SaveUdf $request): bool {
        $action = $request->query()->get('action');
        $params = $request->query()->get('params');

        return $action === 'dp_save_udf_xml'
            && str_contains($params, '@matching_id=12345')
            && str_contains($params, "@field_name='PRONOUN_UDF'")
            && str_contains($params, "@data_type='C'")
            && str_contains($params, "@field_value='they/them'");
    });
});

it('serializes a null UDF value as null (not the literal string "null")', function () {
    $mockClient = new MockClient([
        SaveUdf::class => MockResponse::make('<?xml version="1.0"?><result/>'),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    $connector->udfs()->save(12345, 'PRONOUN_UDF', 'C', null);

    $mockClient->assertSent(function (SaveUdf $request): bool {
        return str_contains($request->query()->get('params'), '@field_value=null');
    });
});

it('throws InvalidDataException for an unsupported data_type', function () {
    (new DonorPerfect('k'))->udfs()->save(1, 'F', 'X', 'v');
})->throws(InvalidDataException::class);

it('accepts every documented DPFIELDS data_type', function (string $dataType) {
    $mockClient = new MockClient([
        SaveUdf::class => MockResponse::make('<?xml version="1.0"?><result/>'),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    $connector->udfs()->save(1, 'F', $dataType, 'v');

    $mockClient->assertSent(function (SaveUdf $request) use ($dataType): bool {
        return str_contains($request->query()->get('params'), "@data_type='{$dataType}'");
    });
})->with(['C', 'D', 'M', 'N']);
