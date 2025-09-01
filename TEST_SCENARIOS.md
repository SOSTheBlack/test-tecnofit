# PIX Withdrawal System - Test Scenarios & Curl Examples

This document provides comprehensive test scenarios for the PIX withdrawal system, including manual testing commands.

## Test Environment Setup

```bash
# Navigate to project directory
cd /home/runner/work/test-tecnofit/test-tecnofit

# Run all tests
composer test

# Run tests with coverage
composer test-coverage

# Run specific test suites
./vendor/bin/phpunit test/Unit/Enum/ --no-coverage
./vendor/bin/phpunit test/Unit/Rules/ --no-coverage
./vendor/bin/phpunit test/Integration/ --no-coverage
```

## Manual API Testing Examples

### 1. Successful Immediate PIX Withdrawal

```bash
curl --request POST \
  --url http://localhost/account/123e4567-e89b-12d3-a456-426614174000/balance/withdraw \
  --header 'Content-Type: application/json' \
  --data '{
    "method": "PIX",
    "pix": {
      "type": "email",
      "key": "success@example.com"
    },
    "amount": 150.75,
    "schedule": null
  }' \
  --verbose
```

**Expected Response (201):**
```json
{
  "status": "success",
  "message": "Saque realizado com sucesso",
  "data": {
    "id": "withdraw-uuid",
    "type": "immediate",
    "transaction_id": "TXN-123456789",
    "amount": 150.75,
    "method": "PIX",
    "created_at": "2025-01-15T14:30:00Z"
  }
}
```

### 2. Successful Scheduled PIX Withdrawal

```bash
curl --request POST \
  --url http://localhost/account/123e4567-e89b-12d3-a456-426614174000/balance/withdraw \
  --header 'Content-Type: application/json' \
  --data '{
    "method": "PIX",
    "pix": {
      "type": "email",
      "key": "scheduled@example.com"
    },
    "amount": 200.00,
    "schedule": "2025-01-20 14:30"
  }' \
  --verbose
```

**Expected Response (201):**
```json
{
  "status": "success",
  "message": "Saque agendado com sucesso",
  "data": {
    "id": "withdraw-uuid",
    "type": "scheduled",
    "transaction_id": "TXN-123456789",
    "amount": 200.00,
    "method": "PIX",
    "scheduled_for": "2025-01-20T14:30:00Z",
    "created_at": "2025-01-15T14:30:00Z"
  }
}
```

### 3. Insufficient Balance Error

```bash
curl --request POST \
  --url http://localhost/account/323e4567-e89b-12d3-a456-426614174002/balance/withdraw \
  --header 'Content-Type: application/json' \
  --data '{
    "method": "PIX",
    "pix": {
      "type": "email",
      "key": "test@example.com"
    },
    "amount": 1000.00,
    "schedule": null
  }' \
  --verbose
```

**Expected Response (422):**
```json
{
  "status": "validation_error",
  "message": "Saldo insuficiente. Saldo disponível: R$ 50,00, Valor solicitado: R$ 1.000,00",
  "errors": []
}
```

### 4. Invalid PIX Key Format

```bash
curl --request POST \
  --url http://localhost/account/123e4567-e89b-12d3-a456-426614174000/balance/withdraw \
  --header 'Content-Type: application/json' \
  --data '{
    "method": "PIX",
    "pix": {
      "type": "email",
      "key": "invalid-email-format"
    },
    "amount": 100.00,
    "schedule": null
  }' \
  --verbose
```

**Expected Response (422):**
```json
{
  "status": "validation_error",
  "message": "Dados de entrada inválidos",
  "errors": {
    "pix.key": ["Formato de chave PIX inválido."]
  }
}
```

### 5. Invalid Withdraw Method

```bash
curl --request POST \
  --url http://localhost/account/123e4567-e89b-12d3-a456-426614174000/balance/withdraw \
  --header 'Content-Type: application/json' \
  --data '{
    "method": "INVALID_METHOD",
    "amount": 100.00,
    "schedule": null
  }' \
  --verbose
```

**Expected Response (422):**
```json
{
  "status": "validation_error",
  "message": "Dados de entrada inválidos",
  "errors": {
    "method": ["Método de saque inválido. Use: PIX"]
  }
}
```

### 6. Past Schedule Date

```bash
curl --request POST \
  --url http://localhost/account/123e4567-e89b-12d3-a456-426614174000/balance/withdraw \
  --header 'Content-Type: application/json' \
  --data '{
    "method": "PIX",
    "pix": {
      "type": "email",
      "key": "test@example.com"
    },
    "amount": 100.00,
    "schedule": "2020-01-01 12:00"
  }' \
  --verbose
```

**Expected Response (422):**
```json
{
  "status": "validation_error",
  "message": "Dados de entrada inválidos",
  "errors": {
    "schedule": ["Data de agendamento deve ser no futuro."]
  }
}
```

### 7. Schedule Date Too Far in Future

```bash
curl --request POST \
  --url http://localhost/account/123e4567-e89b-12d3-a456-426614174000/balance/withdraw \
  --header 'Content-Type: application/json' \
  --data '{
    "method": "PIX",
    "pix": {
      "type": "email",
      "key": "test@example.com"
    },
    "amount": 100.00,
    "schedule": "2025-12-31 23:59"
  }' \
  --verbose
```

**Expected Response (422):**
```json
{
  "status": "validation_error",
  "message": "Dados de entrada inválidos",
  "errors": {
    "schedule": ["Data de agendamento não pode ser superior a 7 dias."]
  }
}
```

