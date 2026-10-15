# Changelog

All notable changes to `betterworldcollective/donorperfect-php-sdk` will be documented in this file.

## [0.4.1] — 2026-10-15

### Changed

- Allow Laravel 13: `illuminate/collections` and `illuminate/support` now accept `^13.0`.

## [0.4.0] — 2026-08-03

Failures that DonorPerfect used to hide now surface as exceptions. **Expect calls that
previously "succeeded" to start throwing** — in every case they were not actually doing
anything, so the new exception is exposing a pre-existing silent failure, not creating one.

Everything below was verified against a live DonorPerfect sandbox, not just mocks.

### Fixed — writes that silently did nothing now throw

DP answers HTTP 200 for failures, so `AlwaysThrowOnErrors` never fired. There are three
response shapes and the SDK only recognised the first two:

```
success    <result><record><field value="297"/></record></result>
envelope   <result><field name="success" value="false" reason="…"/><error>…</error></result>
in-record  <result><record><field value="…this field does not exist"/></record></result>   ← missed
```

The third shape passed the old `isset($xml->record)` check and was reported as success:

- **`UdfResource::save`** — a UDF written to a non-existent `field_name` reported success and wrote nothing. Silent data loss on every affected sync.
- **`DonorPerfect::saveDonor` / `saveGift`** — cast DP's error text with `(int)` and returned **`0`** as the new record id. Callers then stored `0` as the CRM id.
- **`FlagResource::save`** — a null `donor_id` returned an empty id. This is worse than a no-op: the row DP writes can surface on an unrelated donor created later. Observed in the sandbox.

Detection now lives in the requests themselves (`DetectsDonorPerfectFailure`), matching how
`CallSqlRequest` already worked, so resources no longer hand-roll their own checks.

### Fixed — `UnauthorizedException` now means what it says

`user not authorized for this api call.` is **not** an authentication failure. It is DP's
blanket mask for any SQL-level failure — a duplicate row, a missing or misnamed parameter,
an unknown id. Per the DP XML API manual (p.11) API keys carry no per-procedure permissions
at all, so that text can never indicate a permissions problem.

A genuinely bad key answers `Invalid token.` or `login failed` with no `<error>` element.
Only that now raises `UnauthorizedException`; everything else raises `DonorPerfectException`
with DP's real `<error>` text and an explicit note that it is not a key problem.

`UnauthorizedException` now extends `DonorPerfectException`, so existing
`catch (DonorPerfectException)` blocks keep covering every DP failure.

### Fixed — 500-row cap silently truncating code and UDF lists

DP caps every query at 500 rows and gives no indication it truncated (sandbox: 816 `dpcodes`
rows, 500 returned, HTTP 200). `CodeResource::list` and `UdfMetadataResource::list` now page
with `TOP 500 … WHERE col > {last} ORDER BY col` per the manual (p.17).

This mattered downstream: callers that deactivate anything missing from the returned list
were marking every code past the 500th as inactive, which removes it from dropdowns and
fails validation on save.

### Added — `FlagResource::listForDonor(int $donorId): array`

Returns the upper-cased flag codes already on a donor. `dpflags` is keyed on
(`donor_id`, `flag`) under a case-insensitive collation, so re-applying an existing flag is a
primary-key violation — which DP reports as "user not authorized for this api call." Read
with `listForDonor()` and write only what is missing to keep `save()` idempotent.

```php
$existing = $dp->flags()->listForDonor(297);          // ['CBM', 'STAFF']

foreach (array_diff(['CBM', 'VOL'], $existing) as $code) {
    $dp->flags()->save(297, $code, 'MyApp');          // only VOL is sent
}
```

### Note — `@user_id` length is not a failure mode

An earlier read of the docs suggested a `@user_id` longer than `varchar(20)` was rejected.
It is not: DP truncates silently and the write succeeds. Verified in isolation on the
sandbox.

## [0.3.7] — 2026-06-22

