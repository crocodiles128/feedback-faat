<?php
header('Content-Type: text/html; charset=utf-8');
$data = json_decode(file_get_contents('php://input'), true);
$user = $data['user'] ?? null;

if (!$user || !isset($user['area_id']) || ($user['area_id'] != 2 && $user['area_id'] !== "2")) {
    // Não autorizado ou não RH
    http_response_code(403);
    exit;
}

// Card HTML com botão
echo '
<div class="bg-white border border-unifaat rounded-lg shadow-lg p-6 mt-8 flex flex-col items-center max-w-md w-full">
    <h2 class="text-unifaat text-xl font-bold mb-2">Dashboard RH</h2>
    <p class="mb-4 text-gray-700 text-center">Acesse a dashboard exclusiva do Recursos Humanos para visualizar e gerenciar feedbacks.</p>
    <button onclick="window.location.href=\'../private/RH-dash.html\'" class="bg-unifaat text-white px-5 py-2 rounded hover:bg-blue-900 transition">Entrar na Dashboard do RH</button>
</div>
';
?>
