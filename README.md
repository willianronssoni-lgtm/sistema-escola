# Biblioteca Escolar

Sistema web em PHP e MySQL para catálogo, reservas, fila de espera, renovação de empréstimos e administração da biblioteca escolar.

## Requisitos
- PHP 8.1 ou superior
- MySQL 8 / MariaDB compatível
- Extensões PHP: PDO, pdo_mysql, fileinfo e mbstring

## Instalação
1. Configure o servidor web para publicar a pasta `public/` (ela é a raiz da aplicação).
2. Crie a estrutura do banco:

   ```bash
   mysql -u root -p < sql/schema.sql
   ```

   `schema.sql` recria o banco `biblioteca_escolar`. Em um banco já existente, use `sql/railway-schema.sql`, que é idempotente e não apaga nada.

3. Opcionalmente, carregue dados fictícios para demonstração:

   ```bash
   mysql -u root -p biblioteca_escolar < sql/seed.sql
   ```

   **Atenção:** `seed.sql` limpa as tabelas antes de inserir exemplos. Nunca o execute em um banco com dados reais.

4. Configure variáveis de ambiente no servidor PHP:
   - `DB_HOST`: host MySQL (padrão `127.0.0.1`)
   - `DB_PORT`: porta do MySQL (padrão `3306`)
   - `DB_NAME`: banco (padrão `biblioteca_escolar`)
   - `DB_USER`: usuário (padrão `root`)
   - `DB_PASS`: senha (padrão vazio)
   - `APP_URL`: URL pública da aplicação, **sem barra final** (padrão `http://localhost/biblioteca/public`)
   - `APP_NAME`: nome da aplicação
   - `APP_TIMEZONE`: fuso horário (padrão `America/Sao_Paulo`)
   - `DEBUG`: use `0` em produção; `1` somente em desenvolvimento

   Todos os links da interface são gerados a partir de `APP_URL`. Em produção, informe a URL raiz
   (por exemplo `https://biblioteca.up.railway.app`) e o sistema funciona sem nenhum ajuste adicional.

5. Garanta permissão de escrita em `uploads/capas`.
6. Acesse a URL definida em `APP_URL`.

Teste local rápido:
```bash
php -S 127.0.0.1:8000 -t public public/router.php
```
Ajuste `APP_URL=http://127.0.0.1:8000` nesse cenário.

## Conta de demonstração
Depois de importar `sql/seed.sql`, use matrícula `BIB001` e senha `123456` (bibliotecário).
Os alunos de demonstração usam matrículas de `2024001` a `2024015` e a mesma senha.
Troque a senha imediatamente e nunca use os dados fictícios em produção.

## Funcionalidades

**Aluno**
- Login com matrícula e senha, aviso de suspensão por atraso.
- Catálogo com busca por título, autor, ISBN e filtro por categoria e disponibilidade.
- Página da obra com exemplares, situação e histórico de reserva.
- Reserva de exemplar disponível, entrada na fila de espera e cancelamento.
- Acompanhamento de reservas, **fila de espera** (com a posição de cada obra), empréstimos, atrasos e notificações.
- Renovação de empréstimo (15 dias, 1 renovação por empréstimo, bloqueada em atraso ou quando há fila).

**Bibliotecário**
- Painel com indicadores e operações rápidas.
- Acervo: cadastro de obras e exemplares, ajuste de status (disponível, emprestado, reservado, manutenção) e localização.
- Alunos: cadastro, edição, ativação/desativação, suspensão e redefinição de senha.
- Reservas: acompanhamento de confirmadas e fila de espera, confirmação manual e cancelamento.
- Empréstimos: registro de retirada, renovação e busca por aluno, obra ou tombo.
- Devoluções: registro de devolução, atraso, suspensão proporcional e promoção da fila.
- Relatórios: indicadores, acervo por categoria, obras mais emprestadas, movimentação e atrasos.

## Regras de negócio
| Regra | Valor |
|---|---|
| Prazo do empréstimo | 15 dias |
| Renovação | 15 dias, 1 renovação por empréstimo |
| Retirada da reserva | 48 horas |
| Empréstimos simultâneos por aluno | 3 |
| Atraso | gera suspensão do aluno pelo número de dias de atraso |

## API REST
A aplicação expõe uma API JSON versionada em `public/api/index.php`, com
autenticação por token de acesso. As rotas, os códigos de resposta e os
exemplos estão em [docs/API.md](docs/API.md).

