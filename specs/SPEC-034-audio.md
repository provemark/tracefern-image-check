# SPEC-034: Check audio uploads (WAV, MP3, FLAC)

| Field      | Value                                             |
|------------|---------------------------------------------------|
| Status     | approved                                          |
| Author     | Maurice van Loon                                  |
| Approved   | Maurice van Loon, 2026-10-05                      |
| Supersedes | —                                                 |

> Lifecycle: `draft` → maintainer approves → `approved` → tests-first →
> `implemented` (Traceability filled). No implementation code while `draft`.
> Only the Traceability section of an `approved` spec may change without a new
> approval; everything else needs a proposed amendment.

## Problem

The bundled verifier reads and verifies WAV, MP3 and FLAC since
c2pa-verifier 0.3.0 (its SPEC-055, SPEC-056 and SPEC-057; C2PA 2.4 §A.3.7
for the RIFF `C2PA` chunk, §A.3.4 for the ID3v2 GEOB frame). The plugin
checks only JPEG, PNG and WebP (SPEC-001 AC5): an audio upload gets no
verdict, and the readme says "Video and audio are not checked". The
plugin's brief had audio out of scope; this spec is the proposal to bring
it in.

Measured on 2026-10-05 in the plugin's test environment (WordPress 7.1.2,
PHP 8.3, wp-env), with the verifier's signed fixtures imported through
`wp media import`:

| file | allowed by default | post_mime_type | bytes after upload | what WordPress reads |
|---|---|---|---|---|
| `fixture-signed.wav` | yes | `audio/wav` | unchanged (SHA-256 equal) | format, codec, sample rate, length |
| `fixture-signed.mp3` | yes | `audio/mpeg` | unchanged | format, sample rate, length |
| `fixture-signed.flac` | yes | `audio/flac` | unchanged | format, sample rate, length |
| `fixture-signed.avi` | yes | `video/avi` | unchanged | format, width, height, length |

WordPress makes no sizes of audio and rewrites nothing: the original is
the file that was signed, which is simpler than for images (SPEC-001 AC4).
The plugin stores nothing for any of the four today.

One finding shapes the design. An MP3 whose ID3 tag carries cover art (an
`APIC` frame) gets that picture extracted by
[`wp_generate_attachment_metadata()`](https://developer.wordpress.org/reference/functions/wp_generate_attachment_metadata/)
into a separate JPEG attachment, byte for byte the embedded image, linked
from the audio by its `_thumbnail_id`. The plugin already checks that
JPEG as an image upload. Measured with a cover that carried Content
Credentials of its own: the cover was "Intact: signer not trusted" while
the MP3 showed nothing. A reader can take the cover's verdict for the
audio's; this spec keeps the two apart.

## Scope

**In scope**

- WAV (`audio/wav`, also `audio/x-wav`), MP3 (`audio/mpeg`) and FLAC
  (`audio/flac`) checked as images are: in the background, on the
  original, with the stored result, the verdicts and the AI label of
  SPEC-001, SPEC-002, SPEC-003 and SPEC-027.
- Every place that is limited to the three image types today: the upload
  and file-change hooks, the Media Library column, the attachment details,
  sorting and filtering (SPEC-007), the dashboard summary (SPEC-033), the
  settings-page checks (SPEC-031), WP-CLI (SPEC-008) and the
  `tracefern_verdict` filter (SPEC-026).
- Extracted cover art: its own verdict as an image, with its origin named.
- The readme: the short description, the description, the FAQ on formats.

**Out of scope** (each needs its own spec before it may be built)

- AVI, MP4 and every other video format; Ogg, M4A, AAC and other audio.
- Renaming the plugin (it stays "Tracefern Image Check for C2PA"; the slug
  cannot change).
- A player, a badge or anything on the front end.
- Checking the audio inside a video, or a cover image's relation to the
  audio beyond naming it.

## Behavior

The audio fixtures are the verifier's (`tests/Fixtures/fixture-signed.*`
and `fixture-unsigned.*` of provemark/c2pa-verifier v0.3.0), copied into
the plugin's test fixtures with their source named.

- **AC1 — a signed WAV, MP3 or FLAC: the verifier's verdict**
  - Given each signed fixture, uploaded
  - When its background check has run
  - Then the stored result has the state and failure codes that
    `vendor/bin/c2pa-verify` gives for the same file with the same trust
    settings, `format` `wav`, `mp3` or `flac`, and the Media Library shows
    the matching verdict (with the bundled lists, the test certificate:
    "Intact: signer not trusted")

- **AC2 — an unsigned one: "No Content Credentials", not "Does not verify"**
  - Given each unsigned fixture, uploaded
  - Then state `none`, as SPEC-001 AC2 for images

