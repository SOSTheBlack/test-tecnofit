# Tecnofit PIX API - Sistema de Saque

## 📋 Sobre o Projeto

API REST desenvolvida em **Hyperf 3.1** (PHP 8.2) para gerenciamento de saques via PIX. O sistema implementa validações robustas, suporte a agendamento e estrutura escalável para expansão de métodos de saque.

## 🏗️ Arquitetura

### Stack Tecnológica
- **Framework**: Hyperf 3.1 (Swoole)
- **PHP**: 8.2 com strict_types
- **Banco de Dados**: MySQL 8
- **Cache**: Redis 7
- **Container**: Docker Compose
- **Testes**: PHPUnit 10
- **Qualidade**: PHPStan, PHP CS Fixer

### Estrutura do Projeto
```
app/
├── Controller/
│   └── Accounts/Balances/
│       └── WithDrawController.php      # Controller principal
├── Enum/
│   ├── WithdrawMethodEnum.php          # Métodos de saque (PIX, futuro)
│   └── PixKeyTypeEnum.php              # Tipos de chave PIX
├── Model/
│   └── Account.php                     # Modelo de conta
├── Request/Validator/
│   └── WithdrawRequestValidator.php    # Validador principal
└── Service/
    ├── AccountService.php              # Serviços de conta
    └── Validator/
        └── PixKeyValidator.php         # Validador de chaves PIX
```

## 🚀 Instalação e Execução

### Pré-requisitos
- Docker
- Docker Compose

### Configuração
```bash
# Clone o repositório
git clone <repository-url>
cd tecnofit

# Subir o ambiente
docker-compose up -d

# Executar migrações
docker-compose exec hyperf php bin/hyperf.php migrate

# Verificar saúde da API
curl http://localhost:9501/health
```

## 📚 API Endpoints

### Health Check
```http
GET /health
```

**Resposta:**
```json
{
  "status": "ok",
  "timestamp": "2025-08-28 11:00:00",
  "checks": {
    "database": {"status": "ok", "message": "Database connection successful"},
    "redis": {"status": "ok", "message": "Redis connection successful"}
  }
}
```

### Saque PIX
```http
POST /account/{accountId}/balance/withdraw
```

**Body:**
```json
{
  "method": "PIX",
  "pix": {
    "type": "email",
    "key": "fulano@email.com"
  },
  "amount": 150.75,
  "schedule": null
}
```

#### Parâmetros

| Campo | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| `method` | string | ✅ | Método de saque (apenas "PIX" atualmente) |
| `pix.type` | string | ✅ | Tipo da chave PIX |
| `pix.key` | string | ✅ | Chave PIX |
| `amount` | number | ✅ | Valor do saque (> 0, ≤ saldo) |
| `schedule` | string\|null | ❌ | Data de agendamento (YYYY-MM-DD HH:MM) |

#### Tipos de Chave PIX Suportados

| Tipo | Formato | Exemplo |
|------|---------|---------|
| `email` | E-mail válido | `usuario@example.com` |
| `phone` | Telefone brasileiro | `11999999999` |
| `CPF` | CPF válido | `11144477735` |
| `CNPJ` | CNPJ válido | `11222333000181` |
| `random_key` | Chave aleatória (32 chars) | `1234567890123456789012345678901a` |

#### Respostas

**✅ Saque Imediato (200)**
```json
{
  "status": "success",
  "message": "Saque processado com sucesso.",
  "data": {
    "account_id": "123e4567-e89b-12d3-a456-426614174000",
    "amount": 150.75,
    "method": "PIX",
    "pix": {
      "type": "email",
      "key": "fulano@email.com"
    },
    "new_balance": 849.25,
    "processed_at": "2025-08-28 11:00:00",
    "transaction_id": "TXN_68B0605EA4D0D_1756389470"
  }
}
```

