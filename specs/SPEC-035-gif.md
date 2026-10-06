# SPEC-035: Check GIF uploads

| Field      | Value                                             |
|------------|---------------------------------------------------|
| Status     | approved                                          |
| Author     | Maurice van Loon                                  |
| Approved   | Maurice van Loon, 2026-10-06                      |
| Supersedes | —                                                 |

> Lifecycle: `draft` → maintainer approves → `approved` → tests-first →
> `implemented` (Traceability filled). No implementation code while `draft`.
> Only the Traceability section of an `approved` spec may change without a new
> approval; everything else needs a proposed amendment.

## Problem

The bundled verifier reads and verifies GIF since c2pa-verifier 0.4.0 (its
SPEC-059; C2PA 2.4 §A.3.8, the `C2PA_GIF` Application Extension). The
plugin requires `^0.3.0` and checks JPEG, PNG, WebP, WAV, MP3 and FLAC
(`UploadHook::MIME_TYPES`): a GIF upload gets no verdict, and SPEC-001 AC5
names the GIF as a type that is left alone.

Measured on 2026-10-06 in the plugin's development environment (WordPress
7.1.2, PHP 8.3, wp-env, Imagick), with signed GIFs imported through
`wp media import`:

| file | post_mime_type | attached file | original after upload | sizes made | stored by the plugin |
|---|---|---|---|---|---|
| `fixture-signed.gif` (c2pa-verifier v0.4.0, 129 KB) | `image/gif` | the original | unchanged (SHA-256 equal) | none | nothing |
| an animated GIF, 96×96, 12 frames, signed by `c2patool` 0.27.22 | `image/gif` | the original | unchanged | none | nothing |
| a GIF of 3000×300, signed by `c2patool` 0.27.22 | `image/gif` | `big-signed-scaled.gif` | unchanged, kept as `original_image` | six | nothing |

The `-scaled` copy WordPress makes of a GIF above 2560 pixels carries no
manifest (the verifier: `has_manifest` false), as for a JPEG; the original
does, and it is the file the plugin already checks (SPEC-001 AC4,
`UploadHook::shownFile()`).

Two texts are stale since audio was added (0.2.0) and would become more
so: a file the verifier cannot read as any format is explained as "not a
JPEG, PNG or WebP file" (`Display`), and `wp tracefern check` skips an ID
with "not a JPEG, PNG or WebP attachment" (`RecheckCommand`).

One test changes meaning. SPEC-015 AC1 stores a GIF's bytes under a
`.jpg` name to get an unknown format; under c2pa-verifier 0.4.0 those bytes
are a GIF without a manifest, so they no longer test what AC1 says.

## Scope

**In scope**

- GIF (`image/gif`) checked as the other images are: in the background, on
  the original, with the stored result, the verdicts and the AI label of
  SPEC-001, SPEC-002, SPEC-003 and SPEC-027, and everywhere SPEC-034 AC6
  lists.
- c2pa-verifier `^0.4.0`.
- The two stale texts, said without a list of formats that can go stale.
- SPEC-001 AC5's GIF and SPEC-015 AC1's GIF bytes replaced (amendments
  below).
- The readme: the description and the FAQ on formats.

**Out of scope** (each needs its own spec before it may be built)

- TIFF, SVG, AVI, video, and every other format.
- Anything particular to animation: an animated GIF is one file and gets
  one verdict; the frames are not looked at.
- The sizes WordPress makes of a GIF (they carry no manifest, as for every
  image).

## Behavior

The GIF fixtures are the verifier's (`tests/Fixtures/fixture-signed.gif`,
`fixture-unsigned.gif`, and `gif/flip-image.gif`, `gif/truncated-in-c2pa.gif`
and `gif/block-size-12.gif` of provemark/c2pa-verifier v0.4.0), copied into
the plugin's test fixtures with their source named; the large GIF is made
for this spec and signed with `c2patool` and the public test certificate
(the key stays outside this repository).

- **AC1 — a signed GIF: the verifier's verdict**
  - Given `fixture-signed.gif`, uploaded through `wp media import` and
    through the browser's REST route
  - When its background check has run
  - Then the stored result has the state and failure codes that
    `vendor/bin/c2pa-verify` gives for the same file with the same trust
    settings, `format` `gif`, and the Media Library shows the matching
    verdict (with the bundled lists, the test certificate: "Intact: signer
    not trusted"; with trust settings that hold the signer: "Verified:
    trusted signer")

- **AC2 — an unsigned GIF: "No Content Credentials"**
  - Given `fixture-unsigned.gif`, uploaded
  - Then state `none`, as SPEC-001 AC2

- **AC3 — changed after signing: "Does not verify"** *(error path)*
  - Given `flip-image.gif` (one byte of its image data changed)
  - Then state `Invalid` with `assertion.dataHash.mismatch`, and no AI
    label

- **AC4 — damaged: the verifier's verdict, the upload proceeds** *(error
  path)*
  - Given `truncated-in-c2pa.gif` (cut inside its `C2PA_GIF` block) and
    `block-size-12.gif` (a broken block before the store is reached)
  - Then the first is "Does not verify" (`Invalid`, `general.error`: the
    manifest was reached), the second "Could not be checked" (`error` /
    `unreadable`: no manifest was reached), as SPEC-034 amendment 1 for
    audio; each upload succeeds

- **AC5 — a large GIF: the original is checked, not `-scaled`**
  - Given the 3000×300 signed GIF, uploaded
  - Then WordPress has made `-scaled.gif`, and the entry equals the CLI's
    verdict on the original (`Valid`), not on the copy, as SPEC-001 AC4

