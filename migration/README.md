# Migração Node.js + TypeScript

Esta pasta contém a nova aplicação em construção, isolada do PHP atualmente publicado.

## Stack
- API: Node.js + TypeScript + Express + Prisma + PostgreSQL.
- Interface: React + TypeScript + Vite.
- O frontend fica em `web/`; a API fica em `src/`.

## Desenvolvimento
1. Instale Node.js 22 LTS.
2. Entre em `migration/` e instale as dependências da API: `npm install`.
3. Instale as dependências web: `cd web && npm install`.
4. Inicie a interface: `npm run dev` dentro de `web/`.
5. Inicie a API em outro terminal: `npm run dev` na pasta `migration/`.

## Interface nesta etapa
A primeira tela React é uma prévia responsiva, com menu lateral recolhível, empréstimos em destaque, sete aparências, modo claro/escuro e layout mobile. Os indicadores ficam como traços até a API ser conectada; nenhum número fictício é apresentado como dado real.

## Segurança dos dados
- **Atenção:** `../sql/schema.sql` contém `DROP DATABASE`. Nunca execute contra um banco existente.
- Nenhum banco real é configurado por padrão; não importe dados reais até backup verificado e dry-run.
- Não armazenar segredos no Git.
- A versão PHP e seu banco permanecem independentes.

## Etapas pendentes
- [ ] Integrar interface com endpoints reais e estados de carregamento/erro.
- [ ] Implementar operações de empréstimo, devolução, reserva/fila e notificações.
- [ ] Testes funcionais, acessibilidade e integração.
- [ ] Build e deploy em serviço Render separado.
- [ ] Aprovação antes de qualquer corte para produção.
