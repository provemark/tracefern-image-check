# SPEC-006: The release build

| Field      | Value                                             |
|------------|---------------------------------------------------|
| Status     | implemented                                       |
| Author     | Maurice van Loon                                  |
| Approved   | Maurice van Loon, 2026-09-26                      |
| Supersedes | —                                                 |

> Lifecycle: `draft` → maintainer approves → `approved` → tests-first →
> `implemented` (Traceability filled). No implementation code while `draft`.
> Only the Traceability section of an `approved` spec may change without a new
> approval; everything else needs a proposed amendment.

## Problem

Everything so far runs in the development environment, where `vendor/`
comes from `composer install` and the plugin is a mapped folder. A site
owner installs a zip. `notes/m5-packaging.md` measured what a zip built
naively would hold: the repository's tests, specs and tooling, and a
4.0 MB `vendor/` of which only the verifier's `src/` (680 KB), its
`LICENSE` and Composer's autoloader run. It also measured that Plugin Check
never scans `vendor/`, and that the WordPress Coding Standards' security
sniffs give 641 findings on the verifier's `src/`, none of which points at
a hole in how the plugin uses it (reasoned).

Decisions this spec follows (Maurice, 2026-09-26): the zip carries only
what runs; the shipped verifier is checked with the WPCS security sniffs
directly; findings go to Maurice, and to the verifier as issues when they
are real. `NOTES.md` (Temporary measures): the Plugin Check test's list of
excluded development files is replaced by checking the build.

## Scope

**In scope**

- `.gitattributes` with `export-ignore` for everything that does not ship.
- `composer build`: `git archive` of `HEAD` into
  `build/provemark-c2pa-check/`, `composer install --no-dev
  --optimize-autoloader` there, the verifier trimmed to `src/`, `LICENSE`
  and `composer.json`, and `build/provemark-c2pa-check.zip`.
- A second, clean wp-env (`.wp-env.release.json`, its own port) with no
  mapping: WordPress, Plugin Check, and the built zip as its only other
  plugin.
- Plugin Check on that build, with no excluded files (replacing the
  exclusion list in `tests/Integration/PluginCheckTest.php`).
- A WPCS security scan of the build's `vendor/provemark/c2pa-verifier/src`
  against a reviewed baseline (`tests/wpcs-verifier-baseline.json`: count
  per sniff for v0.2.3); a new sniff or a higher count fails.
- In CI: build, start the clean environment, run the release tests, on one
  PHP version.
- Screenshots of the column, the details and the settings page for the
  README (`docs/screenshots/`), not shipped.

**Out of scope** (each needs its own spec before it may be built)

