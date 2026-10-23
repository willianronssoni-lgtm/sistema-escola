-- Dados de exemplo para a Biblioteca Escolar
-- Execute após o schema.sql: source /caminho/para/seed.sql

USE `biblioteca_escolar`;

-- Limpar dados existentes (manter estrutura)
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `logs`;
TRUNCATE TABLE `notificacoes`;
TRUNCATE TABLE `emprestimos`;
TRUNCATE TABLE `reservas`;
TRUNCATE TABLE `exemplares`;
TRUNCATE TABLE `obras`;
TRUNCATE TABLE `usuarios`;
SET FOREIGN_KEY_CHECKS = 1;

-- Senha padrão para todos: "123456" (hash gerado com password_hash)
-- $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi = "123456"

-- 1. Usuários: 1 bibliotecário + 15 alunos
INSERT INTO `usuarios` (`matricula`, `nome`, `email`, `turma`, `senha_hash`, `tipo`, `ativo`) VALUES
('BIB001', 'Maria Silva Santos', 'maria.bibliotecaria@escola.br', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'bibliotecario', 1),
('2024001', 'João Pedro Alves', 'joao.alves@aluno.escola.br', '6º A', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'aluno', 1),
('2024002', 'Ana Clara Ferreira', 'ana.ferreira@aluno.escola.br', '6º A', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'aluno', 1),
('2024003', 'Lucas Gabriel Souza', 'lucas.souza@aluno.escola.br', '6º B', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'aluno', 1),
('2024004', 'Beatriz Oliveira Costa', 'beatriz.costa@aluno.escola.br', '6º B', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'aluno', 1),
('2024005', 'Rafael Henrique Lima', 'rafael.lima@aluno.escola.br', '7º A', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'aluno', 1),
('2024006', 'Mariana Santos Rocha', 'mariana.rocha@aluno.escola.br', '7º A', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'aluno', 1),
('2024007', 'Gustavo Dias Martins', 'gustavo.martins@aluno.escola.br', '7º B', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'aluno', 1),
('2024008', 'Isabela Cardoso Pereira', 'isabela.pereira@aluno.escola.br', '7º B', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'aluno', 1),
('2024009', 'Pedro Henrique Ribeiro', 'pedro.ribeiro@aluno.escola.br', '8º A', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'aluno', 1),
('2024010', 'Laura Mendes Carvalho', 'laura.carvalho@aluno.escola.br', '8º A', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'aluno', 1),
('2024011', 'Enzo Gabriel Nunes', 'enzo.nunes@aluno.escola.br', '8º B', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'aluno', 1),
('2024012', 'Sophia Alves Monteiro', 'sophia.monteiro@aluno.escola.br', '8º B', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'aluno', 1),
('2024013', 'Arthur Souza Barbosa', 'arthur.barbosa@aluno.escola.br', '9º A', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'aluno', 1),
('2024014', 'Valentina Lima Correia', 'valentina.correia@aluno.escola.br', '9º A', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'aluno', 1),
('2024015', 'Matheus Rodrigues Azevedo', 'matheus.azevedo@aluno.escola.br', '9º B', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'aluno', 1);

