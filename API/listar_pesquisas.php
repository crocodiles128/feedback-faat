<?php
header('Content-Type: application/json');
$envPath = __DIR__ . '/.env';
if (!file_exists($envPath)) {
    echo json_encode(['sucesso' => false, 'mensagem' => '.env não encontrado']);
    exit;
}
$env = parse_ini_file($envPath);

$host = $env['DB_HOST'];
$user = $env['DB_USER'];
$pass = $env['DB_PASSWORD'];
$dbname = $env['DB_NAME'];

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao conectar ao banco']);
    exit;
}

// Busca todas as pesquisas
$sql = "SELECT q.id, q.title, q.is_active, q.created_at,
            (SELECT COUNT(*) FROM responses r WHERE r.questionnaire_id = q.id) as respostas
        FROM questionnaires q
        ORDER BY q.created_at DESC";
$res = $conn->query($sql);

$finalizadas = [];
$em_aberto = [];
$nao_iniciadas = [];

while ($row = $res->fetch_assoc()) {
    if ($row['is_active'] == 0 && $row['respostas'] > 0) {
        $finalizadas[] = $row;
    } elseif ($row['is_active'] == 1 && $row['respostas'] > 0) {
        $em_aberto[] = $row;
    } elseif ($row['respostas'] == 0) {
        $nao_iniciadas[] = $row;
    }
}

echo json_encode([
    'sucesso' => true,
    'finalizadas' => $finalizadas,
    'em_aberto' => $em_aberto,
    'nao_iniciadas' => $nao_iniciadas
]);
$conn->close();
?>
