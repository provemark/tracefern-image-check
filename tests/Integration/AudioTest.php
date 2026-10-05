<?php

declare(strict_types=1);

/*
 * SPEC-034: WAV, MP3 and FLAC are checked as images are. The fixtures are the
 * verifier's own (tests/Fixtures/README.md); the damaged, altered and cover
 * copies are made here, in tests/tmp/.
 */

/** Where the ID3 tag of an MP3 ends: its 10-byte header plus its syncsafe size. */
function spec034TagEnd(string $mp3): int
{
    $size = 0;
    foreach (str_split(substr($mp3, 6, 4)) as $byte) {
        $size = ($size << 7) | (ord($byte) & 0x7F);
    }

    return 10 + $size;
}

/** A copy of a fixture with $change applied, written to tests/tmp/. */
function spec034Copy(string $fixture, string $name, callable $change): string
{
    $path = tmpDir().'/'.$name;
    file_put_contents($path, $change((string) file_get_contents(fixturePath($fixture))));

    return $path;
}

/** The signed MP3 with one byte of its audio frames changed after signing. */
function spec034AlteredMp3(): string
{
    return spec034Copy('fixture-signed.mp3', 'altered.mp3', static function (string $mp3): string {
        $at = spec034TagEnd($mp3) + 200;

        return substr_replace($mp3, chr(ord($mp3[$at]) ^ 0x01), $at, 1);
    });
}

/** The signed WAV cut 100 bytes into its C2PA chunk. */
function spec034CutWav(): string
{
    return spec034Copy('fixture-signed.wav', 'cut-in-c2pa.wav', static fn (string $wav): string => substr($wav, 0, (int) strpos($wav, 'C2PA') + 100));
}

/** The signed MP3 cut inside its ID3 tag. */
function spec034CutMp3(): string
{
    return spec034Copy('fixture-signed.mp3', 'cut-in-tag.mp3', static fn (string $mp3): string => substr($mp3, 0, 200));
}

/** The signed WAV with its RIFF size field set to 2: the store is never reached. */
function spec034RiffSize2Wav(): string
{
    return spec034Copy('fixture-signed.wav', 'riff-size-2.wav', static fn (string $wav): string => substr_replace($wav, pack('V', 2), 4, 4));
}

/**
 * The unsigned MP3's audio under an ID3v2.3 tag whose APIC frame is the
 * signed JPEG: an MP3 without credentials whose cover art has its own.
 */
function spec034CoverMp3(): string
{
    $jpeg = (string) file_get_contents(fixturePath('fixture-signed.jpg'));
    $mp3 = (string) file_get_contents(fixturePath('fixture-unsigned.mp3'));
    $audio = str_starts_with($mp3, 'ID3') ? substr($mp3, spec034TagEnd($mp3)) : $mp3;
    $body = "\0image/jpeg\0\x03\0".$jpeg;
    $frame = 'APIC'.pack('N', strlen($body))."\0\0".$body;
    $n = strlen($frame);
    $size = chr(($n >> 21) & 0x7F).chr(($n >> 14) & 0x7F).chr(($n >> 7) & 0x7F).chr($n & 0x7F);
    $path = tmpDir().'/cover.mp3';
    file_put_contents($path, "ID3\x03\0\0".$size.$frame.$audio);

    return $path;
}

it('AC1: a signed WAV, MP3 or FLAC gets the verifier\'s verdict', function (string $name, string $format): void {
    $id = importMedia(fixturePath($name));
    $entry = storedEntry($id);

    expect($entry)->not->toBeNull()
        ->and(stable((array) $entry))->toBe(expectedEntry(fixturePath($name), defaultSettingsFile()))
        ->and($entry['format'] ?? null)->toBe($format)
        ->and(visibleText(columnHtml($id)))->toBe('Intact: signer not trusted');
})->with([
    'WAV' => ['fixture-signed.wav', 'wav'],
    'MP3' => ['fixture-signed.mp3', 'mp3'],
    'FLAC' => ['fixture-signed.flac', 'flac'],
])->group('SPEC-034');