- **`CodeResource`** — allow `SOLICIT_CODE` and `SUB_SOLICIT_CODE` in the `dpcodes` field-name allowlist (#9).

## [0.3.6] — 2026-05-14

- **`UdfResource::save`** — fix the `dp_save_udf_xml` payload to match DP docs (p.39): seven required params, route value to `@char_value` / `@date_value` / `@number_value` based on `data_type`, include `@user_id`. N-type values are now coerced to bare numerics (DP silently drops quoted ones).
- **`ActionParams::serialize`** — escape single quotes in string values by doubling them (`O'Brien` → `'O''Brien'`). DP's stored-proc parser was treating inner apostrophes as string terminators, causing "user not authorized" rejections on any donor with `'` in a name/address/note.
- **`CodeResource`** — recognise `'Y'` as an inactive marker alongside `'1'` and `'true'` (DP's UI sets it that way). Thanks @josh-b-dev (#8).

## [0.3.5] — 2026-05-13

Three bug fixes that were silently turning into "user not authorized" rejections from DP.

- **`ActionParams::serialize`** — quote numeric-looking strings (e.g. `'+84907921399'`, `'12345'`). `is_numeric()` returns true for them and was letting them through unquoted, which DP rejects.
- **`FlagResource::save`** — fix the `dp_saveflag_xml` payload to match DP docs (p.48): `@donor_id` (not `@matching_id`), and `@user_id` is required. Dropped the unsupported `@flag_date` parameter.
- **`CallSqlRequest`** — detect DP's `<field name="success" value="false"/>` envelope and throw `DonorPerfectException` with the SQL + DP's error text, instead of returning a confusing pseudo-success array.
- **`CodeResource`** — accept `'FLAG'` (the real `dpcodes.field_name` value, singular) alongside `'FLAGS'`.

## [0.3.4] — 2026-05-13

### Changed — UDF metadata now queries the right table

`MetadataResource` (the old `metadata()` accessor) was queried `DPFIELDS WHERE TABLE_NAME IN ('DP','DPGIFT')` — but `DPFIELDS` only contains metadata for built-in DonorPerfect fields. UDFs live as columns on `DPUDF`/`DPGIFTUDF` themselves. Anyone using the old API to populate a "map to your UDFs" dropdown would have surfaced 70+ built-in fields like `AMOUNT` and `ADDRESS` — destructive if mapped.

**`MetadataResource` is replaced with `UdfMetadataResource`**, which queries `INFORMATION_SCHEMA.COLUMNS` against the actual UDF tables and maps SQL types to the C/D/N codes `dp_save_udf_xml` accepts (`varchar/nvarchar/char` → `C`, `numeric/money/int` → `N`, `datetime/date` → `D`).

```php
// Before (returned 70+ built-in fields, NOT UDFs):
$client->metadata()->list(['DP', 'DPGIFT']);

// After (returns only real UDFs with correct types):
$client->udfMetadata()->list('DPUDF');     // donor UDFs
$client->udfMetadata()->list('DPGIFTUDF'); // gift UDFs
```

### Changed — `UdfResource::ALLOWED_DATA_TYPES`

Dropped `'M'` from the allowlist. Per DonorPerfect XML API docs (dp_save_udf_xml, p.39) only `C/D/N` are valid `@data_type` values — money UDFs route through `N` via the `@number_value` parameter. The old `'M'` entry was over-permissive and would have been silently rejected by DP at the wire if anyone passed it.

### Fixed — silent failures in `UdfResource::save` and `FlagResource::save`

The 0.3.2 hotfix added throw-on-rejection for `saveDonor`/`saveGift` (checking for `<record>` element) but missed `UdfResource::save` and `FlagResource::save` — they only checked for empty body. DP's permission rejections come back as `<result><field name="success" value="false" reason="…"/></result>` (valid XML, no `<record>`), so both methods were silently returning the failure response.

Both now throw `DonorPerfectException` with the raw DP body when the response is missing `<record>`. Mirrors the v0.3.2 saveDonor pattern.

### Backward compatibility

**Breaking: `metadata()` accessor renamed to `udfMetadata()`.** Old call sites must update. The signature also changed from `list(array $tableNames = ['DP','DPGIFT'])` to `list(string $tableName)` — single table, allowed values `DPUDF` / `DPGIFTUDF`. Return shape changed to `[{field_name, data_type}]` — no longer includes `table_name` (input) or `prompt` (`INFORMATION_SCHEMA` doesn't have prompts).

**Breaking: `UdfResource::save` rejects `'M'`.** Any caller passing `'M'` (none on Packagist) needs to switch to `'N'` for money UDFs.

**Behavior change: `UdfResource::save` and `FlagResource::save` now throw on DP permission rejections.** Same shape as the v0.3.2 hotfix for saveDonor/saveGift; `bw-api`'s `DonorPerfectDriver::create()` already wraps SDK calls in `catch (Exception)` → `CRMException`, so existing consumers handle this transparently.

## [0.3.3] — 2026-05-12

### Added

- `'FLAGS'` added to `Resources\CodeResource::ALLOWED_FIELD_NAMES`. Callers can now run `$client->codes()->list('FLAGS')` to fetch the org's `dpcodes` rows where `FIELD_NAME='FLAGS'`. Previously rejected with `InvalidDataException`. Enables the donor-flags dropdown in BetterWorld's DP integration dashboard.

### Backward compatibility

Purely additive — no existing call site changes behavior. The allowlist only grows.

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
