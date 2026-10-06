<?php

declare(strict_types=1);

/*
 * SPEC-035: GIF is checked as the other images are. The fixtures are the
 * verifier's own, and one large GIF made for this spec (tests/Fixtures/README.md).
 */

it('AC1: a signed GIF gets the verifier\'s verdict', function (): void {
    $id = importMedia(fixturePath('fixture-signed.gif'));
    $entry = storedEntry($id);

    expect($entry)->not->toBeNull()
        ->and(stable((array) $entry))->toBe(expectedEntry(fixturePath('fixture-signed.gif'), defaultSettingsFile()))
        ->and($entry['format'] ?? null)->toBe('gif')
        ->and(visibleText(columnHtml($id)))->toBe('Intact: signer not trusted');
})->group('SPEC-035');

it('AC1: a GIF uploaded through the browser\'s REST route is checked as WP-CLI\'s import is', function (): void {
    $source = fixturePath('fixture-signed.gif');
    $upload = httpUpload('rest', $source, 'browser-rest.gif', 'image/gif');
    runPendingChecks();

    expect($upload['status'])->toBeIn([200, 201])
        ->and($upload['id'])->toBeGreaterThan(0)
        ->and(sameAsHostFile(keptPath($upload['id']), $source))->toBeTrue()
        ->and(stable((array) storedEntry($upload['id'])))->toBe(expectedEntry($source, defaultSettingsFile()));
})->group('SPEC-035');

it('AC1: with trust settings that hold the signer, a GIF is "Verified: trusted signer"', function (): void {
    setOption('tracefern_custom_trust', customSettingsJson());
    try {
        $id = importMedia(fixturePath('fixture-signed.gif'));
        $entry = (array) storedEntry($id);

        expect(stable($entry))->toBe(expectedEntry(fixturePath('fixture-signed.gif'), customSettingsFile()))
            ->and($entry['state'] ?? null)->toBe('Trusted')
            ->and(visibleText(columnHtml($id)))->toBe('Verified: trusted signer');
    } finally {
        resetTrustOptions();
    }
})->group('SPEC-035');

it('AC2: an unsigned GIF is "No Content Credentials"', function (): void {
    $id = importMedia(fixturePath('fixture-unsigned.gif'));

    expect(storedEntry($id)['state'] ?? null)->toBe('none')
        ->and(stable((array) storedEntry($id)))->toBe(expectedEntry(fixturePath('fixture-unsigned.gif'), defaultSettingsFile()))
        ->and(visibleText(columnHtml($id)))->toBe('No Content Credentials');
})->group('SPEC-035');

it('AC3: a GIF changed after signing is "Does not verify"', function (): void {
    $path = fixturePath('gif-flip-image.gif');
    $id = importMedia($path);
    $entry = (array) storedEntry($id);

    expect(stable($entry))->toBe(expectedEntry($path, defaultSettingsFile()))
        ->and($entry['state'] ?? null)->toBe('Invalid')
        ->and($entry['codes'] ?? [])->toContain('assertion.dataHash.mismatch')
        ->and($entry['ai'] ?? null)->toBeFalse()
        ->and(visibleText(columnHtml($id)))->toBe('Does not verify');
})->group('SPEC-035');

it('AC4: a GIF cut inside its C2PA_GIF block is "Does not verify"; one broken before it "Could not be checked"', function (): void {
    $cut = importMedia(fixturePath('gif-truncated-in-c2pa.gif'));
    $broken = importMedia(fixturePath('gif-block-size-12.gif'));

    expect($cut)->toBeGreaterThan(0)
        ->and(stable((array) storedEntry($cut)))->toBe(expectedEntry(fixturePath('gif-truncated-in-c2pa.gif'), defaultSettingsFile()))
        ->and(storedEntry($cut)['state'] ?? null)->toBe('Invalid')
        ->and(storedEntry($cut)['codes'] ?? [])->toContain('general.error')
        ->and(visibleText(columnHtml($cut)))->toBe('Does not verify')
        ->and($broken)->toBeGreaterThan(0)
        ->and(storedEntry($broken)['state'] ?? null)->toBe('error')
        ->and(storedEntry($broken)['reason'] ?? null)->toBe('unreadable')
        ->and(visibleText(columnHtml($broken)))->toBe('Could not be checked');
})->group('SPEC-035');

it('AC5: a large GIF\'s original is checked, not its -scaled copy', function (): void {
    $path = fixturePath('tracefern-large-signed.gif');
    $id = importMedia($path);

    expect(wpEval("echo basename(get_attached_file($id));"))->toEndWith('-scaled.gif')
        ->and(stable((array) storedEntry($id)))->toBe(expectedEntry($path, defaultSettingsFile()))
        ->and(storedEntry($id)['state'] ?? null)->toBe('Valid');
})->group('SPEC-035');

it('AC6: a GIF is everywhere images are', function (): void {
    emptyTestEnvironment();
    $id = importMedia(fixturePath('fixture-signed.gif'));
    $unchecked = uncheckedAttachments('fixture-unsigned.gif', 1)[0];
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
})->group('SPEC-035');

it('AC7: WP-CLI skips a type it does not check without naming types', function (): void {
    $path = tmpDir().'/skipped-035.pdf';
    file_put_contents($path, "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF\n");
    $id = importMedia($path);
    $result = wpCli(['tracefern', 'check', (string) $id]);

    expect($result['output'])->toContain("Skipped $id: not a type this plugin checks")
        ->and($result['output'])->not->toContain('JPEG');
})->group('SPEC-035');

it('AC8: BMP, PDF and AVI are still left alone', function (string $path): void {
    $id = importMedia($path);

    expect($id)->toBeGreaterThan(0)
        ->and(storedEntry($id))->toBeNull()
        ->and(scheduledChecks($id))->toBe(0);
})->with([
    'a BMP' => [(function (): string {
        $path = tmpDir().'/other-035.bmp';
        file_put_contents($path, (string) base64_decode('Qk06AAAAAAAAADYAAAAoAAAAAQAAAAEAAAABABgAAAAAAAQAAAATCwAAEwsAAAAAAAAAAAAA////AA=='));

        return $path;
    })()],
    'a PDF' => [(function (): string {
        $path = tmpDir().'/other-035.pdf';
        file_put_contents($path, "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF\n");

        return $path;
    })()],
    'an AVI' => [fixturePath('fixture-signed.avi')],
])->group('SPEC-035');
