=== Institutional Email ===
Contributors: rpgmem
Tags: email, school, students, teachers, shortcode
Requires at least: 6.8
Tested up to: 7.1.3
Stable tag: 1.0.0
Requires PHP: 8.3
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Forms, placed with a shortcode, that work out the institutional email address of RME/SP students and teachers.

== Description ==

Students and teachers of the São Paulo municipal schools (RME/SP) have an institutional email address made from their name and their date of birth, RF or CPF. This plugin's forms work it out in the visitor's browser, so nothing typed is sent to the site.

A standalone HTML version, for embedding in an iframe, is published next to the plugin on each GitHub release, and served at https://rpgmem.github.io/institutional-email/ (add `?form=teachers` for the teachers' form).

== Installation ==

1. Download `institutional-email-X.Y.Z.zip` from the latest GitHub release.
2. In WordPress, go to Plugins → Add New → Upload Plugin, choose the zip and activate it.

== Usage ==

Place a form on any page or post:

* `[institutional_email]` or `[institutional_email form="students"]` — students: full name and date of birth.
* `[institutional_email form="teachers"]` — teachers: full name and CPF (partner network) or RF (direct network); interns, on the same form, give their CPF.
* Add `network="state"` for the São Paulo state schools (SEDUC-SP): students give their RA, its check digit and their date of birth; teachers are pointed to the SED.

Settings → Institutional Email chooses the school network (city or state), the forms offered, who may use them, the domain, the initial password, the platform the result links to and every text the form shows. Its Documentation tab explains each option.

== Privacy ==

The address is worked out in the visitor's browser. The form sends nothing, sets no cookie and loads no third-party file; the plugin stores only its own settings, in one option deleted with the plugin.

== Screenshots ==

1. The students' form, with an address worked out from fictitious details.
2. The teachers' form, direct network, with the RF.
3. Errors marked on each field.
4. Settings, General tab: the shortcode, the forms offered and who may use them.
5. Settings, Address rules tab: domain, middle names that count, initial password.
6. Settings, Texts tab: the next-step platform and every text of the form.
7. Settings, Documentation tab.
8. The state students' form, with the RA and its check digit.
9. The state teachers' panel, linking to the SED.

== Changelog ==

The full history of every release is in [CHANGELOG.md](https://github.com/rpgmem/institutional-email/blob/main/CHANGELOG.md).

= 1.0.0 =
* New: the institutional email forms as a WordPress plugin, placed with the [institutional_email] shortcode (students) or [institutional_email form="teachers"].
* New: Settings → Institutional Email for the forms offered, access, domain, middle-name length, initial password, the next-step platform and every text of the form, with a Documentation tab.
* New: São Paulo state schools (SEDUC-SP), chosen in the settings or per page with network="state": state students' RA address and initial password; state teachers pointed to the SED.
* New: interns of the city schools on the teachers' form, with an "e" before the CPF digits.
* New: the RF's check digit is checked (switchable), and RF and CPF are masked as they are typed.
* New: updates from GitHub Releases, each package checked against its SHA-256.
* New: a standalone HTML page on every release, served on GitHub Pages for iframes.
* Changed: only the letters a to z of each name reach the address.
* Removed: the Heroku pages, jQuery, CDN libraries and Google Analytics.

== Upgrade Notice ==

= 1.0.0 =
First release as a WordPress plugin: the [institutional_email] shortcode for city and state schools, settings for forms, access, address rules and texts, and updates from GitHub. Details in CHANGELOG.md.
