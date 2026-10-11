# CLAUDE.md

Project conventions for Claude (Anthropic CLI / agent sessions) working on this repository. Sessions inherit nothing across cold starts, so the durable rules live here. The workflow mirrors `rpgmem/email-signature-generator`, `rpgmem/total-mail-queue` and `rpgmem/ffcertificate`. Where this file is silent, theirs are the reference: Email Signature Generator first, because it has the same shape.

## What this repository ships

**Institutional Email** works out the institutional email address of students and teachers of the São Paulo municipal schools (RME/SP). The address is computed from the name plus the date of birth, RF or CPF, and is not looked up anywhere. Every GitHub release attaches:
- **The WordPress plugin** (`institutional-email-X.Y.Z.zip`). A shortcode places a form, and an administrator configures it.
- **The standalone HTML** (`institutional-email-X.Y.Z.html`). One self-contained file that needs no WordPress, meant to be embedded with an `<iframe>`. GitHub Pages serves the latest release's copy at a stable URL.

They share one source. The rules are JavaScript driven by a configuration object: the plugin builds that object from its settings, and the standalone build embeds the defaults. Never fix a behaviour in only one of them.

The repository was a pair of pages served from Heroku from 2020. That history is versioned afterwards as 0.x (see "Releases").

## Language

Every versioned artifact is in **English**:
- code, comments and docblocks;
- i18n source strings, whose translations live in `languages/` (pt_BR first);
- `CHANGELOG.md` and commit messages;
- issue and PR titles and bodies.

The conversation with the maintainer is in Brazilian Portuguese, and that does not decide the language of anything committed. A PR body is composed in the flow of the conversation, so the discipline belongs at the moment of writing it.

Domain terms stay as they are where they are data: `RF` (Registro Funcional), `CPF`, the `@edu.sme.prefeitura.sp.gov.br` domain.

## Branches and pull requests

- **`main`** is production. It changes only through the release PR (`develop → main`) and through `hotfix/*` PRs.
- **`develop`** is the integration branch and the default base for every feature PR.
- Feature branches are `claude/<short-kebab-description>`, cut from `develop`. Always go through a PR, so the commit goes through CI.
- **Opening a PR:**
  - Open it as **draft**, flip it to ready, then enable **auto-merge (SQUASH)**.
  - When a PR completes a pre-agreed unit, flip it straight to ready.
  - When its scope emerged mid-session, confirm with the maintainer first.
- When `develop` moves under a PR, the PR is `behind`. Update the branch; don't wait it out.
- **Closing issues:** `Closes #NNN` does not auto-close an issue when the PR merges into `develop`, because the default branch is `main`. Close the issue by hand when the work lands, and tick its checkboxes or map each one to the PR that shipped it.
- **Refs:** an agent's credential can create and update refs but **cannot delete a ref** (HTTP 403). Deleting merged branches is the maintainer's job.
- Dependabot targets `develop`. A security update opens against `main`: retarget it to `develop`.

### Rulesets (set by the maintainer in Settings → Rules → Rulesets)

An agent cannot read or change these; the table is the intended configuration, so check the UI before relying on it.

| Ruleset | Rules | Bypass |
| --- | --- | --- |
| `develop-locked` | Restrict deletions | none |
| `develop-ci-gates` | Require a pull request (0 approvals) · Require status checks · branches up to date | Repository admin |
| `main-locked` | Restrict deletions · Block force pushes | none |
| `main-ci-gates` | Require a pull request (0 approvals) · Require status checks · branches up to date | Repository admin |

**Required status checks:** every job in `tests.yml` and `lint.yml`, by job name.

A required check must run on every PR to that branch: no `paths:` filter, and no job-level `if:` that can skip it. Otherwise every PR waits forever on a check that never reports.

Force push stays allowed on `develop`, because the post-release sync is a reset.

## Versioning and CHANGELOG

