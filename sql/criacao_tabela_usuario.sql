CREATE TABLE IF NOT EXISTS usuarios (
    id SERIAL,
    nome VARCHAR(100),
    email VARCHAR(150),
    telefone VARCHAR(15),
    cpf VARCHAR(11),
    senha VARCHAR(32),
    PRIMARY KEY (id),
    UNIQUE (email),
    UNIQUE (cpf)
);