```bash
curl -X POST "http://127.0.0.1:8000/api/index.php?rota=v1/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"matricula":"BIB001","senha":"123456"}'
```

## Navegação
A interface tem três níveis de navegação para não deixar nenhum fluxo sem destino:

- **Menu superior**: catálogo, minhas reservas, minha fila, meus empréstimos e notificações
  (aluno); biblioteca com painel, acervo, alunos, empréstimos, devoluções, reservas e
  relatórios (bibliotecário); perfil e alteração de senha (ambos).
- **Atalhos na página inicial**: cartões de ação diferentes para aluno, bibliotecário e visitante.
- **Trilha de navegação (breadcrumb)** em todas as telas internas, com o caminho de volta ao início.
- **Rodapé** com os links de todas as áreas.

O teste `tests/links.php` confere se todos os links internos apontam para páginas existentes,
impedindo que um item de menu leve a um erro 404.

## Testes
```bash
php tests/run.php           # testes unitários, sem banco de dados
php tests/links.php         # verificação dos links internos (sem banco de dados)
php tests/integration.php   # testes de integração das regras (exige MySQL/MariaDB)
php tests/api.php           # testes da API REST (exige MySQL/MariaDB)
```
Os testes que usam o banco leem as variáveis `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` e `DB_PASS`,
criam dados próprios e os removem ao final. Se o banco não estiver disponível, eles são ignorados com aviso.
O GitHub Actions executa os quatro: unitários, links e integração/API com um serviço MySQL 8.

## Segurança
- Use usuário MySQL dedicado com privilégios mínimos, nunca `root` em produção.
- Use HTTPS em produção.
- Mantenha `DEBUG=0` em produção.
- Faça backup antes de migrações.
- Todas as operações de escrita exigem token CSRF e perfil de acesso.

## Planejamento do projeto

Veja o documento [PLANEJAMENTO.md](docs/PLANEJAMENTO.md) e a documentação da API em
[docs/API.md](docs/API.md) para entender:
- como o sistema deverá funcionar para alunos e bibliotecários;
- quais regras de empréstimo e reserva estão previstas;
- quais tecnologias e estrutura serão utilizadas;
- as etapas de desenvolvimento e os critérios de conclusão.

## Estrutura do projeto (Back-end e Front-end)

O código da aplicação está organizado em duas pastas principais:

- **[Back-end](back/)** — toda a lógica PHP do servidor: configuração, banco de dados, autenticação, serviços, API e controladores das páginas.
  - [Aplicação](back/app/)
  - [Controladores e endpoints](back/controllers/)
- **[Front-end](front/)** — telas, componentes, estilos, scripts e imagens.
  - [Views](front/views/)
  - [Assets (CSS, JavaScript e imagens)](front/assets/)

A pasta `public/` contém somente os pontos de entrada HTTP necessários ao Apache. Eles encaminham as requisições para os controladores em `back/controllers/`. O Apache publica os arquivos de `front/assets/` pelo endereço `/assets`, sem duplicar os arquivos no diretório público.

Os arquivos SQL e de documentação continuam em `sql/` e `docs/`, pois não são código de interface nem lógica executável do servidor.

## Publicação de teste no Railway

O repositório inclui `Dockerfile` e `railway.json` para executar PHP/Apache no Railway.

1. Conecte o Railway à sua conta GitHub e selecione este repositório.
2. Adicione um serviço **MySQL** ao projeto Railway.
3. Configure a aplicação com as referências do MySQL: `MYSQLHOST`, `MYSQLPORT`, `MYSQLDATABASE`, `MYSQLUSER` e `MYSQLPASSWORD` (o código já reconhece essas variáveis). Também defina `APP_URL` com o domínio público gerado (sem barra final) e `DEBUG=0`.
4. No banco MySQL recém-criado e vazio, execute `sql/railway-schema.sql` selecionando o banco da aplicação. Ele cria as tabelas, índices e views sem apagar nem criar bancos, e pode ser executado mais de uma vez.
5. Para uma demonstração com dados fictícios, importe `sql/seed.sql` somente nesse banco de teste, também com o banco selecionado. **Ele apaga os registros existentes das tabelas**. A conta demonstrativa é matrícula `BIB001`, senha `123456`; altere-a antes de qualquer uso real.

Não publique dados reais de alunos em uma instância de demonstração.
