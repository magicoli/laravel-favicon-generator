<?php

use Blockpoint\LaravelFaviconGenerator\LaravelFaviconGenerator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;

it('can generate favicons', function () {
    // Create a test image
    $testImagePath = sys_get_temp_dir().'/test-favicon-source.png';

    // Create a simple 100x100 test image
    $image = imagecreatetruecolor(100, 100);
    imagefill($image, 0, 0, imagecolorallocate($image, 255, 0, 0));
    imagepng($image, $testImagePath);
    imagedestroy($image);

    // Make sure the test image exists
    expect(File::exists($testImagePath))->toBeTrue();

    // Set up the output path for testing
    config(['favicon-generator.output_path' => 'favicon-test']);

    // Clean up any existing test files
    $outputDir = public_path('favicon-test');
    if (File::exists($outputDir)) {
        File::deleteDirectory($outputDir);
    }

    // Generate the favicons
    $generator = new LaravelFaviconGenerator;
    $generatedFiles = $generator->generate($testImagePath);

    // Check that files were generated
    expect($generatedFiles)->not->toBeEmpty();

    // Check that the output directory exists
    expect(File::exists($outputDir))->toBeTrue();

    // Check that the expected files exist
    expect(File::exists(public_path('favicon-test/favicon.ico')))->toBeTrue();
    expect(File::exists(public_path('favicon-test/favicon-96x96.png')))->toBeTrue();
    expect(File::exists(public_path('favicon-test/apple-touch-icon.png')))->toBeTrue();
    expect(File::exists(public_path('favicon-test/site.webmanifest')))->toBeTrue();

    // Clean up
    File::deleteDirectory($outputDir);
    if (File::exists($testImagePath)) {
        File::delete($testImagePath);
    }
});

it('generates into an explicit output path without touching config, and reverts on the next call', function () {
    $testImagePath = sys_get_temp_dir().'/test-favicon-source-2.png';
    $image = imagecreatetruecolor(100, 100);
    imagefill($image, 0, 0, imagecolorallocate($image, 0, 0, 255));
    imagepng($image, $testImagePath);
    imagedestroy($image);

    config(['favicon-generator.output_path' => 'favicon-configured']);

    $configuredDir = public_path('favicon-configured');
    $overrideDir = public_path('favicon-override');
    File::deleteDirectory($configuredDir);
    File::deleteDirectory($overrideDir);

    $generator = new LaravelFaviconGenerator;

    $generatedFiles = $generator->generate($testImagePath, [], 'favicon-override');

    expect(File::exists($overrideDir))->toBeTrue()
        ->and(File::exists($configuredDir))->toBeFalse()
        ->and($generatedFiles)->each->toStartWith('favicon-override/');

    // A subsequent call with no override falls back to config again — the previous call's
    // explicit path must not stick around as a new default.
    $generator->generate($testImagePath);

    expect(File::exists($configuredDir))->toBeTrue();

    File::deleteDirectory($overrideDir);
    File::deleteDirectory($configuredDir);
    File::delete($testImagePath);
});

it('falls back to config(favicon-generator.output_path) when no resolver is registered', function () {
    config(['favicon-generator.output_path' => 'favicon-configured']);

    $html = Blade::render('<x-favicon-meta />');

    expect($html)->toContain('favicon-configured/favicon.ico');
});

it('falls back to config(favicon-generator.output_path) when the resolver returns null', function () {
    config(['favicon-generator.output_path' => 'favicon-configured']);
    app(LaravelFaviconGenerator::class)->resolveUsing(fn () => null);

    $html = Blade::render('<x-favicon-meta />');

    expect($html)->toContain('favicon-configured/favicon.ico');
});

it('renders favicon-meta links from the registered resolver\'s own output_path, generating on demand', function () {
    $testImagePath = sys_get_temp_dir().'/test-favicon-resolver.png';
    $image = imagecreatetruecolor(100, 100);
    imagefill($image, 0, 0, imagecolorallocate($image, 0, 255, 0));
    imagepng($image, $testImagePath);
    imagedestroy($image);

    File::deleteDirectory(public_path('favicon-resolved'));
    app(LaravelFaviconGenerator::class)->resolveUsing(fn () => [
        'source' => $testImagePath,
        'output_path' => 'favicon-resolved',
    ]);

    $html = Blade::render('<x-favicon-meta />');

    expect($html)->toContain('favicon-resolved/favicon.ico')
        ->and(File::exists(public_path('favicon-resolved/favicon.ico')))->toBeTrue();

    File::deleteDirectory(public_path('favicon-resolved'));
    File::delete($testImagePath);
});