- **AC3 — changed after signing: "Does not verify"** *(error path)*
  - Given a signed MP3 with one byte of its audio frames changed
  - Then state `Invalid` with `assertion.dataHash.mismatch`, and no AI
    label

- **AC4 — damaged or unreadable: "Could not be checked", the upload
  proceeds** *(error path)*
  - Given a signed WAV cut short inside its `C2PA` chunk, and an audio
    file that cannot be opened
  - Then each is stored as state `error` with its reason, shown as "Could
    not be checked", and the upload itself succeeds; a check interrupted by
    a time or memory limit is `error` / `interrupted` (SPEC-013), never an
    empty field

- **AC5 — other formats are still left alone**
  - Given an AVI, an Ogg and a PDF upload
  - Then no result is stored and the column shows nothing, as SPEC-001 AC5

- **AC6 — audio everywhere images are**
  - Given a library with checked images and checked audio
  - Then the audio appears in the column, the details, the verdict filter
    and sort, the dashboard counts, "Check images that were never checked",
    `wp tracefern check --all` and `tracefern_verdict`, exactly as images
    do

- **AC7 — cover art keeps its own verdict, and says whose cover it is**
  - Given an MP3 with an `APIC` cover that carries Content Credentials of
    its own, uploaded
  - Then the MP3 has its own result and the extracted JPEG its own; the
    JPEG's details say it is the cover of that audio, with the audio's
    title escaped (`esc_html`); the cover's verdict is never shown on the
    audio, nor the audio's on the cover

- **AC8 — the readme says it**
  - Then the short description and the FAQ "Which formats are checked?"
    name WAV, MP3 and FLAC; the FAQ still says video is not checked; the
    readme stays within wordpress.org's limits (`ReadmeTest`)

## References

- Specification: C2PA 2.4 §A.3.4 (ID3v2 GEOB, MP3 and FLAC) and §A.3.7
  (RIFF `C2PA`, WAV). WordPress:
  [`wp_read_audio_metadata()`](https://developer.wordpress.org/reference/functions/wp_read_audio_metadata/),
  [`wp_generate_attachment_metadata()`](https://developer.wordpress.org/reference/functions/wp_generate_attachment_metadata/)
  (the cover art), `get_allowed_mime_types()`.
- Oracle: provemark/c2pa-verifier v0.3.0, its signed and unsigned audio
  fixtures, `vendor/bin/c2pa-verify [--settings f] <file>`; measured on
  WordPress 7.1.2, PHP 8.3, wp-env, 2026-10-05 (the table above).
- Reasoned: that WordPress's browser upload (`async-upload.php`, REST
  `POST /wp/v2/media`) treats audio as `wp media import` does: the same
  `wp_handle_upload()` and `wp_generate_attachment_metadata()`. Measured
  only through WP-CLI; the tests measure the REST path too.

## API sketch

```php
// namespace Tracefern\ImageCheck;
final class UploadHook
{
    public const array MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'audio/wav', 'audio/x-wav', 'audio/mpeg', 'audio/flac'];
}
```

`ExistingImages` builds its `IN (…)` from the constant instead of three
fixed placeholders. The cover's origin is found through the audio's
`_thumbnail_id`, read when the details are shown; nothing new is stored.

## Open questions

1. **Large WAV files.** The verifier reads them streaming, in flat memory
   (its step 211: 33 MB peak at 2 GB), but hashing takes time, and a
   background check on a slow host can hit `max_execution_time`; AC4 then
   gives `interrupted`. Proposal: no size cap, the interruption reported as
   it is. Alternative: a cap (for instance 512 MB) above which the file is
   `error` / `too_large` without reading it. Non-blocker. **Decided by
   Maurice van Loon, 2026-10-05: no cap**; AC4 covers the interruption.
2. **Wording that says "images".** The settings button ("Check images that
   were never checked"), the dashboard title and parts of the readme say
   images. Proposal: "files" where audio is included, the plugin name
   unchanged. Non-blocker. **Decided by Maurice van Loon, 2026-10-05:
   "files" where audio is included.**
3. **The name.** "Image Check" undersells audio. Out of scope here (see
   above); worth deciding before the readme's short description changes.
   Non-blocker for this spec. **Decided by Maurice van Loon, 2026-10-05:
   the name stays for now; renaming is a decision of its own.**

## Traceability

Filled when status becomes `implemented`. Every acceptance criterion maps to at
least one test; every source file maps back to this spec.

| Acceptance criterion | Test (file :: name / group) | Source (file/symbol) |
|----------------------|-----------------------------|----------------------|
| AC1                  | —                           | —                    |
| AC2                  | —                           | —                    |
| AC3                  | —                           | —                    |
| AC4                  | —                           | —                    |
| AC5                  | —                           | —                    |
| AC6                  | —                           | —                    |
| AC7                  | —                           | —                    |
| AC8                  | —                           | —                    |
