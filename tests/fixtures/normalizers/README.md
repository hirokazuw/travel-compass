# Normalizer contracts

Recorded against the pre-P1-5 `ApifyResponseNormalizer` on 2026-09-08.
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

No local real flight or destination response was available. Anonymized captures
for those domains still need to be added; these fixtures do not claim to verify
the current external Actor schema. No external API was called to create fixtures.

Hotel booking links and flight airline metadata are added downstream by existing
services. Their tests and the HTML contracts remain in place; they are not fields
invented by these normalizers. Intentional normalization changes must review the
expected JSON diff explicitly rather than automatically re-recording it.
