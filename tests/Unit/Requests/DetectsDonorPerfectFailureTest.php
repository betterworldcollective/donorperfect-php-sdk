<?php

use DonorPerfect\DonorPerfect;
use DonorPerfect\Exceptions\DonorPerfectException;
use DonorPerfect\Exceptions\UnauthorizedException;
use DonorPerfect\Requests\CallSqlRequest;
use DonorPerfect\Requests\Flag\SaveFlag;
use DonorPerfect\Requests\Udf\SaveUdf;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

/**
 * DP answers HTTP 200 for failures too. These cover the three response shapes
 * observed against the live DonorPerfect sandbox.
 */

// ---------------------------------------------------------------- in-record errors

it('throws when DP puts an error message inside a well-formed <record>', function () {
    // Real dp_save_udf_xml response for a field_name that does not exist. Before the
    // fix this passed the isset($xml->record) check and was reported as a success,
    // so the UDF was silently never written.
    $mockClient = new MockClient([
        SaveUdf::class => MockResponse::make(
            '<?xml version="1.0"?><result><record><field name="" id="" '
            .'value="Either @field_name was not provided or this field does not exist"/></record></result>'
        ),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    $connector->udfs()->save(640, 'NOPE', 'C', 'x');
})->throws(DonorPerfectException::class, 'this field does not exist');

it('throws when DP reports @matching_id not found inside a <record>', function () {
    $mockClient = new MockClient([
        SaveUdf::class => MockResponse::make(
            '<?xml version="1.0"?><result><record><field name="" id="" '
            .'value="@matching_id not found or no field value provided"/></record></result>'
        ),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    $connector->udfs()->save(640, 'DNICK', 'C', 'x');
})->throws(DonorPerfectException::class, '@matching_id not found');

it('throws when DP returns an empty id, which is a silent no-op', function () {
    // dp_saveflag_xml with @donor_id=null answers like this. It is not harmless: the
    // row DP writes can surface on an unrelated donor created later.
    $mockClient = new MockClient([
        SaveFlag::class => MockResponse::make(
            '<?xml version="1.0"?><result><record><field name="" id="" value=""/></record></result>'
        ),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    $connector->flags()->save(1, 'WEB', 'BetterWorld');
})->throws(DonorPerfectException::class, 'changed nothing');

// ------------------------------------------------------------- auth vs SQL failure

it('raises UnauthorizedException only for a genuinely rejected API key', function () {
    $mockClient = new MockClient([
        SaveFlag::class => MockResponse::make(
            '<?xml version="1.0"?><result><field name="success" reason="Invalid token." id="success" value="false"/></result>'
        ),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    $connector->flags()->save(1, 'WEB', 'BetterWorld');
})->throws(UnauthorizedException::class, 'Invalid token.');

it('raises UnauthorizedException when DP reports login failed', function () {
    $mockClient = new MockClient([
        SaveFlag::class => MockResponse::make(
            '<?xml version="1.0"?><result><field name="success" reason="login failed" id="success" value="false"/></result>'
        ),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    $connector->flags()->save(1, 'WEB', 'BetterWorld');
})->throws(UnauthorizedException::class, 'login failed');

it('does NOT treat "user not authorized for this api call" as an auth failure', function () {
    // This is DP's mask for any SQL-level failure — here, a duplicate flag. Reporting
    // it as an API key problem sends support chasing DonorPerfect for nothing.
    $mockClient = new MockClient([
        SaveFlag::class => MockResponse::make(
            '<?xml version="1.0"?><result><field name="success" reason="user not authorized for this api call." '
            .'id="success" value="false"/><error>SQL statement not allowed</error></result>'
        ),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    try {
        $connector->flags()->save(1, 'WEB', 'BetterWorld');
        expect(false)->toBeTrue('expected a DonorPerfectException');
    } catch (DonorPerfectException $e) {
        expect($e)->not->toBeInstanceOf(UnauthorizedException::class)
            ->and($e->getMessage())->toContain('SQL statement not allowed')
            ->and($e->getMessage())->toContain('not an API key permission problem');
    }
});

it('keeps UnauthorizedException catchable as a DonorPerfectException', function () {
    expect(new UnauthorizedException)->toBeInstanceOf(DonorPerfectException::class);
});

// ---------------------------------------------------------------------- happy path

it('returns the parsed array when DP echoes a numeric row id', function () {
    $mockClient = new MockClient([
        SaveFlag::class => MockResponse::make(
            '<?xml version="1.0"?><result><record><field name="" id="" value="297"/></record></result>'
        ),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    expect($connector->flags()->save(297, 'CBM', 'BetterWorld'))->toBeArray();
});

// ------------------------------------------------- SQL path (CallSqlRequest) parity

it('raises UnauthorizedException from the SQL path too', function () {
    $mockClient = new MockClient([
        CallSqlRequest::class => MockResponse::make(
            '<?xml version="1.0"?><result><field name="success" reason="Invalid token." id="success" value="false"/></result>'
        ),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    $connector->flags()->listForDonor(297);
})->throws(UnauthorizedException::class, 'Invalid token.');

it('still reports a refused query as a plain DonorPerfectException', function () {
    $mockClient = new MockClient([
        CallSqlRequest::class => MockResponse::make(
            '<?xml version="1.0"?><result><field name="success" reason="user not authorized for this api call." '
            .'id="success" value="false"/><error>SQL statement not allowed</error></result>'
        ),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    try {
        $connector->flags()->listForDonor(297);
        expect(false)->toBeTrue('expected a DonorPerfectException');
    } catch (DonorPerfectException $e) {
        expect($e)->not->toBeInstanceOf(UnauthorizedException::class);
    }
});