-- 2. Obras: 25 livros variados
INSERT INTO `obras` (`titulo`, `autor`, `isbn`, `editora`, `ano`, `categoria`, `sinopse`, `capa`) VALUES
('Dom Casmurro', 'Machado de Assis', '9788535902778', 'Companhia das Letras', 1999, 'Literatura Brasileira', 'Romance clássico brasileiro narrado em primeira pessoa por Bentinho, que narra sua vida e seus ciúmes de Capitu.', NULL),
('O Pequeno Príncipe', 'Antoine de Saint-Exupéry', '9788595081846', 'Agir', 2017, 'Infantojuvenil', 'Um piloto perdido no deserto encontra um pequeno príncipe que vem de outro planeta e lhe ensina sobre o essencial da vida.', NULL),
('Harry Potter e a Pedra Filosofal', 'J.K. Rowling', '9788532511010', 'Rocco', 2000, 'Fantasia', 'Harry descobre que é bruxo no seu 11º aniversário e vai estudar na Escola de Magia de Hogwarts.', NULL),
('O Senhor dos Anéis: A Sociedade do Anel', 'J.R.R. Tolkien', '9788533613379', 'Martins Fontes', 2001, 'Fantasia', 'Frodo Bolseiro herda um anel mágico e deve destruí-lo antes que caia nas mãos do Senhor do Escuro.', NULL),
('A Menina que Roubava Livros', 'Markus Zusak', '9788598078348', 'Intrínseca', 2007, 'Ficção Histórica', 'Na Alemanha nazista, uma menina encontra conforto roubando livros e compartilhando-os com outros.', NULL),
('Cem Anos de Solidão', 'Gabriel García Márquez', '9788535914849', 'Companhia das Letras', 2007, 'Realismo Mágico', 'A saga da família Buendía na fictícia Macondo, misturando realidade e fantasia.', NULL),
('O Alquimista', 'Paulo Coelho', '9788576655271', 'Planeta', 2017, 'Ficção', 'Um pastor andaluz segue seu destino em busca de um tesouro nas pirâmides do Egito.', NULL),
('1984', 'George Orwell', '9788535914849', 'Companhia das Letras', 2009, 'Distopia', 'Em um estado totalitário, Winston Smith tenta manter sua individualidade sob vigilância constante.', NULL),
('O Diário de Anne Frank', 'Anne Frank', '9788535903768', 'Companhia das Letras', 1995, 'Biografia', 'O diário de uma menina judia escondida com sua família durante a ocupação nazista em Amsterdã.', NULL),
('As Crônicas de Nárnia: O Leão, a Feiticeira e o Guarda-Roupa', 'C.S. Lewis', '9788595081846', 'Martins Fontes', 2005, 'Fantasia', 'Quatro irmãos entram no mundo mágico de Nárnia através de um guarda-roupa.', NULL),
('O Hobbit', 'J.R.R. Tolkien', '9788533613379', 'Martins Fontes', 2012, 'Fantasia', 'Bilbo Bolseiro parte em uma aventura inesperada com anões e um mago para recuperar um tesouro.', NULL),
('Dom Quixote', 'Miguel de Cervantes', '9788535914849', 'Companhia das Letras', 2005, 'Clássico', 'Um fidalgo espanhol enlouquece lendo romances de cavalaria e sai pelo mundo como cavaleiro andante.', NULL),
('Orgulho e Preconceito', 'Jane Austen', '9788535914849', 'Companhia das Letras', 2009, 'Romance', 'Elizabeth Bennet navega pelas convenções sociais e pelo amor na Inglaterra do século XIX.', NULL),
('A Revolução dos Bichos', 'George Orwell', '9788535914849', 'Companhia das Letras', 2007, 'Fábula Política', 'Animais de uma fazenda se revoltam contra o fazendeiro e criam sua própria sociedade.', NULL),
('O Código Da Vinci', 'Dan Brown', '9788532511010', 'Arqueiro', 2006, 'Suspense', 'Um simbologista desvenda um mistério envolvendo a Igreja e o Santo Graal.', NULL),
('Percy Jackson e o Ladrão de Raios', 'Rick Riordan', '9788532511010', 'Intrínseca', 2006, 'Fantasia', 'Um garoto descobre ser semideus e deve impedir uma guerra entre os deuses gregos.', NULL),
('O Garoto do Pijama Listrado', 'John Boyne', '9788535914849', 'Companhia das Letras', 2007, 'Ficção Histórica', 'A amizade improvável entre dois meninos separados por uma cerca em um campo de concentração.', NULL),
('A Culpa é das Estrelas', 'John Green', '9788532511010', 'Intrínseca', 2013, 'Romance Juvenil', 'Dois adolescentes com câncer se apaixonam e buscam o autor do livro favorito dela.', NULL),
('Moby Dick', 'Herman Melville', '9788535914849', 'Companhia das Letras', 2008, 'Clássico', 'A obsessão do capitão Ahab em caçar a grande baleia branca que lhe arrancou a perna.', NULL),
('O Grande Gatsby', 'F. Scott Fitzgerald', '9788535914849', 'Companhia das Letras', 2013, 'Clássico', 'A decadência do sonho americano nos anos 20, narrada por Nick Carraway.', NULL),
('Crime e Castigo', 'Fiódor Dostoiévski', '9788535914849', 'Companhia das Letras', 2010, 'Clássico', 'Raskolnikov comete um assassinato e luta com sua consciência e a lei.', NULL),
('A Metamorfose', 'Franz Kafka', '9788535914849', 'Companhia das Letras', 2011, 'Clássico', 'Gregor Samsa acorda transformado em um inseto gigante e sua família reage à mudança.', NULL),
('O Nome do Vento', 'Patrick Rothfuss', '9788532511010', 'Arqueiro', 2011, 'Fantasia', 'Kvothe narra sua vida desde a infância em uma trupe até se tornar um lendário mago.', NULL),
('Sapiens: Uma Breve História da Humanidade', 'Yuval Noah Harari', '9788535914849', 'Companhia das Letras', 2015, 'Não-Ficção', 'A história da humanidade desde o surgimento do Homo sapiens até os dias atuais.', NULL),
('O Poder do Hábito', 'Charles Duhigg', '9788535914849', 'Objetiva', 2014, 'Não-Ficção', 'A ciência por trás dos hábitos e como eles podem ser transformados.', NULL);

