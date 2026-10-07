# SPEC-036: Check plain-text uploads

| Field      | Value                                             |
|------------|---------------------------------------------------|
| Status     | approved                                          |
| Author     | Maurice van Loon                                  |
| Approved   | Maurice van Loon, 2026-10-07                      |
| Supersedes | —                                                 |

> Lifecycle: `draft` → maintainer approves → `approved` → tests-first →
> `implemented` (Traceability filled). No implementation code while `draft`.
> Only the Traceability section of an `approved` spec may change without a new
> approval; everything else needs a proposed amendment.

## Problem

Text can carry Content Credentials: C2PA 2.4 §A.8 appends the manifest
store to the text as a `C2PATextManifestWrapper`, a run of invisible
Unicode variation selectors after the visible text. The bundled verifier
reads and verifies it since c2pa-verifier 0.5.0 (its SPEC-060), but only
when the caller turns text on: off by default, because the scheme is
experimental in `c2pa-rs` (the `unstable_plain_text` feature, not in a
stock `c2patool`). The plugin requires `^0.4.0`, constructs the verifier
without the text reader, and checks only images and audio
(`UploadHook::MIME_TYPES`): a text upload gets no verdict.

Measured on 2026-10-07 in the plugin's development environment (WordPress
7.1, PHP 8.3, wp-env, plugin 0.3.0), with the verifier's text fixtures
(provemark/c2pa-verifier v0.5.0, `tests/Fixtures/`) imported through
`wp media import`:

| file | allowed by default | WordPress's type (`wp_check_filetype_and_ext()`, `finfo`) | post_mime_type | bytes after upload | metadata | served | stored by the plugin |
|---|---|---|---|---|---|---|---|
| `fixture-signed.txt` (14,225 bytes) | yes | `text/plain`, `text/plain` | `text/plain` | unchanged (SHA-256 equal) | `filesize` only | the same bytes, `Content-Type: text/plain` | nothing |
| `text/nfd-emoji-signed.txt` (signed from NFD input, an emoji's `U+FE0F`, a lone `U+FEFF`) | yes | `text/plain`, `text/plain` | `text/plain` | unchanged | `filesize` only | the same bytes | nothing |
| `text/flip-text.txt` (one letter changed after signing) | yes | `text/plain`, `text/plain` | `text/plain` | unchanged | `filesize` only | — | nothing |
| `fixture-unsigned.txt` | yes | `text/plain`, `text/plain` | `text/plain` | unchanged | `filesize` only | — | nothing |

Two more were only typed, not uploaded (`wp_check_filetype_and_ext()` on the
file, as an upload does): `text/utf16le.txt` (the signed text in UTF-16LE)
is `application/octet-stream` to `finfo` and **refused** by WordPress (`type`
false), so it never reaches the plugin; a Latin-1 text and the signed text
with one byte `FF` appended are `text/plain` and accepted, though neither
is UTF-8.

`text/plain` is WordPress's type for `txt|asc|c|cc|h|srt`. WordPress makes
no sizes of a text and rewrites nothing: the original is the file that was
signed, as for audio (SPEC-034). The measurement repository
provemark/wp-image-c2pa-measurements found the same on 2026-10-06 for a
`.txt` uploaded over the REST API by an administrator and by an author
(`bin/text-measure.sh`): stored and served unchanged, `Valid`.

What WordPress does to signed text **in a post** (its typography breaks
the signature on the page) is a different job and is out of scope here.

## Scope

**In scope**

- Plain text (`text/plain`) checked as images and audio are: in the
  background, on the uploaded file, with the stored result, the verdicts
  and the AI label of SPEC-001, SPEC-002, SPEC-003 and SPEC-027, and
  everywhere SPEC-034 AC6 lists.
- c2pa-verifier `^0.5.0`; the verifier's text reader turned on for a
  `text/plain` attachment (open question 2).
- `ExistingImages`, whose queries name each checked type by position: the
  ninth type, guarded by its test (the lesson of 0.3.0, SPEC-035 AC6).
- The readme: the description, the FAQ on formats, one sentence that text
  is experimental and hashed byte for byte, the changelog.

**Out of scope** (each needs its own spec before it may be built)

- Signed text in post content, excerpts or comments.
- Other text types (`text/csv`, `text/html`, `text/markdown` and C2PA 2.4
  §A.7 and §A.9 for HTML and structured text).
- Unicode normalisation: the verifier hashes the bytes as they are
  (its SPEC-060 open question 5).

## Behavior

The text fixtures are the verifier's (v0.5.0: `tests/Fixtures/fixture-signed.txt`,
`fixture-unsigned.txt`, and `text/nfd-emoji-signed.txt`, `text/flip-text.txt`,
`text/two-wrappers.txt`), copied into the plugin's test fixtures with their
source named.

