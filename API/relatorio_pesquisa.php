<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);

require_once __DIR__ . '/conexao.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'ID inválido']);
    exit;
}

// Busca dados básicos da pesquisa
$stmt = $pdo->prepare("SELECT q.title, q.created_at, q.is_active FROM questionnaires q WHERE q.id = ?");
$stmt->execute([$id]);
$pesquisa = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$pesquisa) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Pesquisa não encontrada']);
    exit;
}

// Status
$status = 'Não iniciada';
if ($pesquisa['is_active'] == 1) $status = 'Iniciada';
if ($pesquisa['is_active'] == 2) $status = 'Finalizada';

// Total de respondentes
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT user_id) as total FROM responses WHERE questionnaire_id = ?");
$stmt->execute([$id]);
$total_respondentes = (int)($stmt->fetchColumn());

// Descobrir áreas participantes da pesquisa
$stmt = $pdo->prepare("SELECT DISTINCT area_id FROM questionnaire_questions WHERE questionnaire_id = ?");
$stmt->execute([$id]);
$areas_participantes = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Total de funcionários das áreas participantes
$total_funcionarios = 0;
if ($areas_participantes && count($areas_participantes) > 0) {
    $in = implode(',', array_fill(0, count($areas_participantes), '?'));
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE area_id IN ($in)");
    $stmt->execute($areas_participantes);
    $total_funcionarios = (int)$stmt->fetchColumn();
}

// Taxa de participação (%)
$taxa_participacao = ($total_funcionarios > 0)
    ? ($total_respondentes / $total_funcionarios * 100)
    : null;

// Setores que mais responderam
$stmt = $pdo->prepare("
    SELECT a.name as area, COUNT(DISTINCT r.user_id) as qtd
    FROM responses r
    JOIN users u ON r.user_id = u.id
    JOIN areas a ON u.area_id = a.id
    WHERE r.questionnaire_id = ?
    GROUP BY a.id
    ORDER BY qtd DESC
");
$stmt->execute([$id]);
$setores = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $setores[$row['area']] = (int)$row['qtd'];
}

// Perguntas de múltipla escolha e contagem de respostas
$stmt = $pdo->prepare("
    SELECT qq.id as qq_id, q.id as question_id, q.text
    FROM questionnaire_questions qq
    JOIN questions q ON qq.question_id = q.id
    WHERE qq.questionnaire_id = ? AND q.type = 'multipla'
    ORDER BY qq.id
");
$stmt->execute([$id]);
$multiplas = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Opções da pergunta
    $stmtOpt = $pdo->prepare("SELECT id, option_text FROM question_options WHERE question_id = ?");
    $stmtOpt->execute([$row['question_id']]);
    $opcoes = $stmtOpt->fetchAll(PDO::FETCH_ASSOC);

    // Contagem de respostas por opção
    $opcoesData = [];
    foreach ($opcoes as $op) {
        $stmtCount = $pdo->prepare("
            SELECT COUNT(*) FROM responses
            WHERE questionnaire_id = ? AND question_id = ? AND option_id = ?
        ");
        $stmtCount->execute([$id, $row['question_id'], $op['id']]);
        $qtd = (int)$stmtCount->fetchColumn();
        $opcoesData[] = [
            'id' => $op['id'],
            'texto' => $op['option_text'],
            'qtd' => $qtd
        ];
    }
    $multiplas[] = [
        'text' => $row['text'],
        'opcoes' => $opcoesData
    ];
}

// Respostas escritas
$stmt = $pdo->prepare("
    SELECT r.answer_text as texto, a.name as area
    FROM responses r
    JOIN users u ON r.user_id = u.id
    JOIN areas a ON u.area_id = a.id
    WHERE r.questionnaire_id = ? AND r.answer_text IS NOT NULL AND r.answer_text != ''
    ORDER BY r.id
");
$stmt->execute([$id]);
$escritas = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'sucesso' => true,
    'titulo' => $pesquisa['title'],
    'created_at' => $pesquisa['created_at'],
    'status' => $status,
    'total_respondentes' => $total_respondentes,
    'taxa_participacao' => $taxa_participacao,
    'setores' => $setores,
    'multiplas' => $multiplas,
    'escritas' => $escritas
]);
