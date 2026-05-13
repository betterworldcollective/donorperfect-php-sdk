<?php

use DonorPerfect\DonorPerfect;
use DonorPerfect\Exceptions\DonorPerfectException;
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

it('throws InvalidDataException for an unsupported data_type', function () {
    (new DonorPerfect('k'))->udfs()->save(1, 'F', 'X', 'v');
})->throws(InvalidDataException::class);

it('rejects the legacy M (money) data_type — money UDFs route through N per dp_save_udf_xml', function () {
    (new DonorPerfect('k'))->udfs()->save(1, 'AMOUNT_UDF', 'M', '100.00');
})->throws(InvalidDataException::class);

it('throws DonorPerfectException when DP returns an empty body', function () {
    $mockClient = new MockClient([
        SaveUdf::class => MockResponse::make(''),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    $connector->udfs()->save(1, 'PRONOUN_UDF', 'C', 'they/them');
})->throws(DonorPerfectException::class, 'DonorPerfect rejected SaveUdf');

it('throws DonorPerfectException when DP returns a success=false rejection (no <record>)', function () {
    $mockClient = new MockClient([
        SaveUdf::class => MockResponse::make(
            '<?xml version="1.0"?><result><field name="success" value="false" reason="user not authorized for this api call."/></result>'
        ),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    $connector->udfs()->save(1, 'PRONOUN_UDF', 'C', 'they/them');
})->throws(DonorPerfectException::class, 'DonorPerfect rejected SaveUdf');

it('throws DonorPerfectException when DP returns malformed XML', function () {
    $mockClient = new MockClient([
        SaveUdf::class => MockResponse::make('not xml at all'),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    $connector->udfs()->save(1, 'PRONOUN_UDF', 'C', 'they/them');
})->throws(DonorPerfectException::class, 'DonorPerfect rejected SaveUdf');

it('returns the parsed array on a valid response', function () {
    $mockClient = new MockClient([
        SaveUdf::class => MockResponse::make('<?xml version="1.0"?><result><record><field name="success" value="true"/></record></result>'),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    expect($connector->udfs()->save(1, 'PRONOUN_UDF', 'C', 'they/them'))->toBeArray();
});
