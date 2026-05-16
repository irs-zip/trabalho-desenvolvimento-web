# 💻 Sistema Web de Integração - Controle de Chamados

Este repositório contém o projeto prático da disciplina de **Desenvolvimento Web em HTML5, CSS, JavaScript e PHP**. O objetivo do sistema é integrar frontend, backend e banco de dados, permitindo o cadastro de usuários, autenticação e a gestão de chamados de suporte.

trabalho-desenvolvimento-web/
│
├── css/                   
│   └── style.css          # Arquivo para o CSS personalizado de vocês
│
├── js/                    
│   ├── validacoes.js      # JS apenas para as validações de tela (CPF, E-mail)
│   └── ajax.js            # JS dedicado apenas para as requisições ao banco
│
├── php/                   
│   ├── conexao.php        # Arquivo focado apenas na conexão PDO com o PostgreSQL
│   ├── login_action.php   # Arquivo que recebe o POST do login
│   ├── cadastro_action.php# Arquivo que recebe o POST do cadastro
│   └── chamados_action.php# Arquivo que vai lidar com o CRUD de chamados
│
├── sql/                   
│   └── criacao_banco.sql  # Aqui vai o script CREATE TABLE do banco
│
├── docs/                  
│   └── .gitkeep           # Pasta para guardar prints, o DER e o Word final
│
├── index.html             # Tela inicial (Escolha Login/Cadastro)
├── login.html             # Tela do formulário de Login
├── cadastro.html          # Tela do formulário de Cadastro
├── painel.php             # Área logada do usuário (aqui tem que ser .php por causa da sessão)
└── README.md              # Aquele arquivo de apresentação que criamos