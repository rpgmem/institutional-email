# Institutional Email

Forms that work out the institutional email address of students and teachers of the São Paulo Municipal Education Network (RME/SP), from their name and their date of birth, RF or CPF. The address is worked out in the visitor's browser: nothing typed is sent or stored anywhere.

It comes in two forms, both published on every [release](https://github.com/rpgmem/institutional-email/releases/latest):

- **WordPress plugin** (`institutional-email-X.Y.Z.zip`): place a form on any page with a shortcode. An administrator chooses the forms offered, who may use them, the address rules and every text the form shows. Updates arrive through the normal WordPress update screen.
- **Standalone HTML** (`institutional-email-X.Y.Z.html`): one self-contained file that needs no WordPress, for an `<iframe>` on any site. The latest one is served at **https://rpgmem.github.io/institutional-email/**.

**Requires** WordPress 6.8 or later and PHP 8.3 or later (plugin only) · **License** GPLv3 or later

![The students' form with a result](docs/screenshots/1-students.png)

## The rules

| Who | Asked for | Address |
|---|---|---|
| Student | full name, date of birth | first name + the initial of each middle name of 4 letters or more + last name, a dot, the date as DDMMYYYY |
| Teacher, direct network | full name, RF (7 digits) | first name + last name, a dot, the RF |
| Teacher, partner network | full name, CPF (validated) | first name + last name, a dot, the CPF digits |

All at `@edu.sme.prefeitura.sp.gov.br`, with the initial password `12345678`. Accents are dropped and only the letters a to z of each name reach the address. Examples: Maria Aparecida da Silva, born on 7 March 2012 → `mariaasilva.07032012@edu.sme.prefeitura.sp.gov.br`; João dos Santos Oliveira, RF 1234567 → `joaooliveira.1234567@edu.sme.prefeitura.sp.gov.br`.

## How to use it

**In WordPress**, put `[institutional_email]` on any page for the students' form, or `[institutional_email form="teachers"]` for the teachers'.

In **Settings → Institutional Email** an administrator sets, for every form on the site:

- **General:** the forms offered, and who may use them (everyone, or logged-in users only).
- **Address rules:** the domain, how long a middle name must be to give its initial, and the initial password.
- **Texts:** the platform the result links to (Google Classroom by default; empty hides the line), and any label, hint or message of the form.
- **Documentation:** what every option does, with examples.

**In an iframe**, without WordPress:

```html
<iframe src="https://rpgmem.github.io/institutional-email/" style="width:100%;height:640px;border:0" title="Institutional email"></iframe>
```

Add `?form=teachers` to the address for the teachers' form. The standalone page always uses the built-in rules and texts (in Portuguese). On a Google Site, use Insert → Embed → Embed code.

## Screenshots

| | |
|---|---|
| ![Teachers' form, direct network](docs/screenshots/2-teachers.png) | ![Errors marked on each field](docs/screenshots/3-errors.png) |
| ![Settings, General tab](docs/screenshots/4-general.png) | ![Settings, Address rules tab](docs/screenshots/5-rules.png) |
| ![Settings, Texts tab](docs/screenshots/6-texts.png) | ![Settings, Documentation tab](docs/screenshots/7-documentation.png) |

## Privacy

The address is computed by the script on the visitor's device. The form sends nothing, sets no cookie and loads no third-party file. The plugin stores only its own settings, in one option deleted with the plugin.

## Development

See [CLAUDE.md](CLAUDE.md) for the workflow (feature branches into `develop`, releases into `main`), the quality gates and the release steps, and [CHANGELOG.md](CHANGELOG.md) for the history, including the 2020 HTML versions (0.1.0–0.4.0).

## Credits

Created in 2020 by the Educational Technology team of [E.M.E.F. José Américo de Almeida](https://sites.google.com/view/emefjoseamericodealmeida/descubra-seu-e-mail-aluno) (DRE/MP, SME/SP), with Ricardo Fukui, Rafael Meusburger, Thiago Guttierre, Alex Meusburger and other GitHub contributors. Maintained by [Alex Meusburger](https://github.com/rpgmem).

### Who used the 2020 forms

Regional offices and schools that embedded the original forms on their pages or shared them:

**Regional office departments:**

<details markdown="1">
<summary>Expand</summary>

[DIPED - DRE/IP](https://dipedtpaip.wixsite.com/tecnologia/tutoriais/)  
[DIPED - DRE/MP](https://dipedmp.wixsite.com/meusite/descubra-em-login-no-classroom)  
</details>


**Schools, by Regional Education Office (DRE):**

<details markdown="1">
<summary>Expand</summary>

**Escolas da Diretoria Regional de Educação de Campo Limpo (DRE/CL)**  
[C.E.U. E.M.E.F. Paraisópolis](https://sites.google.com/edu.sme.prefeitura.sp.gov.br/ceuemefparaispolis/)  

**Escolas da Diretoria Regional de Educação de Guaianases (DRE/G)**  
[E.M.E.F. Pres. Juscelino Kubitschek De Oliveira](https://sites.google.com/view/emefjk/in%C3%ADcio)  
[E.M.E.F. Prof. Claudia Bartolomazi](https://sites.google.com/edu.sme.prefeitura.sp.gov.br/emefprofclaudiabartolomazi)  
[E.M.E.F. Saturnino Pereira](https://sites.google.com/view/emef-saturnino-pereira/)  

**Escolas da Diretoria Regional de Educação Ipiranga (DRE/IP)**  
[E.M.E.F. José Maria Lisboa](http://emeflisboa.blogspot.com/2020/04/saiba-qual-e-o-seu-email-para-acessar.html) 

**Escolas da Diretoria Regional de Educação de São Miguel Paulista (DRE/MP)**  
[E.M.E.F. Armando Cridey Righetti](https://sites.google.com/view/emefarmandocrideyrighetti/in%C3%ADcio)  
[E.M.E.F. Alm. Pedro de Frontin](https://sites.google.com/view/almirantepedrodefrontin/e-mail-do-aluno)  
[E.M.E.F. Arq. Luis Saia](https://arquitetoluisaia.blogspot.com/)  
[E.M.E.F. Chico Falconi](https://www.facebook.com/EMEF-Padre-Chico-Falconi-482489571772521)  
[E.M.E.F. Dr. José  Pedro Leite Cordeiro](https://sites.google.com/edu.sme.prefeitura.sp.gov.br/emefdrjosepedroleitecordeiro/p%C3%A1gina-inicial/descubra-seu-email-do-classroom)  
[E.M.E.F. Hélio Tavares](http://helliotavares.blogspot.com/)  
[E.M.E.F. Henrique Felipe da Costa](https://sites.google.com/view/henricao/menu/google-classroom/descubra-seu-e-mail)  
[E.M.E.F. José Américo de Almeida](https://sites.google.com/view/emefjoseamericodealmeida/descubra-seu-e-mail-aluno)  
[E.M.E.F. Marisa Moretti Câmara](https://emefmarisamoretti.wixsite.com/meusite)  
[E.M.E.F. Madre Maria Imilda do Santíssimo Sacramento](http://madreonline3.webnode.com/)  
E.M.E.F. Mururés  
[E.M.E.F. Prof Carlos Pasquale](https://sites.google.com/view/pasquale20)  
[E.M.E.F. Prof. José Bento de Assis](https://sites.google.com/view/emefjosebentoprof/descubra-o-seu-emailaluno)  
[E.M.E.F. Prof. Jurandi Gomes de Araújo](https://sites.google.com/view/emefjurandigomes/in%C3%ADcio)  
[E.M.E.F. Raimundo Correia](https://www.facebook.com/emefraimundocorreia/)  
E.M.E.F. Sud Mennucci  
E.M.E.F. Vicente Amato  
E.M.E.F.M. Darcy Ribeiro  

**Escolas da Diretoria Regional de Educação da Penha (DRE/PE)**  
C.E.U. E.M.E.F. Prof. Rosangela Rodrigues Vieira  
[E.M.E.F. Anália Franco Bastos](https://sites.google.com/view/emefanaliafrancobastos/p%C3%A1gina-inicial)  

**Escolas da Diretoria Regional de Educação de Pirituba/Jaragua (DRE/PJ)**  
[E.M.E.F. Prof. Luiz David Sobrinho](https://sites.google.com/escolaluizdavid.com/capa/e-mail-edu-prefeitura)  

**Escolas da Diretoria Regional de Educação de Santo Amaro (DRE/SA)**  
[C.E.U. E.M.E.F. Prof. Paulo Gonçalo dos Santos - Site 1](https://sites.google.com/edu.sme.prefeitura.sp.gov.br/emefceualvarenga/cria%C3%A7%C3%A3o-de-email-institucional-do-alunoa)  
[C.E.U. E.M.E.F. Prof. Paulo Gonçalo dos Santos - Site 2](https://emefceualvarenga.blogspot.com/)  

**Escolas da Diretoria Regional de Educação de São Mateus (DRE/SM)**  
[E.M.E.F. Emílio Ribas](https://conexaoemilio.wixsite.com/emilioribas/orientacoes-covid-19)  
[E.M.E.F. Luiz Gonzaga do Nascimento Jr. Gonzaguinha](https://tgcairo.wixsite.com/emefgonzaguinha/e-mail-google/)  
</details>
