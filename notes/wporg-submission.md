# Checklist: submitting to wordpress.org

Written 2026-09-27. Not shipped. Every step that publishes anything is
Maurice's to take. Sources, read 2026-09-27 (developer.wordpress.org):
[Planning, Submitting, and Maintaining Plugins](https://developer.wordpress.org/plugins/wordpress-org/planning-submitting-and-maintaining-plugins/),
[How to Use Subversion](https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion/),
[Plugin Assets](https://developer.wordpress.org/plugins/wordpress-org/plugin-assets/).
Items marked *reasoned* are not stated on those pages; check them then.

## 1. Before submitting (Maurice)

- [x] A wordpress.org account with an email address that is read
      regularly; allow mail from `plugins@wordpress.org` (the review
      comes by email).
- [ ] Decide the version for the first release (now `0.1.0`); if it
      changes: the plugin header, `Stable tag` in `readme.txt`, the
      changelog and upgrade notice.
- [ ] Decide whether the GitHub repository becomes public (not required
      by wordpress.org).
- [ ] The name. The slug comes from the submission and **cannot be changed
      afterwards**; the display name can. Expected slug:
      `provemark-c2pa-check` (*reasoned*: derived from the plugin name).
      "C2PA" is the coalition's name; the guidelines ask not to use
      others' trademarks in a way that suggests endorsement (*reasoned*: a
      reviewer may ask; "Provemark" comes first for that reason).
      Outcome: the first review (2026-09-28) rejected "Provemark" as a
      possible third-party brand and asked for C2PA after "for"; renamed
      to Tracefern Image Check for C2PA (SPEC-020), new slug
      `tracefern-image-check-for-c2pa` to be requested in the reply.

## 2. Prepare the release (Claude, on request)

- [x] `Contributors:` in `readme.txt` with Maurice's wordpress.org
      username (case-sensitive).
- [x] `Tested up to` equals the current WordPress major version
      (2026-09-27: 7.1; latest release 7.1.2 per api.wordpress.org).
- [ ] `composer check`, `composer test:integration`,
      `composer test:multisite` green; then `composer test:release`,
      which builds `build/provemark-c2pa-check.zip` and runs Plugin Check
      on it (no errors, no warnings).
- [x] `readme.txt` through the
      [readme validator](https://wordpress.org/plugins/developers/readme-validator/)
      (2026-09-27, the readme of `8083358`: no errors, no warnings; notes
      only: the tags `c2pa` and `provenance` are not widely used, no donate
      link).
- [ ] The zip installed by hand once on a clean WordPress (the release
      environment, port 8890) and an image uploaded.

## 3. Submit (Maurice)

- [x] **Submitted by Maurice on 2026-09-27**, the zip of `d938e6c` (SHA-256
      `aa393b53…7a57b7d6`; a copy kept in `build/submitted/`, which the
      build does not clear).
- [x] **A new version uploaded by Maurice on 2026-09-27**, before the review
      began: the zip of `3566af8`, with verifier v0.2.5 (four wrong
      `Trusted` fixed there), 286,674 bytes, SHA-256
      `8c7d7548e4aa1193729cd48aa9813249f8255b41b5c34e24a3507933a50b693e`
      (CI green on every job; kept in `build/submitted/`). Reviewed in the
      first round (below).
- [x] **Frozen until the reviewer replies** (decided by Maurice,
      2026-09-27): no more uploads before the review, as repeated uploads
      would look unsettled. Changes wait for the reviewer's reply (then one
      new version with them) or for 0.1.1: first SPEC-019, the Development
      section in `readme.txt`. `build/provemark-c2pa-check.zip` may be newer
      than what is under review; the submitted zips are in `build/submitted/`.
      Update 2026-09-28: the reviewer replied; SPEC-019 went along in the
      corrected upload below, with SPEC-020 to SPEC-023. Frozen again until
      the next reply.
- [x] **First review received 2026-09-28** (automated pre-review, Review ID
      `P0TDX376861HGN`): the name ("ProveMark" possibly a third-party brand,
      "C2PA" not after "for"), ownership, guideline 11 (notices), the
      `.pem` files, `register_setting()` sanitization. Handled in SPEC-020
      to SPEC-023: renamed to Tracefern Image Check for C2PA, the GitHub
      repository renamed, named sanitize callbacks, the trust notice on
      four screens, the settings page called Tracefern.
- [x] **The corrected version uploaded and the reply sent by Maurice on
      2026-09-28**: the zip of `432a64b` (CI green on every job; release
      suite and Plugin Check clean), `tracefern-image-check-for-c2pa.zip`,
      290,831 bytes, SHA-256
      `66335707d04476b30757469b646ad8322f69a35b2e1de3e89f97c01307a804af`
      (kept in `build/submitted/`). The reply, in the same thread, asked for
      the slug `tracefern-image-check-for-c2pa` and explained "provemark"
      (own GitHub organisation and library) and the `.pem` files. Not sent,
      kept for a question: the paragraph on the bundled library in
      `notes/wporg-review.md`. This is the version under review; the thread
      now waits in the reviewer's queue.
- [x] https://wordpress.org/plugins/developers/add/ (read 2026-09-27,
      logged in): eight confirmations to tick, each a statement by the
      account owner (the FAQ and the guidelines read; Plugin Check run; the
      name not confusingly similar, searched for; permission and an account
      that represents the owner; no trialware; the rejection rules
      understood), and the zip (at most 10 MB). **There is no field for an
      overview**; the reviewer reads `readme.txt`.