**📅 Saque Agendado (201)**
```json
{
  "status": "success",
  "message": "Saque agendado com sucesso.",
  "data": {
    "account_id": "123e4567-e89b-12d3-a456-426614174000",
    "amount": 100.00,
    "method": "PIX",
    "pix": {
      "type": "phone",
      "key": "11999999999"
    },
    "scheduled_for": "2025-08-30 15:00",
    "current_balance": 1000.00,
    "scheduled_at": "2025-08-28 11:00:00",
    "transaction_id": "TXN_68B06096B001A_1756389526"
  }
}
```

**❌ Erro de Validação (422)**
```json
{
  "status": "error",
  "message": "Dados da requisição inválidos.",
  "errors": {
    "pix.key": ["Formato de e-mail inválido."],
    "amount": ["Saldo insuficiente. Saldo atual: R$ 500.00, Valor solicitado: R$ 1000.00"]
  }
}
```

**❌ Conta Não Encontrada (404)**
```json
{
  "status": "error",
  "message": "Conta não encontrada.",
  "errors": {
    "accountId": ["Conta com ID informado não existe."]
  }
}
```

## 🔍 Validações Implementadas

### Método de Saque
- ✅ Campo obrigatório
- ✅ Deve ser "PIX" (preparado para expansão)
- ✅ Enum `WithdrawMethodEnum` para fácil manutenção

### Chave PIX
- ✅ Validação baseada no tipo selecionado
- ✅ E-mail: formato RFC válido, máx 77 caracteres
- ✅ CPF: formato e dígitos verificadores
- ✅ CNPJ: formato e dígitos verificadores
- ✅ Telefone: formato brasileiro (10-11 dígitos)
- ✅ Chave aleatória: 32 caracteres alfanuméricos

### Valor do Saque
- ✅ Deve ser maior que zero
- ✅ Máximo 2 casas decimais
- ✅ Não pode exceder saldo disponível
- ✅ Limite máximo de R$ 999.999,99

### Agendamento
- ✅ Pode ser `null` (saque imediato)
- ✅ Data deve ser futura
- ✅ Máximo 7 dias à frente
- ✅ Formato: `YYYY-MM-DD HH:MM`

### Conta
- ✅ Validação de existência
- ✅ Verificação de saldo em tempo real

## 🧪 Testes

### Executar Testes Unitários
```bash
docker-compose exec hyperf ./vendor/bin/phpunit test/Unit/ --testdox
```

### Cobertura de Testes
- ✅ **19 testes unitários** do `WithdrawRequestValidator`
- ✅ Todos os cenários de validação
- ✅ Testes de casos de erro e sucesso
- ✅ Validação de saldo insuficiente
- ✅ Validação de agendamento

### Casos de Teste Cobertos
1. ✅ Requisições válidas (email, CPF, telefone, CNPJ, chave aleatória)
2. ✅ Agendamento válido e inválido
3. ✅ Métodos de saque inválidos
4. ✅ Formatos de chave PIX inválidos
5. ✅ Valores negativos e zero
6. ✅ Saldo insuficiente
7. ✅ Datas de agendamento inválidas
8. ✅ Campos obrigatórios ausentes

## 🛠️ Exemplos de Uso

### Saque Imediato com E-mail
```bash
curl -X POST -H "Content-Type: application/json" \
  -d '{
    "method": "PIX",
    "pix": {
      "type": "email",
      "key": "usuario@example.com"
    },
    "amount": 150.75
  }' \
  http://localhost:9501/account/123e4567-e89b-12d3-a456-426614174000/balance/withdraw
```

### Saque Agendado com CPF
```bash
curl -X POST -H "Content-Type: application/json" \
  -d '{
    "method": "PIX",
    "pix": {
      "type": "CPF",
      "key": "11144477735"
    },
    "amount": 500.00,
    "schedule": "2025-08-30 15:00"
  }' \
  http://localhost:9501/account/123e4567-e89b-12d3-a456-426614174000/balance/withdraw
```

