<?php
/**
 * Roteador da API REST (versão 1).
 *
 * Toda rota devolve JSON no formato:
 *   sucesso: {"sucesso": true, "dados": {...}, "meta": {...}}
 *   erro:    {"sucesso": false, "erro": "mensagem"}
 */

declare(strict_types=1);

const API_VERSAO = 'v1';

/**
 * Remove a versão e as barras do caminho da rota.
 */
function api_normaliza_rota(string $caminho): string {
    $caminho = trim($caminho, '/');
    if ($caminho === '') {
        return '';
    }

    if (str_starts_with($caminho, API_VERSAO . '/')) {
        $caminho = substr($caminho, strlen(API_VERSAO) + 1);
    } elseif ($caminho === API_VERSAO) {
        $caminho = '';
    }

    return trim($caminho, '/');
}

/**
 * Executa a rota e devolve a resposta pronta para envio.
 *
 * @param array<string, mixed> $body
 * @param array<string, mixed> $query
 */
function api_dispatch(string $metodo, string $caminho, array $body = [], array $query = [], ?string $token = null): array {
    $metodo = strtoupper($metodo);
    $rota = api_normaliza_rota($caminho);
    $partes = $rota === '' ? [] : explode('/', $rota);

    try {
        // --------------------------------------------------
        // Rotas públicas
        // --------------------------------------------------
        if ($rota === 'saude' && $metodo === 'GET') {
            return api_sucesso([
                'servico' => APP_NAME,
                'versao' => APP_VERSION,
                'api' => API_VERSAO,
                'banco' => 'disponivel',
            ]);
        }

        if ($rota === 'auth/login' && $metodo === 'POST') {
            return api_login($body);
        }

        // --------------------------------------------------
        // A partir daqui é obrigatório ter token
        // --------------------------------------------------
        $usuario = api_usuario_do_token($token);
        if (!$usuario) {
            return api_erro('Token ausente, inválido ou expirado.', 401);
        }

        if ($usuario['tipo'] === 'aluno' && !empty($usuario['suspenso_ate']) && $usuario['suspenso_ate'] >= date('Y-m-d')) {
            return api_erro('Conta suspensa até ' . format_date($usuario['suspenso_ate']) . '.', 403);
        }

        // A API não usa sessão: o ator autenticado pelo token é quem
        // responde às verificações de permissão e à auditoria.
        $GLOBALS['api_ator'] = [
            'id' => (int)$usuario['id'],
            'tipo' => (string)$usuario['tipo'],
        ];

        if ($rota === 'auth/logout' && $metodo === 'POST') {
            api_revogar_token($token);
            return api_sucesso(['mensagem' => 'Token revogado.']);
        }

        if ($rota === 'auth/eu' && $metodo === 'GET') {
            return api_sucesso(api_formata_usuario($usuario));
        }

        // --------------------------------------------------
        // Área do aluno
        // --------------------------------------------------
        if ($rota === 'catalogo' && $metodo === 'GET') {
            return api_catalogo($query);
        }

        if ($partes[0] === 'obras' && count($partes) === 2 && $metodo === 'GET') {
            return api_detalhe_obra((int)$partes[1]);
        }

        if ($rota === 'meus/emprestimos' && $metodo === 'GET') {
            return api_meus_emprestimos((int)$usuario['id']);
        }

        if ($rota === 'meus/reservas' && $metodo === 'GET') {
            return api_meus_reservas((int)$usuario['id']);
        }

        if ($rota === 'reservas' && $metodo === 'POST') {
            return api_reservar($body, (int)$usuario['id']);
        }

        if (count($partes) === 3 && $partes[0] === 'reservas' && $partes[2] === 'cancelar' && $metodo === 'POST') {
            return api_cancelar_reserva((int)$partes[1], (int)$usuario['id'], $usuario['tipo'] === 'bibliotecario');
        }

        if ($rota === 'fila' && $metodo === 'POST') {
            return api_entrar_na_fila($body, (int)$usuario['id']);
        }

        if (count($partes) === 3 && $partes[0] === 'emprestimos' && $partes[2] === 'renovar' && $metodo === 'POST') {
            return api_renovar((int)$partes[1], (int)$usuario['id'], $usuario['tipo'] === 'bibliotecario');
        }

        if ($rota === 'notificacoes' && $metodo === 'GET') {
            return api_notificacoes((int)$usuario['id']);
        }

        // --------------------------------------------------
        // Área do bibliotecário
        // --------------------------------------------------
        if (count($partes) >= 2 && $partes[0] === 'admin') {
            if ($usuario['tipo'] !== 'bibliotecario') {
                return api_erro('Acesso restrito ao bibliotecário.', 403);
            }

            return api_admin($partes, $metodo, $body, $query, $usuario);
        }

        return api_erro('Rota não encontrada: ' . $rota, 404);
    } catch (Throwable $e) {
        return api_erro_da_excecao($e);
    }
}