- [x] Ownership of the name: the form names the account's e-mail address
      as how ownership is verified, and names starting with a brand may
      only be submitted by its verified owner. The account's address is
      not on a Provemark domain. Decided by Maurice (2026-09-27): submit
      as is, and show the GitHub organisation `provemark` (which also
      publishes `provemark/c2pa-verifier`) if the reviewer asks.
- [x] Review: the page said 185 plugins awaited a first review and gave
      1 to 10 days (2026-09-27), by email. For questions about the bundled
      verifier's WPCS findings: `notes/wporg-review.md`. For the licences:
      `NOTES.md` (Measured, 2026-09-27).
- [x] **Approved 2026-09-28** under the slug `tracefern-image-check-for-c2pa`
      (Review ID `APPROVED tracefern-image-check-for-c2pa/mauricevanloon/28Sep26/T3`),
      the zip uploaded that morning (SHA-256 `66335707…07a804af`, kept in
      `build/submitted/`). SVN:
      https://plugins.svn.wordpress.org/tracefern-image-check-for-c2pa

The zip prepared for submission (2026-09-27): built from `d938e6c`
(CI green on every job), 279,526 bytes, SHA-256
`aa393b532a3971bb3e8cadb609dc14cec7901be9128f96876fc0195b7a57b7d6`.
Keep it unchanged after submitting.

An overview, not asked by the form; kept for the reviewer's e-mail or a
later description:

```text
Provemark C2PA Check verifies the Content Credentials (C2PA) of JPEG, PNG and WebP images uploaded to the Media Library, and shows the result in a Media Library column, a filter, and the attachment details: "Verified: trusted signer", "Intact: signer not trusted", "Does not verify", "No Content Credentials" or "Could not be checked", with the signer and, when a verified manifest says so, "AI-generated (signed)".

Each upload is checked in the background through WP-Cron, on the original file, so a check can never break an upload. A WP-CLI command re-checks existing images.

The plugin only reads: it never signs anything, holds no keys, blocks no uploads and makes no network calls. Verification is done by the bundled MIT-licensed library provemark/c2pa-verifier (same author), prefixed with Strauss. The default trust anchors are the C2PA conformance programme's public trust lists (CC BY 4.0, credited in readme.txt), bundled as plain-text certificates in trust/.

"C2PA" is used descriptively; the plugin is not made or endorsed by the Coalition for Content Provenance and Authenticity.
```

## 4. After approval: the SVN repository

The approval email gives the SVN address. The SVN password is separate
from the account password:
https://profiles.wordpress.org/me/profile/edit/group/3/?screen=svn-password

