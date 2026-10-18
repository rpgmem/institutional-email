=== Institutional Email ===
Contributors: rpgmem
Tags: email, school, students, teachers, shortcode
Requires at least: 6.8
Tested up to: 7.1.3
Stable tag: 1.4.0
Requires PHP: 8.3
License: AGPLv3 or later
License URI: https://www.gnu.org/licenses/agpl-3.0.html

Forms, placed with a shortcode, that work out the institutional email address of São Paulo city (RME/SP) and state (SEDUC-SP) students and teachers.

== Description ==

Students and teachers of the São Paulo municipal schools (RME/SP) have an institutional email address made from their name and their date of birth, RF or CPF; students of the São Paulo state schools (SEDUC-SP) have one made from their RA. This plugin's forms work it out in the visitor's browser, so nothing typed is sent to the site.

A standalone HTML version, for embedding in an iframe, is published next to the plugin on each GitHub release, and served at https://rpgmem.github.io/institutional-email/ (add `?form=students` or `?form=teachers` for one form alone, and `?network=state` for the state schools).

== Installation ==

1. Download `institutional-email-X.Y.Z.zip` from the latest GitHub release.
2. In WordPress, go to Plugins → Add New → Upload Plugin, choose the zip and activate it.

== Usage ==

Place a form on any page or post:

* `[institutional_email]` — a profile selector over the forms offered (students and teachers by default).
* `[institutional_email form="students"]` — students only: full name and date of birth.
* `[institutional_email form="teachers"]` — teachers only: full name and CPF (partner network) or RF (direct network); interns, on the same form, give their CPF.
* Add `network="state"` for the São Paulo state schools (SEDUC-SP): students give their RA, its check digit and their date of birth; teachers are pointed to the SED.

Settings → Institutional Email chooses the school network (city or state), the forms offered, who may use them, optional Google Analytics, the domain, the initial password, the platform the result links to, every text the form shows and, on the Design tab, the form's width, colours, corners and font, with a warning for colour pairs that are hard to read. A Dark Mode setting colours the settings screen and the form on the site; where Free Form Certificate is active, its Dark Mode decides instead. Its Documentation tab explains each option.

== Privacy ==

The address is worked out in the visitor's browser, and what is typed is never sent anywhere. By default the form sets no cookie and loads no third-party file; if an administrator switches on Google Analytics, the pages with a form load Google's tag, which sets its cookies, and record each address worked out with only the form and the network. The plugin stores only its own settings, in one option deleted with the plugin.

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
10. Settings, Design tab: the form's width, colours, corners and font.

== Changelog ==

The full history of every release is in [CHANGELOG.md](https://github.com/rpgmem/institutional-email/blob/main/CHANGELOG.md).

= 1.2.0 =
* New: an optional dark mode for the settings screen (Off, On, or Auto to follow the operating system), saved and applied at once.
* New: the Design tab warns about colour pairs below WCAG's 4.5:1 contrast.
* Changed: the settings screen takes Free Form Certificate's colours and icons, and the documentation gets its Quick Navigation, with search, in a column that stays in view.
* Fixed: a cleared number field keeps its default, and the Design tab's rows no longer flash when the design is off.

= 1.1.0 =
* Changed: a shortcode or standalone page that names no form opens with a profile selector for students and teachers; add form="students" to keep the students' form alone.

= 1.0.0 =
* New: the institutional email forms as a WordPress plugin, placed with the [institutional_email] shortcode (students) or [institutional_email form="teachers"].
* New: Settings → Institutional Email for the forms offered, access, optional Google Analytics, a Design tab for width, colours, corners and font, domain, middle-name length, initial password, the next-step platform and every text of the form, with a Documentation tab.
* New: São Paulo state schools (SEDUC-SP), chosen in the settings or per page with network="state": state students' RA address and initial password; state teachers pointed to the SED.
* New: interns of the city schools on the teachers' form, with an "e" before the CPF digits; the settings choose which of the partner network, the direct network and interns the form offers.
* New: the RF's check digit is checked (switchable), and RF and CPF are masked as they are typed.
* New: updates from GitHub Releases, each package checked against its SHA-256.
* New: a standalone HTML page on every release, served on GitHub Pages for iframes.
* Changed: only the letters a to z of each name reach the address.
* Removed: the Heroku pages, jQuery, CDN libraries and the hard-coded Google Analytics (now optional, with the site's own ID).

== Upgrade Notice ==

= 1.4.0 =
The form on the site now looks like Free Form Certificate's and follows Dark Mode. A plugin icon on Dashboard → Updates, and "View details" with the readme tabs, Portuguese and screenshots that open in place. Assets ship minified. Details in CHANGELOG.md.
