<?php

declare(strict_types=1);
use Tracefern\ImageCheck\UploadHook;

it('AC8: the section\'s texts are escaped and translatable, numbers localised', function (): void {
    $source = (string) file_get_contents(dirname(__DIR__, 2).'/src/SettingsPage.php');
    $section = (string) preg_replace('/.*(private function existingImages\(\).*?\n    }\n).*/s', '$1', $source);

    expect($section)->toContain("esc_html__('Existing files', 'tracefern-image-check-for-c2pa')")
        ->toContain("esc_html__('Check files that were never checked', 'tracefern-image-check-for-c2pa')")
        ->toContain("esc_html__('Check all files again', 'tracefern-image-check-for-c2pa')")
        ->toContain("esc_html__('Stop', 'tracefern-image-check-for-c2pa')")
        ->toContain('number_format_i18n(')
        ->and(preg_match_all('/\becho\b/', $section))->toBeGreaterThan(0)
        ->and(str_contains($section, '__(') && ! preg_match('/[^_]__\(/', str_replace(['esc_html__(', 'esc_attr__('], '', $section)))->toBeTrue();
})->group('SPEC-031');

it('amendment 3: every text of the settings page that counted images says files', function (): void {
    $source = (string) file_get_contents(dirname(__DIR__, 2).'/src/SettingsPage.php');

    expect($source)->toContain("'%1\$s JPEG, PNG, GIF, WebP, WAV, MP3, FLAC and text files; %2\$s never checked.'")
        ->toContain("'Checking existing files: %1\$s of %2\$s done. The checks run in the background.'")
        ->toContain("'Finished: %1\$s files checked, %2\$s.'")
        ->toContain('a few files at a time; new uploads go first. "Check all files again" applies the current trust settings and verifier to every file.')
        ->toContain('To apply them to the files already in the Media Library, use "Check all files again" below.')
        ->toContain('the last file was checked without trust settings')
        ->and(preg_match('/esc_html__\([\'"][^\'"]*\bimages?\b/', $source))->toBe(0);
})->group('SPEC-031');

it('SPEC-035: every query of ExistingImages names each checked type, no fewer', function (): void {
    $source = (string) file_get_contents(dirname(__DIR__, 2).'/src/ExistingImages.php');
    $types = count(UploadHook::MIME_TYPES);
    preg_match_all('/\[([^\]]*)\] = UploadHook::MIME_TYPES;/', $source, $names);
    preg_match_all('/p\.post_mime_type IN \(([^)]*)\)/', $source, $lists);

    expect($names[1])->not->toBeEmpty()
        ->and(array_map(static fn (string $list): int => count(explode(',', $list)), $names[1]))->each->toBe($types)
        ->and($lists[1])->toHaveCount(4)
        ->and(array_map(static fn (string $list): int => substr_count($list, '%s'), $lists[1]))->each->toBe($types);
})->group('SPEC-035');
