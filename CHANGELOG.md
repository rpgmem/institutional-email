# Changelog

All notable changes to **Institutional Email** are documented in this file.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

### Added

- **Release packaging, CI and a testes deploy** (#14). Every PR runs PHPUnit (also against a real WordPress), PHPStan, PHPCS, ESLint, Stylelint, Vitest and a composer audit; each tag publishes the plugin zip and the notes; `develop` deploys to the testes site, followed by a smoke check.
- **The forms, as a WordPress plugin and as a standalone page** (#14). `[institutional_email form="students|teachers"]` places a form; the address is worked out in the visitor's browser, and nothing typed is sent. Each release also attaches a self-contained HTML file for iframes, and GitHub Pages serves the latest one.
- **Per-field errors, copy buttons and screen-reader announcements** (#14). Each field says what is wrong, the address and the password each have a Copy button, and a status line announces the result.

### Changed

- **Only the letters a to z reach the address** (#14). Accents are dropped as before, and apostrophes, hyphens and repeated spaces no longer leak into it ("D'Ávila" → `davila`). Middle names of up to three letters ("da", "dos", "Rui") are still left out of a student's address.

### Removed

- **The Heroku pages, jQuery, the CDN libraries and Google Analytics** (#14). The forms load no third-party file and collect no usage data.
