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

$ra = $_POST['ra'] ?? '';
$password = $_POST['password'] ?? '';

if (!$ra || !$password) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Preencha RA e senha.']);
    exit;
}

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao conectar ao banco.']);
    exit;
}

// Busca usuário e nome da área
$stmt = $conn->prepare("SELECT u.id, u.password, u.name, u.area_id, a.name AS area_name FROM users u LEFT JOIN areas a ON u.area_id = a.id WHERE u.ra = ?");
$stmt->bind_param("s", $ra);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    if (password_verify($password, $user['password'])) {
        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Login realizado com sucesso.',
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'area_id' => $user['area_id'],
                'area_name' => $user['area_name'],
                'ra' => $ra
            ]
        ]);
    } else {
        echo json_encode(['sucesso' => false, 'mensagem' => 'RA ou senha inválidos.']);
    }
} else {
    echo json_encode(['sucesso' => false, 'mensagem' => 'RA ou senha inválidos.']);
}

$stmt->close();
$conn->close();
?>
