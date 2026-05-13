# Changelog

All notable changes to `betterworldcollective/donorperfect-php-sdk` will be documented in this file.

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
