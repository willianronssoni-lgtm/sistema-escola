<?php
/**
 * Autenticação da API REST por token de acesso.
 *
 * O token em claro é devolvido uma única vez ao solicitante; o banco
 * guarda apenas o hash SHA-256, como nas boas práticas de sessão.
 */

declare(strict_types=1);

const API_TOKEN_TAMANHO = 48;
const API_TOKEN_VALIDADE_DIAS = 30;

/**
 * Cria um token de acesso para um usuário e devolve o valor em claro.
 */
function api_criar_token(int $usuario_id, ?string $descricao = null): array {
    $db = db();

    $token = bin2hex(random_bytes(API_TOKEN_TAMANHO));
    $hash = hash('sha256', $token);
    $expira = date('Y-m-d H:i:s', strtotime('+' . API_TOKEN_VALIDADE_DIAS . ' days'));

    $stmt = $db->prepare("
        INSERT INTO api_tokens (usuario_id, token_hash, descricao, expira_em)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$usuario_id, $hash, $descricao, $expira]);

    return [
        'token' => $token,
        'expira_em' => $expira,
    ];
}

/**
 * Valida o token informado e devolve o usuário correspondente.
 */
function api_usuario_do_token(?string $token): ?array {
    if (!$token || $token === '') {
        return null;
    }

    $db = db();
    $stmt = $db->prepare("
        SELECT t.id AS token_id, t.expira_em, t.revogado,
               u.id, u.matricula, u.nome, u.email, u.turma, u.tipo, u.ativo, u.suspenso_ate
        FROM api_tokens t
        JOIN usuarios u ON u.id = t.usuario_id
        WHERE t.token_hash = ?
        LIMIT 1
    ");
    $stmt->execute([hash('sha256', $token)]);
    $registro = $stmt->fetch();

    if (!$registro) {
        return null;
    }

    if ((int)$registro['revogado'] === 1) {
        return null;
    }

    if ($registro['expira_em'] && $registro['expira_em'] < date('Y-m-d H:i:s')) {
        return null;
    }

    if ((int)$registro['ativo'] !== 1) {
        return null;
    }

    // Atualiza o uso do token (sem bloquear a resposta em caso de falha).
    try {
        $db->prepare("UPDATE api_tokens SET ultimo_uso = NOW() WHERE id = ?")->execute([(int)$registro['token_id']]);
    } catch (Throwable) {
        // ignora: apenas registro de uso
    }

    unset($registro['token_id'], $registro['revogado'], $registro['expira_em']);

    return $registro;
}

/**
 * Revoga o token informado (logout da API).
 */
function api_revogar_token(?string $token): bool {
    if (!$token) {
        return false;
    }

    $db = db();
    $stmt = $db->prepare("UPDATE api_tokens SET revogado = 1 WHERE token_hash = ?");
    $stmt->execute([hash('sha256', $token)]);

    return $stmt->rowCount() > 0;
}

/**
 * Extrai o token do cabeçalho Authorization ou do parâmetro access_token.
 */
function api_token_da_requisicao(?string $authorization, array $query = []): ?string {
    if ($authorization) {
        if (preg_match('/^Bearer\s+(.+)$/i', trim($authorization), $matches)) {
            return trim($matches[1]);
        }
        return trim($authorization);
    }

    if (!empty($query['access_token'])) {
        return trim((string)$query['access_token']);
    }

    return null;
}
