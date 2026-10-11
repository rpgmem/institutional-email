# Changelog

All notable changes to **Institutional Email** are documented in this file.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

## [1.0.0] (2026-10-11)

The forms become a WordPress plugin, with a standalone page for iframes.

### Added

- **The forms as a WordPress plugin** (#14, #16). `[institutional_email]` places the students' form and `form="teachers"` the teachers'; the address is worked out in the visitor's browser and nothing typed is sent. Each field says what is wrong, the address and the password each have a Copy button, and a status line announces the result to screen readers.
- **A standalone page for iframes** (#16). Each release attaches a self-contained HTML file, and GitHub Pages serves the latest one at rpgmem.github.io/institutional-email (`?form=teachers` for the teachers' form).
- **Settings → Institutional Email** (#17). The forms offered and who may use them, the domain, how long a middle name must be to count, the initial password, the platform the result links to, and every text of the form, with a Documentation tab explaining each.
- **Updates from GitHub releases** (#18), each package checked against its published SHA-256.
- **São Paulo state schools (SEDUC-SP)** (#13). Settings → General chooses the school network; `network="state"` on a shortcode, or `?network=state` on the standalone page, serves it on one page. State students give their RA (padded with zeros to 12 digits), its check digit and their date of birth, which is also the initial password; state teachers are pointed to the SED, where their address is created.
- **Interns of the city schools** (#24). The teachers' form offers them next to the two networks; they give their CPF and the address carries an "e" before its digits (`mariasilva.e52998224725`). Settings → General chooses which of the partner network, the direct network and interns the teachers' form offers (#25).
- **The RF's check digit is checked** (#13), with Free Form Certificate's rule, so a mistyped RF is refused instead of becoming an address that does not exist; a switch on the Address rules tab turns it off. RF and CPF are masked as they are typed, `000.000-0` and `000.000.000-00`.
- **Release packaging, CI and a testes deploy** (#15, #19). Every PR runs PHPUnit (also against a real WordPress), Vitest, PHPStan, PHPCS, ESLint and Stylelint; each tag publishes the zip, the standalone page and the notes; the 2020 HTML history is versioned 0.1.0–0.4.0.
- **A README in English with screenshots** (#20), keeping the 2020 credits and the list of schools that used the forms.

### Changed

- **Only the letters a to z reach the address** (#16). Accents are dropped as before, and apostrophes, hyphens and repeated spaces no longer leak into it ("D'Ávila" → `davila`). Middle names of up to three letters are still left out of a student's address, now by a setting.

### Removed

- **The Heroku pages, jQuery, the CDN libraries and Google Analytics** (#16, #20). The forms load no third-party file and collect no usage data.

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
