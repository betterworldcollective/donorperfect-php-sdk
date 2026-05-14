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

it('builds dp_save_udf_xml with all 7 params per docs p.39 (C type routes value to @char_value)', function () {
    $mockClient = new MockClient([
        SaveUdf::class => MockResponse::make('<?xml version="1.0"?><result><record><field name="success" value="true"/></record></result>'),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    $connector->udfs()->save(12345, 'PRONOUN_UDF', 'C', 'they/them', 'BetterWorld');

    $mockClient->assertSent(function (SaveUdf $request): bool {
        $params = $request->query()->get('params');

        return $request->query()->get('action') === 'dp_save_udf_xml'
            && str_contains($params, '@matching_id=12345')
            && str_contains($params, "@field_name='PRONOUN_UDF'")
            && str_contains($params, "@data_type='C'")
            && str_contains($params, "@char_value='they/them'")
            && str_contains($params, '@date_value=null')
            && str_contains($params, '@number_value=null')
            && str_contains($params, "@user_id='BetterWorld'");
    });
});

it('routes Date values to @date_value (others null)', function () {
    $mockClient = new MockClient([
        SaveUdf::class => MockResponse::make('<?xml version="1.0"?><result><record><field name="success" value="true"/></record></result>'),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    $connector->udfs()->save(1, 'BIRTHDATE', 'D', '03/15/1990');

    $mockClient->assertSent(function (SaveUdf $request): bool {
        $params = $request->query()->get('params');

        return str_contains($params, "@date_value='03/15/1990'")
            && str_contains($params, '@char_value=null')
            && str_contains($params, '@number_value=null');
    });
});

it('routes Number values to @number_value as bare numerics (DP rejects quoted numerics)', function () {
    $mockClient = new MockClient([
        SaveUdf::class => MockResponse::make('<?xml version="1.0"?><result><record><field name="success" value="true"/></record></result>'),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    $connector->udfs()->save(1, 'INCOME', 'N', '50000');
    $connector->udfs()->save(1, 'AMOUNT', 'N', '99.50');

    $sentRequests = $mockClient->getRecordedResponses();
    $intParams = $sentRequests[0]->getPendingRequest()->query()->get('params');
    $floatParams = $sentRequests[1]->getPendingRequest()->query()->get('params');

    expect($intParams)->toContain('@number_value=50000')
        ->and($intParams)->not->toContain("@number_value='50000'");

    expect($floatParams)->toContain('@number_value=99.5')
        ->and($floatParams)->not->toContain("@number_value='99.5'");
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
