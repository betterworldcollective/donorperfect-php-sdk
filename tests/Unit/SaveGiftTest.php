<?php

use DonorPerfect\DonorPerfect;
use DonorPerfect\Exceptions\DonorPerfectException;
use DonorPerfect\Requests\Gift\SaveGift;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

it('returns the gift_id from a valid SaveGift response', function () {
    $mockClient = new MockClient([
        SaveGift::class => MockResponse::make(
            '<?xml version="1.0"?><result><record><field name="gift_id" value="555111"/></record></result>'
        ),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    expect($connector->saveGift(['donor_id' => 1, 'amount' => 100]))->toBe(555111);
});

it('throws DonorPerfectException when the response body is empty', function () {
    $mockClient = new MockClient([
        SaveGift::class => MockResponse::make(''),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    $connector->saveGift(['donor_id' => 1, 'amount' => 100]);
})->throws(DonorPerfectException::class, 'DonorPerfect rejected SaveGift');

it('throws DonorPerfectException when DP returns an error body without a <record>', function () {
    $mockClient = new MockClient([
        SaveGift::class => MockResponse::make(
            '<?xml version="1.0"?><result><error>Some DP error</error></result>'
        ),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    $connector->saveGift(['donor_id' => 1, 'amount' => 100]);
})->throws(DonorPerfectException::class, 'DonorPerfect rejected SaveGift');

it('throws DonorPerfectException when <record> exists but the value attribute is missing', function () {
    $mockClient = new MockClient([
        SaveGift::class => MockResponse::make(
            '<?xml version="1.0"?><result><record><field name="gift_id"/></record></result>'
        ),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    $connector->saveGift(['donor_id' => 1, 'amount' => 100]);
})->throws(DonorPerfectException::class, 'unexpected response');
