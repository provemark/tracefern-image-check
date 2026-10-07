# SPEC-037: A name people search for — Tracefern Media Check for Content Credentials (C2PA)

| Field      | Value                                             |
|------------|---------------------------------------------------|
| Status     | implemented                                       |
| Author     | Maurice van Loon                                  |
| Approved   | Maurice van Loon, 2026-10-07                      |
| Supersedes | SPEC-020's display name                           |

> Lifecycle: `draft` → maintainer approves → `approved` → tests-first →
> `implemented` (Traceability filled). No implementation code while `draft`.
> Only the Traceability section of an `approved` spec may change without a new
> approval; everything else needs a proposed amendment.

## Problem

People who look for this kind of plugin search for "Content Credentials",
the name the C2PA gives its labels in public. The plugin's name does not
contain it, and the directory cannot find the plugin by it.

Measured on 2026-10-07 with the directory's API (`query_plugins`, 30 per
page; `plugin_information`):

| search | results | this plugin's place |
|---|---|---|
| `c2pa` | 29 | 4 |
| `content credentials` | 4,269 | not in the first 30 |
| `image authenticity` | 64 | 28 |
| `ai image`, `ai label` | thousands | not in the first 30 |

The first results for `content credentials` are plugins with millions of
installations whose names hold "content" or "credentials" apart (Wordfence,
WP Mail SMTP, Really Simple Security): the search matches the words
separately and weighs installations. The plugin has fewer than 10 active
installations and 523 downloads since 2026-09-28.

The name has also gone stale: "Image Check" says images, while the plugin
checks audio since 0.2.0 (SPEC-034) and plain text since 0.4.0 (SPEC-036).

SPEC-020 settled what a name may look like: wordpress.org's first review
(2026-09-28) asked for a trademark or a project's name after "for" or
"with" (guideline 17), not in front. "Content Credentials" is the C2PA's
public name for its labels, and its icon is a registered mark; whether the
words are a trademark was not established (a web search on 2026-10-07 gave
no answer). Both therefore go after "for", with the plugin's own coined
name in front.

## Decision (Maurice, 2026-10-07)

The display name becomes **Tracefern Media Check for Content Credentials
(C2PA)** (52 characters).

## Scope

**In scope**

- The display name wherever the plugin names itself: the main file's
  `Plugin Name` header and its two notices; `readme.txt`'s title and its
  description; `README.md`; the settings page's title and heading and its
  trust notice (`SettingsPage`); the privacy text and its name
  (`PrivacyPolicy::PLUGIN_NAME`); comments in `assets/admin.css`,
  `uninstall.php` and `trust/README.md`; `package.json`'s description; the
  name row in `NOTES.md`.
- The banner on wordpress.org (`.wordpress-org/source/banner.html`, the two
  PNGs made from it), which shows the name as text. The icon has no text.
- Room in `readme.txt`: the new title makes it 10,268 bytes, over the
  10,240 that `ReadmeTest` allows (open question 2).
- `NameTest` and `PrivacyTest`, which hold the old name.
- A release, 0.4.1 (open question 4).

**Out of scope**

- The slug `tracefern-image-check-for-c2pa`, the text domain, the main
  file's name, the namespace `Tracefern\ImageCheck`, the options, meta keys,
  cron events and the WP-CLI command `wp tracefern`, the settings menu label
  "Tracefern" (SPEC-023), the GitHub repository's name: wordpress.org does
  not change a slug, and none of these is seen in a search.
- Tags (already `c2pa`, `content credentials`, `provenance`, `media
  library`, `ai`).
- Anything else that could raise the ranking (installations, reviews):
  not something a name can do.

## Behavior

