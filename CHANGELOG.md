# Changelog

All notable changes to **Institutional Email** are documented in this file.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

### Added

- **Release packaging, CI and a testes deploy** (#14). Every PR runs PHPUnit (also against a real WordPress), PHPStan, PHPCS, ESLint, Stylelint, Vitest and a composer audit; each tag publishes the plugin zip and the notes; `develop` deploys to the testes site, followed by a smoke check.
- **The forms, as a WordPress plugin and as a standalone page** (#14). `[institutional_email form="students|teachers"]` places a form; the address is worked out in the visitor's browser, and nothing typed is sent. Each release also attaches a self-contained HTML file for iframes, and GitHub Pages serves the latest one.
- **Per-field errors, copy buttons and screen-reader announcements** (#14). Each field says what is wrong, the address and the password each have a Copy button, and a status line announces the result.
- **Settings → Institutional Email** (#14). Vertical tabs as in the other plugins: which forms the site offers and who may use them (everyone or logged-in users), the address rules (domain, how long a middle name must be to count, the initial password), the platform the result links to, and every text the form shows. The on/off switches save as soon as they are flipped.
- **A Documentation tab** in the settings (#14), with the shortcode, the rules with examples, privacy, and credits to the author and the repository.
- **Updates from GitHub releases** (#14). New versions appear on the WordPress update screen, and each package is checked against its published SHA-256 before it is installed.
- **A README for the plugin and the iframe** (#14), in English, with the rules, the settings, the iframe code for the GitHub Pages page, screenshots, and the credits and list of schools from the 2020 README; the Heroku links, which no longer work, are gone.

### Changed

- **Only the letters a to z reach the address** (#14). Accents are dropped as before, and apostrophes, hyphens and repeated spaces no longer leak into it ("D'Ávila" → `davila`). Middle names of up to three letters ("da", "dos", "Rui") are still left out of a student's address.

### Removed

- **The Heroku pages, jQuery, the CDN libraries and Google Analytics** (#14). The forms load no third-party file and collect no usage data.

## [0.4.0] (2020-05-14) — `cbd1f51`

The last version of the HTML pages, served on Heroku, before the project became a plugin.

### Added

- **The teachers' form asks for the network** (#12). Direct network teachers type their 7-digit RF, partner network teachers their CPF; the address ends in whichever was given.
- **A footer on the forms** (#10).

### Changed

- **Short middle names leave a student's address** (#9). Only middle names of four letters or more give their initial; "da", "de", "dos" are left out, and the first and last names are kept whole.
- **The forms check their fields again before showing an address** (#11), and the copy buttons use clipboard.js.

## [0.3.0] (2020-05-13) — `41b8c1e`

### Changed

- **The forms became two pages for an iframe,** `email-alunos.html` and `email-professores.html`, served from Heroku with a shared script and stylesheet. The README told schools to embed them with an `<iframe>` instead of copying the code, so a fix reached every site at once.

## [0.2.0] (2020-05-11) — `a648d9b`

### Added

- **A form for partner network teachers,** `index-professores.html`: full name and CPF, with the CPF's check digits validated, for an address ending in the CPF.

## [0.1.0] (2020-05-01) — `f7b4ca3`

### Added

- **The first form, for students,** one file, `index.html`: full name and date of birth for the address `firstname` + middle initials + `lastname.DDMMYYYY@edu.sme.prefeitura.sp.gov.br`, the initial password `12345678`, buttons to copy both, and a link to Google Classroom. Accents were removed with a character map, and every middle name gave its initial.

These four versions were never tagged at the time: their numbers, dates and commits were assigned afterwards from the repository's history (#14).
