<?php
/**
 * Camada HTTP da API REST: construção de respostas JSON e utilidades.
 */

declare(strict_types=1);

/**
 * Monta a resposta da API no formato esperado pelo chamador.
 *
 * @return array{status: int, headers: array<string, string>, body: array<string, mixed>}
 */
function api_resposta(int $status, array $dados, array $headers = []): array {
    return [
        'status' => $status,
        'headers' => $headers + ['Content-Type' => 'application/json; charset=utf-8'],
        'body' => $dados,
    ];
}

function api_sucesso(array $dados = [], int $status = 200, array $meta = []): array {
    $corpo = ['sucesso' => true, 'dados' => $dados];
    if ($meta) {
        $corpo['meta'] = $meta;
    }
    return api_resposta($status, $corpo);
}

function api_erro(string $mensagem, int $status = 400, array $detalhes = []): array {
    $corpo = ['sucesso' => false, 'erro' => $mensagem];
    if ($detalhes) {
        $corpo['detalhes'] = $detalhes;
    }
    return api_resposta($status, $corpo);
}

/**
 * Converte a exceção de negócio em resposta HTTP adequada.
 */
function api_erro_da_excecao(Throwable $e): array {
    if ($e instanceof PDOException) {
        error_log('API: falha no banco - ' . $e->getMessage());
        return api_erro('Não foi possível concluir a operação.', 500);
    }

    if ($e instanceof InvalidArgumentException) {
        return api_erro($e->getMessage(), 422);
    }

    if ($e instanceof RuntimeException) {
        return api_erro($e->getMessage(), 409);
    }

    error_log('API: erro inesperado - ' . $e->getMessage());
    return api_erro('Erro interno no servidor.', 500);
}

/**
 * Lê o corpo JSON da requisição.
 */
function api_corpo_json(): array {
    $bruto = file_get_contents('php://input');
    if (!$bruto) {
        return [];
    }

    $dados = json_decode($bruto, true);
    if (!is_array($dados)) {
        return [];
    }

    return $dados;
}

/**
 * Formata uma linha de reserva para a API.
 */
function api_formata_reserva(array $reserva): array {
    return [
        'id' => (int)$reserva['id'],
        'obra_id' => (int)$reserva['obra_id'],
        'obra' => $reserva['obra_titulo'] ?? ($reserva['titulo'] ?? null),
        'exemplar_id' => $reserva['exemplar_id'] !== null ? (int)$reserva['exemplar_id'] : null,
        'tombo' => $reserva['tombo'] ?? null,
        'status' => $reserva['status'],
        'posicao_fila' => $reserva['posicao_fila'] !== null ? (int)$reserva['posicao_fila'] : null,
        'data_reserva' => $reserva['data_reserva'] ?? null,
        'data_confirmacao' => $reserva['data_confirmacao'] ?? null,
        'data_expiracao' => $reserva['data_expiracao'] ?? null,
    ];
}

/**
 * Formata uma linha de empréstimo para a API.
 */
function api_formata_emprestimo(array $emprestimo): array {
    $data_devolucao = $emprestimo['data_devolucao'] ?? null;
    $atraso = 0;
    if ($data_devolucao === null && !empty($emprestimo['data_prevista_devolucao'])) {
        $atraso = max(0, (int)((strtotime(date('Y-m-d')) - strtotime($emprestimo['data_prevista_devolucao'])) / 86400));
    }

    return [
        'id' => (int)$emprestimo['id'],
        'obra_id' => isset($emprestimo['obra_id']) ? (int)$emprestimo['obra_id'] : null,
        'obra' => $emprestimo['obra_titulo'] ?? ($emprestimo['titulo'] ?? null),
        'tombo' => $emprestimo['tombo'] ?? null,
        'usuario_id' => isset($emprestimo['usuario_id']) ? (int)$emprestimo['usuario_id'] : null,
        'aluno' => $emprestimo['aluno'] ?? null,
        'data_retirada' => $emprestimo['data_retirada'] ?? null,
        'data_prevista_devolucao' => $emprestimo['data_prevista_devolucao'] ?? null,
        'data_devolucao' => $data_devolucao,
        'renovado' => (int)($emprestimo['renovado'] ?? 0) === 1,
        'dias_atraso' => $atraso,
    ];
}

/**
 * Formata uma obra para a API.
 */
function api_formata_obra(array $obra): array {
    return [
        'id' => (int)$obra['id'],
        'titulo' => $obra['titulo'],
        'autor' => $obra['autor'],
        'categoria' => $obra['categoria'] ?? null,
        'isbn' => $obra['isbn'] ?? null,
        'editora' => $obra['editora'] ?? null,
        'ano' => $obra['ano'] !== null ? (int)$obra['ano'] : null,
        'sinopse' => $obra['sinopse'] ?? null,
        'total_exemplares' => (int)($obra['total_exemplares'] ?? 0),
        'disponiveis' => (int)($obra['disponiveis'] ?? 0),
        'emprestados' => (int)($obra['emprestados'] ?? 0),
        'reservados' => (int)($obra['reservados'] ?? 0),
    ];
}
