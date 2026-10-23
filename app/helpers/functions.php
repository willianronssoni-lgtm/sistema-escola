<?php
/**
 * Funções auxiliares do sistema
 */

declare(strict_types=1);

// ============================================================
// SEGURANÇA E SANITIZAÇÃO
// ============================================================

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function csfr_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csfr_verify(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csfr_input(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csfr_token()) . '">';
}

// ============================================================
// AUTENTICAÇÃO E AUTORIZAÇÃO
// ============================================================

function usuario_logado(): ?array {
    return $_SESSION['usuario'] ?? null;
}

function usuario_id(): ?int {
    return $_SESSION['usuario']['id'] ?? null;
}

function usuario_tipo(): ?string {
    return $_SESSION['usuario']['tipo'] ?? null;
}

function is_aluno(): bool {
    return usuario_tipo() === 'aluno';
}

function is_bibliotecario(): bool {
    return usuario_tipo() === 'bibliotecario';
}

function require_login(): void {
    if (!usuario_logado()) {
        redirect('/login.php');
    }
}

function require_aluno(): void {
    require_login();
    if (!is_aluno()) {
        redirect('/painel.php');
    }
}

function require_bibliotecario(): void {
    require_login();
    if (!is_bibliotecario()) {
        redirect('/index.php');
    }
}