it('AC1: an MP3 uploaded through the browser\'s routes is checked as WP-CLI\'s import is', function (string $route): void {
    assert($route === 'rest' || $route === 'async');
    $source = fixturePath('fixture-signed.mp3');
    $upload = httpUpload($route, $source, 'browser-'.$route.'.mp3', 'audio/mpeg');
    runPendingChecks();

    expect($upload['status'])->toBeIn([200, 201])
        ->and($upload['id'])->toBeGreaterThan(0)
        ->and(sameAsHostFile(keptPath($upload['id']), $source))->toBeTrue()
        ->and(stable((array) storedEntry($upload['id'])))->toBe(expectedEntry($source, defaultSettingsFile()));
})->with(['rest', 'async'])->group('SPEC-034');

it('AC2: an unsigned one is "No Content Credentials"', function (string $name): void {
    $id = importMedia(fixturePath($name));

    expect(storedEntry($id)['state'] ?? null)->toBe('none')
        ->and(stable((array) storedEntry($id)))->toBe(expectedEntry(fixturePath($name), defaultSettingsFile()))
        ->and(visibleText(columnHtml($id)))->toBe('No Content Credentials');
})->with(['fixture-unsigned.wav', 'fixture-unsigned.mp3', 'fixture-unsigned.flac'])->group('SPEC-034');

it('AC3: changed after signing is "Does not verify"', function (): void {
    $path = spec034AlteredMp3();
    $id = importMedia($path);
    $entry = (array) storedEntry($id);

    expect(stable($entry))->toBe(expectedEntry($path, defaultSettingsFile()))
        ->and($entry['state'] ?? null)->toBe('Invalid')
        ->and($entry['codes'] ?? [])->toContain('assertion.dataHash.mismatch')
        ->and($entry['ai'] ?? null)->toBeFalse()
        ->and(visibleText(columnHtml($id)))->toBe('Does not verify');
})->group('SPEC-034');

it('AC4 (amendment 1): damaged after the manifest is "Does not verify"', function (callable $make): void {
    $path = $make();
    assert(is_string($path));
    $id = importMedia($path);
    $entry = (array) storedEntry($id);

    expect(stable($entry))->toBe(expectedEntry($path, defaultSettingsFile()))
        ->and($entry['state'] ?? null)->toBe('Invalid')
        ->and($entry['codes'] ?? [])->toContain('general.error')
        ->and(visibleText(columnHtml($id)))->toBe('Does not verify');
})->with([
    'a WAV cut inside its C2PA chunk' => [fn (): string => spec034CutWav()],
    'an MP3 cut inside its tag' => [fn (): string => spec034CutMp3()],
])->group('SPEC-034');

it('AC4 (amendment 1): a WAV whose store is never reached, or a missing file, is "Could not be checked"', function (): void {
    $damaged = importMedia(spec034RiffSize2Wav());
    $out = wpEval(<<<'PHP'
        $id = wp_insert_attachment(['post_mime_type' => 'audio/mpeg', 'post_title' => 'gone', 'post_status' => 'inherit'], '/var/www/html/wp-content/uploads/does-not-exist.mp3');
        echo 'ID:', $id, "\n";
        PHP);
    $gone = (int) preg_replace('/.*ID:(\d+).*/s', '$1', $out);
    runPendingChecks();

    foreach ([$damaged, $gone] as $id) {
        expect($id)->toBeGreaterThan(0)
            ->and(storedEntry($id)['state'] ?? null)->toBe('error')
            ->and(storedEntry($id)['reason'] ?? null)->toBe('unreadable')
            ->and(visibleText(columnHtml($id)))->toBe('Could not be checked');
    }
})->group('SPEC-034');

