<?php

namespace Blockpoint\LaravelFaviconGenerator\View\Components;

use Blockpoint\LaravelFaviconGenerator\LaravelFaviconGenerator;
use Illuminate\View\Component;

class FaviconMeta extends Component
{
    /**
     * Resolves the current target via LaravelFaviconGenerator::resolveUsing() (see its own
     * docblock) and regenerates it on demand when stale, before rendering the tags — so an app
     * with several favicon sets doesn't need to pass anything here, or remember to trigger
     * generation itself.
     */
    public function render()
    {
        $generator = app(LaravelFaviconGenerator::class);
        $target = $generator->resolve();

        $faviconPath = config('favicon-generator.output_path', 'favicon');

        if ($target && ! empty($target['source'])) {
            $generator->generateIfNeeded($target['source'], $target['output_path'] ?? null, $target['manifest'] ?? []);
            $faviconPath = $target['output_path'] ?? $faviconPath;
        }

        return view('favicon-generator::components.favicon-meta', ['faviconPath' => $faviconPath]);
    }
}
