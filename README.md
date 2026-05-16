# 💻 Sistema de Controle de Chamados

Este repositório contém o trabalho prático acadêmico da disciplina de **Desenvolvimento Web em HTML5, CSS, JavaScript e PHP**. 

O objetivo do projeto é desenvolver um sistema web simples integrando frontend, backend e banco de dados, focado na autenticação de usuários e na gestão de chamados de suporte.

## 🛠️ Tecnologias Utilizadas

* **Frontend:** HTML5, CSS3, Bootstrap e JavaScript (Validações e Fetch API/AJAX).
* **Backend:** PHP puro (com controle de sessão e hash de senhas `password_hash`).
* **Banco de Dados:** PostgreSQL com comunicação via PDO.

## 🚀 Como subir o sistema localmente

Para rodar este projeto na sua máquina, você precisará de um servidor web local configurado para rodar PHP (como XAMPP, WAMP ou Laragon) e o PostgreSQL instalado.

### 1. Clonar o repositório
Abra o terminal ou prompt de comando na pasta pública do seu servidor web (ex: pasta `htdocs` no XAMPP ou `www` no WAMP) e execute o comando abaixo:

```bash
git clone [https://github.com/irs-zip/trabalho-desenvolvimento-web.git](https://github.com/irs-zip/trabalho-desenvolvimento-web.git)
```

### 2. Configurar o Banco de Dados
1. Abra o seu gerenciador do PostgreSQL (ex: pgAdmin ou DBeaver).
2. Crie um novo banco de dados.
3. Execute o script SQL contido no arquivo `sql/criacao_banco.sql` para criar as tabelas `usuarios` e `chamados`.

### 3. Configurar a Conexão
Abra o arquivo `php/conexao.php` no seu editor de código e altere as credenciais (nome do banco, usuário e senha) de acordo com o seu PostgreSQL local.

### 4. Acessar o sistema
Com o servidor Apache/PHP rodando, abra o seu navegador e acesse a URL:

```text
http://localhost/trabalho-desenvolvimento-web/index.html
```