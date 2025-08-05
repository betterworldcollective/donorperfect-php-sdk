<?php

use DonorPerfect\DonorPerfect;

it('can test connection', function () {
    $donorPerfect = new DonorPerfect('test-api-key');

    expect($donorPerfect)->toBeInstanceOf(DonorPerfect::class);
});
