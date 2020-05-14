**Desenvolvido pela equipe de Informática Educativa da [E.M.E.F. José Américo de Almeida - DRE/MP - SME/SP](https://sites.google.com/view/emefjoseamericodealmeida/descubra-seu-e-mail-aluno), com a colaboração de Ricardo Fukui, Rafael Meusburger, Thiago Guttierre, Alex Meusburger e demais usuários do GitHub**

Este projeto visa a implementação de formulário simples e acessível (inclusive a leitores de tela) para conversão de e-mails institucionais da Rede Municipal de Educação de São Paulo (RME/SP) nos cenários:

- De alunos, com base em seus nomes completos e datas de nascimento;  
- De professores (servidores), com base em seus nomes completos e Registro Funcional;  
- De professores (rede conveniada), com base em seus nomes completos e CPF;  

**Avisos importantes:**
- O formulário tem código aberto e colaborativo, seu uso é irrestrito desde que seja para fins não-comerciais;  
- Não armazenamos quaisquer informações pessoais, digitadas neste formulário pelo usuário;  
- Dados estatísticos de uso e comportamento podem vir a ser coletados com o único fim de melhoria da experiência do usuário;  
- Como está agora (iframe) o formulário nos permite atualizar o código a qualquer tempo (bugs e implementações) sem que os usuários finais, que implementarem em seus sites, precisem se preocupar com qualquer questão adicional.  
- Ao usar o código abaixo os usuários finais concordam com estes termos acima de maneira irrestrita e se comprometem a manter sua integridade;  

---

**Para ver funcionando o formulário de** ***ALUNOS,*** **acesse:**  
[ex. Formulário de Alunos](https://institutional-email.herokuapp.com/email-alunos.php)

**Para implementar o formulário de** ***ALUNOS*** **em um site próprio (da escola, por exemplo):**  

**1-** Copie TODO o código HTML abaixo;  
```html
<iframe style="height:100%;width:100%;border:0;" src="https://institutional-email.herokuapp.com/email-alunos.php"></iframe>
```
---
**Para ver funcionando o formulário de** ***PROFESSORES,*** **acesse:**  
[ex. Formulário de Professores](https://institutional-email.herokuapp.com/email-professores.php)

**Para implementar o formulário de** ***PROFESSORES*** **em um site próprio (da escola, por exemplo):**  

**1-** Copie TODO o código HTML abaixo;  
```html
<iframe style="height:100%;width:100%;border:0;" src="https://institutional-email.herokuapp.com/email-professores.php"></iframe>
```
---

**Caso, onde irá incorporar o código, seja um "Google Site":**  
**2-** Abra o gerenciamento do Google Site e edite a página onde quer inserir o código;  
**3-** No meu lateral direito (Inserir) localize o elemento "Incorporar" e clique sobre ele;  
**4-** Na caixa que surgir, mude para "Incorporar Código";  
**5-** Cole todo o código HTML copiado (em uma das opções "passo 1" acima) na caixa e clique em "Inserir";  

---

**Caso, onde irá incorporar o código, seja qualquer outra ferramenta de publicação:**  
Verifique termos como "incorporar", "código html", "código fonte" entre outros;  
Faça testes de incorporação adicionando código nas páginas que quer.  

**Atenção:** Ferramentas gratuitas em geral tem limitações a implementação de códigos de terceiros (que não sejam nativos da ferramenta). Em muitos casos essa opção sequer existe, impossibilitando a inclusão.

---
**Setores de DREs que implementaram o código em suas páginas:**

<details markdown="1">
<summary>Clique para expandir</summary>

[DIPED - DRE/IP](https://dipedtpaip.wixsite.com/tecnologia/tutoriais/)
</details>

---
**Escolas que implementaram o código em suas páginas ou o compartilharam (por DRE):**

<details markdown="1">
<summary>Clique para expandir</summary>

**Escolas da Diretoria Regional de Educação de Campo Limpo (DRE/CL)**  
[C.E.U. E.M.E.F. Paraisópolis](https://sites.google.com/edu.sme.prefeitura.sp.gov.br/ceuemefparaispolis/)  

**Escolas da Diretoria Regional de Educação de Guaianases (DRE/G)**  
[E.M.E.F. Pres. Juscelino Kubitschek De Oliveira](https://sites.google.com/view/emefjk/in%C3%ADcio)  
[E.M.E.F. Prof. Claudia Bartolomazi](https://sites.google.com/edu.sme.prefeitura.sp.gov.br/emefprofclaudiabartolomazi)  
[E.M.E.F. Saturnino Pereira](https://sites.google.com/view/emef-saturnino-pereira/)  

**Escolas da Diretoria Regional de Educação de São Miguel Paulista (DRE/MP)**  
[E.M.E.F. José Américo de Almeida](https://sites.google.com/view/emefjoseamericodealmeida/descubra-seu-e-mail-aluno)  
[E.M.E.F. Prof. Jurandi Gomes de Araújo](https://sites.google.com/view/emefjurandigomes/in%C3%ADcio)  
[E.M.E.F. Raimundo Correia](https://www.facebook.com/emefraimundocorreia/)  

**Escolas da Diretoria Regional de Educação da Penha (DRE/PE)**  
[E.M.E.F. Anália Franco Bastos](https://sites.google.com/view/>emefanaliafrancobastos/p%C3%A1gina-inicial)

**Escolas da Diretoria Regional de Educação de Pirituba/Jaragua (DRE/PJ)**  
[E.M.E.F. Prof. Luiz David Sobrinho](https://sites.google.com/escolaluizdavid.com/capa/e-mail-edu-prefeitura)  

[E.M.E.F. Gonzaguinha - DRE/?](https://tgcairo.wixsite.com/emefgonzaguinha/e-mail-google/)  
</details>