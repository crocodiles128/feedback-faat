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

$data = json_decode(file_get_contents('php://input'), true);

$questionnaire_id = intval($data['questionnaire_id'] ?? 0);
$user_id = intval($data['user_id'] ?? 0);
$area_id = intval($data['area_id'] ?? 0);
$respostas = $data['respostas'] ?? [];

if (!$questionnaire_id || !$user_id || !$area_id || !is_array($respostas) || !count($respostas)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Dados inválidos']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Registra o respondente na tabela de controle (ignora duplicidade)
    $stmt = $pdo->prepare("INSERT IGNORE INTO questionnaire_responses_control (questionnaire_id, user_id) VALUES (?, ?)");
    $stmt->execute([$questionnaire_id, $user_id]);

    // Salva as respostas (user_id do usuário que respondeu)
    foreach ($respostas as $resp) {
        $question_id = intval($resp['question_id'] ?? 0);
        if (!$question_id) continue;
        if ($resp['tipo'] === 'escrita') {
            $stmt = $pdo->prepare("INSERT INTO responses (questionnaire_id, question_id, user_id, area_id, answer_text) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$questionnaire_id, $question_id, $user_id, $area_id, $resp['answer_text']]);
        } else if ($resp['tipo'] === 'multipla') {
            $option_id = intval($resp['option_id'] ?? 0);
            $stmt = $pdo->prepare("INSERT INTO responses (questionnaire_id, question_id, user_id, area_id, option_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$questionnaire_id, $question_id, $user_id, $area_id, $option_id]);
        }
    }

    $pdo->commit();
    echo json_encode(['sucesso' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao salvar respostas: ' . $e->getMessage()]);
}