-- 3. Exemplares: 2-4 cópias por obra (total ~60 exemplares)
INSERT INTO `exemplares` (`obra_id`, `tombo`, `status`, `localizacao`) VALUES
-- Dom Casmurro (3 exemplares)
(1, 'TOM-0001', 'disponivel', 'Estante A1'),
(1, 'TOM-0002', 'disponivel', 'Estante A1'),
(1, 'TOM-0003', 'emprestado', 'Estante A1'),
-- O Pequeno Príncipe (4 exemplares)
(2, 'TOM-0004', 'disponivel', 'Estante A2'),
(2, 'TOM-0005', 'disponivel', 'Estante A2'),
(2, 'TOM-0006', 'disponivel', 'Estante A2'),
(2, 'TOM-0007', 'disponivel', 'Estante A2'),
-- Harry Potter 1 (3 exemplares)
(3, 'TOM-0008', 'disponivel', 'Estante B1'),
(3, 'TOM-0009', 'emprestado', 'Estante B1'),
(3, 'TOM-0010', 'disponivel', 'Estante B1'),
-- Senhor dos Anéis 1 (2 exemplares)
(4, 'TOM-0011', 'disponivel', 'Estante B2'),
(4, 'TOM-0012', 'reservado', 'Estante B2'),
-- A Menina que Roubava Livros (3 exemplares)
(5, 'TOM-0013', 'disponivel', 'Estante C1'),
(5, 'TOM-0014', 'disponivel', 'Estante C1'),
(5, 'TOM-0015', 'disponivel', 'Estante C1'),
-- Cem Anos de Solidão (2 exemplares)
(6, 'TOM-0016', 'disponivel', 'Estante C2'),
(6, 'TOM-0017', 'manutencao', 'Estante C2'),
-- O Alquimista (4 exemplares)
(7, 'TOM-0018', 'disponivel', 'Estante D1'),
(7, 'TOM-0019', 'disponivel', 'Estante D1'),
(7, 'TOM-0020', 'emprestado', 'Estante D1'),
(7, 'TOM-0021', 'disponivel', 'Estante D1'),
-- 1984 (3 exemplares)
(8, 'TOM-0022', 'disponivel', 'Estante D2'),
(8, 'TOM-0023', 'disponivel', 'Estante D2'),
(8, 'TOM-0024', 'emprestado', 'Estante D2'),
-- Diário de Anne Frank (2 exemplares)
(9, 'TOM-0025', 'disponivel', 'Estante E1'),
(9, 'TOM-0026', 'disponivel', 'Estante E1'),
-- Nárnia (3 exemplares)
(10, 'TOM-0027', 'disponivel', 'Estante E2'),
(10, 'TOM-0028', 'disponivel', 'Estante E2'),
(10, 'TOM-0029', 'emprestado', 'Estante E2'),
-- O Hobbit (2 exemplares)
(11, 'TOM-0030', 'disponivel', 'Estante F1'),
(11, 'TOM-0031', 'disponivel', 'Estante F1'),
-- Dom Quixote (2 exemplares)
(12, 'TOM-0032', 'disponivel', 'Estante F2'),
(12, 'TOM-0033', 'disponivel', 'Estante F2'),
-- Orgulho e Preconceito (3 exemplares)
(13, 'TOM-0034', 'disponivel', 'Estante G1'),
(13, 'TOM-0035', 'emprestado', 'Estante G1'),
(13, 'TOM-0036', 'disponivel', 'Estante G1'),
-- A Revolução dos Bichos (4 exemplares)
(14, 'TOM-0037', 'disponivel', 'Estante G2'),
(14, 'TOM-0038', 'disponivel', 'Estante G2'),
(14, 'TOM-0039', 'disponivel', 'Estante G2'),
(14, 'TOM-0040', 'disponivel', 'Estante G2'),
-- O Código Da Vinci (2 exemplares)
(15, 'TOM-0041', 'disponivel', 'Estante H1'),
(15, 'TOM-0042', 'reservado', 'Estante H1'),
-- Percy Jackson (3 exemplares)
(16, 'TOM-0043', 'disponivel', 'Estante H2'),
(16, 'TOM-0044', 'disponivel', 'Estante H2'),
(16, 'TOM-0045', 'emprestado', 'Estante H2'),
-- O Garoto do Pijama Listrado (2 exemplares)
(17, 'TOM-0046', 'disponivel', 'Estante I1'),
(17, 'TOM-0047', 'disponivel', 'Estante I1'),
-- A Culpa é das Estrelas (3 exemplares)
(18, 'TOM-0048', 'disponivel', 'Estante I2'),
(18, 'TOM-0049', 'disponivel', 'Estante I2'),
(18, 'TOM-0050', 'disponivel', 'Estante I2'),
-- Moby Dick (2 exemplares)
(19, 'TOM-0051', 'disponivel', 'Estante J1'),
(19, 'TOM-0052', 'disponivel', 'Estante J1'),
-- O Grande Gatsby (2 exemplares)
(20, 'TOM-0053', 'disponivel', 'Estante J2'),
(20, 'TOM-0054', 'manutencao', 'Estante J2'),
-- Crime e Castigo (2 exemplares)
(21, 'TOM-0055', 'disponivel', 'Estante K1'),
(21, 'TOM-0056', 'disponivel', 'Estante K1'),
-- A Metamorfose (3 exemplares)
(22, 'TOM-0057', 'disponivel', 'Estante K2'),
(22, 'TOM-0058', 'disponivel', 'Estante K2'),
(22, 'TOM-0059', 'emprestado', 'Estante K2'),
-- O Nome do Vento (2 exemplares)
(23, 'TOM-0060', 'disponivel', 'Estante L1'),
(23, 'TOM-0061', 'disponivel', 'Estante L1'),
-- Sapiens (2 exemplares)
(24, 'TOM-0062', 'disponivel', 'Estante L2'),
(24, 'TOM-0063', 'disponivel', 'Estante L2'),
-- O Poder do Hábito (2 exemplares)
(25, 'TOM-0064', 'disponivel', 'Estante M1'),
(25, 'TOM-0065', 'disponivel', 'Estante M1');