### 8. Missing PIX Data for PIX Method

```bash
curl --request POST \
  --url http://localhost/account/123e4567-e89b-12d3-a456-426614174000/balance/withdraw \
  --header 'Content-Type: application/json' \
  --data '{
    "method": "PIX",
    "amount": 100.00,
    "schedule": null
  }' \
  --verbose
```

**Expected Response (422):**
```json
{
  "status": "validation_error",
  "message": "Dados de entrada inválidos",
  "errors": {
    "pix": ["Para saques PIX é necessário informar os dados PIX."]
  }
}
```

### 9. Amount Below Minimum

```bash
curl --request POST \
  --url http://localhost/account/123e4567-e89b-12d3-a456-426614174000/balance/withdraw \
  --header 'Content-Type: application/json' \
  --data '{
    "method": "PIX",
    "pix": {
      "type": "email",
      "key": "test@example.com"
    },
    "amount": 0.001,
    "schedule": null
  }' \
  --verbose
```

**Expected Response (422):**
```json
{
  "status": "validation_error",
  "message": "Valor mínimo para saque é R$ 0,01",
  "errors": []
}
```

### 10. Non-Existent Account

```bash
curl --request POST \
  --url http://localhost/account/non-existent-account-id/balance/withdraw \
  --header 'Content-Type: application/json' \
  --data '{
    "method": "PIX",
    "pix": {
      "type": "email",
      "key": "test@example.com"
    },
    "amount": 100.00,
    "schedule": null
  }' \
  --verbose
```

**Expected Response (404):**
```json
{
  "status": "error",
  "message": "Account not found",
  "errors": []
}
```

## Test Data Setup

### Account IDs for Testing

- **Premium Account (High Balance)**: `123e4567-e89b-12d3-a456-426614174000`
  - Balance: R$ 5,000.00
  
- **Standard Account (Medium Balance)**: `223e4567-e89b-12d3-a456-426614174001`  
  - Balance: R$ 1,000.00
  
- **Low Balance Account**: `323e4567-e89b-12d3-a456-426614174002`
  - Balance: R$ 50.00

### Valid PIX Key Examples

- **Email**: `test@example.com`, `user.name+tag@domain.co.uk`
- **CPF**: `12345678901` (11 digits)
- **CNPJ**: `12345678901234` (14 digits) 
- **Phone**: `11999999999`, `+5511999999999`
- **Random Key**: `abc123def456ghi789jkl012mno345pq` (32 chars)

## Load Testing Examples

### Concurrent Withdrawals Test

```bash
# Terminal 1
curl --request POST \
  --url http://localhost/account/223e4567-e89b-12d3-a456-426614174001/balance/withdraw \
  --header 'Content-Type: application/json' \
  --data '{
    "method": "PIX",
    "pix": {"type": "email", "key": "test1@example.com"},
    "amount": 600.00,
    "schedule": null
  }' &

# Terminal 2 (simultaneously)
curl --request POST \
  --url http://localhost/account/223e4567-e89b-12d3-a456-426614174001/balance/withdraw \
  --header 'Content-Type: application/json' \
  --data '{
    "method": "PIX", 
    "pix": {"type": "email", "key": "test2@example.com"},
    "amount": 600.00,
    "schedule": null
  }' &
```

**Expected Behavior**: One request succeeds (201), one fails with insufficient balance (422). Account balance never goes negative.

## Automated Test Script

Create a test script to run all scenarios:

```bash
#!/bin/bash
# save as: test-all-scenarios.sh

BASE_URL="http://localhost"
ACCOUNT_HIGH="123e4567-e89b-12d3-a456-426614174000"
ACCOUNT_LOW="323e4567-e89b-12d3-a456-426614174002"

echo "🧪 Testing PIX Withdrawal API..."

echo "✅ Test 1: Successful immediate withdrawal"
curl -s -X POST "$BASE_URL/account/$ACCOUNT_HIGH/balance/withdraw" \
  -H "Content-Type: application/json" \
  -d '{"method":"PIX","pix":{"type":"email","key":"test@example.com"},"amount":100}' \
  | jq '.status'

echo "❌ Test 2: Insufficient balance"  
curl -s -X POST "$BASE_URL/account/$ACCOUNT_LOW/balance/withdraw" \
  -H "Content-Type: application/json" \
  -d '{"method":"PIX","pix":{"type":"email","key":"test@example.com"},"amount":1000}' \
  | jq '.status'

echo "❌ Test 3: Invalid email"
curl -s -X POST "$BASE_URL/account/$ACCOUNT_HIGH/balance/withdraw" \
  -H "Content-Type: application/json" \
  -d '{"method":"PIX","pix":{"type":"email","key":"invalid-email"},"amount":100}' \
  | jq '.status'

echo "🧪 All tests completed!"
```

## Performance Benchmarks

Target performance metrics:
- **Response Time**: < 200ms for immediate withdrawals
- **Throughput**: > 100 requests/second
- **Concurrency**: Handle 50+ concurrent requests without data corruption
- **Availability**: 99.9% uptime
- **Error Rate**: < 0.1% for valid requests

## Monitoring & Observability

Key metrics to monitor:
- Total withdrawals processed
- Average withdrawal amount
- Success/failure rates
- Response times by endpoint
- Queue processing times
- Database connection pool usage
- Email notification delivery rates

## Security Considerations

- All requests require HTTPS in production
- Rate limiting: 10 requests per minute per account
- Input validation and sanitization 
- SQL injection prevention
- XSS protection
- CSRF tokens for web interfaces
- Audit logging for all financial transactions