- [x] `trunk/`: the **contents of the build**, not the git repository.
      The main file at the top of `trunk/`, not in a subfolder. Anything in
      SVN is shipped to every user, so only what the zip holds.
- [x] `assets/` (top level, beside `trunk/` and `tags/`): the eight files
      in `.wordpress-org/` (`icon-128x128.png`, `icon-256x256.png`,
      `banner-772x250.png`, `banner-1544x500.png`, `screenshot-1..4.png`),
      not `.wordpress-org/source/`, with `svn:mime-type image/png`. One
      screenshot per caption line in `readme.txt` (tested:
      `tests/Unit/ReadmeTest.php`). Limits: icons 1 MB, banners 4 MB,
      screenshots 10 MB; ours are all under 250 KB.
- [x] `svn cp trunk tags/<version>`; `Stable tag` in `trunk/readme.txt`
      equal to that version; commit both together.
- [x] **0.1.0 released 2026-09-28**, SVN r3716634, committed by Maurice.
      `trunk/` is the unpacked approved zip itself: a fresh build of
      `c3c972d` differed only in the commit hash and Composer's autoloader
      class name, and no shipped file changed after `432a64b`. Measured
      afterwards: `diff -r` of the public download
      (`downloads.wordpress.org/plugin/tracefern-image-check-for-c2pa.0.1.0.zip`)
      against the approved zip is empty (the zip itself differs in bytes,
      as wordpress.org packs it again); the plugin API reports version
      0.1.0, requires 7.1, PHP 8.3, four screenshots, both banners and
      both icons; the public page returns 200.
- [x] A matching git tag: `v0.1.0` on `432a64b`, the commit the
      released zip names in `vendor/composer/installed.php`; pushed
      2026-09-28 on Maurice's go. A GitHub release:
      https://github.com/provemark/tracefern-image-check/releases/tag/v0.1.0,
      with the approved zip attached (SHA-256 `66335707…07a804af`).

SVN is a release system: commit to it only for a release, as each commit
rebuilds every version's zip.

## 4a. The Live Preview (SPEC-024)

- [x] `.wordpress-org/blueprints/blueprint.json` to SVN as
      `assets/blueprints/blueprint.json` (not in `trunk/`; no new
      version needed). Committed by Maurice, r3716729 (2026-09-28).
      Imported by the directory about 20 minutes later; the blueprint
      endpoint serves it with the same content as git (a request without
      a query string kept a cached 404 for a while).
- [ ] "Test Preview" on the plugin page (committers only). Not shown yet
      (2026-09-28): the directory shows it to users with
      `plugin_admin_edit`, and the account shows "Your account has
      elevated privileges and requires extra security before you can
      continue. Please enable two-factor authentication." That this
      hides the button is *reasoned*, not measured.
- [x] Measured in Chrome instead, with the link the button opens
      (`playground.wordpress.net/?plugin=…&blueprint-url=…blueprint.json?lang=nl_NL`,
      as `Template::preview_link()` builds it), 2026-09-28: the Media
      Library with all five verdicts about 12 s after opening; order
      Pixel, OpenAI, altered copy, Amazon, unsigned. An image uploaded in
      the preview through Media → Add New stays "Check pending": still so
      after more than 2 minutes and several page loads; `wp-cron.php`
      called directly answers 503. WP-Cron does not run in the browser
      Playground; the five demo images are unaffected.
- [x] SPEC-024 amendment 1 (a visitor's upload checked on admin page
      loads) to SVN: the updated `assets/blueprints/blueprint.json`,
      committed by Maurice, r3716856 (2026-09-28 12:11); served by the
      directory from 12:43, same content as git. Measured in Chrome with
      the preview link: the five verdicts after about 25 s; an image
      uploaded through Media → Add New (browser uploader) shows "Intact:
      signer not trusted" on the Media Library page WordPress redirects
      to. The drag-and-drop uploader is not measured.
- [ ] The preview set to "public" in the plugin's Advanced view: Maurice's
      decision, after the test.

## 5. Each later release

