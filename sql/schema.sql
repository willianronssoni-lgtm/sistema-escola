-- Biblioteca Escolar - Schema do banco de dados
-- Execute no MariaDB/MySQL: source /caminho/para/schema.sql

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP DATABASE IF EXISTS `biblioteca_escolar`;
CREATE DATABASE `biblioteca_escolar` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `biblioteca_escolar`;

-- 1. Usuários (alunos e bibliotecários)
CREATE TABLE `usuarios` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `matricula` VARCHAR(20) NOT NULL,
    `nome` VARCHAR(120) NOT NULL,
    `email` VARCHAR(150) DEFAULT NULL,
    `turma` VARCHAR(20) DEFAULT NULL,
    `senha_hash` VARCHAR(255) NOT NULL,
    `tipo` ENUM('aluno','bibliotecario') NOT NULL DEFAULT 'aluno',
    `ativo` TINYINT(1) NOT NULL DEFAULT 1,
    `suspenso_ate` DATE DEFAULT NULL,
    `criado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_matricula` (`matricula`),
    UNIQUE KEY `uk_email` (`email`),
    KEY `idx_tipo_ativo` (`tipo`, `ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Obras (títulos dos livros)
CREATE TABLE `obras` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `titulo` VARCHAR(200) NOT NULL,
    `autor` VARCHAR(150) NOT NULL,
    `isbn` VARCHAR(20) DEFAULT NULL,
    `editora` VARCHAR(100) DEFAULT NULL,
    `ano` YEAR DEFAULT NULL,
    `categoria` VARCHAR(80) DEFAULT NULL,
    `sinopse` TEXT DEFAULT NULL,
    `capa` VARCHAR(255) DEFAULT NULL,
    `criado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_titulo` (`titulo`),
    KEY `idx_autor` (`autor`),
    KEY `idx_categoria` (`categoria`),
    KEY `idx_isbn` (`isbn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Exemplares (cópias físicas de cada obra)
CREATE TABLE `exemplares` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `obra_id` INT UNSIGNED NOT NULL,
    `tombo` VARCHAR(30) NOT NULL,
    `status` ENUM('disponivel','emprestado','reservado','manutencao') NOT NULL DEFAULT 'disponivel',
    `localizacao` VARCHAR(80) DEFAULT NULL,
    `observacoes` TEXT DEFAULT NULL,
    `criado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_tombo` (`tombo`),
    KEY `idx_obra_status` (`obra_id`, `status`),
    CONSTRAINT `fk_exemplar_obra` FOREIGN KEY (`obra_id`) REFERENCES `obras` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Reservas (solicitações de empréstimo)
CREATE TABLE `reservas` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `usuario_id` INT UNSIGNED NOT NULL,
    `obra_id` INT UNSIGNED NOT NULL,
    `exemplar_id` INT UNSIGNED DEFAULT NULL,
    `status` ENUM('pendente','confirmada','na_fila','expirada','cancelada','atendida') NOT NULL DEFAULT 'pendente',
    `posicao_fila` INT UNSIGNED DEFAULT NULL,
    `data_reserva` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `data_confirmacao` TIMESTAMP NULL DEFAULT NULL,
    `data_expiracao` TIMESTAMP NULL DEFAULT NULL,
    `data_atendimento` TIMESTAMP NULL DEFAULT NULL,
    `observacoes` TEXT DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_usuario_status` (`usuario_id`, `status`),
    KEY `idx_obra_status` (`obra_id`, `status`),
    KEY `idx_exemplar` (`exemplar_id`),
    CONSTRAINT `fk_reserva_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_reserva_obra` FOREIGN KEY (`obra_id`) REFERENCES `obras` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_reserva_exemplar` FOREIGN KEY (`exemplar_id`) REFERENCES `exemplares` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Empréstimos (retiradas e devoluções)
CREATE TABLE `emprestimos` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `exemplar_id` INT UNSIGNED NOT NULL,
    `usuario_id` INT UNSIGNED NOT NULL,
    `bibliotecario_id` INT UNSIGNED NOT NULL,
    `data_retirada` DATE NOT NULL,
    `data_prevista_devolucao` DATE NOT NULL,
    `data_devolucao` DATE DEFAULT NULL,
    `renovado` TINYINT(1) NOT NULL DEFAULT 0,
    `dias_atraso` INT UNSIGNED NOT NULL DEFAULT 0,
    `suspensao_aplicada` TINYINT(1) NOT NULL DEFAULT 0,
    `observacoes` TEXT DEFAULT NULL,
    `criado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_exemplar` (`exemplar_id`),
    KEY `idx_usuario_ativo` (`usuario_id`, `data_devolucao`),
    KEY `idx_atrasados` (`data_prevista_devolucao`, `data_devolucao`),
    CONSTRAINT `fk_emprestimo_exemplar` FOREIGN KEY (`exemplar_id`) REFERENCES `exemplares` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_emprestimo_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_emprestimo_bibliotecario` FOREIGN KEY (`bibliotecario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Notificações
CREATE TABLE `notificacoes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `usuario_id` INT UNSIGNED NOT NULL,
    `tipo` ENUM('reserva_confirmada','livro_disponivel','devolucao_proxima','atrasado','suspensao','reserva_expirada','renovacao') NOT NULL,
    `titulo` VARCHAR(150) NOT NULL,
    `mensagem` TEXT NOT NULL,
    `lida` TINYINT(1) NOT NULL DEFAULT 0,
    `link` VARCHAR(255) DEFAULT NULL,
    `criada_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_usuario_lida` (`usuario_id`, `lida`),
    CONSTRAINT `fk_notificacao_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Logs de auditoria
CREATE TABLE `logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `usuario_id` INT UNSIGNED DEFAULT NULL,
    `acao` VARCHAR(80) NOT NULL,
    `entidade` VARCHAR(50) DEFAULT NULL,
    `entidade_id` INT UNSIGNED DEFAULT NULL,
    `detalhes` JSON DEFAULT NULL,
    `ip` VARCHAR(45) DEFAULT NULL,
    `user_agent` TEXT DEFAULT NULL,
    `criado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_usuario_acao` (`usuario_id`, `acao`),
    KEY `idx_entidade` (`entidade`, `entidade_id`),
    KEY `idx_criado_em` (`criado_em`),
    CONSTRAINT `fk_log_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- View: Disponibilidade por obra
CREATE OR REPLACE VIEW `vw_disponibilidade` AS
SELECT
    o.`id` AS `obra_id`,
    o.`titulo`,
    o.`autor`,
    COUNT(e.`id`) AS `total_exemplares`,
    SUM(e.`status` = 'disponivel') AS `disponiveis`,
    SUM(e.`status` = 'emprestado') AS `emprestados`,
    SUM(e.`status` = 'reservado') AS `reservados`,
    SUM(e.`status` = 'manutencao') AS `manutencao`
FROM `obras` o
LEFT JOIN `exemplares` e ON e.`obra_id` = o.`id`
GROUP BY o.`id`, o.`titulo`, o.`autor`;

-- View: Empréstimos ativos com detalhes
CREATE OR REPLACE VIEW `vw_emprestimos_ativos` AS
SELECT
    emp.`id`,
    emp.`data_retirada`,
    emp.`data_prevista_devolucao`,
    emp.`renovado`,
    emp.`dias_atraso`,
    u.`id` AS `usuario_id`,
    u.`matricula`,
    u.`nome` AS `usuario_nome`,
    u.`turma`,
    o.`id` AS `obra_id`,
    o.`titulo` AS `obra_titulo`,
    e.`tombo`,
    b.`nome` AS `bibliotecario_nome`
FROM `emprestimos` emp
JOIN `usuarios` u ON u.`id` = emp.`usuario_id`
JOIN `exemplares` e ON e.`id` = emp.`exemplar_id`
JOIN `obras` o ON o.`id` = e.`obra_id`
JOIN `usuarios` b ON b.`id` = emp.`bibliotecario_id`
WHERE emp.`data_devolucao` IS NULL;

-- View: Fila de espera por obra
CREATE OR REPLACE VIEW `vw_fila_espera` AS
SELECT
    r.`id` AS `reserva_id`,
    r.`obra_id`,
    o.`titulo` AS `obra_titulo`,
    r.`usuario_id`,
    u.`matricula`,
    u.`nome` AS `usuario_nome`,
    u.`turma`,
    r.`posicao_fila`,
    r.`data_reserva`
FROM `reservas` r
JOIN `usuarios` u ON u.`id` = r.`usuario_id`
JOIN `obras` o ON o.`id` = r.`obra_id`
WHERE r.`status` = 'na_fila'
ORDER BY r.`obra_id`, r.`posicao_fila`;

SET FOREIGN_KEY_CHECKS = 1;