- **AC1 — the new name in the header and the readme**
  - Then the main file's header reads `Plugin Name: Tracefern Media Check
    for Content Credentials (C2PA)`, `readme.txt` opens with `=== Tracefern
    Media Check for Content Credentials (C2PA) ===`, and the two are equal
    (`NameTest`)

- **AC2 — no trademark in front**
  - Then the name starts with "Tracefern", and "Content Credentials" and
    "C2PA" come only after "for" (`NameTest`)

- **AC3 — the old name is gone from what ships**
  - Given the built zip
  - Then "Tracefern Image Check" occurs in no shipped file; the specs,
    notes and AI log keep it as history (they do not ship)

- **AC4 — the settings page, the notices and the privacy text say the new name**
  - Then the settings page's title and heading, the trust notice, both
    notices of the main file and the privacy text show the new name, and
    the privacy text is registered under it (`PrivacyTest`, amended)

- **AC5 — nothing that a site stores changes**
  - Given a site that ran 0.4.0, with checked files and custom trust
    settings
  - When 0.4.1 replaces it
  - Then every stored result, option and scheduled check is read as
    before, the slug and the text domain are unchanged, and the plugin
    stays active

- **AC6 — the readme fits**
  - Then `readme.txt` is under 10,240 bytes with room for the next
    changelog line (at least 60 bytes), and still says what SPEC-025 and
    SPEC-036 AC8 require (`ReadmeTest`)

- **AC7 — the banner shows the new name**
  - Then `.wordpress-org/source/banner.html` and the two banner PNGs made
    from it show the new name; they go to SVN `assets/`, which needs no
    release

## References

- WordPress Detailed Plugin Guidelines 17 (trademarks); SPEC-020 and the
  first review's request (2026-09-28).
- The directory's API (`api.wordpress.org/plugins/info/1.2/`,
  `query_plugins` and `plugin_information`), measured 2026-10-07 (the
  tables above).
- Reasoned, not measured: that a display name can change without a new
  review (the slug cannot); that the directory search weighs the name.
  Measured after the release: the place for `content credentials` and
  `c2pa` again.

## Open questions

1. **The privacy text's key.** WordPress keeps a plugin's suggested privacy
   text under its name (`wp_add_privacy_policy_content()`); a site's
   Privacy Policy Guide will show it under the new name, and may mark the
   text as changed. Proposal: accept that; the text itself only changes its
   name. Non-blocker. **Decided by Maurice van Loon, 2026-10-07: as proposed.**
2. **Room in the readme.** Proposal: "Tracefern Image Check for C2PA
   verifies that record" in the description becomes "Tracefern verifies
   that record" (the title already says the full name), and the bullet
   "Sort and filter the Media Library list by verdict, including all
   AI-generated images, and see the counts on the dashboard" becomes "Sort
   and filter by verdict, AI-generated included, and see the counts on the
   dashboard". Together about 75 bytes. Non-blocker. **Decided by Maurice van Loon, 2026-10-07: as proposed.**
3. **The banner.** It is made from `banner.html` by a screenshot at
   1544×500 and halved. Proposal: the same way, the layout unchanged, the
   name on two lines if it does not fit on one. Non-blocker. **Decided by Maurice van Loon, 2026-10-07: as proposed.**
4. **The version.** No behaviour changes. Proposal: 0.4.1, with one
   changelog line. Non-blocker. **Decided by Maurice van Loon, 2026-10-07: as proposed.**

## Amendments

None.

## Traceability

| AC | Test | Implementation |
|----|------|----------------|
| AC1 | tests/Unit/NameTest.php :: carries the Tracefern name in the main file and readme.txt / SPEC-037 | `tracefern-image-check-for-c2pa.php` (`Plugin Name`); `readme.txt` (title) |
| AC2 | tests/Unit/NameTest.php :: SPEC-037 AC2: puts no trademark in front of the name / SPEC-037 | the name itself |
| AC3 | tests/Unit/NameTest.php :: SPEC-037 AC3: uses the old display name only in the history (every tracked file, so the built zip too) / SPEC-037 | every shipped file: the main file, `readme.txt`, `README.md`, `src/SettingsPage.php`, `src/PrivacyPolicy.php`, `assets/admin.css`, `uninstall.php`, `trust/README.md`, `package.json`, `.wordpress-org/` (blueprint, banner source, icon source); `NOTES.md` |
| AC4 | tests/Integration/PrivacyTest.php :: AC1, AC2 (the new name, amended); tests/Release/ReleaseTest.php :: AC2 (the settings page heading) / SPEC-037 | `SettingsPage` (title, heading, trust notice); the main file's two notices; `PrivacyPolicy::PLUGIN_NAME` and its text ("Tracefern reads …") |
| AC5 | by hand at the release: 0.4.0 replaced by 0.4.1 on a site with checked files and custom trust settings; the slug and text domain held by NameTest | nothing that stores or names data changed (slug, text domain, options, meta keys, cron events) |
| AC6 | tests/Unit/ReadmeTest.php :: SPEC-037 AC6: the readme leaves room for the next changelog line / SPEC-037 | `readme.txt`: "Tracefern verifies that record", the sort-and-filter bullet shortened, the `trust/README.md` aside removed (10,142 bytes before the changelog line) |
| AC7 | tests/Unit/NameTest.php :: SPEC-037 AC3 (`banner.html`); the two PNGs by eye | `.wordpress-org/source/banner.html` (the name on two lines, "every file you upload"); `banner-1544x500.png` by headless Chrome, `banner-772x250.png` halved with `sips` |
