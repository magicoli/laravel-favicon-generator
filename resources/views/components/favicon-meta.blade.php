{{--
    Favicon Meta Tags Component — $faviconPath is resolved and kept current by the component
    class. Browsers cache favicon-type assets unusually aggressively, often ignoring normal
    Cache-Control — regenerating the files at the same URL isn't enough on its own, a viewer can
    keep seeing the old bytes for a long time. A ?v= query string tied to the file's own mtime
    forces a fetch whenever the file actually changes, while leaving the URL (and browser cache)
    alone the rest of the time.
--}}
@php
    $version = file_exists(public_path("{$faviconPath}/favicon.ico"))
        ? filemtime(public_path("{$faviconPath}/favicon.ico"))
        : null;
    $versioned = fn (string $path): string => asset($path).($version ? "?v={$version}" : '');
@endphp
<link rel="icon" type="image/png" href="{{ $versioned("{$faviconPath}/favicon-96x96.png") }}" sizes="96x96" />
<link rel="icon" type="image/svg+xml" href="{{ $versioned("{$faviconPath}/favicon.svg") }}" />
<link rel="shortcut icon" href="{{ $versioned("{$faviconPath}/favicon.ico") }}" />
<link rel="apple-touch-icon" sizes="180x180" href="{{ $versioned("{$faviconPath}/apple-touch-icon.png") }}" />
<link rel="manifest" href="{{ $versioned("{$faviconPath}/site.webmanifest") }}" />

{{-- Web App Title Meta Tags --}}
@php
    $manifestPath = public_path("{$faviconPath}/site.webmanifest");
    $appName = '';
    $shortName = '';

    if (file_exists($manifestPath)) {
        $manifestContent = json_decode(file_get_contents($manifestPath), true);
        $appName = $manifestContent['name'] ?? config('app.name', '');
        $shortName = $manifestContent['short_name'] ?? $appName;
    } else {
        $appName = config('app.name', '');
        $shortName = $appName;
    }
@endphp

@if(!empty($appName))
<meta name="application-name" content="{{ $appName }}" />
<meta name="apple-mobile-web-app-title" content="{{ $appName }}" />
@endif
<meta name="apple-mobile-web-app-capable" content="yes" />
<meta name="apple-mobile-web-app-status-bar-style" content="default" />
