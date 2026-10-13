=== Institutional Email ===

Formulários, inseridos por shortcode, que descobrem o e-mail institucional de alunos e professores da rede municipal (RME/SP) e da rede estadual (SEDUC-SP) de São Paulo.

== Description ==

Alunos e professores das escolas municipais de São Paulo (RME/SP) têm um e-mail institucional formado pelo nome e pela data de nascimento, RF ou CPF; alunos das escolas estaduais de São Paulo (SEDUC-SP) têm um formado pelo RA. Os formulários deste plugin descobrem o endereço no navegador do visitante, então nada do que é digitado é enviado ao site.

Uma versão em HTML avulsa, para incorporar em um iframe, é publicada junto do plugin em cada release no GitHub, e fica em https://rpgmem.github.io/institutional-email/ (acrescente `?form=students` ou `?form=teachers` para um único formulário, e `?network=state` para as escolas estaduais).

== Installation ==

1. Baixe `institutional-email-X.Y.Z.zip` da release mais recente no GitHub.
2. No WordPress, vá em Plugins → Adicionar novo → Enviar plugin, escolha o zip e ative-o.

== Uso ==

Coloque um formulário em qualquer página ou post:

* `[institutional_email]` — um seletor de perfil sobre os formulários oferecidos (alunos e professores por padrão).
* `[institutional_email form="students"]` — só alunos: nome completo e data de nascimento.
* `[institutional_email form="teachers"]` — só professores: nome completo e CPF (rede parceira) ou RF (rede direta); estagiários, no mesmo formulário, informam o CPF.
* Acrescente `network="state"` para as escolas estaduais de São Paulo (SEDUC-SP): os alunos informam o RA, seu dígito verificador e a data de nascimento; os professores são encaminhados à SED.

Configurações → E-mail Institucional escolhe a rede de ensino (municipal ou estadual), os formulários oferecidos, quem pode usá-los, o Google Analytics opcional, o domínio, a senha inicial, a plataforma para onde o resultado aponta, cada texto que o formulário mostra e, na aba Design, a largura, as cores, os cantos e a fonte do formulário, com um aviso para pares de cores difíceis de ler. Uma configuração de Modo Escuro colore a própria tela de configurações. A aba Documentação explica cada opção.

== Privacidade ==

O endereço é descoberto no navegador do visitante, e o que é digitado nunca é enviado a lugar nenhum. Por padrão, o formulário não grava cookies nem carrega arquivos de terceiros; se um administrador ligar o Google Analytics, as páginas com um formulário carregam a tag do Google, que grava os cookies dele, e registram cada endereço descoberto apenas com o formulário e a rede. O plugin guarda só as próprias configurações, em uma opção apagada junto com o plugin.

== Screenshots ==

1. O formulário de alunos, com um endereço descoberto a partir de dados fictícios.
2. O formulário de professores, rede direta, com o RF.
3. Erros marcados em cada campo.
4. Configurações, aba Geral: o shortcode, os formulários oferecidos e quem pode usá-los.
5. Configurações, aba Regras do endereço: domínio, nomes do meio que contam, senha inicial.
6. Configurações, aba Textos: a plataforma do próximo passo e cada texto do formulário.
7. Configurações, aba Documentação.
8. O formulário de alunos da rede estadual, com o RA e seu dígito verificador.
9. O painel de professores da rede estadual, com o link para a SED.
10. Configurações, aba Design: a largura, as cores, os cantos e a fonte do formulário.
