<?php
/**
 * fix_permissions.php — Ajusta permissões no servidor Linux / cPanel
 * Pastas: 0755 (drwxr-xr-x)
 * Arquivos: 0644 (-rw-r--r--)
 */
header('Content-Type: text/html; charset=UTF-8');

$root = __DIR__;
$dirsCount = 0;
$filesCount = 0;
$errors = [];

@chmod($root, 0755);

try {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $path = $item->getPathname();
        if ($item->isDir()) {
            if (@chmod($path, 0755)) {
                $dirsCount++;
            } else {
                $errors[] = "Pasta: " . htmlspecialchars($path);
            }
        } else {
            if (@chmod($path, 0644)) {
                $filesCount++;
            } else {
                $errors[] = "Arquivo: " . htmlspecialchars($path);
            }
        }
    }
} catch (\Exception $e) {
    $errors[] = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Permissões Corrigidas — SATI-Alerta</title>
  <style>
    body { font-family: sans-serif; background: #080f1a; color: #e2eaf4; max-width: 600px; margin: 40px auto; padding: 20px; }
    .card { background: #0f2040; border-radius: 12px; padding: 24px; border: 1px solid rgba(0,180,216,.2); }
    h1 { color: #00b4d8; font-size: 1.4rem; margin-top: 0; }
    .badge { background: #06d6a0; color: #000; padding: 4px 10px; border-radius: 20px; font-weight: bold; }
    a { display: inline-block; margin-top: 20px; background: #0077b6; color: #fff; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; }
  </style>
</head>
<body>
  <div class="card">
    <h1>✅ Permissões Ajustadas com Sucesso!</h1>
    <p>O servidor Linux do cPanel agora possui as permissões corretas para todos os arquivos e pastas do projeto.</p>
    <ul>
      <li><strong>Pastas ajustadas para 0755:</strong> <?= $dirsCount ?></li>
      <li><strong>Arquivos ajustados para 0644:</strong> <?= $filesCount ?></li>
    </ul>

    <?php if (!empty($errors)): ?>
      <p style="color: #ef233c;">Alguns itens não puderam ser alterados:</p>
      <ul>
        <?php foreach ($errors as $err): ?>
          <li><?= $err ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <a href="./">→ Ir para o SATI-Alerta</a>
  </div>
</body>
</html>
