# Changelog

All notable changes to `laravel-favicon-generator` will be documented in this file.

## 1.2.0 magicoli 2026-09-13

- feat: add Laravel 13 and Intervention Image v4 support
- add: multi-target resolver, on-demand regeneration, and cache-busting
- fix: never let a generation failure crash the page rendering it
- fix generateIcoFavicon: unique temp filenames per invocation
- fix favicon-meta: read output_path from config, not hardcoded 'favicon'
- Update package name from blockpoint to magicoli until PR is applied to original
- Merge pull request #9 from Blockpoint/dependabot/github_actions/dependabot/fetch-metadata-2.5.0
- Bump dependabot/fetch-metadata from 2.4.0 to 2.5.0
- Merge pull request #5 from Blockpoint/dependabot/github_actions/aglipanci/laravel-pint-action-2.6
- Bump aglipanci/laravel-pint-action from 2.5 to 2.6

## 1.1.1 - 2025-05-11

- Fixed issue where generated SVG favicons were not square, causing display problems
- Added web app title meta tags to the favicon-meta component
- Added apple-mobile-web-app-capable and apple-mobile-web-app-status-bar-style meta tags

## 1.1.0 - 2025-05-11

- Fixed bug where .svg favicons were not generated correctly from non-SVG source images
- Added command options to specify web manifest details (name, short_name, theme_color, background_color)

## 1.0.0 - 2025-05-11

- Initial release
