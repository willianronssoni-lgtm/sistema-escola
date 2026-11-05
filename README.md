# Biblioteca Escolar

Sistema web em PHP e MySQL para catálogo, reservas, fila de espera e acompanhamento de empréstimos.

## Requisitos
- PHP 8 ou superior
- MySQL 8 / MariaDB compatível
- Extensões PHP: PDO, pdo_mysql, fileinfo e mbstring

## Instalação
1. Configure o servidor web para publicar a pasta `public/`.
2. Crie a estrutura do banco:

   ```bash
   mysql -u root -p < sql/schema.sql
   ```

3. Opcionalmente, carregue dados fictícios para demonstração:

   ```bash
   mysql -u root -p biblioteca_escolar < sql/seed.sql
   ```

   **Atenção:** `schema.sql` recria o banco e `seed.sql` limpa tabelas antes de inserir exemplos. Não execute em banco com dados reais.

4. Configure variáveis de ambiente no servidor PHP:
   - `DB_HOST`: host MySQL (padrão `127.0.0.1`)
   - `DB_NAME`: banco (padrão `biblioteca_escolar`)
   - `DB_USER`: usuário (padrão `root`)
   - `DB_PASS`: senha (padrão vazio)
   - `APP_URL`: URL pública (padrão `http://localhost/biblioteca/public`)
   - `APP_NAME`: nome da aplicação
   - `APP_TIMEZONE`: fuso horário (padrão `America/Sao_Paulo`)
   - `DEBUG`: use `0` em produção; `1` somente em desenvolvimento

5. Garanta permissão de escrita em `uploads/capas`.
6. Acesse a URL definida em `APP_URL`.

Teste local rápido:
```bash
php -S 127.0.0.1:8000 -t public
```
Ajuste `APP_URL=http://127.0.0.1:8000` nesse cenário.

## Conta de demonstração
Depois de importar `sql/seed.sql`, use matrícula `BIB001` e senha `123456`. Troque a senha imediatamente e nunca use os dados fictícios em produção.

## Situação atual
A base possui autenticação, catálogo, reservas, fila e páginas do aluno. A árvore atual não contém páginas administrativas referenciadas por redirecionamentos (como `public/painel.php`) nem testes automatizados. A área administrativa e a validação integral dos fluxos ainda precisam ser concluídas antes de produção.

## Segurança
- Use usuário MySQL dedicado com privilégios mínimos, nunca `root` em produção.
- Use HTTPS em produção.
- Mantenha `DEBUG=0` em produção.
- Faça backup antes de migrações.
