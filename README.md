# Laravel Favicon Generator

[![Latest Version on Packagist](https://img.shields.io/packagist/v/magicoli/laravel-favicon-generator.svg?style=flat-square)](https://packagist.org/packages/magicoli/laravel-favicon-generator)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/magicoli/laravel-favicon-generator/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/magicoli/laravel-favicon-generator/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/magicoli/laravel-favicon-generator/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/magicoli/laravel-favicon-generator/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/magicoli/laravel-favicon-generator.svg?style=flat-square)](https://packagist.org/packages/magicoli/laravel-favicon-generator)

A Laravel package to generate and manage high-quality favicons for your web application. This package provides two main features:

1. A generator to create all necessary favicon files from a single source image with optimal quality
2. A Blade component to easily include the favicon meta tags in your HTML

### Features

- Generates high-quality PNG icons using Imagick when available (with GD fallback)
- Creates favicon.ico, favicon-96x96.png, favicon.svg, apple-touch-icon-180x180.png
- Generates web app manifest icons (192x192 and 512x512)
- Creates site.webmanifest file
- Generates SVG favicon from any source image format
- Maintains aspect ratio while ensuring exact dimensions

## Installation

You can install the package via composer:

```bash
composer require magicoli/laravel-favicon-generator
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-favicon-generator-config"
```

Optionally, you can publish the views using:

```bash
php artisan vendor:publish --tag="laravel-favicon-generator-views"
```

## Usage

### Generating Favicons

To generate favicons from a source image, use the provided Artisan command:

```bash
php artisan favicon:generate {path/to/your/source/image.png}
```

This will generate the following favicon files in your public/favicon directory:

- favicon.ico for general browser support
- favicon-96x96.png for higher-resolution displays
- favicon.svg for scalable, high-quality vector images (if source is SVG)
- apple-touch-icon.png for iOS devices
- web-app-manifest-192x192.png and web-app-manifest-512x512.png for Progressive Web Apps
- site.webmanifest for PWA configuration

### Using the Blade Component

To include the favicon meta tags in your HTML, add the following component to your layout file:

```blade
<head>
    <!-- Other head elements -->
    <x-favicon-meta />
</head>
```

This will output the necessary meta tags for all generated favicons:

```html
<link
    rel="icon"
    type="image/png"
    href="/favicon/favicon-96x96.png"
    sizes="96x96"
/>
<link rel="icon" type="image/svg+xml" href="/favicon/favicon.svg" />
<link rel="shortcut icon" href="/favicon/favicon.ico" />
<link
    rel="apple-touch-icon"
    sizes="180x180"
    href="/favicon/apple-touch-icon.png"
/>
<link rel="manifest" href="/favicon/site.webmanifest" />
<meta name="application-name" content="Your App Name" />
<meta name="apple-mobile-web-app-title" content="Your App Name" />
<meta name="apple-mobile-web-app-capable" content="yes" />
<meta name="apple-mobile-web-app-status-bar-style" content="default" />
```

The component will automatically use the application name from your web manifest file or fall back to your Laravel app name configuration.

### Programmatic Usage

You can also generate favicons programmatically:

```php
use Blockpoint\LaravelFaviconGenerator\LaravelFaviconGenerator;

$generator = new LaravelFaviconGenerator();
$generatedFiles = $generator->generate('path/to/your/source/image.png');
```

`generate()` also accepts an explicit output path as its third argument, overriding
`config('favicon-generator.output_path')` for that one call — useful for generating more than
one favicon set from a single process without mutating global config in between:

```php
$generator->generate($sourcePath, $manifestOptions, 'favicons/some-other-set');
```

### Multiple Favicon Sets (Multi-Tenant, Multi-Brand, ...)

An app with more than one brand — multi-tenant, multi-site, whatever its own domain calls it —
registers a resolver closure once, typically in a service provider's `boot()`:

```php
use Blockpoint\LaravelFaviconGenerator\Facades\LaravelFaviconGenerator;

LaravelFaviconGenerator::resolveUsing(function (): ?array {
    $tenant = /* however your app resolves the current one */;

    return $tenant ? [
        'source' => $tenant->iconPath(),           // an absolute filesystem path
        'output_path' => "favicons/{$tenant->slug}", // relative to public/, or null for the default
        'manifest' => ['name' => $tenant->name],    // optional: name, short_name, theme_color, background_color
    ] : null; // null falls back to config('favicon-generator.output_path')
});
```

`<x-favicon-meta />` then needs nothing else — it resolves the closure and regenerates on demand
on every request (see "Automatic Regeneration" below), so the app never has to remember to
trigger generation itself after an upload, a settings change, or a deploy.

> Calling the Facade from your own service provider's `boot()` (as above) is safe regardless of
> provider order: this package binds its singleton during `register()`, and Laravel guarantees
> every provider's `register()` completes before any provider's `boot()` runs — so the binding
> always exists by the time any `boot()`-time Facade call resolves it.

### Automatic Regeneration

Favicons don't need a manual regeneration step (a console command, an observer). Every time
`<x-favicon-meta />` renders, it calls `generateIfNeeded()`, which regenerates only when the
existing output is missing or older than the source image — a couple of cheap filesystem stats
once warm, a real regeneration only when something actually changed:

```php
$generator->generateIfNeeded($sourcePath, $outputPath, $manifestOptions);
```

Browsers cache favicon-type assets unusually aggressively, often ignoring normal `Cache-Control`
headers — regenerating the files at the same URL isn't enough on its own to make a viewer see
the update. `<x-favicon-meta />` appends a `?v=` query string derived from the generated
`favicon.ico`'s own mtime to every link it renders (and a matching one to the icons referenced
from inside `site.webmanifest`), so the URL itself changes whenever the file does, forcing a
refetch — no dependency on how long a browser happens to cache things for.

### A Note on SVG Sources With Embedded Web Fonts

If your source SVG renders text via an embedded font (a `@font-face` with a base64 `woff2`
`src`, the common export format from design tools) rather than converting the text to outline
paths, be aware: ImageMagick's SVG rasterizer (used for every raster output — the `.ico`, the
PNGs, the manifest icons) does **not** reliably read that embedded font. It typically falls back
to a generic system font instead, silently — no error, no warning, just the wrong typeface in
the generated raster files. The vector `favicon.svg` output itself is unaffected (browsers
render that directly, with full font support) — this only affects the rasterized outputs.

This package rasterizes SVG sources at a fixed high resolution before decoding them (so results
are crisp rather than blurry), but it cannot currently substitute the correct font into that
rasterization. If your source SVG depends on an embedded font for its text, and the generated
`.ico`/PNG outputs render in the wrong typeface, provide an already-rasterized PNG/JPG as the
source instead (exported with the real font, e.g. from the same design tool that produced the
SVG) — the package always uses whatever source it's given as-is, it will not substitute a
different file on your behalf.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