- [ ] Changelog and upgrade notice in `readme.txt`; version in the header
      and `Stable tag`.
- [ ] Section 2 again; then `trunk/` updated from the new build, a new tag,
      `Stable tag` pointing at it, one commit.
- [ ] With every WordPress major version: test, then `Tested up to` (this
      alone can be changed in `trunk/readme.txt` without a new version;
      *reasoned*).
- [ ] New C2PA trust lists or a new verifier version: a normal release
      (`trust/README.md`, `tests/wpcs-verifier-baseline.json` reviewed).

Releases after 0.1.0:

- **2026-09-28, readme only** (SPEC-025): `readme.txt` in `trunk/` and
  `tags/0.1.0/`, no new version, committed by Maurice (r3717036); the
  page and the plugin API showed the new text from 14:02.
- **0.1.1, 2026-09-28** (SPEC-026, the `tracefern_verdict` filter):
  built from `46e9f47` (SHA-256 `db7674d8…dbebed`, kept in
  `build/submitted/`), the Release suite green on that build; `trunk/`
  replaced by the build and `tags/0.1.1` copied, committed by Maurice
  (r3717131, 14:14). Git tag `v0.1.1` on `46e9f47` and a GitHub release
  with the same zip, on Maurice's go. Measured: the plugin API reported
  0.1.1 at 14:53; the public download
  (`downloads.wordpress.org/plugin/tracefern-image-check-for-c2pa.0.1.1.zip`)
  unpacks to the same files as the build.
- **0.1.2, 2026-09-28** (bundles c2pa-verifier 0.2.6, a security
  release: an RSA key with public exponent 1 no longer comes out
  `Trusted`): built from `ebbcb62` (SHA-256 `9bf8b88a…d1d56c2`, kept in
  `build/submitted/`), the Release suite green on that build; `trunk/`
  replaced by the build and `tags/0.1.2` copied, committed by Maurice
  (r3717314, 15:58). Measured: the plugin API reported 0.1.2 (last
  updated 13:58 GMT); `trunk/readme.txt` and `tags/0.1.2/readme.txt`
  say `Stable tag: 0.1.2`; the public download
  (`downloads.wordpress.org/plugin/tracefern-image-check-for-c2pa.0.1.2.zip`)
  unpacks to the same files as the build and names verifier v0.2.6; the
  plugin page returns 200. Measured afterwards: the public download
  unpacks to exactly the files of SVN `tags/0.1.2`. Git tag `v0.1.2` on
  `ebbcb62` and a GitHub release, on Maurice's go (about 16:10); the
  release's zip holds exactly the files of SVN `tags/0.1.2` (SHA-256
  `5ad5a395…`, 294,929 bytes).
- **0.1.3, 2026-09-28** (SPEC-027, the AI label follows an image's
  history; new label "AI-edited (signed)"): built from `0737d8f` (SHA-256
  `0351b6d2…a481b4c5`, kept in `build/submitted/`). Before the build:
  `composer check`, integration (172), multisite (7) and the Release
  suite (17) green, and CI green on `0737d8f`. `trunk/` was replaced by
  the build (`diff -r` empty) and `tags/0.1.3` copied; Maurice committed
  them (r3717745, 20:26). Measured: the plugin API reported 0.1.3 (last
  updated 18:26 GMT) by 20:36. The public download
  (`downloads.wordpress.org/plugin/tracefern-image-check-for-c2pa.0.1.3.zip`)
  unpacks to the same files as the build, and SVN `tags/0.1.3` holds the
  same files. Git tag `v0.1.3` on `0737d8f`, and a GitHub release with
  the same zip (296,391 bytes), on Maurice's go. Found on the way: the
  readme is 10,140 bytes, about 100 below the 10 KB its test enforces.
  The next release must move older changelog entries out first.
