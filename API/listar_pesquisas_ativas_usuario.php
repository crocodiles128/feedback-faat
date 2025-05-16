<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);

require_once __DIR__ . '/conexao.php';

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $data['user_id'] ?? null;
$area_id = $data['area_id'] ?? null;

if (!$user_id || !$area_id) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário não informado']);
    exit;
}

// Busca pesquisas ativas (is_active = 1) para a área do usuário OU geral (todas as áreas)
$stmt = $pdo->prepare("
    SELECT DISTINCT q.id, q.title, q.created_at
    FROM questionnaires q
    JOIN questionnaire_questions qq ON qq.questionnaire_id = q.id
    WHERE q.is_active = 1
      AND (qq.area_id = :area_id OR (
            -- Pesquisa geral: está disponível para todas as áreas
            (SELECT COUNT(DISTINCT area_id) FROM questionnaire_questions WHERE questionnaire_id = q.id) = 
            (SELECT COUNT(*) FROM areas)
          ))
    ORDER BY q.created_at DESC
");
$stmt->execute(['area_id' => $area_id]);
$pesquisas = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['sucesso' => true, 'pesquisas' => $pesquisas]);