- **The version** lives in three places, all updated in the same commit:
  - the `Version:` header in `institutional-email.php`;
  - `INSTITUTIONAL_EMAIL_VERSION`, defined just below it in the same file. `Plugin::VERSION` reads it, and the unit bootstrap reads it from the file's text;
  - `Stable tag` in `readme.txt`.
- **The floors** (PHP 8.3, WordPress 6.8) live in the plugin header, `readme.txt`, `Plugin::REQUIRES_PHP` / `REQUIRES_WP` and `phpcs.xml.dist`. `composer.json` also declares the PHP floor.
- **`PluginIdentityTest`** fails when any of those disagree.
- **Feature PRs never bump the version.**
  - Their CHANGELOG entry goes under `## [Unreleased]`.
  - Entries are grouped by `Added` / `Changed` / `Fixed` / `Removed` / `Security` / `Deprecated`, and no other heading.
  - Append the bullet at the end of its heading.
- **A bullet** is:
  - a bold lead sentence;
  - the PR or issue reference;
  - then one or two short sentences, aiming for 300 characters.

  A released heading reads `` ## [X.Y.Z] (YYYY-MM-DD) — `<short-sha>` ``, the sha being the release commit on `main`.

## Releases

1. **On `develop`,** through a dedicated `release: X.Y.Z` PR:
   - bump the three version sites;
   - rename `[Unreleased]` to `## [X.Y.Z] (YYYY-MM-DD)`;
   - add a fresh `[Unreleased]` above it.
2. **Open the `develop → main` PR.** Keep it draft until the maintainer confirms the production release.
3. **After it lands on `main`,** tag that commit (`vX.Y.Z`) and push the tag. `.github/workflows/release.yml` then:
   - checks the tag against the header version;
   - builds the zip through `bin/build-release-zip.sh` and `.distignore`;
   - builds the standalone HTML from the tag's own files (`bin/build-standalone.php`) and attaches it beside the zip;
   - publishes a GitHub Release whose notes are that CHANGELOG section, marked as the latest. The plugin's updater reads `releases/latest`;
   - calls `.github/workflows/pages.yml`, which publishes that release's HTML on GitHub Pages.

   The Lint workflow runs the same build against the newest tag on every PR, so a broken `.distignore` or CHANGELOG section fails there first.

   **Pushing the tag publishes a production release, so an agent hands that command to the maintainer and does not run it.** Verify `HEAD` equals `origin/main` before tagging.
4. **Sync `develop` with `main`:**
   - after a squash merge, reset it (`git reset --hard origin/main`, then a force-with-lease push);
   - after a merge commit, fast-forward it.
5. **Backfill the sha** through a normal PR to `develop`: append `` — `<short-sha>` `` to the new heading. This must come after step 4, which would discard it.

**The 0.x tags are history, not releases of the plugin.** 0.1.0 to 0.4.0 were the HTML pages of 2020, versioned afterwards on the commits their CHANGELOG headings name. Pushing them starts no workflow, because those commits have none. To give one a GitHub Release, run `release.yml` by hand (`workflow_dispatch`, the tag as input) once the workflow is on `main`. Its release is those pages as `institutional-email-X.Y.Z-pages.zip`, no plugin zip, and it is never marked latest. `bin/build-release-zip.sh` tells such a tag apart by the missing plugin file.

The zip ships no Composer or npm dependency. The autoloader is inline in the main plugin file. Anything new at the repository root that should not ship goes into `.distignore`.

## Deploy to testes

`.github/workflows/deploy-develop.yml` runs on every push to `develop`, and by hand through `workflow_dispatch`. It rsyncs the tree to the testes site, the same host the other plugins deploy to, and then runs a smoke check there.

- **What lands is what a release would ship.** The rsync excludes `.distignore`, and `--delete` keeps the directory an exact copy.
- **Secrets,** set in this repository:
  - `TESTES_SSH_HOST`, `TESTES_SSH_USER`, `TESTES_SSH_KEY` (no passphrase) and the optional `TESTES_SSH_PORT` carry the same values as in the other repositories;
  - `TESTES_REMOTE_PATH` is this plugin's own folder: `…/wp-content/plugins/institutional-email`, absolute, no trailing slash.
