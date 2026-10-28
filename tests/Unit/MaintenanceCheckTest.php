<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2).'/tools/maintenance-check.php';

/**
 * The local values as the repository holds them on 2026-09-29.
 *
 * @return array{wordpress: string, trust: string, verifier: string}
 */
function currentLocal(): array
{
    return ['wordpress' => '7.1', 'trust' => '99927ca', 'verifier' => 'v0.2.6'];
}

/**
 * The current values as measured on 2026-09-29, with overrides.
 *
 * @param  array<string, mixed>  $overrides
 * @return array{wordpress: string, trust: array{sha: string, date: string, differs: list<string>}, verifier: string}
 */
function currentRemote(array $overrides = []): array
{
    /** @var array{wordpress: string, trust: array{sha: string, date: string, differs: list<string>}, verifier: string} */
    return $overrides + [
        'wordpress' => '7.1',
        'trust' => ['sha' => '99927ca5f0c1d2e3a4b5c6d7e8f9a0b1c2d3e4f5', 'date' => '2026-08-14', 'differs' => []],
        'verifier' => 'v0.2.6',
    ];
}

it('AC1: reports nothing when all are current', function (): void {
    expect(maintenanceReport(currentLocal(), currentRemote()))->toBe([]);
})->group('SPEC-030');

it('AC1: reads the three local values from the repository', function (): void {
    $root = dirname(__DIR__, 2);

    expect(testedUpTo((string) file_get_contents($root.'/readme.txt')))->toBe('7.1')
        ->and(bundledTrustCommit((string) file_get_contents($root.'/trust/README.md')))->toBe('47009ee')
        ->and(lockedVerifier((string) file_get_contents($root.'/composer.lock')))->toBe('v0.8.3');
})->group('SPEC-030');

it('AC2: a new WordPress major version is reported, a minor one is not', function (): void {
    $report = maintenanceReport(currentLocal(), currentRemote(['wordpress' => wordpressMajorMinor('{"offers":[{"current":"7.2"}]}')]));

    expect($report)->toHaveCount(1)
        ->and($report[0]['title'])->toBe('WordPress 7.2')
        ->and($report[0]['line'])->toContain('7.2')->toContain('Tested up to')->toContain('7.1')->toContain('test')
        ->and(maintenanceReport(currentLocal(), currentRemote(['wordpress' => wordpressMajorMinor('{"offers":[{"current":"7.1.3"}]}')])))->toBe([]);
})->group('SPEC-030');

it('AC3: new trust lists are reported with both commits, the date and the files that differ', function (): void {
    $trust = latestTrustCommit('[{"sha":"1a2b3c4d5e6f","commit":{"committer":{"date":"2026-10-01T09:00:00Z"}}}]');
    $differs = trustListsThatDiffer(
        ['C2PA-TRUST-LIST.pem' => "signers\n", 'C2PA-TSA-TRUST-LIST.pem' => "tsa\n"],
        ['C2PA-TRUST-LIST.pem' => "signers and one more\n", 'C2PA-TSA-TRUST-LIST.pem' => "tsa\n"],
    );
    $report = maintenanceReport(currentLocal(), currentRemote(['trust' => $trust + ['differs' => $differs]]));

    expect($differs)->toBe(['C2PA-TRUST-LIST.pem'])
        ->and($report)->toHaveCount(1)
        ->and($report[0]['title'])->toBe('trust lists')
        ->and($report[0]['line'])->toContain('1a2b3c4')->toContain('99927ca')->toContain('2026-10-01')
        ->toContain('C2PA-TRUST-LIST.pem')
        ->and($report[0]['line'])->not->toContain('C2PA-TSA-TRUST-LIST.pem');
})->group('SPEC-030');

it('AC3: the files compared are the two PEM lists, fetched at the new commit', function (): void {
    expect(trustListFiles())->toBe(['C2PA-TRUST-LIST.pem', 'C2PA-TSA-TRUST-LIST.pem'])
        ->and(trustListUrl('d6dec63aa1b2', 'C2PA-TSA-TRUST-LIST.pem'))
        ->toBe('https://raw.githubusercontent.com/c2pa-org/conformance-public/d6dec63aa1b2/trust-list/C2PA-TSA-TRUST-LIST.pem');
})->group('SPEC-030');

it('AC3a: a new commit whose PEM lists equal the bundled ones reports nothing', function (): void {
    // The case of d6dec63 (2026-10-26): only the JSON forms changed upstream.
    $bundled = bundledTrustLists(dirname(__DIR__, 2).'/trust');
    $differs = trustListsThatDiffer($bundled, $bundled);
    $trust = ['sha' => 'd6dec63aa1b2', 'date' => '2026-10-26', 'differs' => $differs];

    expect(array_keys($bundled))->toBe(trustListFiles())
        ->and($differs)->toBe([])
        ->and(maintenanceReport(currentLocal(), currentRemote(['trust' => $trust])))->toBe([]);
})->group('SPEC-030');