// ============================================================
// Handlers
// ============================================================

function api_formata_usuario(array $usuario): array {
    return [
        'id' => (int)$usuario['id'],
        'matricula' => $usuario['matricula'],
        'nome' => $usuario['nome'],
        'email' => $usuario['email'] ?? null,
        'turma' => $usuario['turma'] ?? null,
        'tipo' => $usuario['tipo'],
        'suspenso_ate' => $usuario['suspenso_ate'] ?? null,
    ];
}

function api_login(array $body): array {
    $matricula = trim((string)($body['matricula'] ?? ''));
    $senha = (string)($body['senha'] ?? '');

    if ($matricula === '' || $senha === '') {
        return api_erro('Informe matrícula e senha.', 422);
    }

    $resultado = login($matricula, $senha);
    if (!$resultado['sucesso']) {
        return api_erro($resultado['erro'], 401);
    }

    $usuario = $resultado['usuario'];
    $novo = api_criar_token((int)$usuario['id'], trim((string)($body['descricao'] ?? 'api')));

    return api_sucesso([
        'token' => $novo['token'],
        'expira_em' => $novo['expira_em'],
        'usuario' => api_formata_usuario($usuario),
    ]);
}

function api_catalogo(array $query): array {
    $busca = trim((string)($query['q'] ?? ''));
    $categoria = trim((string)($query['categoria'] ?? ''));
    $pagina = max(1, (int)($query['pagina'] ?? 1));
    $por_pagina = min(50, max(1, (int)($query['por_pagina'] ?? 20)));

    $filtros = [
        'busca' => $busca,
        'categoria' => $categoria,
        'so_disponiveis' => !empty($query['disponiveis']),
    ];

    $total = contar_obras($filtros);
    $obras = buscar_obras($filtros, $por_pagina, ($pagina - 1) * $por_pagina);

    return api_sucesso(
        array_map('api_formata_obra', $obras),
        200,
        [
            'pagina' => $pagina,
            'por_pagina' => $por_pagina,
            'total' => $total,
            'total_paginas' => (int)ceil($total / $por_pagina),
        ]
    );
}

function api_detalhe_obra(int $obra_id): array {
    $obra = buscar_obra_por_id($obra_id);
    if (!$obra) {
        return api_erro('Obra não encontrada.', 404);
    }

    $dados = api_formata_obra($obra);
    $dados['exemplares'] = array_map(static function (array $exemplar): array {
        return [
            'id' => (int)$exemplar['id'],
            'tombo' => $exemplar['tombo'],
            'status' => $exemplar['status'],
            'localizacao' => $exemplar['localizacao'] ?? null,
        ];
    }, $obra['exemplares']);

    return api_sucesso($dados);
}