- **0.1.4, 2026-09-29** (SPEC-029, rotated photos from the block editor
  are checked on the upload; SPEC-028, "Changed after upload"): built
  from `10d5f01` (SHA-256 `fb33f82b…2f036f09`, kept in
  `build/submitted/`). Before the build: `composer check` (99),
  integration (187), multisite (7) and the Release suite (17) green, run
  in parallel in their own environments; CI green on `10d5f01`. The
  readme's changelog keeps only 0.1.4 and links the GitHub releases
  (9,800 bytes). `trunk/` was replaced by the build (`diff -r` empty) and
  `tags/0.1.4` copied; Maurice committed them (r3718293, 07:35).
  Measured: the plugin API reported 0.1.4 (last updated 05:35 GMT) by
  07:36; the public download
  (`downloads.wordpress.org/plugin/tracefern-image-check-for-c2pa.0.1.4.zip`)
  unpacks to the same files as the build, and so does an export of SVN
  `tags/0.1.4`; the plugin page returns 200. Git tag `v0.1.4` on
  `10d5f01`, and a GitHub release with the same zip (298,163 bytes), on
  Maurice's go.
- **0.1.5, 2026-09-29** (SPEC-031, check existing images from the
  settings page, with amendments 1 and 2; SPEC-025 amendment 1 in the
  readme): built from `63be425` (SHA-256 `4e70db65…3816a87`, kept in
  `build/submitted/`). Before the build: `composer check` (119),
  integration (198), multisite (7) and the Release suite (17) green, run
  in parallel; CI green on `63be425`. The readme-only SVN update prepared
  for SPEC-025 amendment 1 was dropped: this release carries it. `trunk/`
  was replaced by the build (`diff -r` empty) and `tags/0.1.5` copied;
  Maurice committed them (r3718478, 09:42). Measured: the plugin API
  reported 0.1.5 (last updated 07:42 GMT) at once; the public download
  and an export of SVN `tags/0.1.5` hold the same files as the build; the
  plugin page returns 200. Git tag `v0.1.5` on `63be425`, and a GitHub
  release with the same zip (301,997 bytes), on Maurice's go.
- **0.1.6, 2026-09-30** (verifier v0.2.7, its SPEC-050; SPEC-006
  amendment 9): built from `605b3da` (SHA-256 `e639c64a…05ac36`, kept in
  `build/submitted/`). Before the build: `composer check` (119),
  integration (198), multisite (7) and the Release suite (17) green; CI
  run 36676062646 green on `605b3da` (9 jobs). `trunk/` was replaced by
  the build (`diff -r` empty) and `tags/0.1.6` copied; Maurice committed
  them (r3720433, 08:14). Measured: the plugin API reported 0.1.6 (last
  updated 06:14 GMT) at once; the public download and an export of SVN
  `tags/0.1.6` hold the same files as the build; the plugin page returns
  200. Git tag `v0.1.6` on `605b3da`, and a GitHub release with the same
  zip (302,943 bytes), on Maurice's go.
- **0.1.7, 2026-09-30** (verifier v0.2.8, its SPEC-051, SPEC-052 and
  SPEC-053; SPEC-006 amendment 10): built from `a028c48` (SHA-256
  `100866c0…b4b3e7`, kept in `build/submitted/`). Before the build:
  `composer check` (120), integration (208), multisite (7) and the Release
  suite (17) green. `trunk/` was replaced by the build (`diff -r` empty),
  the one new file (`Hash/BmffLimitException.php`) added, and `tags/0.1.7`
  copied; Maurice committed them (r3721108, 13:31) while CI run
  36708160959 on `a028c48` was still running; it finished green (9 jobs)
  afterwards. Measured: the plugin API reported 0.1.7 (last updated 11:31
  GMT) at once; the public download and an export of SVN `tags/0.1.7`
  hold the same files as the build; the plugin page returns 200. Git tag
  `v0.1.7` on `a028c48` after CI was green, and a GitHub release with the
  same zip, whose files equal SVN `tags/0.1.7`, on Maurice's go.
