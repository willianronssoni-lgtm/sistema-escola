# API REST

A API fica em `public/api/index.php` e responde em JSON. Todas as regras de
negócio são as mesmas das páginas web: a API e a interface compartilham os
serviços em `app/services/`.

## Endereço

Com o servidor Apache (que usa o `.htaccess` do projeto), as rotas são:

```
https://<dominio>/api/v1/<recurso>
```

Se o servidor não reescrever as URLs, use o caminho explícito:

```
https://<dominio>/api/index.php?rota=v1/<recurso>
```

## Autenticação

A API usa token de acesso (`Bearer`). O token é gerado no login, devolvido
uma única vez em texto claro e guardado apenas como hash SHA-256 no banco.
Cada token vale 30 dias e pode ser revogado.

```bash
curl -X POST https://<dominio>/api/index.php?rota=v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"matricula":"BIB001","senha":"123456"}'
```

```json
{
  "sucesso": true,
  "dados": {
    "token": "9f2c…",
    "expira_em": "2026-12-11 17:00:00",
    "usuario": {"id": 1, "matricula": "BIB001", "nome": "…", "tipo": "bibliotecario"}
  }
}
```

Use o token nas chamadas seguintes:

```bash
curl -H "Authorization: Bearer <token>" https://<dominio>/api/index.php?rota=v1/catalogo
```

O token também pode ser enviado em `?access_token=<token>` (útil para
clientes que não permitem cabeçalhos customizados).

Formato das respostas:

```json
{ "sucesso": true,  "dados": {}, "meta": {} }
{ "sucesso": false, "erro": "mensagem" }
```

| Código | Significado |
|---|---|
| 200 | operação concluída |
| 201 | recurso criado |
| 401 | token ausente, inválido ou expirado |
| 403 | perfil sem permissão (por exemplo, aluno em rota administrativa) |
| 404 | rota ou recurso inexistente |
| 409 | conflito com o estado atual (ex.: exemplar indisponível) |
| 422 | dados inválidos (campos faltando ou regra de validação) |
| 500 | erro interno (sem exposição de detalhes) |

## Rotas

### Público
| Método | Rota | Descrição |
|---|---|---|
| GET | `/v1/saude` | Estado do serviço e do banco |

### Sessão
| Método | Rota | Descrição |
|---|---|---|
| POST | `/v1/auth/login` | Gera um token de acesso |
| POST | `/v1/auth/logout` | Revoga o token usado |
| GET | `/v1/auth/eu` | Perfil do usuário autenticado |

### Aluno
| Método | Rota | Descrição |
|---|---|---|
| GET | `/v1/catalogo?q=&categoria=&pagina=&por_pagina=&disponiveis=1` | Lista o acervo com paginação |
| GET | `/v1/obras/{id}` | Detalhe da obra e seus exemplares |
| GET | `/v1/meus/emprestimos` | Empréstimos ativos e histórico |
| GET | `/v1/meus/reservas` | Reservas do aluno |
| POST | `/v1/reservas` | Reserva um exemplar (`{"obra_id": 1}`) |
| POST | `/v1/reservas/{id}/cancelar` | Cancela a reserva e promove o próximo da fila |
| POST | `/v1/fila` | Entra na fila de espera (`{"obra_id": 1}`) |
| POST | `/v1/emprestimos/{id}/renovar` | Renova o empréstimo por 15 dias |
| GET | `/v1/notificacoes` | Notificações do usuário |

### Bibliotecário
| Método | Rota | Descrição |
|---|---|---|
| GET | `/v1/admin/alunos?q=` | Lista os alunos |
| POST | `/v1/admin/alunos` | Cadastra um aluno |
| GET | `/v1/admin/emprestimos` | Empréstimos registrados |
| POST | `/v1/admin/emprestimos` | Registra a retirada (`{"aluno_id": 1, "exemplar_id": 2}`) |
| POST | `/v1/admin/devolucoes` | Registra a devolução (`{"emprestimo_id": 3}`) |
| GET | `/v1/admin/reservas` | Reservas e fila de espera |
| POST | `/v1/admin/reservas/{id}/confirmar` | Confirma uma reserva da fila |
| POST | `/v1/admin/reservas/{id}/cancelar` | Cancela uma reserva |
| GET | `/v1/admin/relatorios` | Indicadores e obras mais emprestadas |

## Exemplos

```bash
# reservar um livro
curl -X POST -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"obra_id": 3}' https://<dominio>/api/index.php?rota=v1/reservas

# registrar um empréstimo (bibliotecário)
curl -X POST -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"aluno_id": 5, "exemplar_id": 12}' https://<dominio>/api/index.php?rota=v1/admin/emprestimos
```

## Testes

```bash
php tests/api.php   # exige MySQL/MariaDB
```

O arquivo exercita login, tokens, expiração, revogação, permissões, catálogo,
reserva, fila de espera, promoção, empréstimo, renovação, devolução e rotas
administrativas.
