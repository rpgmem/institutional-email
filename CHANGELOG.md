# Changelog

All notable changes to **Institutional Email** are documented in this file.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

The forms were created in 2020 by the Educational Technology team of E.M.E.F. José Américo de Almeida (DRE/MP, SME/SP), with Ricardo Fukui, Rafael Meusburger ([@meusburger](https://github.com/meusburger)), Thiago Guttierre and Alex Meusburger ([@rpgmem](https://github.com/rpgmem)), as the pages themselves credit them. Each version below names who wrote it, read from the commits' authors.

## [Unreleased]

## [1.0.0] (2026-10-11)

The forms become a WordPress plugin, with a standalone page for iframes.

Built on the 2020 forms credited above; maintained by Alex Meusburger ([@rpgmem](https://github.com/rpgmem)).

### Added

- **The forms as a WordPress plugin** (#14, #16). `[institutional_email]` places the students' form and `form="teachers"` the teachers'; the address is worked out in the visitor's browser and nothing typed is sent. Each field says what is wrong, the address and the password each have a Copy button, and a status line announces the result to screen readers.
- **A standalone page for iframes** (#16). Each release attaches a self-contained HTML file, and GitHub Pages serves the latest one at rpgmem.github.io/institutional-email (`?form=teachers` for the teachers' form).
- **Settings → Institutional Email** (#17). The forms offered and who may use them, how wide the form grows, the domain, how long a middle name must be to count, the initial password, the platform the result links to, and every text of the form, grouped by school network and by form (#26), with a Documentation tab explaining each.
- **Updates from GitHub releases** (#18), each package checked against its published SHA-256.
- **São Paulo state schools (SEDUC-SP)** (#13). Settings → General chooses the school network; `network="state"` on a shortcode, or `?network=state` on the standalone page, serves it on one page. State students give their RA (padded with zeros to 12 digits), its check digit and their date of birth, which is also the initial password; state teachers are pointed to the SED, where their address is created.
- **Interns of the city schools** (#24). The teachers' form offers them next to the two networks; they give their CPF and the address carries an "e" before its digits (`mariasilva.e52998224725`). Settings → General chooses which of the partner network, the direct network and interns the teachers' form offers (#25).
- **The RF's check digit is checked** (#13), with Free Form Certificate's rule, so a mistyped RF is refused instead of becoming an address that does not exist; a switch on the Address rules tab turns it off. RF and CPF are masked as they are typed, `000.000-0` and `000.000.000-00`.
- **Release packaging, CI and a testes deploy** (#15, #19). Every PR runs PHPUnit (also against a real WordPress), Vitest, PHPStan, PHPCS, ESLint and Stylelint; each tag publishes the zip, the standalone page and the notes; the HTML history is versioned 0.1.0–0.5.0.
- **A README in English with screenshots** (#20), keeping the 2020 credits and the list of schools that used the forms.

### Changed

- **Only the letters a to z reach the address** (#16). Accents are dropped as before, and apostrophes, hyphens, any kind of space and the invisible characters a paste can carry no longer leak into it or split a name ("D'Ávila" → `davila`; #27). Middle names of up to three letters are still left out of a student's address, now by a setting.

### Removed

- **The Heroku pages, jQuery, the CDN libraries and Google Analytics** (#16, #20). The forms load no third-party file and collect no usage data.

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

By Alex Meusburger ([@rpgmem](https://github.com/rpgmem)). The teachers' page credits Ricardo Fukui, Rafael Meusburger, Thiago Guttierre and Alex Meusburger.

### Added

- **A form for partner network teachers,** `index-professores.html`: full name and CPF, with the CPF's check digits validated, for an address ending in the CPF.

## [0.1.0] (2020-05-01) — `f7b4ca3`

By Alex Meusburger ([@rpgmem](https://github.com/rpgmem)), with the layout and the copy buttons by Rafael Meusburger ([@meusburger](https://github.com/meusburger)) (#2 to #6). The page credits the Educational Technology team of E.M.E.F. José Américo de Almeida, Ricardo Fukui, Rafael Meusburger and Alex Meusburger.

### Added

- **The first form, for students,** one file, `index.html`: full name and date of birth for the address `firstname` + middle initials + `lastname.DDMMYYYY@edu.sme.prefeitura.sp.gov.br`, the initial password `12345678`, buttons to copy both, and a link to Google Classroom. Accents were removed with a character map, and every middle name gave its initial.

Versions 0.1.0 to 0.4.0 were never tagged at the time: their numbers, dates and commits were assigned afterwards from the repository's history (#14).
