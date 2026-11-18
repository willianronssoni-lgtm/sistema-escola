# Mapeamento do sistema PHP para Node.js + TypeScript

Este documento registra o que foi encontrado no código legado e o que precisa ser preservado na nova aplicação. É uma referência de implementação, não uma autorização para importar dados ou alterar o banco em uso.

## Estrutura de dados

| MySQL legado | Modelo Prisma | Observações |
|---|---|---|
| `usuarios` | `User` | Matrícula única, e-mail opcional e único, turma, hash de senha, tipo, ativo e suspensão |
| `obras` | `Book` | Título, autor, ISBN, editora, ano, categoria, sinopse e capa |
| `exemplares` | `Copy` | Tombo único, obra, status, localização e observações |
| `reservas` | `Reservation` | Estado, exemplar associado, posição de fila e datas de reserva/expiração/atendimento |
| `emprestimos` | `Loan` | Aluno, bibliotecário, exemplar, datas, renovação, atraso e suspensão aplicada |
| `notificacoes` | `Notification` | Tipo, título, mensagem, leitura e link |
| `logs` | `AuditLog` | Ação, entidade, detalhes JSON, IP, user-agent e data |
| `api_tokens` | `ApiToken` | SHA-256 do token, descrição, último uso, expiração e revogação |

## Regras funcionais identificadas

- Os tipos de usuário existentes são somente `aluno` e `bibliotecario`; não inventar um papel administrativo adicional sem evidência no legado.
- O login web aceita e-mail escolar ou matrícula, verifica `password_verify` e bloqueia alunos com suspensão vigente.
- O token da API é gerado com bytes aleatórios, devolvido uma única vez e armazenado como hash SHA-256; validade padrão de 30 dias.
- O catálogo oferece busca, categoria, filtro de disponibilidade e paginação (limite de 50 itens por página).
- Um aluno não pode manter reserva ativa duplicada para a mesma obra nem reservar obra que já tenha emprestada.
- Reserva direta associa um exemplar disponível e o marca como reservado; a reserva expira conforme configuração do sistema.
- Fila usa posição por obra e a devolução/cancelamento pode promover o próximo aluno.
- Empréstimo registra bibliotecário responsável, data de retirada e data prevista de devolução; o exemplar muda para emprestado.
- Devolução calcula atraso, libera o exemplar, promove fila e pode aplicar suspensão proporcional ao atraso.
- Renovação é bloqueada quando há atraso, quando o limite foi atingido ou quando outro usuário aguarda a obra.
- Operações importantes geram logs e notificações.

## Rotas REST encontradas

### Públicas
- `GET /api/v1/saude`
- `POST /api/v1/auth/login`

### Usuário autenticado
- `POST /api/v1/auth/logout`
- `GET /api/v1/auth/eu`
- `GET /api/v1/catalogo`
- `GET /api/v1/obras/:id`
- `GET /api/v1/meus/emprestimos`
- `GET /api/v1/meus/reservas`
- `POST /api/v1/reservas`
- `POST /api/v1/reservas/:id/cancelar`
- `POST /api/v1/fila`
- `POST /api/v1/emprestimos/:id/renovar`
- `GET /api/v1/notificacoes`
- Rotas de bibliotecário sob `/api/v1/admin/*` (mapear individualmente no handler legado antes de implementar).

## Cuidados na migração

1. O arquivo `sql/schema.sql` contém `DROP DATABASE`; não é um script seguro para inicializar um banco com dados existentes.
2. O tipo MySQL `YEAR` deve ser convertido conscientemente para inteiro/ano no PostgreSQL.
3. IDs e chaves estrangeiras precisam manter correspondência durante a cópia; validar referências e contagens antes/depois.
4. Hashes de senha PHP devem ser testados com amostras não sensíveis. Não redefinir senhas em massa nem registrar senhas ou tokens em logs.
5. O schema PostgreSQL deve ser criado em banco de teste separado. Não conectar nem executar migrações contra o banco de produção.
6. Views MySQL (`vw_disponibilidade`, `vw_emprestimos_ativos`, `vw_fila_espera`) precisam ser reimplementadas como consultas ou views PostgreSQL, após validar resultados.
7. Todas as operações que mudam empréstimos, exemplares e reservas devem usar transações e bloqueios adequados para evitar que dois usuários reservem/emprestem o mesmo exemplar simultaneamente.
8. Confirmar configurações de prazo e limites no PHP antes de codificar valores fixos.

## Estado

O schema Prisma inicial foi ampliado para refletir as oito tabelas principais. Ainda não há endpoints de negócio migrados, autenticação implementada, importação de dados ou testes de equivalência.
