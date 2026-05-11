<?php

use DonorPerfect\DonorPerfect;
use DonorPerfect\Exceptions\DonorPerfectException;
use DonorPerfect\Requests\Donor\SaveDonor;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

it('returns the donor_id from a valid SaveDonor response', function () {
    $mockClient = new MockClient([
        SaveDonor::class => MockResponse::make(
            '<?xml version="1.0"?><result><record><field name="donor_id" value="987654"/></record></result>'
        ),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    expect($connector->saveDonor(['first_name' => 'Test']))->toBe(987654);
});

it('throws DonorPerfectException when the response body is empty', function () {
    $mockClient = new MockClient([
        SaveDonor::class => MockResponse::make(''),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    $connector->saveDonor(['first_name' => 'Test']);
})->throws(DonorPerfectException::class, 'DonorPerfect rejected SaveDonor');

it('throws DonorPerfectException when DP returns an error body without a <record>', function () {
    $mockClient = new MockClient([
        SaveDonor::class => MockResponse::make(
            '<?xml version="1.0"?><result><error>Some DP error</error></result>'
        ),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    $connector->saveDonor(['first_name' => 'Test']);
})->throws(DonorPerfectException::class, 'DonorPerfect rejected SaveDonor');

it('throws DonorPerfectException when <record> exists but the value attribute is missing', function () {
    $mockClient = new MockClient([
        SaveDonor::class => MockResponse::make(
            '<?xml version="1.0"?><result><record><field name="donor_id"/></record></result>'
        ),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    $connector->saveDonor(['first_name' => 'Test']);
})->throws(DonorPerfectException::class, 'unexpected response');
