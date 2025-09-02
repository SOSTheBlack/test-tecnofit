#!/bin/bash

# Script para testar conexão com banco de dados
echo "🔍 Testando conexão com banco de dados..."

# Verificar variáveis de ambiente
echo "DB_HOST: ${DB_HOST:-'não definido'}"
echo "DB_PORT: ${DB_PORT:-'não definido'}"
echo "DB_DATABASE: ${DB_DATABASE:-'não definido'}"
echo "DB_USERNAME: ${DB_USERNAME:-'não definido'}"

# Testar conexão MySQL diretamente
if command -v mysql &> /dev/null; then
    echo "🔗 Tentando conectar ao MySQL..."
    mysql -h"${DB_HOST:-127.0.0.1}" -P"${DB_PORT:-3306}" -u"${DB_USERNAME:-root}" -p"${DB_PASSWORD:-root}" -e "SELECT 1;" "${DB_DATABASE:-tecnofit_pix_test}" 2>/dev/null
    if [ $? -eq 0 ]; then
        echo "✅ Conexão MySQL bem-sucedida!"
    else
        echo "❌ Falha na conexão MySQL!"
        exit 1
    fi
else
    echo "⚠️  Cliente MySQL não encontrado, tentando com PHP..."
fi

# Testar conexão via PHP/Hyperf
php -r "
try {
    \$host = getenv('DB_HOST') ?: '127.0.0.1';
    \$port = getenv('DB_PORT') ?: '3306';
    \$db = getenv('DB_DATABASE') ?: 'tecnofit_pix_test';
    \$user = getenv('DB_USERNAME') ?: 'root';
    \$pass = getenv('DB_PASSWORD') ?: 'root';
    
    \$dsn = \"mysql:host=\$host;port=\$port;dbname=\$db\";
    \$pdo = new PDO(\$dsn, \$user, \$pass);
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    \$result = \$pdo->query('SELECT 1 as test')->fetch();
    if (\$result['test'] == 1) {
        echo \"✅ Conexão PHP/PDO bem-sucedida!\n\";
    } else {
        echo \"❌ Falha no teste de conexão PHP/PDO!\n\";
        exit(1);
    }
} catch (Exception \$e) {
    echo \"❌ Erro na conexão PHP/PDO: \" . \$e->getMessage() . \"\n\";
    exit(1);
}
"

echo "🎯 Teste de conexão concluído!"
