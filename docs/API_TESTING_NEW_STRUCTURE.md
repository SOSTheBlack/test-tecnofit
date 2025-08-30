# Teste da API de Saque com Nova Estrutura DTO

## 📋 Estrutura Atualizada do Request

### 1. Saque PIX Imediato
```bash
curl -X POST http://localhost:8080/account/123e4567-e89b-12d3-a456-426614174000/balance/withdraw \
  -H "Content-Type: application/json" \
  -d '{
    "method": "PIX",
    "pix": {
      "type": "email",
      "key": "fulano@email.com"
    },
    "amount": 100.50,
    "schedule": null
  }'
```

### 2. Saque PIX Agendado
```bash
curl -X POST http://localhost:8080/account/123e4567-e89b-12d3-a456-426614174000/balance/withdraw \
  -H "Content-Type: application/json" \
  -d '{
    "method": "PIX",
    "pix": {
      "type": "email",
      "key": "fulano@email.com"
    },
    "amount": 1000.00,
    "schedule": "2025-08-30 15:00:00"
  }'
```

### 3. Saque PIX com CPF
```bash
curl -X POST http://localhost:8080/account/123e4567-e89b-12d3-a456-426614174000/balance/withdraw \
  -H "Content-Type: application/json" \
  -d '{
    "method": "PIX",
    "pix": {
      "type": "CPF",
      "key": "12345678901"
    },
    "amount": 500.75
  }'
```

### 4. Saque PIX com Telefone
```bash
curl -X POST http://localhost:8080/account/123e4567-e89b-12d3-a456-426614174000/balance/withdraw \
  -H "Content-Type: application/json" \
  -d '{
    "method": "PIX",
    "pix": {
      "type": "phone",
      "key": "11987654321"
    },
    "amount": 250.00
  }'
```

### 5. Saque PIX com Chave Aleatória
```bash
curl -X POST http://localhost:8080/account/123e4567-e89b-12d3-a456-426614174000/balance/withdraw \
  -H "Content-Type: application/json" \
  -d '{
    "method": "PIX",
    "pix": {
      "type": "random_key",
      "key": "550e8400-e29b-41d4-a716-446655440000"
    },
    "amount": 1500.00
  }'
```

## 🏆 Melhorias Implementadas

### ✅ Estrutura Hierárquica
- **Antes**: `pix_type` e `pix_key` como campos separados
- **Depois**: Objeto `pix` contendo `type` e `key`

### ✅ Validação Integrada
- Validação automática do formato da chave PIX
- Validação específica por tipo (CPF, CNPJ, email, telefone)
- Mensagens de erro detalhadas

### ✅ Extensibilidade
- Estrutura preparada para outros métodos (DEBIT, TED, etc.)
- Cada método pode ter seu próprio DTO específico
- Fácil adição de novos campos

### ✅ Type Safety Completa
- DTO específico para dados PIX
- Validação em tempo de criação
- Impossível criar estruturas inválidas

## 🚀 Resposta da API

### Resposta de Sucesso (Imediato)
```json
{
  "success": true,
  "message": "Saque processado com sucesso.",
  "data": {
    "account_id": "123e4567-e89b-12d3-a456-426614174000",
    "account_name": "João da Silva",
    "amount": 100.50,
    "new_balance": 899.50,
    "available_balance": 899.50,
    "method": "PIX",
    "pix_key": "fu***@email.com",
    "pix_type": "email",
    "type": "immediate"
  },
  "transaction_id": "TXN_66F5D4E1A2B3C_1725021281",
  "processed_at": "2025-08-29T14:34:41.000000Z"
}
```

### Resposta de Sucesso (Agendado)
```json
{
  "success": true,
  "message": "Saque agendado com sucesso.",
  "data": {
    "account_id": "123e4567-e89b-12d3-a456-426614174000",
    "account_name": "João da Silva",
    "amount": 1000.00,
    "current_balance": 1000.00,
    "available_balance": 0.00,
    "method": "PIX",
    "scheduled_for": "2025-08-30T15:00:00.000000Z",
    "pix_key": "fu***@email.com",
    "pix_type": "email",
    "type": "scheduled"
  },
  "transaction_id": "TXN_66F5D4E1A2B3D_1725021282",
  "processed_at": "2025-08-29T14:34:42.000000Z"
}
```

### Resposta de Erro (Validação)
```json
{
  "success": false,
  "message": "Dados inválidos fornecidos.",
  "error_code": "VALIDATION_ERROR",
  "errors": [
    "PIX: Email PIX inválido.",
    "O valor deve ser maior que zero."
  ]
}
```

### Resposta de Erro (Saldo Insuficiente)
```json
{
  "success": false,
  "message": "Saldo insuficiente. Saldo disponível: R$ 50.00, Valor solicitado: R$ 100.00",
  "error_code": "INSUFFICIENT_BALANCE"
}
```

## 🔧 Validações Implementadas

### Validações Gerais
- ✅ Valor deve ser maior que zero
- ✅ Conta deve existir
- ✅ Saldo deve ser suficiente

### Validações PIX
- ✅ Email: Formato válido de email
- ✅ CPF: Validação com dígitos verificadores
- ✅ CNPJ: Validação com dígitos verificadores  
- ✅ Telefone: 10 ou 11 dígitos
- ✅ Chave Aleatória: Formato UUID

### Validações de Agendamento
- ✅ Data deve ser futura
- ✅ Formato de data válido

## 🎯 Próximos Passos

1. **Adicionar suporte para outros métodos**:
   ```json
   {
     "method": "DEBIT",
     "debit": {
       "bank_code": "033",
       "agency": "1234",
       "account": "567890"
     },
     "amount": 100.00
   }
   ```

2. **Implementar webhooks para notificações**
3. **Adicionar rate limiting por conta**
4. **Implementar cache para dados de conta**
5. **Adicionar auditoria completa das operações**
