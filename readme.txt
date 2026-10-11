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
* `[institutional_email form="teachers"]` — teachers: full name and CPF (partner network) or RF (direct network).

Settings → Institutional Email chooses the forms offered, who may use them, the domain, the initial password, the platform the result links to and every text the form shows. Its Documentation tab explains each option.

== Changelog ==

The full history of every release is in [CHANGELOG.md](https://github.com/rpgmem/institutional-email/blob/main/CHANGELOG.md).
