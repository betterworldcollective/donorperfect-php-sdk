<?php

use DonorPerfect\Data\Donor\Donor;

it('round-trips email_type and phone_type through from() and toApiArray()', function () {
    $donor = Donor::from([
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'email' => 'ada@example.com',
        'email_type' => 'PERSONAL',
        'home_phone' => '555-0101',
        'phone_type' => 'HOME',
    ]);

    expect($donor->emailType)->toBe('PERSONAL')
        ->and($donor->phoneType)->toBe('HOME');

    $payload = $donor->toApiArray();

    expect($payload['email_type'])->toBe('PERSONAL')
        ->and($payload['phone_type'])->toBe('HOME');
});

it('omits email_type and phone_type as null when not provided', function () {
    $donor = Donor::from([
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
    ]);

    expect($donor->emailType)->toBeNull()
        ->and($donor->phoneType)->toBeNull();

    $payload = $donor->toApiArray();

    expect($payload)->toHaveKey('email_type', null)
        ->and($payload)->toHaveKey('phone_type', null);
});