it('generateIfNeeded skips regeneration when the existing output is already current', function () {
    $testImagePath = sys_get_temp_dir().'/test-favicon-fresh.png';
    $image = imagecreatetruecolor(100, 100);
    imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 0));
    imagepng($image, $testImagePath);
    imagedestroy($image);

    File::deleteDirectory(public_path('favicon-fresh'));
    $generator = new LaravelFaviconGenerator;
    $generator->generate($testImagePath, [], 'favicon-fresh');

    $result = $generator->generateIfNeeded($testImagePath, 'favicon-fresh');

    expect($result)->toBe([]);

    File::deleteDirectory(public_path('favicon-fresh'));
    File::delete($testImagePath);
});

it('generateIfNeeded regenerates once the source is newer than the existing output', function () {
    $testImagePath = sys_get_temp_dir().'/test-favicon-stale.png';
    $image = imagecreatetruecolor(100, 100);
    imagefill($image, 0, 0, imagecolorallocate($image, 255, 0, 255));
    imagepng($image, $testImagePath);
    imagedestroy($image);

    File::deleteDirectory(public_path('favicon-stale'));
    $generator = new LaravelFaviconGenerator;
    $generator->generate($testImagePath, [], 'favicon-stale');

    // Simulate the source having changed after generation (a new upload, a fixed asset...).
    touch($testImagePath, time() + 5);

    $result = $generator->generateIfNeeded($testImagePath, 'favicon-stale');

    expect($result)->not->toBeEmpty();

    File::deleteDirectory(public_path('favicon-stale'));
    File::delete($testImagePath);
});

it('renders a version query string tied to favicon.ico\'s own mtime, so browsers refetch after a regeneration', function () {
    $testImagePath = sys_get_temp_dir().'/test-favicon-version.png';
    $image = imagecreatetruecolor(100, 100);
    imagefill($image, 0, 0, imagecolorallocate($image, 0, 128, 255));
    imagepng($image, $testImagePath);
    imagedestroy($image);

    File::deleteDirectory(public_path('favicon-version'));
    $generator = new LaravelFaviconGenerator;
    $generator->generate($testImagePath, [], 'favicon-version');
    touch(public_path('favicon-version/favicon.ico'), 1000000000);

    app(LaravelFaviconGenerator::class)->resolveUsing(fn () => [
        'source' => $testImagePath,
        'output_path' => 'favicon-version',
    ]);
    $firstHtml = Blade::render('<x-favicon-meta />');

    touch(public_path('favicon-version/favicon.ico'), 2000000000);
    $secondHtml = Blade::render('<x-favicon-meta />');

    expect($firstHtml)->toContain('favicon.ico?v=1000000000')
        ->and($secondHtml)->toContain('favicon.ico?v=2000000000')
        ->and($firstHtml)->not->toBe($secondHtml);

    File::deleteDirectory(public_path('favicon-version'));
    File::delete($testImagePath);
});

it('never lets a resolver/generation failure crash the page — the component always renders', function () {
    app(LaravelFaviconGenerator::class)->resolveUsing(fn () => [
        'source' => '/nonexistent/path/does-not-exist.svg',
        'output_path' => 'favicon-broken',
    ]);

    $html = Blade::render('<x-favicon-meta />');

    expect($html)->toBeString()->not->toBe('');

    File::deleteDirectory(public_path('favicon-broken'));
});

it('never lets the resolver closure itself throwing crash the page', function () {
    app(LaravelFaviconGenerator::class)->resolveUsing(function () {
        throw new \RuntimeException('resolver blew up');
    });

    $html = Blade::render('<x-favicon-meta />');

    expect($html)->toBeString()->not->toBe('');
});
