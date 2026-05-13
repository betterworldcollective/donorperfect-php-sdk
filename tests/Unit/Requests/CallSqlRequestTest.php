<?php

use DonorPerfect\DonorPerfect;
use DonorPerfect\Exceptions\DonorPerfectException;
use DonorPerfect\Requests\CallSqlRequest;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

it('returns the parsed array on a normal record set', function () {
    $mockClient = new MockClient([
        CallSqlRequest::class => MockResponse::make(
            '<?xml version="1.0"?><result><record><field name="x" value="1"/></record></result>'
        ),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    expect($connector->executeSql('SELECT * FROM dp'))->toBeArray();
});

it('returns an empty array (not an error) when DP returns no records', function () {
    $mockClient = new MockClient([
        CallSqlRequest::class => MockResponse::make('<?xml version="1.0"?><result/>'),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    expect($connector->executeSql('SELECT * FROM dp WHERE 1=0'))->toBe([]);
});

it('throws DonorPerfectException when DP returns the success=false envelope', function () {
    // DP rejects specific column projections / non-whitelisted tables with this
    // misleading "user not authorized" reason — the <error> tag has the real signal.
    $mockClient = new MockClient([
        CallSqlRequest::class => MockResponse::make(
            '<?xml version="1.0"?><result><field name="success" value="false" reason="user not authorized for this api call."/><error>SQL statement not allowed</error></result>'
        ),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    $connector->executeSql('SELECT donor_id, flag, flag_date FROM dpflags');
})->throws(
    DonorPerfectException::class,
    'DonorPerfect rejected SQL (SQL statement not allowed — user not authorized for this api call.): SELECT donor_id, flag, flag_date FROM dpflags'
);