function usuario_pode_emprestar(int $usuario_id): array {
    $db = db();
    
    // Verificar suspensão
    $stmt = $db->prepare("SELECT suspenso_ate FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $user = $stmt->fetch();
    
    if ($user && $user['suspenso_ate'] && $user['suspenso_ate'] >= date('Y-m-d')) {
        return ['pode' => false, 'motivo' => 'Usuário suspenso até ' . format_date($user['suspenso_ate'])];
    }
    
    // Verificar empréstimos ativos
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM emprestimos WHERE usuario_id = ? AND data_devolucao IS NULL");
    $stmt->execute([$usuario_id]);
    $ativos = (int)$stmt->fetchColumn();
    
    if ($ativos >= MAX_EMPRESTIMOS_SIMULTANEOS) {
        return ['pode' => false, 'motivo' => "Limite de $MAX_EMPRESTIMOS_SIMULTANEOS empréstimos simultâneos atingido"];
    }
    
    // Verificar empréstimos atrasados
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM emprestimos WHERE usuario_id = ? AND data_devolucao IS NULL AND data_prevista_devolucao < CURDATE()");
    $stmt->execute([$usuario_id]);
    $atrasados = (int)$stmt->fetchColumn();
    
    if ($atrasados > 0) {
        return ['pode' => false, 'motivo' => 'Possui empréstimo(s) em atraso'];
    }
    
    return ['pode' => true, 'motivo' => ''];
}

// ============================================================
// FORMATAÇÃO
// ============================================================

function format_date(?string $date, string $format = 'd/m/Y'): string {
    if (!$date) return '-';
    try {
        return (new DateTime($date))->format($format);
    } catch (Exception) {
        return '-';
    }
}

function format_datetime(?string $datetime, string $format = 'd/m/Y H:i'): string {
    if (!$datetime) return '-';
    try {
        return (new DateTime($datetime))->format($format);
    } catch (Exception) {
        return '-';
    }
}

function tempo_restante(string $data_futura): string {
    $agora = new DateTime();
    $futuro = new DateTime($data_futura);
    $diff = $agora->diff($futuro);
    
    if ($diff->invert) {
        return 'Expirado';
    }
    
    $partes = [];
    if ($diff->d > 0) $partes[] = "{$diff->d} dia(s)";
    if ($diff->h > 0) $partes[] = "{$diff->h} hora(s)";
    if ($diff->i > 0) $partes[] = "{$diff->i} min";
    
    return $partes ? implode(' ', $partes) : 'Menos de 1 min';
}

function status_badge(string $status): string {
    $classes = [
        'disponivel' => 'bg-success',
        'emprestado' => 'bg-danger',
        'reservado' => 'bg-warning text-dark',
        'manutencao' => 'bg-secondary',
        'pendente' => 'bg-info',
        'confirmada' => 'bg-primary',
        'na_fila' => 'bg-warning text-dark',
        'expirada' => 'bg-secondary',
        'cancelada' => 'bg-dark',
        'atendida' => 'bg-success',
    ];
    
    $labels = [
        'disponivel' => 'Disponível',
        'emprestado' => 'Emprestado',
        'reservado' => 'Reservado',
        'manutencao' => 'Manutenção',
        'pendente' => 'Pendente',
        'confirmada' => 'Confirmada',
        'na_fila' => 'Na fila',
        'expirada' => 'Expirada',
        'cancelada' => 'Cancelada',
        'atendida' => 'Atendida',
    ];
    
    $class = $classes[$status] ?? 'bg-secondary';
    $label = $labels[$status] ?? ucfirst($status);
    
    return "<span class=\"badge $class\">$label</span>";
}

function nome_abreviado(string $nome): string {
    $partes = explode(' ', trim($nome));
    if (count($partes) <= 1) return $nome;
    $primeiro = $partes[0];
    $ultimo = end($partes);
    return "$primeiro " . mb_substr($ultimo, 0, 1) . '.';
}

// ============================================================
// BANCO DE DADOS - HELPERS
// ============================================================

function buscar_obras(array $filtros = [], int $limite = 20, int $offset = 0): array {
    $db = db();
    $where = [];
    $params = [];
    
    if (!empty($filtros['busca'])) {
        $where[] = "(o.titulo LIKE ? OR o.autor LIKE ? OR o.isbn LIKE ?)";
        $termo = "%{$filtros['busca']}%";
        $params[] = $termo;
        $params[] = $termo;
        $params[] = $termo;
    }
    
    if (!empty($filtros['categoria'])) {
        $where[] = "o.categoria = ?";
        $params[] = $filtros['categoria'];
    }
    
    if (isset($filtros['so_disponiveis']) && $filtros['so_disponiveis']) {
        $where[] = "EXISTS (SELECT 1 FROM exemplares e WHERE e.obra_id = o.id AND e.status = 'disponivel')";
    }
    
    $sql = "
        SELECT o.*, 
            COUNT(e.id) as total_exemplares,
            SUM(e.status = 'disponivel') as disponiveis,
            SUM(e.status = 'emprestado') as emprestados,
            SUM(e.status = 'reservado') as reservados
        FROM obras o
        LEFT JOIN exemplares e ON e.obra_id = o.id
    ";
    
    if ($where) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }
    
    $sql .= " GROUP BY o.id ORDER BY o.titulo LIMIT ? OFFSET ?";
    $params[] = $limite;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function contar_obras(array $filtros = []): int {
    $db = db();
    $where = [];
    $params = [];
    
    if (!empty($filtros['busca'])) {
        $where[] = "(titulo LIKE ? OR autor LIKE ? OR isbn LIKE ?)";
        $termo = "%{$filtros['busca']}%";
        $params[] = $termo;
        $params[] = $termo;
        $params[] = $termo;
    }
    
    if (!empty($filtros['categoria'])) {
        $where[] = "categoria = ?";
        $params[] = $filtros['categoria'];
    }
    
    if (isset($filtros['so_disponiveis']) && $filtros['so_disponiveis']) {
        $where[] = "EXISTS (SELECT 1 FROM exemplares e WHERE e.obra_id = obras.id AND e.status = 'disponivel')";
    }
    
    $sql = "SELECT COUNT(*) FROM obras";
    if ($where) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

function buscar_obra_por_id(int $id): ?array {
    $db = db();
    $stmt = $db->prepare("
        SELECT o.*, 
            COUNT(e.id) as total_exemplares,
            SUM(e.status = 'disponivel') as disponiveis,
            SUM(e.status = 'emprestado') as emprestados,
            SUM(e.status = 'reservado') as reservados,
            SUM(e.status = 'manutencao') as manutencao
        FROM obras o
        LEFT JOIN exemplares e ON e.obra_id = o.id
        WHERE o.id = ?
        GROUP BY o.id
    ");
    $stmt->execute([$id]);
    $obra = $stmt->fetch();
    
    if (!$obra) return null;
    
    // Buscar exemplares
    $stmt = $db->prepare("SELECT * FROM exemplares WHERE obra_id = ? ORDER BY tombo");
    $stmt->execute([$id]);
    $obra['exemplares'] = $stmt->fetchAll();
    
    return $obra;
}

function buscar_categorias(): array {
    $db = db();
    $stmt = $db->query("SELECT DISTINCT categoria FROM obras WHERE categoria IS NOT NULL AND categoria != '' ORDER BY categoria");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function buscar_exemplar_disponivel(int $obra_id): ?array {
    $db = db();
    $stmt = $db->prepare("SELECT * FROM exemplares WHERE obra_id = ? AND status = 'disponivel' ORDER BY id LIMIT 1");
    $stmt->execute([$obra_id]);
    return $stmt->fetch() ?: null;
}

function posicao_fila(int $obra_id, int $usuario_id): ?int {
    $db = db();
    $stmt = $db->prepare("
        SELECT COUNT(*) + 1 as posicao 
        FROM reservas 
        WHERE obra_id = ? AND status = 'na_fila' AND data_reserva < (
            SELECT data_reserva FROM reservas WHERE obra_id = ? AND usuario_id = ? AND status = 'na_fila'
        )
    ");
    $stmt->execute([$obra_id, $obra_id, $usuario_id]);
    return $stmt->fetchColumn() ?: null;
}

function proximo_da_fila(int $obra_id): ?array {
    $db = db();
    $stmt = $db->prepare("
        SELECT r.*, u.nome, u.matricula, u.turma
        FROM reservas r
        JOIN usuarios u ON u.id = r.usuario_id
        WHERE r.obra_id = ? AND r.status = 'na_fila'
        ORDER BY r.data_reserva
        LIMIT 1
    ");
    $stmt->execute([$obra_id]);
    return $stmt->fetch() ?: null;
}

// ============================================================
// NOTIFICAÇÕES
// ============================================================

function criar_notificacao(int $usuario_id, string $tipo, string $titulo, string $mensagem, string $link = ''): void {
    $db = db();
    $stmt = $db->prepare("
        INSERT INTO notificacoes (usuario_id, tipo, titulo, mensagem, link)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$usuario_id, $tipo, $titulo, $mensagem, $link]);
}

function notificacoes_nao_lidas(int $usuario_id): int {
    $db = db();
    $stmt = $db->prepare("SELECT COUNT(*) FROM notificacoes WHERE usuario_id = ? AND lida = 0");
    $stmt->execute([$usuario_id]);
    return (int)$stmt->fetchColumn();
}

// ============================================================
// LOGS
// ============================================================

function registrar_log(string $acao, ?string $entidade = null, ?int $entidade_id = null, array $detalhes = []): void {
    $db = db();
    $stmt = $db->prepare("
        INSERT INTO logs (usuario_id, acao, entidade, entidade_id, detalhes, ip, user_agent)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        usuario_id(),
        $acao,
        $entidade,
        $entidade_id,
        $detalhes ? json_encode($detalhes, JSON_UNESCAPED_UNICODE) : null,
        $_SERVER['REMOTE_ADDR'] ?? 'cli',
        $_SERVER['HTTP_USER_AGENT'] ?? 'cli'
    ]);
}

// ============================================================
// UPLOAD
// ============================================================

function upload_capa(array $file): array {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['sucesso' => false, 'erro' => 'Erro no upload'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['sucesso' => false, 'erro' => 'Arquivo muito grande (máx. 2MB)'];
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
        return ['sucesso' => false, 'erro' => 'Formato não permitido (jpg, png, webp)'];
    }
    
    // Verificar MIME type real
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mime, $allowed_mimes)) {
        return ['sucesso' => false, 'erro' => 'Tipo de arquivo inválido'];
    }
    
    $nome = 'capa_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $destino = CAPAS_PATH . '/' . $nome;
    
    if (!move_uploaded_file($file['tmp_name'], $destino)) {
        return ['sucesso' => false, 'erro' => 'Falha ao salvar arquivo'];
    }
    
    return ['sucesso' => true, 'arquivo' => $nome];
}

function deletar_capa(?string $arquivo): void {
    if ($arquivo && file_exists(CAPAS_PATH . '/' . $arquivo)) {
        unlink(CAPAS_PATH . '/' . $arquivo);
    }
}

// ============================================================
// REDIRECT E FLASH MESSAGES
// ============================================================

function redirect(string $url): never {
    header("Location: $url");
    exit;
}

function flash(string $tipo, string $mensagem): void {
    $_SESSION['flash'][] = ['tipo' => $tipo, 'mensagem' => $mensagem];
}

function get_flashes(): array {
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

function flash_html(): string {
    $html = '';
    foreach (get_flashes() as $flash) {
        $alert_class = match($flash['tipo']) {
            'sucesso' => 'alert-success',
            'erro' => 'alert-danger',
            'aviso' => 'alert-warning',
            'info' => 'alert-info',
            default => 'alert-info'
        };
        $html .= "<div class=\"alert $alert_class alert-dismissible fade show\" role=\"alert\">
            {$flash['mensagem']}
            <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\"></button>
        </div>";
    }
    return $html;
}

// ============================================================
// PAGINAÇÃO
// ============================================================

function paginacao(int $total, int $por_pagina, int $pagina_atual, string $base_url, array $params = []): string {
    $total_paginas = ceil($total / $por_pagina);
    if ($total_paginas <= 1) return '';
    
    $query = http_build_query($params);
    $query = $query ? '?' . $query : '';
    
    $html = '<nav><ul class="pagination pagination-sm justify-content-center">';
    
    // Anterior
    if ($pagina_atual > 1) {
        $html .= "<li class=\"page-item\"><a class=\"page-link\" href=\"{$base_url}{$query}&page=" . ($pagina_atual - 1) . "\">Anterior</a></li>";
    }
    
    // Páginas
    $inicio = max(1, $pagina_atual - 2);
    $fim = min($total_paginas, $pagina_atual + 2);
    
    if ($inicio > 1) {
        $html .= "<li class=\"page-item\"><a class=\"page-link\" href=\"{$base_url}{$query}&page=1\">1</a></li>";
        if ($inicio > 2) $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
    }
    
    for ($i = $inicio; $i <= $fim; $i++) {
        $active = $i === $pagina_atual ? ' active' : '';
        $html .= "<li class=\"page-item$active\"><a class=\"page-link\" href=\"{$base_url}{$query}&page=$i\">$i</a></li>";
    }
    
    if ($fim < $total_paginas) {
        if ($fim < $total_paginas - 1) $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        $html .= "<li class=\"page-item\"><a class=\"page-link\" href=\"{$base_url}{$query}&page=$total_paginas\">$total_paginas</a></li>";
    }
    
    // Próxima
    if ($pagina_atual < $total_paginas) {
        $html .= "<li class=\"page-item\"><a class=\"page-link\" href=\"{$base_url}{$query}&page=" . ($pagina_atual + 1) . "\">Próxima</a></li>";
    }
    
    $html .= '</ul></nav>';
    return $html;
}