<?php

use DonorPerfect\DonorPerfect;
use DonorPerfect\Requests\Flag\SaveFlag;
use DonorPerfect\Resources\FlagResource;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

it('exposes a flags() accessor on the connector', function () {
    expect((new DonorPerfect('k'))->flags())->toBeInstanceOf(FlagResource::class);
});

it('builds a dp_saveflag_xml call with @matching_id and @flag', function () {
    $mockClient = new MockClient([
        SaveFlag::class => MockResponse::make('<?xml version="1.0"?><result><record><field name="success" value="true"/></record></result>'),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    $connector->flags()->save(donorId: 9876, flagCode: 'WEB');

    $mockClient->assertSent(function (SaveFlag $request): bool {
        return $request->query()->get('action') === 'dp_saveflag_xml'
            && str_contains($request->query()->get('params'), '@matching_id=9876')
            && str_contains($request->query()->get('params'), "@flag='WEB'");
    });
});

it('passes @flag_date when one is provided', function () {
    $mockClient = new MockClient([
        SaveFlag::class => MockResponse::make('<?xml version="1.0"?><result/>'),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    $connector->flags()->save(1, 'WEB', new DateTimeImmutable('2026-05-05'));

    $mockClient->assertSent(function (SaveFlag $request): bool {
        return str_contains($request->query()->get('params'), "@flag_date='2026-05-05'");
    });
});

it('omits @flag_date when none is provided so DP defaults to today', function () {
    $mockClient = new MockClient([
        SaveFlag::class => MockResponse::make('<?xml version="1.0"?><result/>'),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    $connector->flags()->save(1, 'WEB');

    $mockClient->assertSent(function (SaveFlag $request): bool {
        return ! str_contains($request->query()->get('params'), '@flag_date');
    });
});