- **The path is checked before anything is sent.** `--delete` empties whatever directory it is pointed at, so the workflow refuses a path that is not absolute or does not end in `/wp-content/plugins/institutional-email`.
- **The smoke** (`.github/scripts/testes-smoke.php`) boots the real WordPress. It checks three things: the plugin is active, `Plugin::VERSION` on the host equals the deployed commit, and the shortcode is registered. A boot that stops early fails; a timeout only warns.
- **It is an alarm, not a gate:** it runs after the merge. Anything that must block a merge belongs in CI.

## Quality gates

These run in CI on every PR and on pushes to `main` and `develop`:
- PHPUnit `unit` suite on PHP 8.3 and 8.4, and the functional suite against a real WordPress and MySQL (`phpunit.functional.xml.dist`).
- PHPStan level 8, and PHPCS with WPCS and PHPCompatibilityWP.
- ESLint over `assets/js` and Stylelint over `assets/css`.
- Vitest over `tests/js`.
- `composer validate --strict` and `composer audit --locked`.
- The release build against the newest tag.

Locally:

```bash
composer install && npm ci
vendor/bin/phpunit --testsuite unit
vendor/bin/phpstan analyse --no-progress
vendor/bin/phpcs
npm run lint
npm test
```

## The standalone page and GitHub Pages

- `php bin/build-standalone.php <version> <out-file> [locale]` writes one self-contained HTML file: the stylesheet and the script are inlined, and the configuration is `Config::defaults()` translated from `languages/` (pt_BR by default). Nothing is fetched.
- It shows the students' form; `?form=teachers` in the address shows the teachers'. The plugin's shortcode ignores the address and reads its `form` attribute.
- With `-` as the out file it prints the configuration as JSON. The Vitest suite reads it that way, so the JS tests run against the real strings, never a copy.
- **Pages serves the release asset, not a build from a branch,** so the page is always the file the release offers for download. `pages.yml` runs from `release.yml` after a tag push (a release created with the workflow token fires no `release` event) and by hand through `workflow_dispatch`. Pages must be enabled with **GitHub Actions** as its source.
- A historical release published by hand never replaces the page.

## Updates

`Update\GithubUpdater` (ported from Email Signature Generator) feeds WordPress's own update check from `releases/latest`:

- **The asset.** It picks the `institutional-email-*.zip` asset and never the standalone `.html` beside it.
- **The checksum.** It verifies the asset's SHA-256 digest before WordPress installs it.
- **The release's own metadata.** `Requires at least`, `Tested up to`, `Requires PHP` and the Upgrade Notice come from the offered release's own `readme.txt`.
- **Caching.** It caches for 12 hours in the `institutional_email_github_update` site transient, with ETag requests. `uninstall.php` deletes it.
- **Registration.** It is registered outside `is_admin()`, because WordPress also scans from cron.
- **The `releases/latest` consequence.** A release published by hand for an old tag must never be marked latest, which is why `release.yml` sets `make_latest: false` for `workflow_dispatch`.

## Translations

`languages/` holds the `.pot`, and per locale the `.po`, `.mo` and `.l10n.php`. After changing a translatable string, run `bin/i18n.sh` (needs GNU gettext): it re-extracts the `.pot`, merges it into each `.po` and recompiles. Translate the new entries in the `.po`, then run it again.

## Code

- Namespace `InstitutionalEmail`; no global functions. A class maps to a kebab-case file under `src/`, and `AutoloaderTest` fails when one does not.
- Escape on output; check a nonce and a capability on every request entry point; sanitize every setting on save.
- **Every option the plugin writes is deleted in `uninstall.php`**, in the same change that adds it.
- **The address is worked out in the browser**, and the plugin never receives a name, a date of birth, an RF or a CPF. Keep it that way: it is why the plugin stores no personal data.
