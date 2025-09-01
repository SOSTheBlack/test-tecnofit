#!/bin/bash

# 🚀 Script de Teste da API PIX Tecnofit
# Este script valida todos os exemplos do README.md
#
# Uso: ./scripts/test-api-examples.sh

set -e

# Configurações
BASE_URL="http://localhost"
ACCOUNT_PREMIUM="123e4567-e89b-12d3-a456-426614174000"
ACCOUNT_STANDARD="223e4567-e89b-12d3-a456-426614174001"
ACCOUNT_LOW_BALANCE="323e4567-e89b-12d3-a456-426614174002"

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Função para imprimir com cores
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Função para testar endpoint
test_endpoint() {
    local name="$1"
    local url="$2"
    local method="$3"
    local data="$4"
    local expected_status="$5"
    
    print_status "Testando: $name"
    
    if [ "$method" = "GET" ]; then
        response=$(curl -s -w "HTTPSTATUS:%{http_code}" "$url")
    else
        response=$(curl -s -w "HTTPSTATUS:%{http_code}" -X "$method" -H "Content-Type: application/json" -d "$data" "$url")
    fi
    
    http_code=$(echo "$response" | tr -d '\n' | sed -e 's/.*HTTPSTATUS://')
    body=$(echo "$response" | sed -e 's/HTTPSTATUS:.*//g')
    
    if [ "$http_code" -eq "$expected_status" ]; then
        print_success "✅ $name - Status: $http_code"
        echo "   Response: $(echo "$body" | jq -r '.message // .status // "OK"' 2>/dev/null || echo "$body" | head -c 100)..."
    else
        print_error "❌ $name - Expected: $expected_status, Got: $http_code"
        echo "   Response: $body"
        return 1
    fi
    
    echo ""
}

# Banner
echo "=================================================="
echo "🏦 TECNOFIT PIX API - TESTE DE EXEMPLOS"
echo "=================================================="
echo ""

# 1. Health Check
print_status "🔍 Verificando saúde da API..."
test_endpoint "Health Check" "$BASE_URL/health" "GET" "" 200

# 2. Saque com Email (Conta Premium)
print_status "📧 Testando saque com email..."
test_endpoint "Saque Email" \
    "$BASE_URL/account/$ACCOUNT_PREMIUM/balance/withdraw" \
    "POST" \
    '{"method":"PIX","pix":{"type":"email","key":"test@example.com"},"amount":50.00}' \
    200

# 3. Saque com Telefone (Conta Standard)
print_status "📱 Testando saque com telefone..."
test_endpoint "Saque Telefone" \
    "$BASE_URL/account/$ACCOUNT_STANDARD/balance/withdraw" \
    "POST" \
    '{"method":"PIX","pix":{"type":"phone","key":"11999999999"},"amount":100.00}' \
    200

# 4. Saque com CPF (Conta Premium)
print_status "🆔 Testando saque com CPF..."
test_endpoint "Saque CPF" \
    "$BASE_URL/account/$ACCOUNT_PREMIUM/balance/withdraw" \
    "POST" \
    '{"method":"PIX","pix":{"type":"CPF","key":"11144477735"},"amount":75.50}' \
    200

# 5. Saque Agendado
print_status "📅 Testando saque agendado..."
test_endpoint "Saque Agendado" \
    "$BASE_URL/account/$ACCOUNT_PREMIUM/balance/withdraw" \
    "POST" \
    '{"method":"PIX","pix":{"type":"email","key":"agendado@test.com"},"amount":200.00,"schedule":"2025-01-25 14:30"}' \
    201

# 6. Teste de Validação - Saldo Insuficiente
print_status "❌ Testando validação - saldo insuficiente..."
test_endpoint "Saldo Insuficiente" \
    "$BASE_URL/account/$ACCOUNT_LOW_BALANCE/balance/withdraw" \
    "POST" \
    '{"method":"PIX","pix":{"type":"email","key":"test@example.com"},"amount":1000.00}' \
    422

# 7. Teste de Validação - Email Inválido
print_status "❌ Testando validação - email inválido..."
test_endpoint "Email Inválido" \
    "$BASE_URL/account/$ACCOUNT_PREMIUM/balance/withdraw" \
    "POST" \
    '{"method":"PIX","pix":{"type":"email","key":"email-invalido"},"amount":50.00}' \
    422

# 8. Teste de Validação - CPF Inválido
print_status "❌ Testando validação - CPF inválido..."
test_endpoint "CPF Inválido" \
    "$BASE_URL/account/$ACCOUNT_PREMIUM/balance/withdraw" \
    "POST" \
    '{"method":"PIX","pix":{"type":"CPF","key":"12345678901"},"amount":50.00}' \
    422

# 9. Teste de Validação - Conta Inexistente
print_status "❌ Testando validação - conta inexistente..."
test_endpoint "Conta Inexistente" \
    "$BASE_URL/account/00000000-0000-0000-0000-000000000000/balance/withdraw" \
    "POST" \
    '{"method":"PIX","pix":{"type":"email","key":"test@example.com"},"amount":50.00}' \
    404

echo "=================================================="
print_success "🎉 TODOS OS TESTES CONCLUÍDOS!"
echo "=================================================="
echo ""
print_status "📊 Resumo dos testes realizados:"
echo "   ✅ Health Check"
echo "   ✅ Saques com diferentes tipos de chave PIX"
echo "   ✅ Saque agendado"
echo "   ✅ Validações de erro (saldo, formato, conta)"
echo ""
print_status "📧 Verifique emails no Mailhog: http://localhost:8025"
print_status "🗄️ Verifique logs: docker compose logs hyperf"
echo ""