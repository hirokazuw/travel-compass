# Normalizer contracts

Original fixtures recorded against the pre-P1-5 `ApifyResponseNormalizer` on 2026-09-08.
`*-expected.json` fixes every emitted key, value, type and record order, including
nulls and omitted private sorting fields. Tests compare both the compatibility
facade and the domain normalizer against the same expected output.

## Provenance

- `hotels-input.json`: the first property of the local
  `storage/cache/apify/diagnostic-hotels/` response, reduced to consumed fields.
  Name, description, property token, links, images, coordinates and amenities
  were replaced with test values. Search parameters, metadata and unused fields
  were excluded. Rates, rating, review count, class and times retain their original
  response shapes. Sparse/invalid records following the first property are synthetic.
- `flights-input.json`: synthetic. Covers grouped and flattened Actor layouts,
  multiple segments, price sorting, invalid price, empty segments, fallback fields,
  duration/time formatting, stop count and unsafe logo rejection.
- `destinations-input.json`: synthetic. Covers aliases, trimming, country case,
  coordinates, sparse/invalid records and the configured result limit.

- `flights-real-input.json`: added 2026-09-15 from user-provided
  `docs/airticket.json` (response timestamp `2026-09-15T01:33:57.434444`).
  Actor identity/build is not recorded in that JSON and has not been independently
  verified. The source contains 4 best and 70 other flights, plus 74 flattened rows.
  Retained the first two best flights, first other flight and first two flattened
  rows. Grouped results were reduced to consumed fields; airport names, IDs,
  times, airline names, flight numbers, prices and durations retain their source
  values/types. Logo URLs were replaced with `https://example.com/airline.png`;
  token fields, request parameters, metadata and unused top-level fields were
  removed. The source file was not modified.
  Expected output was assembled separately from the captured values, not recorded
  from the normalizer. It checks three offers, exact output keys/types/order,
  equal-price ordering, JPY fallback, time/duration formatting, and no duplicate
  offers from `all_flights` when both grouped collections exist. It covers domestic
  nonstop flights, not every Actor layout. Synthetic edge cases remain in place.

No external API was called to create these fixtures.

## Hotel destination capture (completed 2026-09-15)

`destinations-real-input.json` comes from user-provided `docs/destination.json`.
The source has no capture timestamp or Actor build identifier; neither is inferred.
All five records and their original field presence, order and types were retained.
Keyword/name/address strings, place IDs and coordinates were replaced with fixture
values. Numeric coordinates remain floating-point numbers; country code JP was
retained. The original file was not changed. Expected output was authored manually.

Compared with the synthetic fixture, the capture has three keyword-only records
followed by two named places, numeric coordinates instead of numeric strings,
and no type/category. The existing normalizer correctly emits all five records.
Keyword-only records have empty optional strings and null coordinates. Named
places map item_id to place_id and coordinate elements to latitude/longitude.
The seven output keys and record order are compared through both the domain
normalizer and compatibility facade. A limit of two returns the first two keyword
records; the current contract does not prioritize geocoded places.

Additional synthetic mutations test missing/null optional fields, empty or invalid
coordinate containers, empty input, non-record elements and ignored unknown nested
fields. A nonempty trimmed name or keyword is required: absent/null name falls
back to keyword, while an explicitly empty name continues to suppress fallback.
No production normalizer changes were needed. Arbitrary wrong types in string
fields are not covered by this capture and are not claimed to be validated.

Aviation origin and
destination autocomplete uses `iata_cities`, not an Apify Actor. Its Japanese,
English and IATA queries, ten matching rows capped at eight, and selected search
codes are tested in `tests/flight-suggestions.php`.

Validation: `php tests/run.php`, `php tests/view-contract.php` and
`node tests/browser.mjs`. Existing synthetic fixtures remain for edge coverage.

Hotel booking links and flight airline metadata are added downstream by existing
services. Their tests and the HTML contracts remain in place; they are not fields
invented by these normalizers. Intentional normalization changes must review the
expected JSON diff explicitly rather than automatically re-recording it.