- **AC6 — GIF everywhere images are**
  - Then a checked GIF appears in the column, the details, the verdict
    filter and sort, the dashboard counts, "Check files that were never
    checked", `wp tracefern check --all` and `tracefern_verdict`, as
    SPEC-034 AC6 for audio

- **AC7 — a file of no known format: said without a list** *(error path)*
  - Given a BMP's bytes stored under a `.jpg` name and checked (SPEC-015
    AC1, amended)
  - Then `error` / `unsupported`, and the details say the file is not a
    type this plugin can check, without naming types; `wp tracefern check
    <id>` on a PDF attachment warns that it is not a type the plugin
    checks, without naming types

- **AC8 — other formats are still left alone**
  - Given a BMP, a PDF and an AVI upload
  - Then no result is stored and the column shows nothing, as SPEC-001 AC5
    (amended) and SPEC-034 AC5

- **AC9 — the readme says it**
  - Then the description and the FAQ "Which formats are checked?" name
    GIF; the readme stays within wordpress.org's limits (`ReadmeTest`)

## Amendments this spec makes to others (approved with it)

- **SPEC-001 AC5**: "a PDF, a GIF" becomes "a PDF, a BMP"; the GIF dataset
  of its test becomes a BMP.
- **SPEC-015 AC1**: "a GIF's bytes uploaded as `image/jpeg`" becomes "a
  BMP's bytes"; the shown reason is AC7's text.

## References

- Specification: C2PA 2.4 §A.3.8 (GIF). WordPress:
  [`wp_create_image_subsizes()`](https://developer.wordpress.org/reference/functions/wp_create_image_subsizes/)
  (the big-image threshold and `original_image`),
  [`wp_get_original_image_path()`](https://developer.wordpress.org/reference/functions/wp_get_original_image_path/).
- Oracle: provemark/c2pa-verifier v0.4.0, its GIF fixtures,
  `vendor/bin/c2pa-verify [--settings f] <file>`; measured on WordPress
  7.1.2, PHP 8.3, wp-env, 2026-10-06 (the table above). The verifier's own
  GIF work: its notes for steps 256–261 (21 variants, two `c2patool`
  versions, a review).
- Reasoned: that the browser's upload routes treat a GIF as `wp media
  import` does (the same `wp_handle_upload()` and
  `wp_generate_attachment_metadata()`); AC1 measures the REST route.

## API sketch

```php
// namespace Tracefern\ImageCheck;
final class UploadHook
{
    public const array MIME_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'audio/wav', 'audio/x-wav', 'audio/mpeg', 'audio/flac'];
}
```

Nothing else in the check path changes: `ExistingImages`, `MediaScreens`,
`MediaSort`, `DashboardSummary` and `RecheckCommand` already read the
constant (SPEC-034).

## Open questions

1. **The version.** A new format, as audio was for 0.2.0. Proposal:
   plugin 0.3.0. Non-blocker. **Decided by Maurice van Loon, 2026-10-06:
   0.3.0.**
2. **The two texts.** Proposal: "not a file type this plugin can check"
   in the details and "not a type this plugin checks" in WP-CLI, so that
   the next format does not make them stale again; the readme's FAQ is the
   one place that lists the formats. Alternative: list every format in
   both. Non-blocker. **Decided by Maurice van Loon, 2026-10-06: the
   general texts.**
3. **Animated GIFs that are very large.** The verifier walks only the
   blocks before the first image and hashes the file streaming (its step
   261: 100 MB in 0.4 s); a slow host can still hit `max_execution_time`,
   which SPEC-013 reports as `interrupted`. Proposal: no cap, as decided
   for audio in SPEC-034. Non-blocker. **Decided by Maurice van Loon,
   2026-10-06: no cap.**

## Amendments

None.

## Traceability

| AC | Test | Implementation |
|----|------|----------------|
| AC1 | tests/Integration/GifTest.php :: AC1: a signed GIF gets the verifier's verdict; AC1: a GIF uploaded through the browser's REST route is checked as WP-CLI's import is; AC1: with trust settings that hold the signer, a GIF is "Verified: trusted signer" / SPEC-035 | — |
| AC2 | tests/Integration/GifTest.php :: AC2: an unsigned GIF is "No Content Credentials" / SPEC-035 | — |
| AC3 | tests/Integration/GifTest.php :: AC3: a GIF changed after signing is "Does not verify" / SPEC-035 | — |
| AC4 | tests/Integration/GifTest.php :: AC4: a GIF cut inside its C2PA_GIF block is "Does not verify"; one broken before it "Could not be checked" / SPEC-035 | — |
| AC5 | tests/Integration/GifTest.php :: AC5: a large GIF's original is checked, not its -scaled copy / SPEC-035 | — |
| AC6 | tests/Integration/GifTest.php :: AC6: a GIF is everywhere images are / SPEC-035 | — |
| AC7 | tests/Integration/GifTest.php :: AC7: WP-CLI skips a type it does not check without naming types; tests/Integration/RobustnessTest.php :: AC1: an unknown format is an error, not "no credential" (amended) / SPEC-035, SPEC-015 | — |
| AC8 | tests/Integration/GifTest.php :: AC8: BMP, PDF and AVI are still left alone (a guard, green before and after); tests/Integration/UploadTest.php :: AC5: leaves other file types alone (the GIF dataset now a BMP) / SPEC-035, SPEC-001 | — |
| AC9 | tests/Unit/ReadmeTest.php :: SPEC-035 AC9: the readme says GIF is checked / SPEC-035 | — |