-- 4. Reservas de exemplo
INSERT INTO `reservas` (`usuario_id`, `obra_id`, `exemplar_id`, `status`, `posicao_fila`, `data_reserva`, `data_confirmacao`, `data_expiracao`) VALUES
-- Reserva confirmada (João - Dom Casmurro)
(2, 1, 3, 'confirmada', NULL, '2024-11-10 14:30:00', '2024-11-10 15:00:00', '2024-11-12 15:00:00'),
-- Reserva na fila (Ana - Harry Potter)
(3, 3, NULL, 'na_fila', 1, '2024-11-15 09:15:00', NULL, NULL),
-- Reserva pendente (Lucas - O Alquimista)
(4, 7, NULL, 'pendente', NULL, '2024-11-18 11:20:00', NULL, NULL),
-- Reserva expirada (Beatriz - 1984)
(5, 8, NULL, 'expirada', NULL, '2024-11-01 10:00:00', NULL, '2024-11-03 10:00:00'),
-- Reserva na fila (Rafael - Senhor dos Anéis)
(6, 4, NULL, 'na_fila', 1, '2024-11-17 16:45:00', NULL, NULL),
-- Reserva confirmada (Mariana - O Código Da Vinci)
(7, 15, 42, 'confirmada', NULL, '2024-11-16 13:30:00', '2024-11-16 14:00:00', '2024-11-18 14:00:00'),
-- Reserva atendida (Gustavo - Nárnia) -> virou empréstimo
(8, 10, 29, 'atendida', NULL, '2024-11-05 10:30:00', '2024-11-05 11:00:00', '2024-11-07 11:00:00');

