<?php

use DonorPerfect\Support\ActionParams;

it('serializes a string value with single quotes', function () {
    expect(ActionParams::serialize(['field_name' => 'CAMPAIGN']))
        ->toBe("@field_name='CAMPAIGN'");
});

it('serializes a numeric value without quotes', function () {
    expect(ActionParams::serialize(['matching_id' => 12345]))
        ->toBe('@matching_id=12345');
});

it('serializes a null value as bare null (not the string "null")', function () {
    expect(ActionParams::serialize(['field_value' => null]))
        ->toBe('@field_value=null');
});

it('joins multiple params with ", " preserving insertion order', function () {
    expect(ActionParams::serialize([
        'matching_id' => 12345,
        'field_name' => 'PRONOUN',
        'data_type' => 'C',
        'field_value' => 'they',
    ]))->toBe("@matching_id=12345, @field_name='PRONOUN', @data_type='C', @field_value='they'");
});

it('returns an empty string for an empty property bag', function () {
    expect(ActionParams::serialize([]))->toBe('');
});

it("escapes single quotes by doubling them (DP's stored proc parser convention)", function () {
    expect(ActionParams::serialize(['last_name' => "O'Brien"]))
        ->toBe("@last_name='O''Brien'");

    expect(ActionParams::serialize(['note' => "don't can't won't"]))
        ->toBe("@note='don''t can''t won''t'");
});

it('quotes numeric-looking strings (e.g. phone "+84907921399", zip "12345")', function () {
    // Real numeric PHP types stay bare; numeric-looking strings get quoted.
    // is_numeric() returns true for "+84907921399" and would let it slip
    // through unquoted, which DP's parser rejects with "user not authorized".
    expect(ActionParams::serialize(['phone' => '+84907921399']))
        ->toBe("@phone='+84907921399'");

    expect(ActionParams::serialize(['zip' => '12345']))
        ->toBe("@zip='12345'");

    expect(ActionParams::serialize(['amount' => '100.50']))
        ->toBe("@amount='100.50'");
});
