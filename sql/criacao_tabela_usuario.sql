CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT,
    nome VARCHAR(100),
    email VARCHAR(150),
    telefone VARCHAR(15),
    cpf VARCHAR(11),
    senha VARCHAR(32),
    PRIMARY KEY (id),
    UNIQUE KEY uq_email (email),
    UNIQUE KEY uq_cpf (cpf)
);