<?php
// Habilitar exibição de erros para depuração
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Configurações da API Evolution V2
define('EVOLUTION_API_URL', 'https://evov2.duckdns.org');
define('EVOLUTION_API_KEY', '79Bb4lpu2TzxrSMu3SDfSGvB3MIhkur7');

// Configurações do Mercado Pago
define('MERCADO_PAGO_ACCESS_TOKEN', 'SEU_ACCESS_TOKEN_AQUI');
define('MERCADO_PAGO_PUBLIC_KEY', 'SEU_PUBLIC_KEY_AQUI');

// Configurações gerais
define('SITE_URL', 'http://localhost');
define('ADMIN_EMAIL', 'admin@seusite.com');

// Configurações de sessão
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Função para debug
function debug($data) {
    echo '<pre>';
    print_r($data);
    echo '</pre>';
}

// Função para redirecionar
function redirect($url) {
    header("Location: $url");
    exit();
}
?>