<?php

use DonorPerfect\DonorPerfect;
use DonorPerfect\Exceptions\DonorPerfectException;
use DonorPerfect\Requests\Flag\SaveFlag;
use DonorPerfect\Resources\FlagResource;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

it('exposes a flags() accessor on the connector', function () {
    expect((new DonorPerfect('k'))->flags())->toBeInstanceOf(FlagResource::class);
});

it('builds dp_saveflag_xml with @donor_id, @flag, @user_id (per DP docs p.48)', function () {
    $mockClient = new MockClient([
        SaveFlag::class => MockResponse::make('<?xml version="1.0"?><result><record><field name="success" value="true"/></record></result>'),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    $connector->flags()->save(donorId: 9876, flagCode: 'WEB', userId: 'BetterWorld');

    $mockClient->assertSent(function (SaveFlag $request): bool {
        $params = $request->query()->get('params');

        return $request->query()->get('action') === 'dp_saveflag_xml'
            && str_contains($params, '@donor_id=9876')
            && str_contains($params, "@flag='WEB'")
            && str_contains($params, "@user_id='BetterWorld'");
    });
});

it('throws DonorPerfectException when DP returns an empty body', function () {
    $mockClient = new MockClient([
        SaveFlag::class => MockResponse::make(''),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    $connector->flags()->save(1, 'WEB', 'BetterWorld');
})->throws(DonorPerfectException::class, 'DonorPerfect rejected SaveFlag');

it('throws DonorPerfectException when DP returns a success=false rejection (no <record>)', function () {
    $mockClient = new MockClient([
        SaveFlag::class => MockResponse::make(
            '<?xml version="1.0"?><result><field name="success" value="false" reason="user not authorized for this api call."/></result>'
        ),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    $connector->flags()->save(1, 'WEB', 'BetterWorld');
})->throws(DonorPerfectException::class, 'DonorPerfect rejected SaveFlag');

it('throws DonorPerfectException when DP returns malformed XML', function () {
    $mockClient = new MockClient([
        SaveFlag::class => MockResponse::make('not xml at all'),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    $connector->flags()->save(1, 'WEB', 'BetterWorld');
})->throws(DonorPerfectException::class, 'DonorPerfect rejected SaveFlag');

it('returns the parsed array on a valid response', function () {
    $mockClient = new MockClient([
        SaveFlag::class => MockResponse::make('<?xml version="1.0"?><result><record><field name="success" value="true"/></record></result>'),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    expect($connector->flags()->save(1, 'WEB', 'BetterWorld'))->toBeArray();
});