- A GitHub release, a tag, or a wordpress.org submission (each needs
  Maurice's explicit go).
- Changing the verifier; its findings become issues there.

## Behavior

Acceptance criteria as Given/When/Then. Each is individually testable and will be
covered by a Pest test tagged `->group('SPEC-006')`, in `tests/Unit` when it
needs no WordPress and in `tests/Integration` (wp-env, WP-CLI) when it does.
At least one criterion MUST be an error / malformed-input path. This project
fails closed: it never shows a verdict the verifier did not give, a failure
is stored as state `error` with a reason, and the upload always proceeds.
Everything read from a file is untrusted text; a criterion that displays it
says how it is escaped.

- **AC1 — the zip holds what runs, and nothing else**
  - Given `composer build` on a clean checkout
  - When the zip's file list is read
  - Then it has one top folder `provemark-c2pa-check/` with the main file,
    `uninstall.php`, `readme.txt`, `README.md`, `LICENSE`, `composer.json`
    (amendment 1), `src/`, `trust/` and `vendor/` (Composer's autoloader and the verifier's `src/`,
    `LICENSE`, `composer.json`), and none of `tests/`, `specs/`, `notes/`,
    `.github/`, `AI-LOG.md`, `NOTES.md`, `package.json`, `composer.lock`,
    `.wp-env*.json`, `phpstan.neon`, `phpunit.xml`, `pint.json`, dotfiles,
    `*.key`, or the verifier's `src/Cli/` (amendment 4)

- **AC2 — the zip installs and works on a clean WordPress**
  - Given the clean environment with the built zip installed and active
  - When `fixture-signed.jpg` and the Pixel 10 photo are uploaded
  - Then their entries are `Valid` and `Trusted`, equal to the CLI with the
    default settings, and the settings page renders

- **AC3 — Plugin Check passes on the build, with nothing excluded**
  - Given the installed build
  - When `wp plugin check provemark-c2pa-check` runs without exclusions
  - Then it reports no errors and no warnings

- **AC4 — the shipped verifier matches its reviewed WPCS baseline** *(error path)*
  - Given the build's verifier `src/` and the baseline
  - When the WPCS security sniffs run
  - Then every sniff's count is at most its baseline count and no sniff
    outside the baseline appears; a planted `echo $_GET['x'];` in a copy
    makes the test fail

- **AC5 — a build without its verifier fails loudly** *(error path)*
  - Given a build whose `vendor/` is missing
  - When it is activated on the clean WordPress
  - Then it stays active, shows the "bundled libraries are missing" notice,
    and no upload gets an entry (M0.2's behaviour, now on the zip)

## References

- WordPress: [Plugin Check](https://wordpress.org/plugins/plugin-check/),
  [plugin readme](https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/);
  WPCS 3 security sniffs; Composer `--no-dev`, `git archive` and
  `.gitattributes` `export-ignore`.
- Oracle: `unzip -l` of the zip; Plugin Check 2.1.0 on the clean
  environment; PHPCS with WPCS 3; the verifier CLI with the default
  settings. Measured in `notes/m5-packaging.md`.
- Reasoned: that wp-env installs a zip listed under `plugins` in a
  `--config` file and gives that environment its own Compose project; to
  be measured first.

## API sketch

```text
composer build          → build/provemark-c2pa-check.zip
npm run release:start   → wp-env start --config .wp-env.release.json
composer test:release   → pest --testsuite=Release (tests/Release/)
```

## Open questions

None. Resolved by Maurice on 2026-09-26, as proposed in the draft:

- WPCS as a dev dependency: `wp-coding-standards/wpcs` ^3 under
  `require-dev`, run by the release test on the build only.
- The release job in CI runs on PHP 8.3, the lowest supported.

## Amendments

1. **2026-09-26, approved by Maurice van Loon.** AC1 listed
   `composer.json` among what must not ship. Plugin Check 2.1.0 warns
   `missing_composer_json_file` on the build: a `vendor/` directory
   without `composer.json` (a wordpress.org review guideline, so reviewers
   can see the dependencies). `composer.json` now ships; `composer.lock`
   does not.

2. **2026-09-26, approved by Maurice van Loon** with SPEC-009. The
   verifier ships prefixed, in `vendor-prefixed/provemark/c2pa-verifier/`
   (`src/`, `LICENSE`, `composer.json`), not in `vendor/provemark/`. AC1's
   required and forbidden paths and AC4's scanned path
   (`vendor-prefixed/provemark/c2pa-verifier/src`) follow the move; the
   WPCS baseline is unchanged (641 findings in 10 sniffs).

3. **Withdrawn 2026-09-26 by Maurice van Loon**, the same day: the header
   was removed to keep the plugin wordpress.org-ready (decision 2), and
   AC3 is "no errors or warnings" again. What it said: approved with `Update URI: false`
   (see `NOTES.md`). Plugin Check reports that header as an ERROR,
   `plugin_updater_detected`, because wordpress.org does not allow it.
   While the header is in the plugin, AC3 requires exactly that one
   finding and nothing else (ignoring it would make Plugin Check print
   nothing, not even "Checks complete"). Before a wordpress.org
   submission the header goes and AC3 is "no errors or warnings" again.

4. **2026-09-27, approved by Maurice van Loon.** The
   verifier's command-line tool (`src/Cli/`, one file, `Command.php`) no
   longer ships. The plugin never uses it (measured: no reference in
   `src/`, the main file or the rest of the verifier's `src/`), and it
   holds 13 of the 641 WPCS findings (`fopen`/`fwrite`/`fclose`,
   `file_get_contents`, two `set_error_handler`). The build removes it
   from `vendor/provemark/c2pa-verifier/src/` after `composer install`
   and regenerates the autoloader before Strauss runs, so no classmap or
   alias refers to it (measured in a scratch build on 2026-09-27: no
   autoload file mentions `Cli`; `fixture-signed.jpg` still `Valid`).
   - AC1: `vendor-prefixed/provemark/c2pa-verifier/src/Cli/` is among what
     must not ship.
   - AC4: the baseline drops to 628 findings in 6 sniffs
     (`ExceptionNotEscaped` 608, `base64_encode` 5, `base64_decode` 3,
     `set_error_handler` 4, `fread` 6, `json_encode` 2); verifier v0.2.3.

5. **2026-09-27, approved by Maurice van Loon after his review.** The
   bundled verifier moves to v0.2.4, a security release. AC4's baseline
   rises from 628 to 636 findings in the same 6 sniffs:
   `ExceptionNotEscaped` 608 → 616, every other count unchanged, verifier
   v0.2.4. Measured by running the sniff over the verifier's `src/` at
   both tags: all eight new findings are in `Manifest/Manifest.php`, four
   on each of two new exception messages (a JSON assertion over the size
   limit, and one over the item budget). They are exception messages,
   not output; the plugin shows no verifier exception message except
   `TrustException`'s, through `esc_html`.

6. **2026-09-27, approved by Maurice van Loon** with SPEC-016. `README.md`
   no longer ships: it is the GitHub page, and in the zip it said "not
   released yet" and linked to folders that do not ship. AC1 moves it from
   what must ship to what must not; `readme.txt` is the plugin's
   documentation on wordpress.org.

7. **2026-09-27, approved by Maurice van Loon after his review.** The
   bundled verifier moves to v0.2.5, a security release. AC4's baseline
   rises from 636 to 641 findings in the same 6 sniffs:
   `ExceptionNotEscaped` 616 → 621, every other count unchanged, verifier
   v0.2.5. Measured by running the sniff over the verifier's `src/` at
   both tags:
   - three findings in `Cose/CoseSign1.php`, the chain-placement
     refusals of the verifier's SPEC-047 (a missing-chain message
     replaced by a longer one);
   - two in `Trust/CertificateExtensions.php`, a certificate whose
     extensions cannot be read.

   They are exception messages, not output. The plugin shows no verifier
   exception message except `TrustException`'s, through `esc_html`.

8. **2026-09-28, approved by Maurice van Loon after his review.** The
   bundled verifier moves to v0.2.6, a security release. AC4's baseline
   rises from 641 to 642 findings in the same 6 sniffs:
   `obfuscation_base64_decode` 3 → 4, every other count unchanged,
   verifier v0.2.6. The new finding is `Trust/Certificate.php`'s
   `rsaExponent()` (the verifier's SPEC-049), which decodes the PEM of a
   certificate's public key to read its RSA exponent from the DER, as the
   three existing findings decode PEM. It decodes key material the file
   carries; nothing is executed.

9. **2026-09-30, approved by Maurice van Loon after his review.** The
   bundled verifier moves to v0.2.7 (its SPEC-050: a short read is not the
   end of the file). AC4's baseline falls from 642 to 638 findings in the
   same 6 sniffs: `file_system_operations_fread` 6 → 2, every other count
   unchanged, verifier v0.2.7. The five single `fread()` calls of v0.2.6
   now go through one helper, `Container/Read.php`, which reads in a loop;
   the other finding is `Hash/BmffHashCheck.php`'s range digest, a loop
   already. Nothing new is read or executed.

10. **2026-09-30, approved by Maurice van Loon after his review.** The
    bundled verifier moves to v0.2.8 (its SPEC-051, SPEC-052 and SPEC-053;
    SPEC-052 fixes a wrong `Trusted`). AC4's baseline rises from 621 to
    634 `WordPress.Security.EscapeOutput.ExceptionNotEscaped` findings;
    every other count is unchanged. All 13 are in
    `Hash/BmffHashCheck.php` (21 → 34, measured per file on v0.2.7 and
    v0.2.8), in six new exception messages: the merkle map's `alg` not
    text or not one of the three, the range digest's guard, and the three
    exclusion limits. Two of them carry text from the file (a hash
    algorithm name, an exclusion's `xpath`). They become a status
    explanation, which the plugin escapes where it shows one. Nothing new
    is read or executed.
11. **2026-10-05, approved by Maurice van Loon after his review.** The
    bundled verifier moves to v0.3.0 (WAV, AVI, MP3 and FLAC, which this
    plugin does not check, and a fix for a wrong `Trusted` under a name
    constraint, present since v0.2.5). AC4's baseline rises from 634 to
    695 `WordPress.Security.EscapeOutput.ExceptionNotEscaped` findings and
    from 5 to 6 `WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode`;
    every other count is unchanged. Measured per file on v0.2.9 and
    v0.3.0: `Container/RiffManifestStoreExtractor.php` 0 → 38 (the walk
    WebP, WAV and AVI now share), `Container/WebpManifestStoreExtractor.php`
    26 → 0 (moved into it), `Container/Id3ManifestStoreExtractor.php`
    0 → 36 (MP3 and FLAC), `Cose/PublicKey.php` 7 → 20 and one more
    `base64_encode` (an RSA-PSS public key read as rsaEncryption, built as
    PEM). The messages quote bytes from the file only as hex or printable
    characters; they become a status explanation, which the verifier keeps
    UTF-8 since v0.3.0 and the plugin escapes where it shows one. Nothing
    new is read or executed.
12. **2026-10-06, approved by Maurice van Loon after his review.** The bundled verifier moves to v0.4.0
    (GIF, which this plugin now checks, SPEC-035). AC4's baseline rises
    from 695 to 721 `WordPress.Security.EscapeOutput.ExceptionNotEscaped`
    findings; every other count is unchanged. Measured per file on
    `git archive` of v0.3.0 and v0.4.0 (`vendor/` equal to v0.4.0's
    `src/`): `Container/GifManifestStoreExtractor.php` 0 → 23 (the new
    reader), `Container/Id3ManifestStoreExtractor.php`,
    `IsobmffManifestStoreExtractor.php`, `JpegManifestStoreExtractor.php`
    and `PngManifestStoreExtractor.php` one more each (each rethrows its
    fault with `withStoreReached()`, the verifier's step 253),
    `Container/RiffManifestStoreExtractor.php` one fewer. The new messages
    carry numbers, and a byte of the file only as hex; they become a
    status explanation, which the plugin escapes where it shows one.
    Nothing new is read or executed.
13. **2026-10-07, approved by Maurice van Loon after his review.** The bundled verifier moves to v0.5.0
    (plain text, which this plugin now checks, SPEC-036). AC4's baseline
    rises from 721 to 744 `WordPress.Security.EscapeOutput.ExceptionNotEscaped`
    findings and from 2 to 4 `WordPress.WP.AlternativeFunctions.file_system_operations_fread`;
    every other count is unchanged. Measured per file with the same sniffs
    on `git archive` of v0.4.0 and on v0.5.0's `src/` from `vendor/`, both
    without `src/Cli` (v0.4.0 gave the baseline's counts exactly):
    `Container/PlainTextManifestStoreExtractor.php` 0 → 22 (21 messages,
    one `fread` in `isText()`) and `Container/SelectorReader.php` 0 → 3
    (two messages, one `fread` in `fill()`), the new text reader. The new
    messages carry numbers, offsets and fixed text, one of them PHP's own
    `preg_last_error_msg()`, never bytes of the file; they become a status
    explanation, which the plugin escapes where it shows one. The two reads
    take the stream in pieces of 64 KiB. Nothing new is executed.
    `MaintenanceCheckTest` reads `v0.5.0` from the lock.
14. **2026-10-08, approved by Maurice van Loon.** The bundled verifier moves to v0.5.1,
    a security release: a trust anchor outside its own validity no longer
    vouches for a signer, and only the active manifest's own signer makes
    a file `Trusted`. Measured for this plugin: every one of the 53
    bundled anchors is valid today (the earliest expires 2030-05-08), and
    all 601 files of the verifier's fixtures give byte-identical results
    under 0.5.0 and 0.5.1 with the plugin's bundled settings, DigiCert on
    and off (1,202 runs); the fix reaches custom trust settings only.
    AC4's baseline is unchanged: the same sniffs on v0.5.0's and v0.5.1's
    `src/` (without `src/Cli`) give 744 `ExceptionNotEscaped` and 4
    `fread` each. `composer.json` requires `^0.5.1`;
    `MaintenanceCheckTest` reads `v0.5.1` from the lock.
15. **2026-10-08, approved by Maurice van Loon.** The bundled verifier moves to v0.5.2,
    a security release: a certificate authority without keyUsage no
    longer issues. Measured for this plugin: none of the 53 bundled
    anchors lacks keyUsage, and all 604 files of the verifier's fixtures
    give byte-identical results under 0.5.1 and 0.5.2 with the plugin's
    bundled settings, DigiCert on and off (1,208 runs); the fix reaches
    custom trust settings only. AC4's baseline is unchanged (744
    `ExceptionNotEscaped`, 4 `fread`, measured on v0.5.2's `src/` without
    `src/Cli`). `composer.json` requires `^0.5.2`; `MaintenanceCheckTest`
    reads `v0.5.2` from the lock.
16. **2026-10-09, approved by Maurice van Loon.** The bundled verifier moves to v0.5.3.
    It refuses four kinds of certificate or manifest box that 0.5.2 accepted: a
    TSA leaf off the certificate profile, a JUMBF description box without
    Requestable, a serial number that is not positive, and RSASSA-PSS
    parameters outside SHA-256/384/512 or with another MGF1 hash. Two of
    these gave `Trusted` for certificates issued as they are. Measured for
    this plugin: the verifier's 483 JPEG, PNG and WebP fixture files under the
    bundled settings, DigiCert on and off (966 runs), under 0.5.2 and 0.5.3.
    36 runs move, all from `Valid` to `Invalid`, all synthetic probes of the
    verifier's timestamp and trust matrices. No real file moves. AC4's
    baseline: one more `ExceptionNotEscaped` (745), the new message in
    `Jumbf/JumbfParser.php`, whose only value is an integer offset; the other
    counts are unchanged (measured on v0.5.3's `src/` without `src/Cli`).
    `composer.json` requires `^0.5.3`; `MaintenanceCheckTest` reads `v0.5.3`
    from the lock.
17. **2026-10-09, approved by Maurice van Loon.** The bundled verifier moves to v0.6.0,
    the release that checks the rest of the C2PA 2.4 validator rules this
    plugin's formats meet: cloud data, the time-stamp assertion, the
    alternative content representation, revocation beyond the signer's own
    staple, the certificate profile above the signer, duplicate and
    malformed manifest labels. Measured for this plugin: the verifier's 547
    JPEG, PNG and WebP fixture files under the bundled settings, DigiCert on
    and off (1094 runs), under 0.5.3 and 0.6.0. 22 files move state, all
    synthetic probes of the verifier (manifest, revocation, profile and
    chain probes): 21 from `Valid` to `Invalid`, and `type-c2md.png` from
    `Invalid` to `Valid` (the c2md box type is now read, as `c2patool`
    does). Three more probes keep their state and change codes. No real
    file moves state or codes. AC4's baseline: nine more
    `ExceptionNotEscaped` (754). Four are in the plain-text reader's new
    messages, whose values are integers (offsets, lengths, a version). Five
    are in the two new label refusals (`Manifest/Manifest.php`,
    `Manifest/ManifestStore.php`), whose value is the manifest label from
    the file; the plugin stores and shows status codes only, never a
    verifier message, so no label reaches output. The other counts are
    unchanged (measured on v0.6.0's `src/` without `src/Cli`).
    `composer.json` requires `^0.6`; `MaintenanceCheckTest` reads `v0.6.0`
    from the lock. SPEC-036 amendment 1 follows the new text code.
18. **2026-10-13, approved by Maurice van Loon.** The bundled verifier moves to v0.6.1.
    It reads an icon stored in a data box, the mechanism of earlier C2PA
    versions, and checks its hash, where 0.6.0 refused it. Real files have
    one: the Drupal module C2PA Sign 1.4.11 stores the site's logo that way
    in every manifest it writes. Measured for this plugin: the verifier's
    552 JPEG, PNG and WebP fixture files under the bundled settings,
    DigiCert on and off (1,104 runs), under 0.6.0 and 0.6.1. 2 runs move
    state, both of `databox/c2pasign-logo.png`, the real C2PA Sign file,
    from `Invalid` to `Valid` (its signer is not on the bundled list). The
    four synthetic data-box probes stay `Invalid`; one changes its code
    from `assertion.missing` to `assertion.hashedURI.mismatch`. No other
    file changes state or codes. AC4's baseline is unchanged (the release
    suite passes on v0.6.1's `src/` without `src/Cli`). `composer.json`
    keeps `^0.6`; `MaintenanceCheckTest` reads `v0.6.1` from the lock.

19. **2026-10-13, approved by Maurice van Loon.** The bundled verifier moves to v0.7.0,
    a minor: a data hash (or a BMFF hash) may no longer leave the image outside
    what is signed (its SPEC-068, SPEC-069, SPEC-071), Pixel Camera video
    verifies (SPEC-072), and an ingredient failure a signer acknowledged is
    reported as an informational line (SPEC-070, `ValidationStatus::$acknowledged`).
    The plugin reads `validation_status` (failures only) and an ingredient
    delta's `failure` list, so the acknowledged lines change nothing it reads.
    Measured for this plugin through its own `Outcome` (state, codes, AI
    history): the verifier's 563 JPEG, PNG and WebP fixture files under the
    bundled settings, DigiCert on and off (1,126 runs), under 0.6.1 and 0.7.0,
    0 exceptions. 16 runs move state, all eight attack files of the verifier's
    `exclusion-coverage/` (the five PNG and three JPEG shapes whose hash leaves
    the image out), from `Valid` to `Invalid`; their throw-away signer is not
    on the bundled list, hence `Valid` before, not `Trusted`. Two more files,
    `binding/exclusion-extra.png` and `binding/exclusions-unsorted.png`, keep
    `Invalid` and change their codes. Nothing else changes, the AI history
    included. `composer.json` moves to `^0.7`; `MaintenanceCheckTest` reads
    `v0.7.0` from the lock (red on `v0.6.1`, green after). AC4's baseline
    rises by fifteen `ExceptionNotEscaped` (754 to 769), reviewed: twelve in
    the new JPEG segment walk (`JpegExclusionAllowance`: offsets, lengths and
    marker bytes, integers or hex only), two in `StreamReader::seek()` (an
    offset and a fixed noun), and one in `BmffHashCheck`'s refused-exclusion
    message (the xpath from the file). The plugin keeps only failure codes
    (`Outcome::failureCodes()`) and never shows a verifier message, so none
    of them reaches a page.

20. **2026-10-22, approved by Maurice van Loon.** The bundled verifier moves to v0.7.1,
    a patch: an ingredient failure counts as acknowledged only when the
    signer recorded it under `failure` (its SPEC-070 amendment 2, C2PA 2.4
    §18.16.12.4.3). A failure a v3 ingredient assertion files under
    `success` or `informational` used to be acknowledged; it now stays a
    failure, so the file reads `Invalid`. `src/` changes in one file
    (`Verifier/IngredientManifestCheck.php`), with no new message.
    Measured for this plugin through its own `Outcome` (state, codes, AI
    history): the verifier's 566 JPEG, PNG and WebP fixture files (its 563
    of amendment 19 and the three new `acknowledged-kind/` files) under the
    bundled settings, DigiCert on and off (1,132 runs), under 0.7.0 and
    0.7.1, 0 exceptions. 4 runs move state, the two crafted files
    `acknowledged-kind/recorded-success.jpg` and
    `recorded-informational.jpg`, from `Valid` to `Invalid` with
    `claimSignature.mismatch` (and `signingCredential.untrusted`: their
    throw-away signer is not on the bundled list). Nothing else changes, the
    AI history included. `composer.json` keeps `^0.7`; `MaintenanceCheckTest`
    reads `v0.7.1` from the lock (red on `v0.7.0`, green after). AC4's
    baseline counts stand (the release suite passes on v0.7.1's `src/`);
    only its `verifier` field moves.

21. **2026-10-23, approved by Maurice van Loon ("wordpress kan nu al"; the baseline and plugin 0.7.0: "ja goed").** The bundled verifier moves to v0.8.0, a minor: a validation time, crJSON and the conformance programme's trust lists, none of which the plugin calls, and the fixes of a review of the whole code base, two of which (an ingredient acknowledging its own failures; a redaction reaching the active manifest) apply to JPEG, PNG and WebP. `composer.json` moves to `^0.8`; `MaintenanceCheckTest` reads `v0.8.0` from the lock (red on `v0.7.1`, green after). Measured for this plugin through its own `Outcome` (every field): the verifier's 572 JPEG, PNG and WebP fixture files under the bundled settings, DigiCert on and off (1,144 runs), under 0.7.1 and 0.8.0, 0 exceptions: no entry moves, in any field. AC4's baseline grows, each finding reviewed: two `json_encode`, two `serialize` (a comparison; nothing is unserialized), two `base64_encode`, one `base64_decode` and 27 `ExceptionNotEscaped`, all in code the plugin never calls (`toCrJson()`, `fromTrustLists()`); `tests/wpcs-verifier-baseline.json` names each.

22. **2026-10-24, approved by Maurice van Loon ("akkoord, bouw stap 1 en 2").** The bundled verifier moves to v0.8.1, a patch: two ways to a wrong `Trusted` that 0.8.0 has, both for JPEG, PNG and WebP, are closed. A second ingredient assertion naming the same manifest now has its own hash checked (the verifier's SPEC-021 amendment 7), and an ingredient whose references lead back to the active manifest can no longer redact one of its assertions (SPEC-035 amendment 7); a cyclic reference is no longer acknowledged by a recorded status (SPEC-070 amendment 4). `src/` changes in four files (`Manifest/ManifestGraph.php`, `Manifest/ManifestStore.php`, `Verifier/IngredientManifestCheck.php`, `Verifier/Verifier.php`), with no new message. `composer.json` stays on `^0.8`; `MaintenanceCheckTest` reads `v0.8.1` from the lock (red on `v0.8.0`, green after). Measured through the plugin's own `Outcome` (every field but the time and the verifier version): the verifier's 581 JPEG, PNG and WebP fixture files under the bundled settings, DigiCert on and off (1,162 runs), under 0.8.0 and 0.8.1, 0 exceptions: only the five fault files of `graph-fuzz/`, `graph-fuzz-cycle/` and `graph-fuzz-cycle-acknowledged/` move, `Valid` to `Invalid`, in both modes (10 runs); their signer is a throw-away root outside the bundled lists, hence `Valid` and not `Trusted`. WPCS on the verifier's `src/` of both versions gives the same counts, so AC4's baseline stands; only its `verifier` field moves.

## Traceability

| Acceptance criterion | Test (file :: name / group) | Source (file/symbol) |
|----------------------|-----------------------------|----------------------|
| AC1 | `tests/Release/ReleaseTest.php` :: AC1 | `.gitattributes`, `tools/build.sh` |
| AC2 | `tests/Release/ReleaseTest.php` :: AC2 | the built zip; `.wp-env.release.json` |
| AC3 | `tests/Release/ReleaseTest.php` :: AC3 | the built zip (Plugin Check 2.1.0, nothing excluded) |
| AC4 | `tests/Release/ReleaseTest.php` :: AC4 | `tests/wpcs-verifier-baseline.json`; `wpcsFindings()`, `beyondBaseline()` in `tests/Pest.php` |
| AC5 | `tests/Release/ReleaseTest.php` :: AC5 | `provemark-c2pa-check.php` (the missing-vendor notice) |
| Screenshots | — (made by hand in Chrome on the clean environment) | `docs/screenshots/`, `README.md` |