it('AC4 (amendment 1): an interrupted audio check is error / interrupted', function (): void {
    $id = importMedia(fixturePath('fixture-signed.wav'));
    wpEval("(new Tracefern\\ImageCheck\\UploadHook(new Tracefern\\ImageCheck\\Checker(function () { exit(0); })))->runScheduled($id);");

    expect(storedEntry($id)['state'] ?? null)->toBe('error')
        ->and(storedEntry($id)['reason'] ?? null)->toBe('interrupted');
})->group('SPEC-034');

it('AC5: AVI, Ogg and PDF are still left alone', function (string $path): void {
    $id = importMedia($path);

    expect($id)->toBeGreaterThan(0)
        ->and(storedEntry($id))->toBeNull()
        ->and(scheduledChecks($id))->toBe(0);
})->with([
    'an AVI' => [fixturePath('fixture-signed.avi')],
    'an Ogg' => [(function (): string {
        // a first page with a Vorbis identification header, enough for WordPress to read it as audio/ogg
        $packet = "\x01vorbis".pack('V', 0)."\x02".pack('V', 44100).pack('V', 0).pack('V', 128000).pack('V', 0)."\xb8\x01";
        $path = tmpDir().'/other.ogg';
        file_put_contents($path, "OggS\0\x02".str_repeat("\0", 8).pack('V', 1).pack('V', 0).pack('V', 0)."\x01".chr(strlen($packet)).$packet.str_repeat("\0", 64));

        return $path;
    })()],
    'a PDF' => [(function (): string {
        $path = tmpDir().'/other-034.pdf';
        file_put_contents($path, "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF\n");

        return $path;
    })()],
])->group('SPEC-034');

it('AC6: audio is everywhere images are', function (): void {
    emptyTestEnvironment();
    $id = importMedia(fixturePath('fixture-signed.mp3'));
    $unchecked = uncheckedAttachments('fixture-signed.flac', 1)[0];
    $counts = json_decode(wpEval('echo wp_json_encode(Tracefern\ImageCheck\DashboardSummary::counts());'), true);

    expect(visibleText(columnHtml($id)))->toBe('Intact: signer not trusted')
        ->and(visibleText(detailsHtml($id, false)))->toContain('Intact: signer not trusted')
        ->and(listedIds([$id], ['tracefern' => 'valid'])['ids'])->toBe([$id])
        ->and(is_array($counts) ? ($counts['total'] ?? null) : null)->toBe(2)
        ->and((int) wpEval("echo Tracefern\\ImageCheck\\ExistingImages::count('unchecked');"))->toBe(1)
        ->and((int) wpEval("echo Tracefern\\ImageCheck\\ExistingImages::count('all');"))->toBe(2)
        ->and(rowIds(recheckJson(['--all'])))->toEqualCanonicalizing([$id, $unchecked])
        ->and(verdict($id)['status'] ?? null)->toBe('checked')
        ->and(verdict($id)['state'] ?? null)->toBe('Valid');
})->group('SPEC-034');

it('AC7: cover art keeps its own verdict and says whose cover it is', function (): void {
    $mp3 = importMedia(spec034CoverMp3());
    wpEval("wp_update_post(['ID' => $mp3, 'post_title' => 'Song <b>one</b>']);");
    $cover = (int) wpEval("echo (int) get_post_meta($mp3, '_thumbnail_id', true);");
    runPendingChecks();
    $coverDetails = detailsHtml($cover, false);

    expect($cover)->toBeGreaterThan(0)
        ->and(storedEntry($mp3)['state'] ?? null)->toBe('none')
        ->and(stable((array) storedEntry($cover)))->toBe(expectedEntry(fixturePath('fixture-signed.jpg'), defaultSettingsFile()))
        ->and(visibleText(columnHtml($mp3)))->toBe('No Content Credentials')
        ->and(visibleText(columnHtml($cover)))->toBe('Intact: signer not trusted')
        ->and(visibleText($coverDetails))->toContain('Cover of Song <b>one</b>')
        ->and($coverDetails)->toContain('Song &lt;b&gt;one&lt;/b&gt;')
        ->and(visibleText(detailsHtml($mp3, false)))->not->toContain('Intact');
})->group('SPEC-034');
