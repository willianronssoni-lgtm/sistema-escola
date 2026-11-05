# Planejamento do Projeto — Biblioteca Escolar

> Documento de referência para explicar a proposta, o funcionamento esperado, as tecnologias e a ordem de desenvolvimento.

## 1. Objetivo

Desenvolver um sistema web para organizar a biblioteca escolar. A aplicação deverá permitir que alunos consultem o acervo e acompanhem suas solicitações, enquanto bibliotecários administram livros, exemplares, reservas e empréstimos.

## 2. Como o sistema vai funcionar

### Perfil do aluno
1. Acessa o sistema com matrícula e senha.
2. Pesquisa o catálogo por título, autor ou categoria.
3. Consulta os detalhes do livro e a disponibilidade dos exemplares.
4. Solicita uma reserva quando houver exemplar disponível.
5. Entra na fila de espera quando não houver disponibilidade.
6. Acompanha suas reservas e empréstimos, incluindo a data prevista de devolução.
7. Cancela uma reserva quando ela ainda permitir cancelamento.

### Perfil do bibliotecário
1. Entra em uma área administrativa protegida por perfil de acesso.
2. Cadastra e atualiza obras e seus exemplares físicos.
3. Consulta alunos, reservas e fila de espera.
4. Registra a retirada de um exemplar e a devolução.
5. Acompanha atrasos, disponibilidade e movimentações.
6. Consulta relatórios para apoiar a gestão da biblioteca.

> A área administrativa completa e os fluxos de gestão devem ser concluídos e validados antes de considerar o sistema pronto para uso real.

## 3. Regras de negócio previstas

- Cada obra pode possuir vários exemplares, identificados individualmente pelo número de tombo.
- Um exemplar pode estar disponível, emprestado, reservado ou em manutenção.
- O prazo padrão de empréstimo configurado atualmente é de 15 dias.
- O limite configurado atualmente é de 3 empréstimos simultâneos por aluno.
- A configuração prevê uma renovação de 15 dias, sujeita à validação das regras de empréstimo.
- A reserva tem prazo de retirada configurado em 48 horas.
- Quando não houver exemplar disponível, o aluno poderá aguardar em uma fila.
- A aplicação deve impedir operações incompatíveis com o estado do exemplar ou com as restrições do aluno.

Os valores acima estão definidos na configuração atual e poderão ser ajustados conforme as regras da escola.

## 4. Tecnologias que serão utilizadas

| Tecnologia | Finalidade |
|---|---|
| PHP 8+ | Lógica do sistema, autenticação e regras de negócio |
| MySQL 8 / MariaDB | Armazenamento de usuários, livros, exemplares e movimentações |
| PDO | Acesso ao banco com consultas parametrizadas |
| HTML5 | Estrutura das páginas |
| CSS3 | Aparência e adaptação a diferentes telas |
| JavaScript | Interações da interface |
| Git e GitHub | Versionamento, colaboração e histórico do projeto |
| GitHub Actions | Execução automática de verificações e testes (etapa planejada) |

A aplicação utiliza PHP e MySQL diretamente; não depende de um framework PHP para funcionar.

## 5. Organização do projeto

- `app/auth/`: autenticação e controle de acesso.
- `app/config/`: configurações gerais e conexão com o banco.
- `app/helpers/`: funções auxiliares e renderização de páginas.
- `public/`: ponto de entrada e páginas acessíveis pelo navegador.
- `views/`: modelos visuais das páginas.
- `sql/schema.sql`: criação da estrutura do banco de dados.
- `sql/seed.sql`: dados fictícios para demonstração e desenvolvimento.
- `tests/`: espaço destinado aos testes automatizados.
- `.github/workflows/`: automações do GitHub Actions.

## 6. Modelo de dados

O banco está organizado em entidades principais:

