# Changelog

All notable changes to **Institutional Email** are documented in this file.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

### Added

- **Release packaging, CI and a testes deploy** (#14). Every PR runs PHPUnit (also against a real WordPress), PHPStan, PHPCS, ESLint, Stylelint, Vitest and a composer audit; each tag publishes the plugin zip and the notes; `develop` deploys to the testes site, followed by a smoke check.
