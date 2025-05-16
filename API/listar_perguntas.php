<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
set_exception_handler(function($e) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro interno: ' . $e->getMessage()]);
    exit;
});
set_error_handler(function($errno, $errstr) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'mensagem' => "Erro interno: $errstr"]);
    exit;
});

require_once __DIR__ . '/conexao.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'ID inválido']);
    exit;
}

// Busca perguntas do questionário
$stmt = $pdo->prepare("
    SELECT qq.id as qq_id, q.id as question_id, q.text, q.type
    FROM questionnaire_questions qq
    JOIN questions q ON qq.question_id = q.id
    WHERE qq.questionnaire_id = ?
    ORDER BY qq.id
");
$stmt->execute([$id]);
$perguntas = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $pergunta = [
        'id' => $row['question_id'],
        'text' => $row['text'],
        'tipo' => $row['type'], // <-- Corrija para 'tipo' para compatibilidade com o frontend
        'opcoes' => []
    ];
    if ($row['type'] === 'multipla') {
        $stmt2 = $pdo->prepare("SELECT id, option_text FROM question_options WHERE question_id = ?");
        $stmt2->execute([$row['question_id']]);
        $pergunta['opcoes'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    }
    $perguntas[] = $pergunta;
}

echo json_encode(['sucesso' => true, 'perguntas' => $perguntas]);