-- 5. Empréstimos de exemplo
INSERT INTO `emprestimos` (`exemplar_id`, `usuario_id`, `bibliotecario_id`, `data_retirada`, `data_prevista_devolucao`, `data_devolucao`, `renovado`, `dias_atraso`, `suspensao_aplicada`) VALUES
-- Dom Casmurro emprestado para João (TOM-0003)
(3, 2, 1, '2024-11-10', '2024-11-25', NULL, 0, 0, 0),
-- Harry Potter emprestado para Lucas (TOM-0009)
(9, 4, 1, '2024-11-05', '2024-11-20', NULL, 0, 3, 0),
-- O Alquimista emprestado para Rafael (TOM-0020)
(20, 6, 1, '2024-11-01', '2024-11-16', NULL, 0, 12, 1),
-- 1984 emprestado para Beatriz (TOM-0024)
(24, 5, 1, '2024-10-20', '2024-11-04', '2024-11-10', 0, 6, 1),
-- Nárnia emprestado para Gustavo (TOM-0029)
(29, 8, 1, '2024-11-05', '2024-11-20', NULL, 0, 0, 0),
-- Orgulho e Preconceito emprestado para Isabela (TOM-0035)
(35, 9, 1, '2024-11-08', '2024-11-23', NULL, 0, 0, 0),
-- Percy Jackson emprestado para Pedro (TOM-0045)
(45, 10, 1, '2024-11-12', '2024-11-27', NULL, 0, 0, 0),
-- A Metamorfose emprestado para Valentina (TOM-0059)
(59, 14, 1, '2024-11-15', '2024-11-30', NULL, 0, 0, 0);

-- Atualizar status dos exemplares emprestados
UPDATE `exemplares` SET `status` = 'emprestado' WHERE `id` IN (3, 9, 20, 24, 29, 35, 45, 59);
UPDATE `exemplares` SET `status` = 'reservado' WHERE `id` IN (12, 42);

-- 6. Notificações de exemplo
INSERT INTO `notificacoes` (`usuario_id`, `tipo`, `titulo`, `mensagem`, `lida`, `link`) VALUES
(2, 'reserva_confirmada', 'Reserva confirmada: Dom Casmurro', 'Sua reserva foi confirmada. Retire o livro até 12/11/2024.', 0, '/minhas-reservas.php'),
(3, 'livro_disponivel', 'Harry Potter disponível', 'Um exemplar de Harry Potter e a Pedra Filosofal ficou disponível. Você é o 1º na fila.', 0, '/livro.php?id=3'),
(4, 'reserva_confirmada', 'Reserva confirmada: O Alquimista', 'Sua reserva foi confirmada. Retire o livro até 20/11/2024.', 0, '/minhas-reservas.php'),
(6, 'devolucao_proxima', 'Devolução próxima: Harry Potter', 'Seu empréstimo de Harry Potter vence em 2 dias (20/11/2024).', 0, '/meus-emprestimos.php'),
(6, 'atrasado', 'Empréstimo atrasado: O Alquimista', 'Seu empréstimo está atrasado há 12 dias. Você ficará suspenso por 12 dias.', 0, '/meus-emprestimos.php'),
(5, 'suspensao', 'Suspensão aplicada', 'Devido a 6 dias de atraso na devolução de 1984, você ficará suspenso por 6 dias.', 1, '/meus-emprestimos.php'),
(7, 'reserva_confirmada', 'Reserva confirmada: O Código Da Vinci', 'Sua reserva foi confirmada. Retire o livro até 18/11/2024.', 0, '/minhas-reservas.php');

-- 7. Logs de exemplo
INSERT INTO `logs` (`usuario_id`, `acao`, `entidade`, `entidade_id`, `detalhes`, `ip`, `user_agent`) VALUES
(1, 'cadastro_obra', 'obras', 1, '{"titulo": "Dom Casmurro"}', '192.168.1.100', 'Mozilla/5.0'),
(1, 'cadastro_exemplar', 'exemplares', 1, '{"tombo": "TOM-0001", "obra_id": 1}', '192.168.1.100', 'Mozilla/5.0'),
(2, 'reserva_criada', 'reservas', 1, '{"obra_id": 1}', '192.168.1.50', 'Mozilla/5.0'),
(1, 'reserva_confirmada', 'reservas', 1, '{"exemplar_id": 3}', '192.168.1.100', 'Mozilla/5.0'),
(1, 'emprestimo_criado', 'emprestimos', 1, '{"exemplar_id": 3, "usuario_id": 2}', '192.168.1.100', 'Mozilla/5.0'),
(2, 'login', 'usuarios', 2, '{}', '192.168.1.50', 'Mozilla/5.0'),
(1, 'emprestimo_devolvido', 'emprestimos', 4, '{"dias_atraso": 6, "suspensao_dias": 6}', '192.168.1.100', 'Mozilla/5.0');