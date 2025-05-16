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

if (
    !isset($data['titulo']) ||
    !isset($data['areas']) ||
    !is_array($data['areas']) ||
    !isset($data['perguntas']) ||
    !is_array($data['perguntas'])
) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Dados inválidos']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Ajuste conforme sua autenticação, aqui fixo para exemplo
    $created_by = 1;

    // 1. Cria o questionário
    $stmt = $pdo->prepare("INSERT INTO questionnaires (title, created_by) VALUES (?, ?)");
    $stmt->execute([$data['titulo'], $created_by]);
    $questionnaire_id = $pdo->lastInsertId();

    foreach ($data['perguntas'] as $pergunta) {
        // 2. Cria a pergunta
        $stmt = $pdo->prepare("INSERT INTO questions (text, type) VALUES (?, ?)");
        $stmt->execute([$pergunta['text'], $pergunta['tipo']]);
        $question_id = $pdo->lastInsertId();

        // 3. Se for múltipla escolha, salva as opções
        if ($pergunta['tipo'] === 'multipla' && !empty($pergunta['opcoes'])) {
            $stmtOpt = $pdo->prepare("INSERT INTO question_options (question_id, option_text) VALUES (?, ?)");
            foreach ($pergunta['opcoes'] as $opcao) {
                $stmtOpt->execute([$question_id, $opcao]);
            }
        }

        // 4. Liga a pergunta ao questionário para cada área selecionada
        foreach ($data['areas'] as $area_id) {
            $stmt = $pdo->prepare("INSERT INTO questionnaire_questions (questionnaire_id, question_id, area_id) VALUES (?, ?, ?)");
            $stmt->execute([$questionnaire_id, $question_id, $area_id]);
        }
    }

    $pdo->commit();
    echo json_encode(['sucesso' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao salvar: ' . $e->getMessage()]);
}
?>
