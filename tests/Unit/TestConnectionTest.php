<?php

use DonorPerfect\DonorPerfect;
use DonorPerfect\Requests\TestConnection;
use Psr\Log\AbstractLogger;
use Psr\Log\NullLogger;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

/**
 * Build an in-test PSR-3 logger spy that records every call. Avoids pulling
 * in a mocking library just to assert on log writes.
 */
function spyLogger(): object
{
    return new class extends AbstractLogger
    {
        /** @var list<array{level:mixed, message:string, context:array<string, mixed>}> */
        public array $records = [];

        public function log($level, $message, array $context = []): void
        {
            $this->records[] = ['level' => $level, 'message' => (string) $message, 'context' => $context];
        }
    };
}

it('returns true when DP responds with a record', function () {
    $mockClient = new MockClient([
        TestConnection::class => MockResponse::make('<?xml version="1.0"?><result><record>ok</record></result>'),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    expect($connector->testConnection())->toBeTrue();
});

it('returns false when DP responds with success=false', function () {
    $mockClient = new MockClient([
        TestConnection::class => MockResponse::make('<?xml version="1.0"?><result><field name="success" value="false"/></result>'),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    expect($connector->testConnection())->toBeFalse();
});

it('returns false and logs the exception when the request throws', function () {
    $logger = spyLogger();

    $mockClient = new MockClient([
        TestConnection::class => MockResponse::make('Server exploded', 500),
    ]);
    $connector = (new DonorPerfect('k', $logger))->withMockClient($mockClient);

    expect($connector->testConnection())->toBeFalse();
    expect($logger->records)->toHaveCount(1);
    expect($logger->records[0]['message'])->toContain('DonorPerfect testConnection failed');
});

it('defaults to NullLogger when no logger is supplied', function () {
    $mockClient = new MockClient([
        TestConnection::class => MockResponse::make('Server exploded', 500),
    ]);
    $connector = (new DonorPerfect('k'))->withMockClient($mockClient);

    expect($connector->testConnection())->toBeFalse();
    expect($connector->getLogger())->toBeInstanceOf(NullLogger::class);
});
