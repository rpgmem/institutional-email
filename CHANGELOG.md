# Changelog

All notable changes to **Institutional Email** are documented in this file.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

## [1.4.0] (2026-10-13)

### Added

- **The plugin has an icon on Dashboard → Updates and in the plugin list** (#64). A building beside an @, white on amber, replaces WordPress's grey placeholder, read from the installed copy. `assets/img/brand/` also ships black, white, dark-screen and no-background variants, each as SVG and as 128 px and 256 px PNG.
- **"View details" shows a banner, the readme's tabs and screenshots in place, in Portuguese too** (#67, #69). Description, Installation, FAQ, Screenshots and Other notes come from the offered release's `readme.txt` (or `languages/readme-pt_BR.txt` for a pt_BR viewer), the changelog renders its Markdown, and a screenshot opens in an overlay with arrows, swipe and zoom.

### Changed

- **The translation files are checked against each other, and the compiler writes WP-CLI's shape** (#72, #71, #74). `bin/po-to-l10n.php`, `bin/po-catalogue.php` and a test share one `.po` reader; CI fails when the `.po`, `.mo` and `.l10n.php` disagree, the `.l10n.php` matches `wp i18n make-php` byte for byte, and a readme change carries its pt_BR copy in the same PR.
- **CSS and JS ship minified, and the clipboard buffer uses a class** (#78). `npm run build` writes `.min` files, loaded unless `SCRIPT_DEBUG` is on, and CI fails when one is stale. The textarea the legacy copy path uses takes `.ie-copy-buffer` instead of inline styles.
- **The form looks like Free Form Certificate's** (#82). The same type scale, field and button measures and card, the main action at full width, and the theme's font by default; the Design tab offers the theme's, the plugin's system font or Arial.

### Fixed

- **Dark mode reaches what the browser paints itself** (#76). The settings screen declares `color-scheme: dark`, so scrollbars and native pickers stop painting light, and link-styled buttons read the palette.
- **Dark Mode reaches the form on the site** (#80). The shortcode loads the theme switch and the form gets its own dark palette; the Design tab's colours apply to the light look only, and the standalone HTML follows the operating system.


## [1.3.0] (2026-10-12) — `ff0104e3`

The licence moves to AGPLv3 or later. The rest is the release process catching up with Free Form Certificate: CI tests the declared WordPress floor, Dependabot's minor updates merge on their own, and every workflow runs with a read-only token. Nothing changes in what the plugin does.

### Changed

- ⚠ **The licence is now AGPLv3 or later** (#57). Reuse and forks stay free; a modified copy offered as a network service must now publish its source too. Releases already published stay GPLv3, and a test holds every declaration of the licence together.
- **CI tests the declared WordPress floor, and the PHP coverage floor rises to 90%** (#52). The functional suite also runs on WordPress 6.8, and `composer.json` pins the PHP platform to 8.3.
- **Dependabot's patch and minor updates merge on their own** (#54). Required checks still gate the merge; majors stay manual.

### Security

- **CI jobs run with a read-only token, and form ids no longer use `Math.random()`** (#59). Both close CodeQL findings.

## [1.2.0] (2026-10-12) — `a8812004`

The settings screen takes Free Form Certificate's look: its colours, icons and documentation navigation, and a dark mode; the Design tab warns about hard-to-read colours, and a cleared number keeps its default.

### Added

- **The Design tab warns about colours that are hard to read** (#41). As in Email Signature Generator, a colour pair below WCAG's 4.5:1 (button text on the accent, the accent on white and on the result, notes and errors on white) is still saved, with a warning naming it and its ratio.
- **Dark mode for the settings screen** (#46). A Dark Mode setting on the General tab (Off, On, or Auto to follow the operating system) turns this screen dark with Free Form Certificate's dark palette, saves as soon as it changes and repaints at once. The form on the site keeps its own look.

### Changed

- **CI holds coverage floors and two new guards** (#42). PHP line coverage (unit and functional suites merged) and JS line coverage now have floors. Tests check the Upgrade Notice against the Stable tag, and require every PHPCS or PHPStan suppression to name its rule and give a reason. The admin script's tests now count toward coverage.
- **The Documentation tab's contents stays in view on a wide screen** (#43). From 1100px wide the Contents card is a sticky column beside the topics, so any topic is one click away while reading another; narrower, it stays above them as before. The credits moved below the topics.
- **The documentation's navigation matches Free Form Certificate's** (#44). The Contents card is now Quick Navigation: a search box that filters the topics and their cards, and one bordered row per topic with its icon, filled with the admin colour on hover.
- **The settings screen takes Free Form Certificate's colours and icons** (#45). Its colours come from one palette with Free Form Certificate's light values: the accent is its blue rather than the admin colour scheme's, and borders are darker (3:1 against white). The Design tab and its topic use its palette icon, and the chart, link and key icons are its drawings.

### Fixed

- **A cleared number field keeps its default** (#41). Emptying the minimum middle-name length or the rounded corners stored the lowest value (1, or square corners); it now means "not set", like the form width already did.
- **The Design tab's rows no longer flash** (#41). When the design is off they come hidden from the server instead of being hidden by the script after the page has drawn.

## [1.1.0] (2026-10-11) — `ca96b924`

### Changed

- **⚠ A shortcode or standalone page that names no form opens with a profile selector** (#36). `[institutional_email]` and the standalone page without `?form=` now show "Choose the profile" over the forms the settings offer, students first; with one form offered, that form alone. A bare shortcode used to show only the students' form: add `form="students"` to keep that. The network still comes from `network=` or the General tab.

## [1.0.0] (2026-10-11) — `b8e83efd`

The forms become a WordPress plugin, with a standalone page for iframes.

The forms were created in 2020 by the Educational Technology team of E.M.E.F. José Américo de Almeida (DRE/MP, SME/SP), with Ricardo Fukui ([@rfukui](https://github.com/rfukui)), Rafael Meusburger ([@meusburger](https://github.com/meusburger)), Thiago Guttierre ([@thiagoguttierre](https://github.com/thiagoguttierre)) and Alex Meusburger ([@rpgmem](https://github.com/rpgmem)), as the pages themselves credit them. The plugin is maintained by Alex Meusburger ([@rpgmem](https://github.com/rpgmem)); each 0.x version below names who wrote it, read from its commits' authors.

### Added

- **The forms as a WordPress plugin** (#14, #16). `[institutional_email]` places the students' form and `form="teachers"` the teachers'; the address is worked out in the visitor's browser and nothing typed is sent. Each field says what is wrong, the address and the password each have a Copy button, and a status line announces the result to screen readers.
- **A standalone page for iframes** (#16). Each release attaches a self-contained HTML file, and GitHub Pages serves the latest one at rpgmem.github.io/institutional-email (`?form=teachers` for the teachers' form).
- **Settings → Institutional Email** (#17). The forms offered and who may use them, the domain, how long a middle name must be to count, the initial password, the platform the result links to, and every text of the form, grouped by school network and by form (#26), with a Documentation tab explaining each.
- **Updates from GitHub releases** (#18), each package checked against its published SHA-256.
- **Settings → Design** (#30). Off by default, and then the form keeps its own look. Switched on, the form's width, the colours of the buttons, fields, hints, errors and result, the rounded corners and the font (the form's or the theme's) are set there; switching it off clears the customisation, so it starts from the defaults when switched on again.
- **Optional Google Analytics** (#29). Off by default, and then nothing of Google's is loaded. Switched on with a GA4 measurement ID, the pages with a form load Google's tag and record each address worked out with only the form and the network, never what was typed.
- **São Paulo state schools (SEDUC-SP)** (#13). Settings → General chooses the school network; `network="state"` on a shortcode, or `?network=state` on the standalone page, serves it on one page. State students give their RA (padded with zeros to 12 digits), its check digit and their date of birth, which is also the initial password; state teachers are pointed to the SED, where their address is created.
- **Interns of the city schools** (#24). The teachers' form offers them next to the two networks; they give their CPF and the address carries an "e" before its digits (`mariasilva.e52998224725`). Settings → General chooses which of the partner network, the direct network and interns the teachers' form offers (#25).
- **The RF's check digit is checked** (#13), with Free Form Certificate's rule, so a mistyped RF is refused instead of becoming an address that does not exist; a switch on the Address rules tab turns it off. RF and CPF are masked as they are typed, `000.000-0` and `000.000.000-00`.
- **Release packaging, CI and a testes deploy** (#15, #19). Every PR runs PHPUnit (also against a real WordPress), Vitest, PHPStan, PHPCS, ESLint and Stylelint; each tag publishes the zip, the standalone page and the notes; the HTML history is versioned 0.1.0–0.5.0.
- **A README in English with screenshots** (#20), keeping the 2020 credits and the list of schools that used the forms.

### Changed

- **Only the letters a to z reach the address** (#16). Accents are dropped as before, and apostrophes, hyphens, any kind of space and the invisible characters a paste can carry no longer leak into it or split a name ("D'Ávila" → `davila`; #27). Middle names of up to three letters are still left out of a student's address, now by a setting.

### Removed

- **The Heroku pages, jQuery, the CDN libraries and the hard-coded Google Analytics** (#16, #20). The forms load no third-party file and collect no usage data, unless an administrator switches Analytics on with the site's own ID (#29).

## [0.5.0] (2026-10-11) — `6b1ab33f`

The last version of the HTML pages, published so the download of the pre-plugin forms is complete. It is cut from `legacy/0.x`, not from the plugin's history.

The list of schools is Alex Meusburger's ([@rpgmem](https://github.com/rpgmem)), from 2020; the interns' option was written for this release.

### Added

- **Interns on the teachers' form** (#14). A third choice next to the two networks: the CPF with an "e" before it (`mariasilva.e52998224725`), and the labels name an intern. `?tipo=estagiario` opens the page on it, and `email-estagiarios.html` now leads there; it had been hosted as a separate page that was never committed and never produced an address.

### Changed

- **The README lists the schools that used the forms** (#14), as added after 0.4.0.

## [0.4.0] (2020-05-14) — `cbd1f51`

The last version of the HTML pages served on Heroku.

Code by Rafael Meusburger ([@meusburger](https://github.com/meusburger)), who closed #9 to #12; the README and the iframe instructions by Alex Meusburger ([@rpgmem](https://github.com/rpgmem)).

### Added

- **The teachers' form asks for the network** (#12). Direct network teachers type their 7-digit RF, partner network teachers their CPF; the address ends in whichever was given.
- **A footer on the forms** (#10).

### Changed

- **Short middle names leave a student's address** (#9). Only middle names of four letters or more give their initial; "da", "de", "dos" are left out, and the first and last names are kept whole.
- **The forms check their fields again before showing an address** (#11), and the copy buttons use clipboard.js.

## [0.3.0] (2020-05-13) — `41b8c1e`

The shared script and stylesheet and the Heroku pages by Rafael Meusburger ([@meusburger](https://github.com/meusburger)); the iframe instructions by Alex Meusburger ([@rpgmem](https://github.com/rpgmem)).

### Changed

- **The forms became two pages for an iframe,** `email-alunos.html` and `email-professores.html`, served from Heroku with a shared script and stylesheet. The README told schools to embed them with an `<iframe>` instead of copying the code, so a fix reached every site at once.

## [0.2.0] (2020-05-11) — `a648d9b`

By Alex Meusburger ([@rpgmem](https://github.com/rpgmem)). The teachers' page credits Ricardo Fukui ([@rfukui](https://github.com/rfukui)), Rafael Meusburger, Thiago Guttierre ([@thiagoguttierre](https://github.com/thiagoguttierre)) and Alex Meusburger.

### Added

- **A form for partner network teachers,** `index-professores.html`: full name and CPF, with the CPF's check digits validated, for an address ending in the CPF.

## [0.1.0] (2020-05-01) — `f7b4ca3`

By Alex Meusburger ([@rpgmem](https://github.com/rpgmem)), with the layout and the copy buttons by Rafael Meusburger ([@meusburger](https://github.com/meusburger)) (#2 to #6). The page credits the Educational Technology team of E.M.E.F. José Américo de Almeida, Ricardo Fukui ([@rfukui](https://github.com/rfukui)), Rafael Meusburger and Alex Meusburger.

### Added

- **The first form, for students,** one file, `index.html`: full name and date of birth for the address `firstname` + middle initials + `lastname.DDMMYYYY@edu.sme.prefeitura.sp.gov.br`, the initial password `12345678`, buttons to copy both, and a link to Google Classroom. Accents were removed with a character map, and every middle name gave its initial.

Versions 0.1.0 to 0.4.0 were never tagged at the time: their numbers, dates and commits were assigned afterwards from the repository's history (#14).