- **Usuários:** alunos e bibliotecários, com matrícula, dados de acesso e situação.
- **Obras:** informações bibliográficas dos títulos.
- **Exemplares:** cópias físicas de cada obra e seu estado atual.
- **Reservas:** solicitações, confirmações, cancelamentos e posição na fila.
- **Empréstimos:** retirada, prazo previsto, devolução e renovação.
- **Notificações:** avisos relacionados às atividades da biblioteca.
- **Logs:** histórico de ações importantes para auditoria.

Também existem visões SQL para consultar disponibilidade, empréstimos ativos e fila de espera.

## 7. Etapas de desenvolvimento

### Etapa 1 — Base do projeto
- [x] Estrutura inicial em PHP.
- [x] Configuração de conexão com MySQL via PDO.
- [x] Estrutura inicial do banco de dados.
- [x] Configurações por variáveis de ambiente.

### Etapa 2 — Acesso e experiência do aluno
- [x] Base de autenticação e controle de perfil.
- [x] Catálogo e páginas do aluno.
- [x] Fluxos iniciais de reserva e fila de espera.
- [ ] Validar todos os cenários de uso com dados de teste.

### Etapa 3 — Administração da biblioteca
- [ ] Painel do bibliotecário.
- [ ] Cadastro, edição e consulta de alunos.
- [ ] Cadastro de obras e exemplares.
- [ ] Registro e gestão de empréstimos e devoluções.
- [ ] Gestão de reservas e fila de espera.
- [ ] Relatórios de movimentação e atrasos.

### Etapa 4 — Qualidade e segurança
- [ ] Testes automatizados das regras de negócio.
- [ ] Testes dos fluxos principais de aluno e bibliotecário.
- [ ] Revisão de permissões e proteção das rotas.
- [ ] Validação de formulários e tratamento de erros.
- [ ] Verificação de acessibilidade e responsividade.

### Etapa 5 — Publicação e manutenção
- [ ] Preparar ambiente de produção com HTTPS.
- [ ] Configurar banco e variáveis de ambiente no servidor.
- [ ] Configurar rotina de backup.
- [ ] Publicar documentação de instalação e operação.
- [ ] Monitorar erros e manter dependências atualizadas.

## 8. Como executar em ambiente de desenvolvimento

### Requisitos
- PHP 8 ou superior com extensões PDO, pdo_mysql, fileinfo e mbstring.
- MySQL 8 ou MariaDB compatível.

### Passos
1. Clone o repositório:
   ```bash
   git clone https://github.com/willianronssoni-lgtm/sistema-escola.git
   cd sistema-escola
   ```
2. Crie o banco importando o esquema:
   ```bash
   mysql -u root -p < sql/schema.sql
   ```
3. Configure `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` e `APP_URL` no ambiente em que o PHP será executado.
4. Inicie o servidor local:
   ```bash
   php -S 127.0.0.1:8000 -t public
   ```
5. Acesse `http://127.0.0.1:8000`.

### Dados de demonstração

O arquivo `sql/seed.sql` contém dados fictícios para desenvolvimento. **Ele limpa dados de tabelas antes de inserir os exemplos.** Nunca o execute em um banco com dados reais. Consulte o README para as credenciais de demonstração.

## 9. Critérios para considerar o projeto pronto

- Aluno consegue entrar, pesquisar livros e acompanhar suas solicitações.
- Bibliotecário consegue administrar o acervo e registrar retiradas e devoluções.
- As regras de disponibilidade, reserva, fila e prazo são respeitadas.
- Usuários não conseguem acessar funções de outros perfis sem autorização.
- Testes automatizados e validações dos principais fluxos passam.
- Instalação, configuração, backup e operação estão documentados.

## 10. Situação do projeto

Este planejamento descreve o destino do projeto, não uma declaração de que todas as funcionalidades já estão prontas. Consulte o README e o histórico de commits para acompanhar o que já foi implementado. As caixas das etapas devem ser atualizadas conforme cada entrega for concluída.
