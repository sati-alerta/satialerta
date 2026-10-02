<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Auto-reparo de permissões em servidores Linux (HostGator/cPanel)
if (!is_readable(__DIR__ . '/public/views/public/index.php')) {
    @chmod(__DIR__ . '/public', 0755);
    @chmod(__DIR__ . '/public/views', 0755);
    @chmod(__DIR__ . '/public/views/public', 0755);
    @chmod(__DIR__ . '/public/views/public/index.php', 0644);
    @chmod(__DIR__ . '/public/views/errors', 0755);
    @chmod(__DIR__ . '/public/views/auth', 0755);
    @chmod(__DIR__ . '/public/views/admin', 0755);
    @chmod(__DIR__ . '/public/views/producer', 0755);
    @chmod(__DIR__ . '/public/assets', 0755);
    @chmod(__DIR__ . '/core', 0755);
    @chmod(__DIR__ . '/config', 0755);
}

/**
 * SATI-Alerta — Ponto de Entrada Raiz
 * Carrega a aplicação pública diretamente sem redirecionamento externo
 */
require_once __DIR__ . '/public/index.php';
