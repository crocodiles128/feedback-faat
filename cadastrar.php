<?php
header('Content-Type: application/json');

// Configurações do banco (ajuste o caminho se necessário)
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

// Recebe os dados do POST
$nome = $_POST['nome'] ?? '';
$area_id = $_POST['funcao'] ?? '';
$senha = $_POST['senha'] ?? '';

if (!$nome || !$area_id || !$senha) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Preencha todos os campos']);
    exit;
}

// Função para gerar RA único de 7 dígitos
function gerarRA($conn) {
    do {
        $ra = str_pad(rand(0, 9999999), 7, '0', STR_PAD_LEFT);
        $result = $conn->query("SELECT id FROM users WHERE ra = '$ra'");
    } while ($result && $result->num_rows > 0);
    return $ra;
}

// Conecta ao banco
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao conectar ao banco']);
    exit;
}

// Gera um RA único de 7 dígitos
$ra = gerarRA($conn);

// Criptografa a senha
$senha_hash = password_hash($senha, PASSWORD_BCRYPT);

// Insere o usuário
$stmt = $conn->prepare("INSERT INTO users (ra, password, name, area_id) VALUES (?, ?, ?, ?)");
$stmt->bind_param("sssi", $ra, $senha_hash, $nome, $area_id);

if ($stmt->execute()) {
    echo json_encode(['sucesso' => true, 'ra' => $ra]);
} else {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao cadastrar usuário: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