it('AC4: tags are compared as versions, and other tags are ignored', function (): void {
    $newest = newestVersionTag(tagNames('[{"name":"v0.2.6"},{"name":"v0.2.7"},{"name":"v0.10.0"},{"name":"latest"},{"name":"v1.0.0-rc1"}]'));
    $report = maintenanceReport(currentLocal(), currentRemote(['verifier' => (string) $newest]));

    expect($newest)->toBe('v0.10.0')
        ->and($report)->toHaveCount(1)
        ->and($report[0]['title'])->toBe('verifier v0.10.0')
        ->and($report[0]['line'])->toContain('v0.10.0')->toContain('v0.2.6')
        ->and(newestVersionTag(['latest']))->toBeNull();
})->group('SPEC-030');

it('AC5: several items, in order, and one title', function (): void {
    $report = maintenanceReport(currentLocal(), currentRemote([
        'wordpress' => '7.2',
        'trust' => ['sha' => '1a2b3c4d5e6f', 'date' => '2026-10-01', 'differs' => ['C2PA-TSA-TRUST-LIST.pem']],
        'verifier' => 'v0.2.7',
    ]));

    expect(array_column($report, 'title'))->toBe(['WordPress 7.2', 'trust lists', 'verifier v0.2.7'])
        ->and(maintenanceTitle($report))->toBe('Maintenance: WordPress 7.2, trust lists, verifier v0.2.7');
})->group('SPEC-030');

it('AC6: a value that cannot be read stops the check', function (callable $read): void {
    expect($read)->toThrow(RuntimeException::class);
})->with([
    'readme without Tested up to' => [fn () => testedUpTo("=== X ===\nStable tag: 1\n")],
    'trust README without a commit' => [fn () => bundledTrustCommit('no commit here')],
    'lock without the verifier' => [fn () => lockedVerifier('{"packages":[]}')],
    'lock that is not JSON' => [fn () => lockedVerifier('not json')],
    'WordPress answer without offers' => [fn () => wordpressMajorMinor('{"offers":[]}')],
    'WordPress answer that is not JSON' => [fn () => wordpressMajorMinor('<html>')],
    'no trust-list commits' => [fn () => latestTrustCommit('[]')],
    'tags that are not JSON' => [fn () => tagNames('')],
    'a fetched PEM list that is empty' => [fn () => trustListsThatDiffer(
        ['C2PA-TRUST-LIST.pem' => "a\n", 'C2PA-TSA-TRUST-LIST.pem' => "b\n"],
        ['C2PA-TRUST-LIST.pem' => '', 'C2PA-TSA-TRUST-LIST.pem' => "b\n"],
    )],
    'a fetched PEM list that is missing' => [fn () => trustListsThatDiffer(
        ['C2PA-TRUST-LIST.pem' => "a\n", 'C2PA-TSA-TRUST-LIST.pem' => "b\n"],
        ['C2PA-TRUST-LIST.pem' => "a\n"],
    )],
    'a bundled PEM list that is missing' => [fn () => bundledTrustLists(sys_get_temp_dir().'/no-such-trust-dir-'.bin2hex(random_bytes(4)))],
])->group('SPEC-030');

it('AC6: the script exits non-zero and prints no report when a remote cannot be read', function (): void {
    $script = escapeshellarg(dirname(__DIR__, 2).'/tools/maintenance-check.php');
    exec('MAINTENANCE_CHECK_OFFLINE=1 php '.$script.' 2>&1', $lines, $exit);

    expect($exit)->not->toBe(0)
        ->and(implode("\n", $lines))->not->toContain('"title"');
})->group('SPEC-030');

it('AC7: the workflow runs only on schedule and by hand, with two permissions', function (): void {
    $yaml = (string) file_get_contents(dirname(__DIR__, 2).'/.github/workflows/maintenance.yml');
    $on = (string) preg_replace('/.*\non:\n(.*?)\n\S.*/s', '$1', $yaml);

    expect($on)->toContain("schedule:\n    - cron: '0 6 * * 1'")
        ->and($on)->toContain('workflow_dispatch:')
        ->and(str_contains($on, 'push') || str_contains($on, 'pull_request'))->toBeFalse()
        ->and($yaml)->toContain("permissions:\n  contents: read\n  issues: write\n\n");
})->group('SPEC-030');
