#!/usr/bin/env php
<?php

declare(strict_types=1);

// Script para testar configuração de banco no CI
require_once __DIR__ . '/../vendor/autoload.php';

echo "🔍 Verificando configuração de banco no ambiente de teste...\n";

// Verificar variáveis de ambiente
$envVars = [
    'APP_ENV',
    'DB_HOST',
    'DB_PORT', 
    'DB_DATABASE',
    'DB_USERNAME',
    'DB_PASSWORD'
];

foreach ($envVars as $var) {
    $value = getenv($var);
    echo sprintf("%-15s: %s\n", $var, $value !== false ? $value : 'não definido');
}

// Tentar conectar usando as configurações
try {
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '3306';
    $database = getenv('DB_DATABASE') ?: 'tecnofit_pix_test';
    $username = getenv('DB_USERNAME') ?: 'root';
    $password = getenv('DB_PASSWORD') ?: 'root';
    
    $dsn = "mysql:host={$host};port={$port};dbname={$database}";
    echo "\n🔗 Tentando conectar com DSN: {$dsn}\n";
    echo "👤 Usuário: {$username}\n";
    
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    // Testar uma query simples
    $result = $pdo->query('SELECT 1 as test, NOW() as now')->fetch();
    echo "✅ Conexão bem-sucedida!\n";
    echo "📅 Data/hora do servidor: {$result['now']}\n";
    
    // Listar tabelas se possível
    try {
        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        echo "📋 Tabelas encontradas: " . implode(', ', $tables) . "\n";
    } catch (Exception $e) {
        echo "⚠️  Não foi possível listar tabelas: {$e->getMessage()}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro na conexão: {$e->getMessage()}\n";
    exit(1);
}

echo "🎯 Teste concluído com sucesso!\n";
