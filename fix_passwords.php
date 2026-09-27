<?php
// Script temporário para corrigir os hashes de senha
$host = 'localhost';
$db   = 'sati_alerta';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $newHash = password_hash('Admin@2025', PASSWORD_BCRYPT, ['cost' => 12]);

    $emails = [
        'admin@sati.gov.br',
        'joao@cooperativa.am',
        'maria@escola.am.gov.br',
    ];

    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");

    foreach ($emails as $email) {
        $stmt->execute([$newHash, $email]);
        echo "Atualizado: $email\n";
    }

    echo "\nHash gerado: $newHash\n";

    // Verificar
    echo "\nVerificação password_verify('Admin\@2025', hash): ";
    echo password_verify('Admin@2025', $newHash) ? "OK ✓" : "FALHOU ✗";
    echo "\n";

    // Mostrar hashes atuais no banco
    $rows = $pdo->query("SELECT email, password_hash FROM users")->fetchAll(PDO::FETCH_ASSOC);
    echo "\nHashes no banco:\n";
    foreach ($rows as $row) {
        echo "  {$row['email']}: {$row['password_hash']}\n";
    }

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
