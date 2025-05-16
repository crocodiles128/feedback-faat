<?php
header('Content-Type: application/json');
$envPath = __DIR__ . '/API/.env';
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

$res = $conn->query("SELECT id, name FROM areas ORDER BY name");
$areas = [];
while ($row = $res->fetch_assoc()) {
    $areas[] = $row;
}
echo json_encode(['sucesso' => true, 'areas' => $areas]);
$conn->close();
?>
