<?php

use DonorPerfect\Requests\CallSqlRequest;
use DonorPerfect\Requests\Donor\SaveDonor;
use DonorPerfect\Requests\Gift\SaveGift;
use DonorPerfect\Requests\TestConnection;
use Saloon\Config;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\MockConfig;

MockConfig::setFixturePath('tests/Fixtures');
MockConfig::throwOnMissingFixtures();
Config::preventStrayRequests();

MockClient::global([
    TestConnection::class => MockResponse::fixture('test-connection'),
    CallSqlRequest::class => MockResponse::fixture('call-sql'),
    SaveDonor::class => MockResponse::fixture('save-donor'),
    SaveGift::class => MockResponse::fixture('save-gift'),
]);

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(DonorPerfect\Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}