### Saque com Telefone
```bash
curl -X POST -H "Content-Type: application/json" \
  -d '{
    "method": "PIX",
    "pix": {
      "type": "phone",
      "key": "11999999999"
    },
    "amount": 100.00
  }' \
  http://localhost:9501/account/123e4567-e89b-12d3-a456-426614174000/balance/withdraw
```

## 🔧 Qualidade de Código

### PHPStan
```bash
docker-compose exec hyperf ./vendor/bin/phpstan analyse
```

### PHP CS Fixer
```bash
docker-compose exec hyperf ./vendor/bin/php-cs-fixer fix
```

## 🚀 Escalabilidade e Expansão

### Preparado para Novos Métodos
```php
// app/Enum/WithdrawMethodEnum.php
enum WithdrawMethodEnum: string
{
    case PIX = 'PIX';
    case BANK_TRANSFER = 'BANK_TRANSFER';  // Futuro
    case TED = 'TED';                      // Futuro
    case DOC = 'DOC';                      // Futuro
}
```

### Novos Tipos de Chave PIX
```php
// app/Enum/PixKeyTypeEnum.php
enum PixKeyTypeEnum: string
{
    case EMAIL = 'email';
    case PHONE = 'phone';
    case CPF = 'CPF';
    case CNPJ = 'CNPJ';
    case RANDOM_KEY = 'random_key';
    // Novos tipos podem ser adicionados facilmente
}
```

## 📊 Contas de Teste

O sistema inclui contas pré-configuradas para testes:

| ID | Nome | Saldo Inicial |
|----|------|---------------|
| `123e4567-e89b-12d3-a456-426614174000` | Conta Teste 1 | R$ 1.000,50 |
| `223e4567-e89b-12d3-a456-426614174001` | Conta Teste 2 | R$ 500,25 |
| `323e4567-e89b-12d3-a456-426614174002` | Conta Teste 3 | R$ 0,00 |

## 🏆 Checklist de Entrega

- ✅ Enum de métodos de saque criado e utilizado
- ✅ Enum para tipos de chave PIX implementado
- ✅ Validação de formato para cada tipo de chave PIX
- ✅ Validação de valor (não negativo, não superior ao saldo)
- ✅ Validação de agendamento (null ou data futura, máximo 7 dias)
- ✅ Mensagens de erro claras e detalhadas
- ✅ RequestValidator implementado e integrado
- ✅ 19 testes unitários cobrindo todas as regras
- ✅ Documentação completa com exemplos
- ✅ Código seguindo princípios SOLID
- ✅ Estrutura escalável para novos métodos
- ✅ API funcionando com Docker Compose

## 📝 Decisões Técnicas

### 1. Uso de Enums PHP 8.1+
- **Decisão**: Utilizar enums nativos para métodos e tipos de chave
- **Justificativa**: Type safety, autocompletion, facilita manutenção
- **Impacto**: Código mais robusto e menos propenso a erros

### 2. Validador Dedicado
- **Decisão**: Criar classe `WithdrawRequestValidator` específica
- **Justificativa**: Separação de responsabilidades, reutilização, testabilidade
- **Impacto**: Fácil manutenção e expansão das regras de validação

### 3. Validação de Chaves PIX por Estratégia
- **Decisão**: Método estático `PixKeyValidator::validateKey()`
- **Justificativa**: Pattern Strategy para diferentes tipos de validação
- **Impacto**: Facilita adição de novos tipos de chave

### 4. Estrutura de Resposta Padronizada
- **Decisão**: Formato consistente para sucesso e erro
- **Justificativa**: Melhor experiência do desenvolvedor, fácil integração
- **Impacto**: API previsível e bem documentada

### 5. Agendamento Simples
- **Decisão**: Validação sem persistência (POC)
- **Justificativa**: Foco na validação robusta, infraestrutura de jobs complexa
- **Impacto**: Base sólida para implementação futura completa

---

**Desenvolvido com ❤️ para Tecnofit** 

Documentação técnica completa do sistema de saques PIX implementado em Hyperf 3.1 com validações robustas e arquitetura escalável.
