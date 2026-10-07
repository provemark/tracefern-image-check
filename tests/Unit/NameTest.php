<?php

declare(strict_types=1);

// SPEC-020: the plugin is named Tracefern; "Provemark" is left only where it
// names the bundled verifier or its organisation. SPEC-037: the display name
// is Tracefern Media Check for Content Credentials (C2PA).

it('carries the Tracefern name in the main file and readme.txt', function (): void {
    $root = dirname(__DIR__, 2);
    $main = (string) @file_get_contents($root.'/tracefern-image-check-for-c2pa.php');
    $readme = (string) file_get_contents($root.'/readme.txt');

    expect($main)->toMatch('/^ \* Plugin Name:\s+Tracefern Media Check for Content Credentials \(C2PA\)$/m')
        ->toMatch('/^ \* Text Domain:\s+tracefern-image-check-for-c2pa$/m')
        ->and(strtok($readme, "\n"))->toBe('=== Tracefern Media Check for Content Credentials (C2PA) ===');   // SPEC-037 AC1
});

it('SPEC-023: calls the settings page Tracefern, never C2PA Check', function (): void {
    $root = dirname(__DIR__, 2);
    $files = explode("\n", trim((string) shell_exec('git -C '.escapeshellarg($root).' ls-files')));
    $history = '#^(AI-LOG\.md|specs/|notes/|tests/Unit/NameTest\.php$)#';
    $found = array_values(array_filter($files, fn (string $f): bool => $f !== '' && preg_match($history, $f) !== 1
        && is_file($root.'/'.$f) && str_contains((string) file_get_contents($root.'/'.$f), 'C2PA Check')));
    $settings = (string) file_get_contents($root.'/src/SettingsPage.php');
    $composer = json_decode((string) file_get_contents($root.'/composer.json'), true);

    expect($found)->toBe([])
        ->and($settings)->toContain("__('Tracefern', 'tracefern-image-check-for-c2pa'),")
        ->and(is_array($composer) ? $composer['name'] ?? null : null)->toBe('mauricevanloon/tracefern-image-check');
});

it('uses the old name only for the bundled verifier and in the history', function (): void {
    $root = dirname(__DIR__, 2);
    $files = explode("\n", trim((string) shell_exec('git -C '.escapeshellarg($root).' ls-files')));
    // Records of what happened under the old name.
    $history = '#^(AI-LOG\.md|specs/SPEC-0|notes/|tests/Unit/NameTest\.php$)#';
    $allowed = [
        '#provemark/c2pa-verifier#i',
        '#vendor/provemark/#',
        '#Provemark(\\\\+)C2paVerifier#',
        '#provemark\.github\.io#',
        '#provemark/content-credentials#',
        '#provemark/tracefern-image-check#',
    ];

    $found = [];
    foreach ($files as $file) {
        if ($file === '' || preg_match($history, $file) === 1 || ! is_file($root.'/'.$file)) {
            continue;
        }
        $text = preg_replace($allowed, '', (string) file_get_contents($root.'/'.$file));
        if (stripos((string) $text, 'provemark') !== false) {
            $found[] = $file;
        }
    }

    expect($found)->toBe([]);
});

it('SPEC-037 AC2: puts no trademark in front of the name', function (): void {
    $main = (string) file_get_contents(dirname(__DIR__, 2).'/tracefern-image-check-for-c2pa.php');
    preg_match('/^ \* Plugin Name:\s+(.+)$/m', $main, $m);
    $name = $m[1] ?? '';
    $before = (string) strstr($name, ' for ', true);

    expect($name)->toStartWith('Tracefern ')
        ->and($before)->not->toBe('')
        ->and($before)->not->toContain('C2PA')
        ->and($before)->not->toContain('Content Credentials')
        ->and((string) strstr($name, ' for '))->toContain('Content Credentials')->toContain('C2PA');
})->group('SPEC-037');

it('SPEC-037 AC3: uses the old display name only in the history', function (): void {
    $root = dirname(__DIR__, 2);
    $files = explode("\n", trim((string) shell_exec('git -C '.escapeshellarg($root).' ls-files')));
    $history = '#^(AI-LOG\.md|specs/|notes/|tests/Unit/NameTest\.php$)#';
    $found = array_values(array_filter($files, fn (string $f): bool => $f !== '' && preg_match($history, $f) !== 1
        && is_file($root.'/'.$f) && str_contains((string) file_get_contents($root.'/'.$f), 'Tracefern Image Check')));

    expect($found)->toBe([]);
})->group('SPEC-037');
