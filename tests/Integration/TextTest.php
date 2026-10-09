<?php

declare(strict_types=1);

/*
 * SPEC-036: plain text (`text/plain`) is checked as images and audio are,
 * with the verifier's text reader (C2PA 2.4 §A.8, c2pa-verifier 0.5.0's
 * SPEC-060). The fixtures are the verifier's own (tests/Fixtures/README.md);
 * the oracle is `vendor/bin/c2pa-verify --text`.
 */

it('AC1: a signed text gets the verifier\'s verdict', function (string $name): void {
    $path = fixturePath($name);
    $id = importMedia($path);
    $entry = storedEntry($id);

    expect($entry)->not->toBeNull()
        ->and(stable((array) $entry))->toBe(expectedEntry($path, defaultSettingsFile(), true))
        ->and($entry['format'] ?? null)->toBe('text')
        ->and($entry['state'] ?? null)->toBe('Valid')
        ->and(visibleText(columnHtml($id)))->toBe('Intact: signer not trusted');
})->with(['fixture-signed.txt', 'text-nfd-emoji-signed.txt'])->group('SPEC-036');

it('AC1: a text uploaded through the browser\'s REST route is checked as WP-CLI\'s import is', function (): void {
    $source = fixturePath('fixture-signed.txt');
    $upload = httpUpload('rest', $source, 'browser-rest.txt', 'text/plain');
    runPendingChecks();

    expect($upload['status'])->toBeIn([200, 201])
        ->and($upload['id'])->toBeGreaterThan(0)
        ->and(sameAsHostFile(keptPath($upload['id']), $source))->toBeTrue()
        ->and(stable((array) storedEntry($upload['id'])))->toBe(expectedEntry($source, defaultSettingsFile(), true));
})->group('SPEC-036');

it('AC1: with trust settings that hold the signer, a text is "Verified: trusted signer"', function (): void {
    setOption('tracefern_custom_trust', customSettingsJson());
    try {
        $id = importMedia(fixturePath('fixture-signed.txt'));
        $entry = (array) storedEntry($id);

        expect(stable($entry))->toBe(expectedEntry(fixturePath('fixture-signed.txt'), customSettingsFile(), true))
            ->and($entry['state'] ?? null)->toBe('Trusted')
            ->and(visibleText(columnHtml($id)))->toBe('Verified: trusted signer');
    } finally {
        resetTrustOptions();
    }
})->group('SPEC-036');

it('AC2: an unsigned text is "No Content Credentials"', function (): void {
    $id = importMedia(fixturePath('fixture-unsigned.txt'));

    expect(storedEntry($id)['state'] ?? null)->toBe('none')
        ->and(stable((array) storedEntry($id)))->toBe(expectedEntry(fixturePath('fixture-unsigned.txt'), defaultSettingsFile(), true))
        ->and(visibleText(columnHtml($id)))->toBe('No Content Credentials');
})->group('SPEC-036');

it('AC3: a text changed after signing is "Does not verify"', function (): void {
    $path = fixturePath('text-flip-text.txt');
    $id = importMedia($path);
    $entry = (array) storedEntry($id);

    expect(stable($entry))->toBe(expectedEntry($path, defaultSettingsFile(), true))
        ->and($entry['state'] ?? null)->toBe('Invalid')
        ->and($entry['codes'] ?? [])->toContain('assertion.dataHash.mismatch')
        ->and($entry['ai'] ?? null)->toBeFalse()
        ->and(visibleText(columnHtml($id)))->toBe('Does not verify');
})->group('SPEC-036');

it('AC4: two wrappers are "Does not verify"; a text that is not UTF-8 "Could not be checked"', function (): void {
    $two = importMedia(fixturePath('text-two-wrappers.txt'));
    $notUtf8 = tmpDir().'/not-utf8-036.txt';
    file_put_contents($notUtf8, ((string) file_get_contents(fixturePath('fixture-signed.txt')))."\xFF");
    $broken = importMedia($notUtf8);

    expect($two)->toBeGreaterThan(0)
        ->and(storedEntry($two)['state'] ?? null)->toBe('Invalid')
        ->and(storedEntry($two)['codes'] ?? [])->toContain('manifest.text.multipleWrappers')   // amendment 1
        ->and(visibleText(columnHtml($two)))->toBe('Does not verify')
        ->and($broken)->toBeGreaterThan(0)
        ->and(storedEntry($broken)['state'] ?? null)->toBe('error')
        ->and(storedEntry($broken)['reason'] ?? null)->toBe('unsupported')
        ->and(visibleText(columnHtml($broken)))->toBe('Could not be checked');
})->group('SPEC-036');

it('AC5: a UTF-8 text stored under a .jpg name is still not a type the plugin can check (a guard)', function (): void {
    $text = base64_encode('A line of plain text, stored under an image name.');
    $id = attachmentWithEntry(null);
    wpEval("\$file = wp_get_upload_dir()['basedir'].'/m36-text-as-jpeg.jpg'; file_put_contents(\$file, base64_decode('$text')); update_attached_file($id, \$file); (new Tracefern\\ImageCheck\\UploadHook(new Tracefern\\ImageCheck\\Checker))->checkAndStore($id, \$file);");

    expect(storedEntry($id)['state'] ?? null)->toBe('error')
        ->and(storedEntry($id)['reason'] ?? null)->toBe('unsupported');
})->group('SPEC-036');

it('AC6: a text is everywhere images are', function (): void {
    emptyTestEnvironment();
    $id = importMedia(fixturePath('fixture-signed.txt'));
    $unchecked = uncheckedAttachments('fixture-unsigned.txt', 1)[0];
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
})->group('SPEC-036');

it('AC7: CSV and WebVTT are still left alone', function (string $name, string $content): void {
    $path = tmpDir().'/'.$name;
    file_put_contents($path, $content);
    $id = importMedia($path);

    expect($id)->toBeGreaterThan(0)
        ->and(storedEntry($id))->toBeNull()
        ->and(scheduledChecks($id))->toBe(0);
})->with([
    'a CSV' => ['other-036.csv', "name,value\nalpha,1\n"],
    'a WebVTT' => ['other-036.vtt', "WEBVTT\n\n00:00.000 --> 00:01.000\nHello\n"],
])->group('SPEC-036');
