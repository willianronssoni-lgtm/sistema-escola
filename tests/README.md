# Testes automatizados

O projeto possui uma suíte leve de testes unitários sem dependências externas.

## Executar localmente

Requisitos: PHP 8.2 ou superior.

```bash
php tests/run.php
```

Para verificar a sintaxe dos arquivos PHP:

```bash
find app public views tests -name '*.php' -print0 | xargs -0 -n1 php -l
```

O workflow **Testes PHP** executa essas verificações em cada push e pull request.

> Os testes atuais cobrem helpers, escape de HTML, formatação, token CSRF e identificação de perfis. Eles não substituem testes de integração com MySQL nem testes ponta a ponta no navegador.
