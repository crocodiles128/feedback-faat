<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);

require_once __DIR__ . '/conexao.php';

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if ($id <= 0) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'ID inválido']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE questionnaires SET is_active = 1 WHERE id = ?");
    $stmt->execute([$id]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['sucesso' => true]);
    } else {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Pesquisa não encontrada ou já iniciada.']);
    }
} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao iniciar pesquisa: ' . $e->getMessage()]);
}
?>
