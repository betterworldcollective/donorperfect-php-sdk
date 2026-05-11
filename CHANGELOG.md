# Changelog

All notable changes to `betterworldcollective/donorperfect-php-sdk` will be documented in this file.

## [0.3.2] — 2026-05-11

### Changed — silent failures now throw

The SDK previously swallowed DonorPerfect error and malformed responses, returning `0` from
`saveDonor()`/`saveGift()` and an empty `array` from `UdfResource::save()`/`FlagResource::save()`.
Callers had no way to distinguish "DP rejected the call" from "the call succeeded with id 0".

These methods now throw `DonorPerfect\Exceptions\DonorPerfectException` when DP returns an empty body,
malformed XML, or a body without the expected `<record>` element. The exception message includes the
raw response body for diagnosis.

- `DonorPerfect::saveDonor()` — throws on missing `<record>` or missing `field[value]`
- `DonorPerfect::saveGift()` — same pattern
- `Resources\UdfResource::save()` — throws on empty/malformed XML
- `Resources\FlagResource::save()` — throws on empty/malformed XML

### Added

- `DonorPerfect\Exceptions\DonorPerfectException` — base exception for DP-rejected calls
- Optional PSR-3 logger injection via `new DonorPerfect($apiKey, $logger)`. Defaults to `NullLogger`.
  `testConnection()` keeps its `bool` return contract but now logs the underlying exception via the
  injected logger before returning `false` (silent failure was the bug).
- Explicit `psr/log` requirement in `composer.json` (was previously transitive via Saloon).

### Fixed

- `Responses\DonorPerfectResponse::xml()` no longer emits PHP `E_WARNING` on malformed XML.
  libxml errors are now suppressed via `libxml_use_internal_errors()` and surfaced via the existing
  `false` return value.

### Migration notes

Callers of `saveDonor`, `saveGift`, `UdfResource::save`, and `FlagResource::save` that previously
relied on `0`/`[]` as a "failed silently" signal must now handle `DonorPerfectException`. Inside
`bw-api`, `DonorPerfectDriver::create()` already wraps SDK calls in `try/catch (Exception $e)` and
re-throws as `CRMException`, so no behavior change is needed at that layer — exceptions bubble
correctly into the existing `rescue()` in `SyncModelToCRMJob` and report to Bugsnag.