- **0.1.8, 2026-10-03** (SPEC-033 with amendments 1 and 2, the dashboard
  summary; SPEC-005 amendment 2; verifier v0.2.9, `src/` unchanged, so no
  SPEC-006 amendment): built from `5401874` (SHA-256 `9f620094…41258921`,
  310,362 bytes, kept in `build/submitted/`). Before the build:
  `composer check` (121), integration (229), multisite (9) and the
  Release suite (17) green; CI run 37120790249 green on `5401874` (9
  jobs) before the SVN commit. `trunk/` was replaced by the build
  (`diff -r` empty), the one new file (`src/DashboardSummary.php`) added,
  and `tags/0.1.8` copied; Maurice committed them (r3726234, 14:16).
  Measured: the plugin API reported 0.1.8 (last updated 12:16 GMT) at
  once; the public download and an export of SVN `tags/0.1.8` hold the
  same files as the build; `trunk/readme.txt` says `Stable tag: 0.1.8`;
  the plugin page returns 200. Git tag `v0.1.8` on `5401874`, and a
  GitHub release with the same zip (SHA-256 equal, files equal to SVN
  `tags/0.1.8`), on Maurice's go.
- **0.1.9, 2026-10-05** (verifier v0.3.0, a security release: a leaf
  outside a name-constrained authority, with names that are not UTF-8, is
  no longer `Trusted`; SPEC-006 amendment 11): built from `4b2a6e4`
  (SHA-256 `175b9c7f…7f9ec1de`, kept in `build/submitted/`). Before the
  build: `composer check` (121), integration (229), multisite (9) and the
  Release suite (17) green; CI run 37326423835 green on `4b2a6e4` (9
  jobs) before the tag. `trunk/` was replaced by the build (`diff -r`
  empty), five new verifier files added (`Container/Avi…`, `Id3…`,
  `Riff…`, `Wav…ManifestStoreExtractor.php`, `Hash/HardBindings.php`),
  and `tags/0.1.9` copied; Maurice committed them (r3729107, 16:47).
  Measured: the plugin API reported 0.1.9 (last updated 14:47 GMT) at
  once; the public download and an export of SVN `tags/0.1.9` hold the
  same files as the build; `trunk/readme.txt` says `Stable tag: 0.1.9`;
  the plugin page returns 200. Git tag `v0.1.9` on `4b2a6e4`, and a
  GitHub release with the same zip (SHA-256 equal, files equal to SVN
  `tags/0.1.9`), on Maurice's go.
