-- Biblioteca Escolar: catálogo inicial com 100 títulos sugeridos
-- Seguro para banco existente: não apaga nem altera usuários, empréstimos,
-- reservas ou exemplares. Não cria exemplares físicos nem empréstimos fictícios.
-- Execute somente após selecionar o banco correto.
-- Títulos já cadastrados (mesmo título e autor) serão ignorados.

SET NAMES utf8mb4;

INSERT INTO obras (titulo, autor, categoria)
SELECT catalogo.titulo, catalogo.autor, catalogo.categoria
FROM (
SELECT 'Dom Casmurro' AS titulo, 'Machado de Assis' AS autor, 'Literatura brasileira' AS categoria
UNION ALL
SELECT 'Memórias Póstumas de Brás Cubas' AS titulo, 'Machado de Assis' AS autor, 'Literatura brasileira' AS categoria
UNION ALL
SELECT 'O Cortiço' AS titulo, 'Aluísio Azevedo' AS autor, 'Literatura brasileira' AS categoria
UNION ALL
SELECT 'Iracema' AS titulo, 'José de Alencar' AS autor, 'Literatura brasileira' AS categoria
UNION ALL
SELECT 'O Guarani' AS titulo, 'José de Alencar' AS autor, 'Literatura brasileira' AS categoria
UNION ALL
SELECT 'A Hora da Estrela' AS titulo, 'Clarice Lispector' AS autor, 'Literatura brasileira' AS categoria
UNION ALL
SELECT 'Vidas Secas' AS titulo, 'Graciliano Ramos' AS autor, 'Literatura brasileira' AS categoria
UNION ALL
SELECT 'Capitães da Areia' AS titulo, 'Jorge Amado' AS autor, 'Literatura brasileira' AS categoria
UNION ALL
SELECT 'Gabriela, Cravo e Canela' AS titulo, 'Jorge Amado' AS autor, 'Literatura brasileira' AS categoria
UNION ALL
SELECT 'O Quinze' AS titulo, 'Rachel de Queiroz' AS autor, 'Literatura brasileira' AS categoria
UNION ALL
SELECT 'A Moreninha' AS titulo, 'Joaquim Manuel de Macedo' AS autor, 'Literatura brasileira' AS categoria
UNION ALL
SELECT 'Triste Fim de Policarpo Quaresma' AS titulo, 'Lima Barreto' AS autor, 'Literatura brasileira' AS categoria
UNION ALL
SELECT 'Auto da Compadecida' AS titulo, 'Ariano Suassuna' AS autor, 'Literatura brasileira' AS categoria
UNION ALL
SELECT 'Morte e Vida Severina' AS titulo, 'João Cabral de Melo Neto' AS autor, 'Literatura brasileira' AS categoria
UNION ALL
SELECT 'Claro Enigma' AS titulo, 'Carlos Drummond de Andrade' AS autor, 'Literatura brasileira' AS categoria
UNION ALL
SELECT 'Romanceiro da Inconfidência' AS titulo, 'Cecília Meireles' AS autor, 'Literatura brasileira' AS categoria
UNION ALL
SELECT 'Quarto de Despejo' AS titulo, 'Carolina Maria de Jesus' AS autor, 'Literatura brasileira' AS categoria
UNION ALL
SELECT 'Ponciá Vicêncio' AS titulo, 'Conceição Evaristo' AS autor, 'Literatura brasileira' AS categoria
UNION ALL
SELECT 'O Meu Pé de Laranja Lima' AS titulo, 'José Mauro de Vasconcelos' AS autor, 'Literatura brasileira' AS categoria
UNION ALL
SELECT 'A Bolsa Amarela' AS titulo, 'Lygia Bojunga' AS autor, 'Literatura brasileira' AS categoria
UNION ALL
SELECT 'O Pequeno Príncipe' AS titulo, 'Antoine de Saint-Exupéry' AS autor, 'Literatura infantojuvenil' AS categoria
UNION ALL
SELECT 'O Menino Maluquinho' AS titulo, 'Ziraldo' AS autor, 'Literatura infantojuvenil' AS categoria
UNION ALL
SELECT 'Marcelo, Marmelo, Martelo' AS titulo, 'Ruth Rocha' AS autor, 'Literatura infantojuvenil' AS categoria
UNION ALL
SELECT 'Bisa Bia, Bisa Bel' AS titulo, 'Ana Maria Machado' AS autor, 'Literatura infantojuvenil' AS categoria
UNION ALL
SELECT 'Reinações de Narizinho' AS titulo, 'Monteiro Lobato' AS autor, 'Literatura infantojuvenil' AS categoria
UNION ALL
SELECT 'O Saci' AS titulo, 'Monteiro Lobato' AS autor, 'Literatura infantojuvenil' AS categoria
UNION ALL
SELECT 'A Droga da Obediência' AS titulo, 'Pedro Bandeira' AS autor, 'Literatura infantojuvenil' AS categoria
UNION ALL
SELECT 'A Marca de uma Lágrima' AS titulo, 'Pedro Bandeira' AS autor, 'Literatura infantojuvenil' AS categoria
UNION ALL
SELECT 'O Gênio do Crime' AS titulo, 'João Carlos Marinho' AS autor, 'Literatura infantojuvenil' AS categoria
UNION ALL
SELECT 'Meu Amigo Pintor' AS titulo, 'Lygia Bojunga' AS autor, 'Literatura infantojuvenil' AS categoria
UNION ALL
SELECT 'O Fantástico Mistério de Feiurinha' AS titulo, 'Pedro Bandeira' AS autor, 'Literatura infantojuvenil' AS categoria
UNION ALL
SELECT 'A Arca de Noé' AS titulo, 'Vinicius de Moraes' AS autor, 'Literatura infantojuvenil' AS categoria
UNION ALL
SELECT 'Ou Isto ou Aquilo' AS titulo, 'Cecília Meireles' AS autor, 'Literatura infantojuvenil' AS categoria
UNION ALL
SELECT 'Flicts' AS titulo, 'Ziraldo' AS autor, 'Literatura infantojuvenil' AS categoria
UNION ALL
SELECT 'O Menino no Espelho' AS titulo, 'Fernando Sabino' AS autor, 'Literatura infantojuvenil' AS categoria
UNION ALL
SELECT 'A Ilha Perdida' AS titulo, 'Maria José Dupré' AS autor, 'Literatura infantojuvenil' AS categoria
UNION ALL
SELECT 'Histórias de Alexandre' AS titulo, 'Graciliano Ramos' AS autor, 'Literatura infantojuvenil' AS categoria
UNION ALL
SELECT 'O Escaravelho do Diabo' AS titulo, 'Lúcia Machado de Almeida' AS autor, 'Literatura infantojuvenil' AS categoria
UNION ALL
SELECT 'A Turma da Rua Quinze' AS titulo, 'Marçal Aquino' AS autor, 'Literatura infantojuvenil' AS categoria
UNION ALL
SELECT 'O Mistério do Cinco Estrelas' AS titulo, 'Marcos Rey' AS autor, 'Literatura infantojuvenil' AS categoria
UNION ALL
SELECT 'O Diário de Anne Frank' AS titulo, 'Anne Frank' AS autor, 'História e geografia' AS categoria
UNION ALL
SELECT 'A História do Brasil para Quem Tem Pressa' AS titulo, 'Marcos Costa' AS autor, 'História e geografia' AS categoria
UNION ALL
SELECT '1808' AS titulo, 'Laurentino Gomes' AS autor, 'História e geografia' AS categoria
UNION ALL
SELECT '1822' AS titulo, 'Laurentino Gomes' AS autor, 'História e geografia' AS categoria
UNION ALL
SELECT '1889' AS titulo, 'Laurentino Gomes' AS autor, 'História e geografia' AS categoria
UNION ALL
SELECT 'Brasil: Uma Biografia' AS titulo, 'Lilia Moritz Schwarcz e Heloisa Starling' AS autor, 'História e geografia' AS categoria
UNION ALL
SELECT 'A Ditadura Envergonhada' AS titulo, 'Elio Gaspari' AS autor, 'História e geografia' AS categoria
UNION ALL
SELECT 'As Veias Abertas da América Latina' AS titulo, 'Eduardo Galeano' AS autor, 'História e geografia' AS categoria
UNION ALL
SELECT 'Uma Breve História do Tempo' AS titulo, 'Stephen Hawking' AS autor, 'História e geografia' AS categoria
UNION ALL
SELECT 'Prisioneiros da Geografia' AS titulo, 'Tim Marshall' AS autor, 'História e geografia' AS categoria
UNION ALL
SELECT 'Geografia: Pequena História Crítica' AS titulo, 'Antonio Carlos Robert Moraes' AS autor, 'História e geografia' AS categoria
UNION ALL
SELECT 'A Invenção da Natureza' AS titulo, 'Andrea Wulf' AS autor, 'História e geografia' AS categoria
UNION ALL
SELECT 'O Povo Brasileiro' AS titulo, 'Darcy Ribeiro' AS autor, 'História e geografia' AS categoria
UNION ALL
SELECT 'Sapiens' AS titulo, 'Yuval Noah Harari' AS autor, 'História e geografia' AS categoria
UNION ALL
SELECT 'História da Riqueza no Brasil' AS titulo, 'Jorge Caldeira' AS autor, 'História e geografia' AS categoria
UNION ALL
SELECT 'O Gene Egoísta' AS titulo, 'Richard Dawkins' AS autor, 'Ciências e biologia' AS categoria
UNION ALL
SELECT 'A Origem das Espécies' AS titulo, 'Charles Darwin' AS autor, 'Ciências e biologia' AS categoria
UNION ALL
SELECT 'Uma Breve História de Quase Tudo' AS titulo, 'Bill Bryson' AS autor, 'Ciências e biologia' AS categoria
UNION ALL
SELECT 'O Universo numa Casca de Noz' AS titulo, 'Stephen Hawking' AS autor, 'Ciências e biologia' AS categoria
UNION ALL
SELECT 'Cosmos' AS titulo, 'Carl Sagan' AS autor, 'Ciências e biologia' AS categoria
UNION ALL
SELECT 'O Mundo Assombrado pelos Demônios' AS titulo, 'Carl Sagan' AS autor, 'Ciências e biologia' AS categoria
UNION ALL
SELECT 'A Vida Secreta das Árvores' AS titulo, 'Peter Wohlleben' AS autor, 'Ciências e biologia' AS categoria
UNION ALL
SELECT 'A Sexta Extinção' AS titulo, 'Elizabeth Kolbert' AS autor, 'Ciências e biologia' AS categoria
UNION ALL
SELECT 'O Corpo Humano' AS titulo, 'Bill Bryson' AS autor, 'Ciências e biologia' AS categoria
UNION ALL
SELECT 'A Teia da Vida' AS titulo, 'Fritjof Capra' AS autor, 'Ciências e biologia' AS categoria
UNION ALL
SELECT 'O Andar do Bêbado' AS titulo, 'Leonard Mlodinow' AS autor, 'Ciências e biologia' AS categoria
UNION ALL
SELECT 'A Colher que Desaparece' AS titulo, 'Sam Kean' AS autor, 'Ciências e biologia' AS categoria
UNION ALL
SELECT 'O Livro da Biologia' AS titulo, 'DK' AS autor, 'Ciências e biologia' AS categoria
UNION ALL
SELECT 'O Livro da Ciência' AS titulo, 'DK' AS autor, 'Ciências e biologia' AS categoria
UNION ALL
SELECT 'Primavera Silenciosa' AS titulo, 'Rachel Carson' AS autor, 'Ciências e biologia' AS categoria
UNION ALL
SELECT 'O Homem que Calculava' AS titulo, 'Malba Tahan' AS autor, 'Matemática e tecnologia' AS categoria
UNION ALL
SELECT 'O Diabo dos Números' AS titulo, 'Hans Magnus Enzensberger' AS autor, 'Matemática e tecnologia' AS categoria
UNION ALL
SELECT 'O Último Teorema de Fermat' AS titulo, 'Simon Singh' AS autor, 'Matemática e tecnologia' AS categoria
UNION ALL
SELECT 'Alex no País dos Números' AS titulo, 'Alex Bellos' AS autor, 'Matemática e tecnologia' AS categoria
UNION ALL
SELECT 'Como Mentir com Estatística' AS titulo, 'Darrell Huff' AS autor, 'Matemática e tecnologia' AS categoria
UNION ALL
SELECT 'A Matemática do Ensino Médio: volume 1' AS titulo, 'Elon Lages Lima e outros' AS autor, 'Matemática e tecnologia' AS categoria
UNION ALL
SELECT 'A Matemática do Ensino Médio: volume 2' AS titulo, 'Elon Lages Lima e outros' AS autor, 'Matemática e tecnologia' AS categoria
UNION ALL
SELECT 'A Matemática do Ensino Médio: volume 3' AS titulo, 'Elon Lages Lima e outros' AS autor, 'Matemática e tecnologia' AS categoria
UNION ALL
SELECT 'Algoritmos para Viver' AS titulo, 'Brian Christian e Tom Griffiths' AS autor, 'Matemática e tecnologia' AS categoria
UNION ALL
SELECT 'A Máquina do Tempo' AS titulo, 'H. G. Wells' AS autor, 'Matemática e tecnologia' AS categoria
UNION ALL
SELECT 'Orgulho e Preconceito' AS titulo, 'Jane Austen' AS autor, 'Literatura estrangeira' AS categoria
UNION ALL
SELECT 'Frankenstein' AS titulo, 'Mary Shelley' AS autor, 'Literatura estrangeira' AS categoria
UNION ALL
SELECT 'O Hobbit' AS titulo, 'J. R. R. Tolkien' AS autor, 'Literatura estrangeira' AS categoria
UNION ALL
SELECT 'O Leão, a Feiticeira e o Guarda-Roupa' AS titulo, 'C. S. Lewis' AS autor, 'Literatura estrangeira' AS categoria
UNION ALL
SELECT 'Alice no País das Maravilhas' AS titulo, 'Lewis Carroll' AS autor, 'Literatura estrangeira' AS categoria
UNION ALL
SELECT 'As Aventuras de Tom Sawyer' AS titulo, 'Mark Twain' AS autor, 'Literatura estrangeira' AS categoria
UNION ALL
SELECT 'Viagem ao Centro da Terra' AS titulo, 'Júlio Verne' AS autor, 'Literatura estrangeira' AS categoria
UNION ALL
SELECT 'A Volta ao Mundo em 80 Dias' AS titulo, 'Júlio Verne' AS autor, 'Literatura estrangeira' AS categoria
UNION ALL
SELECT 'O Chamado da Floresta' AS titulo, 'Jack London' AS autor, 'Literatura estrangeira' AS categoria
UNION ALL
SELECT 'O Jardim Secreto' AS titulo, 'Frances Hodgson Burnett' AS autor, 'Literatura estrangeira' AS categoria
UNION ALL
SELECT 'O Mundo de Sofia' AS titulo, 'Jostein Gaarder' AS autor, 'Filosofia e conhecimentos gerais' AS categoria
UNION ALL
SELECT 'Apologia de Sócrates' AS titulo, 'Platão' AS autor, 'Filosofia e conhecimentos gerais' AS categoria
UNION ALL
SELECT 'Ética a Nicômaco' AS titulo, 'Aristóteles' AS autor, 'Filosofia e conhecimentos gerais' AS categoria
UNION ALL
SELECT 'O Príncipe' AS titulo, 'Nicolau Maquiavel' AS autor, 'Filosofia e conhecimentos gerais' AS categoria
UNION ALL
SELECT 'Discurso do Método' AS titulo, 'René Descartes' AS autor, 'Filosofia e conhecimentos gerais' AS categoria
UNION ALL
SELECT 'A República' AS titulo, 'Platão' AS autor, 'Filosofia e conhecimentos gerais' AS categoria
UNION ALL
SELECT 'Filosofando: Introdução à Filosofia' AS titulo, 'Maria Lúcia de Arruda Aranha e Maria Helena Pires Martins' AS autor, 'Filosofia e conhecimentos gerais' AS categoria
UNION ALL
SELECT 'Pequeno Tratado das Grandes Virtudes' AS titulo, 'André Comte-Sponville' AS autor, 'Filosofia e conhecimentos gerais' AS categoria
UNION ALL
SELECT 'O que é lugar de fala?' AS titulo, 'Djamila Ribeiro' AS autor, 'Filosofia e conhecimentos gerais' AS categoria
UNION ALL
SELECT 'Ideias para Adiar o Fim do Mundo' AS titulo, 'Ailton Krenak' AS autor, 'Filosofia e conhecimentos gerais' AS categoria
) AS catalogo
WHERE NOT EXISTS (
  SELECT 1 FROM obras existente
  WHERE existente.titulo = catalogo.titulo
    AND existente.autor = catalogo.autor
);

SELECT COUNT(*) AS total_de_obras FROM obras;