- **AC1 — a signed text: the verifier's verdict**
  - Given `fixture-signed.txt` and `nfd-emoji-signed.txt`, uploaded through
    `wp media import` and through the browser's REST route
  - When the background check has run
  - Then the stored result has the state and failure codes that
    `vendor/bin/c2pa-verify --text` gives for the same file with the same
    trust settings, `format` `text`, and the Media Library shows the
    matching verdict (with the bundled lists, the test certificate:
    "Intact: signer not trusted"; with trust settings that hold the
    signer: "Verified: trusted signer")

- **AC2 — an unsigned text: "No Content Credentials"**
  - Given `fixture-unsigned.txt`, uploaded
  - Then state `none`, as SPEC-001 AC2

- **AC3 — changed after signing: "Does not verify"** *(error path)*
  - Given `flip-text.txt`
  - Then state `Invalid` with `assertion.dataHash.mismatch`, and no AI
    label

- **AC4 — damaged or not UTF-8: the verifier's verdict, the upload
  proceeds** *(error path)*
  - Given `two-wrappers.txt` (two wrappers: the manifest was reached) and
    the signed text with one byte `FF` appended (built in the test; typed
    `text/plain` by WordPress, measured, but not UTF-8)
  - Then the first is "Does not verify" (`Invalid`, `general.error`), the
    second "Could not be checked" (`error` / `unsupported`); each upload
    succeeds

- **AC5 — the reader is on only for text** *(open question 2)*
  - Given every image and audio fixture the plugin's tests already use,
    and the bytes of a UTF-8 text stored under a `.jpg` name and checked
    (built in the test)
  - Then every image and audio verdict is unchanged, and the text under a
    `.jpg` name is `error` / `unsupported`, as before this spec (SPEC-015
    AC1): turning text on for `text/plain` changes no other verdict

- **AC6 — text everywhere images are**
  - Then a checked text appears in the column, the details, the verdict
    filter and sort, the dashboard counts, "Check files that were never
    checked", `wp tracefern check --all` and `tracefern_verdict`, as
    SPEC-034 AC6 for audio; `ExistingImages`' queries name nine types

- **AC7 — other text types are still left alone**
  - Given a CSV (`text/csv`) and a WebVTT (`text/vtt`) upload, both
    allowed by default (measured)
  - Then no result is stored and the column shows nothing, as SPEC-001
    AC5

- **AC8 — the readme says it**
  - Then the description and the FAQ "Which formats are checked?" name
    plain text and say it is experimental and checked byte for byte; the
    readme stays within wordpress.org's limits (`ReadmeTest`)

## References