- **2026-10-05, readme only** (0.1.9's changelog): the line about a
  damaged image named the reason ("could not be read") as if it were the
  label; the Media Library shows "Could not be checked". `readme.txt` in
  `trunk/` and `tags/0.1.9/`, no new version, to be committed by Maurice.
- **0.2.0, 2026-10-05** (SPEC-034, WAV, MP3 and FLAC checked as images
  are, cover art with its own verdict; SPEC-025 amendment 2, SPEC-031
  amendment 3 and SPEC-033 amendments 3 and 4): built from `2d382c1`
  (SHA-256 `d2e1a573…0156f6ed`, 328,970 bytes, kept in
  `build/submitted/`). Before the build: `composer check` (123),
  integration (262), multisite (9) and the Release suite (17) green; an
  update from the released 0.1.9 to the build tested in the release
  environment (it found the stale dashboard counts SPEC-033 amendment 4
  fixed); CI run 37360301784 green on `2d382c1` (9 jobs), after run
  37357247672 had failed one timing-bound test on PHP 8.5, given more room
  in `2d382c1`. `trunk/` was replaced by the build (`diff -r` empty, 12
  files changed) and `tags/0.2.0` copied; Maurice committed them
  (r3729555, 21:19). Measured: the plugin page showed 0.2.0 at once; the
  plugin API and the download answered 0.1.9 and no zip for some minutes
  from their caches, then, asked past the cache, 0.2.0 (last updated
  19:19 GMT) and a public download holding the files of the build, as an
  export of SVN `tags/0.2.0` does; `trunk/readme.txt` says `Stable tag:
  0.2.0`; the page returns 200. Git tag `v0.2.0` on `2d382c1`, and a
  GitHub release with the same zip (SHA-256 equal, files equal to SVN
  `tags/0.2.0`), on Maurice's go. The readme-only SVN update prepared for
  0.1.9's changelog line was never committed: 0.2.0 carries the readme.
- **0.3.0, 2026-10-06** (SPEC-035, GIF checked as the other images are;
  c2pa-verifier 0.4.0, SPEC-006 amendment 12): built from `2d40bcd`
  (SHA-256 `c2d2af58…54c358b2`, 332,709 bytes, kept in
  `build/submitted/`). Before the build: `composer check` (125),
  integration (275), multisite (9) and the Release suite (17) green; by
  hand in the release environment, the built zip with seven fixtures, an
  update from the released 0.2.0 (a GIF uploaded under 0.2.0 counted as
  never checked, then checked `Valid`) and two GIFs uploaded through
  `media-new.php` in Chrome; CI run 37455980280 green on `2d40bcd` (9
  jobs), after run 37454054623 had failed Pint on a test added during the
  build and Playground's download of `wp-cli.phar`. The Release suite ran
  again on the build itself. `trunk/` was replaced by the build (`diff -r`
  empty; 1 file added, 34 changed) and `tags/0.3.0` copied; Maurice
  committed them (r3730701, 13:43). Measured: the plugin API reported
  0.3.0 (last updated 11:43 GMT) within a minute; the page returns 200
  and shows 0.3.0; the public download holds the files of the build, as
  an export of SVN `tags/0.3.0` does. Git tag `v0.3.0` on `2d40bcd`, and
  a GitHub release with the same zip (SHA-256 equal, files equal to SVN
  `tags/0.3.0`), on Maurice's go.
- **0.4.0, 2026-10-07** (SPEC-036, plain-text uploads checked, the
  verifier's text reader on for `text/plain` attachments only;
  c2pa-verifier 0.5.0, SPEC-006 amendment 13; SPEC-025 amendment 3):
  built from `d1627b0` (SHA-256 `23a77ee6…8a6058d47`, 339,062 bytes, kept
  in `build/submitted/`). Found while preparing it: the dashboard and the
  settings page counted text files but did not name them; fixed in
  `d1627b0`. Before the build: `composer check` (126), integration (287),
  multisite (9) and the Release suite (17) green on `d1627b0`; CI run
  37601161239 green on `d1627b0` (9 jobs). By hand, on the build of
  `a3d327e` in the release environment: a signed text `Valid`, the same
  text with one letter changed `Invalid` (`assertion.dataHash.mismatch`),
  through the prefixed verifier. After the release, in the release
  environment (WordPress 7.1.3): plugin 0.3.0 installed from wordpress.org,
  a signed text uploaded under it (no entry, not counted, not "never
  checked"); then 0.4.0 installed over it from the public download (the
  update check, `api.wordpress.org/plugins/update-check/1.1/`, still
  offered 0.3.0 at 10:32 UTC, 39 minutes after the SVN commit, while the
  plugin page and the info API showed 0.4.0): total 1, "never checked" 1,
  `wp tracefern check --unchecked` gives `Valid` (`text`), then 0; two
  texts uploaded through `wp-admin/media-new.php` (browser uploader) in
  Chrome, checked once WP-Cron ran: the column shows "Intact: signer not
  trusted" and "Does not verify", the dashboard widget "3 JPEG, PNG, GIF,
  WebP, WAV, MP3, FLAC and text files" with 2 intact and 1 not verifying.
  The update offer itself was not seen; the test attachments were
  deleted. The readme is 10,224 bytes, 16 below its test's limit: the
  next release must move text out first. `trunk/` was replaced by the
  build (`diff -r` empty; 2 files added, 42 changed) and `tags/0.4.0`
  copied; Maurice committed them in his own terminal (r3732283, 11:53;
  a first try through the session's `!` could not answer SVN's password
  prompt). Measured: the plugin API reported 0.4.0 (last updated 09:53
  GMT) at once; `trunk/readme.txt` says `Stable tag: 0.4.0`; the page
  returns 200; the public download holds the files of the build, as an
  export of SVN `tags/0.4.0` does. Git tag `v0.4.0` on `d1627b0`, and a
  GitHub release with the same zip (SHA-256 equal), on Maurice's go.
