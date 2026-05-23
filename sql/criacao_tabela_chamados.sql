CREATE TABLE IF NOT EXISTS chamados (
    id INT AUTO_INCREMENT,
    user_id INT,
    titulo VARCHAR(155),
    descricao VARCHAR(155),
    departamento VARCHAR(50),
    responsavel VARCHAR(100),
    regiao VARCHAR(60),
    status VARCHAR(15),
    data TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_chamado_usuario FOREIGN KEY (user_id) REFERENCES usuarios (id)
);