- Specification: C2PA 2.4 §A.8 (unstructured text). WordPress:
  [`wp_check_filetype_and_ext()`](https://developer.wordpress.org/reference/functions/wp_check_filetype_and_ext/),
  [`get_allowed_mime_types()`](https://developer.wordpress.org/reference/functions/get_allowed_mime_types/).
- Oracle: provemark/c2pa-verifier v0.5.0 and its text fixtures,
  `vendor/bin/c2pa-verify --text [--settings f] <file>`; the verifier
  measured its text reader against `c2patool` 0.28.1 built with
  `unstable_plain_text` (its steps 265–269). Measured on WordPress 7.1,
  PHP 8.3, wp-env, 2026-10-07 (the table above).
- Reasoned: that the browser's upload routes treat a text as
  `wp media import` does (the same `wp_handle_upload()`); AC1 measures the
  REST route, as the measurement repository did.

## API sketch

```php
// namespace Tracefern\ImageCheck;
final class UploadHook
{
    public const array MIME_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'audio/wav', 'audio/x-wav', 'audio/mpeg', 'audio/flac', 'text/plain'];
}

final class Checker
{
    // the attachment's type decides whether the verifier's text reader is on (open question 2)
    public function check(string $path, ?TrustSettings $settings = null, string $trust = 'none', ?string &$sha256 = null, bool $text = false): array;
}
```

## Open questions

1. **On by default, or a setting?** The library keeps text off by default
   because a caller should choose a verdict format that may still change.
   Here the site owner's own uploads are checked, the plugin only shows a
   verdict and never blocks, and a setting adds a screen and a state to
   test. Proposal: on, with the readme saying it is experimental.
   Non-blocker. **Decided by Maurice van Loon, 2026-10-07: as proposed.**
2. **The reader on for every file, or only for `text/plain`
   attachments?** On for every file is one line, but then a file of an
   image or audio type whose bytes are UTF-8 text (an administrator may
   upload one) becomes "No Content Credentials" instead of "Could not be
   checked". Proposal: only for `text/plain`, through a flag on
   `Checker::check()` that `UploadHook` sets from the attachment's type, so
   that no other verdict moves (AC5). Non-blocker. **Decided by Maurice van Loon, 2026-10-07: as proposed.**
3. **All of `text/plain`, or only `.txt`?** WordPress gives `.asc`, `.c`,
   `.cc`, `.h` and `.srt` the same type. The verifier reads the content,
   not the name; any of them without a wrapper is "No Content
   Credentials". Proposal: all of `text/plain`. Non-blocker. **Decided by Maurice van Loon, 2026-10-07: as proposed.**
4. **The version.** A new format, as GIF was for 0.3.0, and a new verifier
   minor version. Proposal: plugin 0.4.0. Non-blocker. **Decided by Maurice van Loon, 2026-10-07: as proposed.**

## Amendments

None.

## Traceability

| AC | Test | Implementation |
|----|------|----------------|
| AC1 | tests/Integration/TextTest.php :: AC1: a signed text gets the verifier's verdict (two datasets); AC1: a text uploaded through the browser's REST route is checked as WP-CLI's import is; AC1: with trust settings that hold the signer, a text is "Verified: trusted signer" / SPEC-036 | — |
| AC2 | tests/Integration/TextTest.php :: AC2: an unsigned text is "No Content Credentials" / SPEC-036 | — |
| AC3 | tests/Integration/TextTest.php :: AC3: a text changed after signing is "Does not verify" / SPEC-036 | — |
| AC4 | tests/Integration/TextTest.php :: AC4: two wrappers are "Does not verify"; a text that is not UTF-8 "Could not be checked" / SPEC-036 | — |
| AC5 | tests/Integration/TextTest.php :: AC5: a UTF-8 text stored under a .jpg name is still not a type the plugin can check (a guard, green before and after); every other test file, unchanged / SPEC-036 | — |
| AC6 | tests/Integration/TextTest.php :: AC6: a text is everywhere images are / SPEC-036 | — |
| AC7 | tests/Integration/TextTest.php :: AC7: CSV and WebVTT are still left alone (a guard, green before and after) / SPEC-036 | — |
| AC8 | tests/Unit/ReadmeTest.php :: SPEC-036 AC8: the readme says plain text is checked, experimentally and byte for byte / SPEC-036 | — |
