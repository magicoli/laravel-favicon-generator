<?php

namespace Blockpoint\LaravelFaviconGenerator\View\Components;

use Blockpoint\LaravelFaviconGenerator\LaravelFaviconGenerator;
use Illuminate\View\Component;
use Throwable;

class FaviconMeta extends Component
{
    /**
     * Resolves the current target via LaravelFaviconGenerator::resolveUsing() (see its own
     * docblock) and regenerates it on demand when stale, before rendering the tags — so an app
     * with several favicon sets doesn't need to pass anything here, or remember to trigger
     * generation itself.
     *
     * Generation happens on every request, implicitly, as a side effect of rendering a page —
     * unlike calling generate()/generateIfNeeded() directly (e.g. from the favicon:generate
     * command, where a thrown exception is exactly what the user needs to see and fix), a
     * failure here must never turn an otherwise-fine page into a 500. A missing SVG delegate, a
     * corrupt upload, a read-only public/ in some environment — none of that should be able to
     * take down every page in the app just because this component is in the layout. Log it and
     * fall back to config('favicon-generator.output_path') (which may itself be empty/missing —
     * that's a broken favicon link, not a broken page).
     */
    public function render()
    {
        $generator = app(LaravelFaviconGenerator::class);
        $faviconPath = config('favicon-generator.output_path', 'favicon');

        try {
            $target = $generator->resolve();

            if ($target && ! empty($target['source'])) {
                $generator->generateIfNeeded($target['source'], $target['output_path'] ?? null, $target['manifest'] ?? []);
                $faviconPath = $target['output_path'] ?? $faviconPath;
            }
        } catch (Throwable $e) {
            report($e);
        }

        return view('favicon-generator::components.favicon-meta', ['faviconPath' => $faviconPath]);
    }
}
