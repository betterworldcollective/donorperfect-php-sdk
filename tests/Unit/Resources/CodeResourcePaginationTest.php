<?php

use DonorPerfect\DonorPerfect;
use DonorPerfect\Requests\CallSqlRequest;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

/**
 * DP caps every query at 500 rows and gives no signal that it truncated (verified
 * against the sandbox: 816 dpcodes rows, 500 returned, HTTP 200). Before paging, an
 * org with 500+ codes for one field silently lost the rest — and callers that
 * deactivate anything missing from the list would mark those codes inactive.
 */
function codePage(int $count, int $offset = 0): MockResponse
{
    $records = '';

    for ($i = 0; $i < $count; $i++) {
        $code = sprintf('CODE%04d', $offset + $i);
        $records .= "<record><field name=\"CODE\" value=\"{$code}\"/>"
            .'<field name="DESCRIPTION" value="d"/><field name="INACTIVE" value="0"/></record>';
    }

    return MockResponse::make('<?xml version="1.0"?><result>'.$records.'</result>');
}

it('pages past DP\'s 500-row cap and returns every code', function () {
    $mockClient = new MockClient([
        codePage(500),        // full page -> must ask for more
        codePage(120, 500),   // short page -> stop
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    $rows = $connector->codes()->list('SOLICIT_CODE');

    expect($rows)->toHaveCount(620)
        ->and($rows[0]['code'])->toBe('CODE0000')
        ->and($rows[619]['code'])->toBe('CODE0619');

    $mockClient->assertSentCount(2);
});

it('keys the next page off the last code seen', function () {
    $mockClient = new MockClient([
        codePage(500),
        codePage(1, 500),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    $connector->codes()->list('CAMPAIGN');

    $mockClient->assertSent(function (CallSqlRequest $request): bool {
        $sql = $request->query()->get('action');

        // Second page must continue after the last code of the first page.
        return ! str_contains($sql, 'CODE0499') || str_contains($sql, "CODE > 'CODE0499'");
    });
});

it('makes a single request when the first page is short', function () {
    $mockClient = new MockClient([codePage(3)]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    expect($connector->codes()->list('CAMPAIGN'))->toHaveCount(3);

    $mockClient->assertSentCount(1);
});
