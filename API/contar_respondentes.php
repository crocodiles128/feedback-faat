<?php
header('Content-Type: application/json');
require_once __DIR__ . '/conexao.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'ID inválido']);
    exit;
}

try {
    // Primeiro tenta contar na tabela de controle
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT user_id) as respondentes FROM questionnaire_responses_control WHERE questionnaire_id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    $respondentes = intval($row['respondentes']);

    // Se não houver registros na tabela de controle, faz fallback para responses
    if ($respondentes === 0) {
        $stmt2 = $pdo->prepare("SELECT COUNT(DISTINCT user_id) as respondentes FROM responses WHERE questionnaire_id = ? AND user_id IS NOT NULL");
        $stmt2->execute([$id]);
        $row2 = $stmt2->fetch();
        $respondentes = intval($row2['respondentes']);
    }

    echo json_encode([
        "sucesso" => true,
        "respondentes" => $respondentes
    ]);
} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao contar respondentes: ' . $e->getMessage()]);
}