function api_meus_emprestimos(int $usuario_id): array {
    $db = db();
    $stmt = $db->prepare("
        SELECT e.*, ex.tombo, ex.obra_id, o.titulo AS obra_titulo
        FROM emprestimos e
        JOIN exemplares ex ON ex.id = e.exemplar_id
        JOIN obras o ON o.id = ex.obra_id
        WHERE e.usuario_id = ?
        ORDER BY e.data_retirada DESC, e.id DESC
    ");
    $stmt->execute([$usuario_id]);

    $ativos = [];
    $historico = [];
    foreach ($stmt->fetchAll() as $emprestimo) {
        if ($emprestimo['data_devolucao'] === null) {
            $ativos[] = api_formata_emprestimo($emprestimo);
        } else {
            $historico[] = api_formata_emprestimo($emprestimo);
        }
    }

    return api_sucesso(['ativos' => $ativos, 'historico' => $historico]);
}

function api_meus_reservas(int $usuario_id): array {
    $db = db();
    $stmt = $db->prepare("
        SELECT r.*, o.titulo AS obra_titulo, x.tombo
        FROM reservas r
        JOIN obras o ON o.id = r.obra_id
        LEFT JOIN exemplares x ON x.id = r.exemplar_id
        WHERE r.usuario_id = ?
        ORDER BY r.data_reserva DESC, r.id DESC
    ");
    $stmt->execute([$usuario_id]);

    $reservas = array_map('api_formata_reserva', $stmt->fetchAll());

    return api_sucesso($reservas);
}

function api_reservar(array $body, int $usuario_id): array {
    $obra_id = (int)($body['obra_id'] ?? 0);
    if (!$obra_id) {
        return api_erro('Informe obra_id.', 422);
    }

    $resultado = servico_reservar_obra($obra_id, $usuario_id);

    return api_sucesso([
        'reserva_id' => $resultado['id'],
        'obra_id' => $obra_id,
        'status' => 'confirmada',
        'data_expiracao' => $resultado['expiracao'],
    ], 201);
}

function api_cancelar_reserva(int $reserva_id, int $usuario_id, bool $bibliotecario): array {
    if (!$reserva_id) {
        return api_erro('Informe o identificador da reserva.', 422);
    }

    $resultado = servico_cancelar_reserva($reserva_id, $usuario_id, $bibliotecario);

    return api_sucesso([
        'reserva_id' => $reserva_id,
        'status' => 'cancelada',
        'proximo_promovido' => $resultado['promovido'],
    ]);
}

function api_entrar_na_fila(array $body, int $usuario_id): array {
    $obra_id = (int)($body['obra_id'] ?? 0);
    if (!$obra_id) {
        return api_erro('Informe obra_id.', 422);
    }

    $resultado = servico_entrar_na_fila($obra_id, $usuario_id);

    return api_sucesso([
        'reserva_id' => $resultado['id'],
        'obra_id' => $obra_id,
        'status' => 'na_fila',
        'posicao' => $resultado['posicao'],
    ], 201);
}

function api_renovar(int $emprestimo_id, int $usuario_id, bool $bibliotecario): array {
    if (!$emprestimo_id) {
        return api_erro('Informe o identificador do empréstimo.', 422);
    }

    $resultado = servico_renovar_emprestimo($emprestimo_id, $usuario_id, $bibliotecario);
    if (!$resultado['sucesso']) {
        return api_erro($resultado['erro'], 409);
    }

    return api_sucesso([
        'emprestimo_id' => $emprestimo_id,
        'data_prevista_devolucao' => $resultado['data_prevista'],
    ]);
}

function api_notificacoes(int $usuario_id): array {
    $db = db();
    $stmt = $db->prepare("
        SELECT id, tipo, titulo, mensagem, lida, link, criada_em
        FROM notificacoes
        WHERE usuario_id = ?
        ORDER BY criada_em DESC, id DESC
        LIMIT 100
    ");
    $stmt->execute([$usuario_id]);

    $notificacoes = array_map(static function (array $n): array {
        return [
            'id' => (int)$n['id'],
            'tipo' => $n['tipo'],
            'titulo' => $n['titulo'],
            'mensagem' => $n['mensagem'],
            'lida' => (int)$n['lida'] === 1,
            'link' => $n['link'],
            'criada_em' => $n['criada_em'],
        ];
    }, $stmt->fetchAll());

    return api_sucesso($notificacoes);
}

/**
 * Rotas administrativas (exigem perfil de bibliotecário).
 */
function api_admin(array $partes, string $metodo, array $body, array $query, array $usuario): array {
    $recurso = $partes[1];

    if ($recurso === 'alunos' && $metodo === 'GET') {
        $db = db();
        $busca = trim((string)($query['q'] ?? ''));
        $where = ["u.tipo = 'aluno'"];
        $params = [];

        if ($busca !== '') {
            $where[] = '(u.nome LIKE ? OR u.matricula LIKE ? OR u.turma LIKE ?)';
            $termo = "%{$busca}%";
            array_push($params, $termo, $termo, $termo);
        }

        $stmt = $db->prepare("
            SELECT u.id, u.matricula, u.nome, u.email, u.turma, u.tipo, u.ativo, u.suspenso_ate
            FROM usuarios u
            WHERE " . implode(' AND ', $where) . '
            ORDER BY u.nome
        ');
        $stmt->execute($params);

        $alunos = array_map(static function (array $a): array {
            return [
                'id' => (int)$a['id'],
                'matricula' => $a['matricula'],
                'nome' => $a['nome'],
                'email' => $a['email'] ?? null,
                'turma' => $a['turma'] ?? null,
                'ativo' => (int)$a['ativo'] === 1,
                'suspenso_ate' => $a['suspenso_ate'] ?? null,
            ];
        }, $stmt->fetchAll());

        return api_sucesso($alunos);
    }

    if ($recurso === 'alunos' && $metodo === 'POST') {
        $resultado = criar_usuario([
            'matricula' => trim((string)($body['matricula'] ?? '')),
            'nome' => trim((string)($body['nome'] ?? '')),
            'email' => trim((string)($body['email'] ?? '')),
            'turma' => trim((string)($body['turma'] ?? '')),
            'senha' => (string)($body['senha'] ?? ''),
            'tipo' => 'aluno',
        ]);

        if (!$resultado['sucesso']) {
            return api_erro($resultado['erro'], 422);
        }

        return api_sucesso(['aluno_id' => $resultado['id']], 201);
    }

    if ($recurso === 'emprestimos' && $metodo === 'GET') {
        $db = db();
        $stmt = $db->query("
            SELECT emp.*, u.nome AS aluno, o.titulo AS obra_titulo, x.tombo
            FROM emprestimos emp
            JOIN usuarios u ON u.id = emp.usuario_id
            JOIN exemplares x ON x.id = emp.exemplar_id
            JOIN obras o ON o.id = x.obra_id
            ORDER BY emp.data_retirada DESC, emp.id DESC
            LIMIT 200
        ");

        return api_sucesso(array_map('api_formata_emprestimo', $stmt->fetchAll()));
    }

    if ($recurso === 'emprestimos' && $metodo === 'POST') {
        $aluno_id = (int)($body['aluno_id'] ?? 0);
        $exemplar_id = (int)($body['exemplar_id'] ?? 0);

        if (!$aluno_id || !$exemplar_id) {
            return api_erro('Informe aluno_id e exemplar_id.', 422);
        }

        $resultado = servico_registrar_emprestimo($aluno_id, $exemplar_id, (int)$usuario['id']);

        return api_sucesso([
            'emprestimo_id' => $resultado['id'],
            'data_prevista_devolucao' => $resultado['data_prevista'],
        ], 201);
    }

    if ($recurso === 'devolucoes' && $metodo === 'POST') {
        $emprestimo_id = (int)($body['emprestimo_id'] ?? 0);
        if (!$emprestimo_id) {
            return api_erro('Informe emprestimo_id.', 422);
        }

        $resultado = servico_registrar_devolucao($emprestimo_id);

        return api_sucesso([
            'emprestimo_id' => $emprestimo_id,
            'dias_atraso' => $resultado['dias_atraso'],
            'suspensao_ate' => $resultado['suspensao_ate'],
        ]);
    }

    if ($recurso === 'reservas' && $metodo === 'GET') {
        $db = db();
        $stmt = $db->query("
            SELECT r.*, u.nome AS aluno, o.titulo AS obra_titulo, x.tombo
            FROM reservas r
            JOIN usuarios u ON u.id = r.usuario_id
            JOIN obras o ON o.id = r.obra_id
            LEFT JOIN exemplares x ON x.id = r.exemplar_id
            ORDER BY r.data_reserva DESC, r.id DESC
            LIMIT 200
        ");

        return api_sucesso(array_map('api_formata_reserva', $stmt->fetchAll()));
    }

    if (count($partes) === 3 && $partes[2] === 'confirmar' && $metodo === 'POST') {
        $resultado = servico_confirmar_reserva((int)$partes[1]);
        if (!$resultado['sucesso']) {
            return api_erro($resultado['erro'], 409);
        }

        return api_sucesso([
            'reserva_id' => $resultado['id'],
            'status' => 'confirmada',
            'data_expiracao' => $resultado['expiracao'],
        ]);
    }

    if (count($partes) === 3 && $partes[2] === 'cancelar' && $metodo === 'POST') {
        $resultado = servico_cancelar_reserva((int)$partes[1], (int)$usuario['id'], true);

        return api_sucesso([
            'reserva_id' => (int)$partes[1],
            'status' => 'cancelada',
            'proximo_promovido' => $resultado['promovido'],
        ]);
    }

    if ($recurso === 'relatorios' && $metodo === 'GET') {
        $db = db();

        $totais = [
            'alunos' => (int)$db->query("SELECT COUNT(*) FROM usuarios WHERE tipo = 'aluno'")->fetchColumn(),
            'obras' => (int)$db->query('SELECT COUNT(*) FROM obras')->fetchColumn(),
            'exemplares' => (int)$db->query('SELECT COUNT(*) FROM exemplares')->fetchColumn(),
            'disponiveis' => (int)$db->query("SELECT COUNT(*) FROM exemplares WHERE status = 'disponivel'")->fetchColumn(),
            'emprestados' => (int)$db->query("SELECT COUNT(*) FROM exemplares WHERE status = 'emprestado'")->fetchColumn(),
            'emprestimos_ativos' => (int)$db->query('SELECT COUNT(*) FROM emprestimos WHERE data_devolucao IS NULL')->fetchColumn(),
            'atrasados' => (int)$db->query('SELECT COUNT(*) FROM emprestimos WHERE data_devolucao IS NULL AND data_prevista_devolucao < CURDATE()')->fetchColumn(),
            'fila_espera' => (int)$db->query("SELECT COUNT(*) FROM reservas WHERE status = 'na_fila'")->fetchColumn(),
            'suspensos' => (int)$db->query('SELECT COUNT(*) FROM usuarios WHERE suspenso_ate IS NOT NULL AND suspenso_ate >= CURDATE()')->fetchColumn(),
        ];

        $maisEmprestados = $db->query("
            SELECT o.titulo, o.autor, COUNT(emp.id) AS total
            FROM emprestimos emp
            JOIN exemplares x ON x.id = emp.exemplar_id
            JOIN obras o ON o.id = x.obra_id
            GROUP BY o.id
            ORDER BY total DESC, o.titulo
            LIMIT 10
        ")->fetchAll();

        return api_sucesso(['totais' => $totais, 'mais_emprestados' => $maisEmprestados]);
    }

    return api_erro('Rota administrativa não encontrada.', 404);
}
