# Migração Node.js + TypeScript

Esta pasta contém a nova aplicação em construção. Ela é isolada do PHP atual e não é publicada pelo serviço Render existente enquanto a branch `migration/node-typescript` não for integrada.

## Stack
- Node.js + TypeScript + Express
- Prisma ORM
- PostgreSQL
- React será adicionado para a nova interface após estabilizar a API.

## Desenvolvimento
1. Instale Node.js 22 LTS.
2. Entre nesta pasta: `cd migration`
3. Copie `.env.example` para `.env` e configure uma URL PostgreSQL de teste.
4. Instale: `npm install`
5. Gere o cliente: `npm run prisma:generate`
6. Valide o schema: `npm run prisma:validate`
7. Execute: `npm run dev`

A rota `GET /api/v1/health` verifica a API e a conexão PostgreSQL. Login permanece desativado (501) até que a migração dos hashes e regras de autenticação seja implementada e testada.

## Segurança dos dados
- Não aponta para o banco atual por padrão.
- Não executar migrações contra o banco de produção.
- Não importar dados reais até validar backup, mapeamento de IDs, hashes de senha, datas e integridade referencial.
- O banco MySQL/PHP e o site atual devem continuar disponíveis durante a construção.
- Não armazenar segredos no Git. Use variáveis de ambiente no serviço de teste.

## Etapas pendentes
- [ ] Validar o schema Prisma e gerar migrations PostgreSQL de teste.
- [ ] Implementar autenticação segura, sessões/tokens, CSRF/CORS e permissões.
- [ ] Migrar catálogo e pesquisa.
- [ ] Migrar exemplares, empréstimos, devoluções, reservas e fila.
- [ ] Migrar notificações e auditoria.
- [ ] Criar frontend React responsivo preservando a identidade visual aprovada.
- [ ] Criar testes automatizados e importar cópia anonimizada.
- [ ] Testar deploy em serviço Render separado.
- [ ] Planejar corte de produção somente após aprovação e backup verificado.
