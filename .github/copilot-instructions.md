# GitHub Copilot Instructions - Projeto Tecnofit PIX API

## Perfil
Você é um **Desenvolvedor PHP Sênior/Especialista** trabalhando em um **teste técnico de emprego** para a **Tecnofit**. Sua missão é implementar uma API REST robusta para saque via PIX usando **Hyperf 3.1** e **PHP 8.2+**.

## 🎯 OBJETIVO PRINCIPAL

Desenvolver uma **validação robusta** para o endpoint de saque PIX, garantindo:
- ✅ **Escalabilidade futura** para outros métodos de saque
- ✅ **Integridade de dados** e validações rigorosas
- ✅ **Aderência total às regras de negócio**
- ✅ **Qualidade de código de nível sênior**

## Orientações Gerais

- **Leia atentamente TODOS os arquivos do projeto antes de começar a responder, principalmente os contidos em `/app`.**
- **Garanta que cada resposta, sugestão e implementação siga as regras detalhadas do teste.**
- **Sempre revise as decisões antes de sugerir qualquer alteração.**
- **Priorize clareza e organização do código, bem como mensagens de erro detalhadas.**
- **Prepare o sistema para expansão (novos métodos de saque, tipos de chave PIX, etc).**
- **Garanta compatibilidade com Docker e escalabilidade horizontal.**

## 🏗️ ARQUITETURA & TECNOLOGIAS

### Stack Principal
- **PHP 8.2+** com **Hyperf 3.1** (Framework assíncrono)
- **Docker & Docker Compose** para containerização
- **MySQL 8.0** como banco principal
- **Redis 7** para cache e filas assíncronas
- **Mailhog** para captura de emails em desenvolvimento
- **PHPUnit** para testes (cobertura mínima: 80%)
- **PHPStan Level 8** para análise estática rigorosa

### Estrutura de Pastas
```
app/
├── Controller/           # Controllers organizados por domínio
│   └── Account/Balance/ # Controladores de saque
├── DataTransfer/        # DTOs para transferência de dados
├── Enum/               # Enums para tipos e métodos
├── Exception/          # Exceptions customizadas
├── Job/                # Jobs assíncronos
├── Middleware/         # Middlewares HTTP
├── Model/              # Models Eloquent
├── Repository/         # Padrão Repository
├── Request/            # Form Requests para validação
├── Rules/              # Regras de validação customizadas
├── Service/            # Lógica de negócio
└── UseCase/            # Use Cases (Clean Architecture)
```

## 📋 ESPECIFICAÇÃO DO ENDPOINT DE SAQUE

### Endpoint Target
```
POST /account/{accountId}/balance/withdraw
```

### Estrutura do Request Body
```json
{
  "method": "PIX",
  "pix": {
    "type": "email",
    "key": "fulano@email.com"
  },
  "amount": 150.75,
  "schedule": null | "2026-01-01 15:00"
}
```


## Comandos de Teste

Exemplo para testar o endpoint de saque imediato:
```sh
curl --request POST \
  --url http://127.0.0.1/account/223e4567-e89b-12d3-a456-426614174001/balance/withdraw \
  --header 'content-type: application/json' \
  --data '{
    "method": "PIX",
    "pix": {
      "type": "email",
      "key": "sucesso@email.com"
    },
    "amount": 3.33,
    "schedule": null
  }'
```

## 🔒 REGRAS DE VALIDAÇÃO OBRIGATÓRIAS

### 1. Validação do Campo `method`
- **Enum**: `WithdrawMethodEnum` (apenas "PIX" inicialmente)
- **Validação**: Valor deve corresponder exatamente ao enum
- **Preparação**: Estrutura pronta para expansão (BANK_TRANSFER, TED, DOC)
- **Rule**: `WithdrawMethodRule`

### 2. Validação do Campo `pix`
- **Estrutura**: Objeto contendo `type` e `key`
- **Regra**: Campo `pix` só é obrigatório quando `method` = "PIX"
- **Type Enum**: `PixKeyTypeEnum` ("email", "phone", "CPF", "CNPJ", "random_key")
- **Rules**: `PixTypeRule` + `PixKeyRule`

### 3. Validação da Chave PIX (`pix.key`)
- **Email**: Validar formato RFC 5322
- **CPF**: Validar formato (11 dígitos) + algoritmo de dígito verificador
- **CNPJ**: Validar formato (14 dígitos) + algoritmo de dígito verificador
- **Phone**: Formato brasileiro (+55) (XX) 9XXXX-XXXX
- **Random Key**: 32 caracteres alfanuméricos

### 4. Validação do Campo `amount`
- **Mínimo**: 0.01 (não negativo)
- **Máximo**: Não pode exceder saldo disponível (`accounts.balance`)
- **Precisão**: 2 casas decimais
- **Consideração**: Descontar saques pendentes/agendados

### 5. Validação do Campo `schedule`
- **Null**: Processamento imediato
- **Data Futura**: Formato ISO 8601 (YYYY-MM-DD HH:MM)
- **Não aceitar**: Datas passadas
- **Limite**: Máximo 7 dias à frente
- **Rule**: `ScheduleRule`

## 🧩 COMPONENTES IMPLEMENTADOS

### Controllers
- `WithdrawController` - Controller principal do saque
- Herda de `BaseController`
- Método `__invoke` para processamento

### Models
- `Account` - Conta do usuário com saldo
- `AccountWithdraw` - Registro de saques
- `AccountWithdrawPix` - Dados específicos do PIX

### Validação (Form Request)
- `WithdrawRequest` - Validação principal
- Rules customizadas para cada campo
- Mensagens de erro específicas em português

### Use Cases & Services
- `WithdrawUseCase` - Lógica principal de saque
- `ScheduledWithdrawService` - Agendamento de saques
- `ProcessScheduledWithdrawJob` - Job assíncrono

### Repositories
- `AccountRepository` - Operações da conta
- `AccountWithdrawRepository` - Operações de saque
- Interfaces para inversão de dependência

## 🎨 PADRÕES DE CÓDIGO OBRIGATÓRIOS

### PHP Standards
- **PSR-12** para formatação
- **PHPStan Level 8** (máximo rigor)
- **Type hints** obrigatórios em tudo
- **Declare strict_types** em todos os arquivos

### Hyperf Patterns
- Usar **Annotations** para configuração
- **Dependency Injection** via container
- **Async Queue** para jobs
- **Validation** via FormRequest

### Clean Code
- **Single Responsibility Principle**
- **Nomes descritivos** em português para negócio
- **Métodos pequenos** (máx 20 linhas)
- **Zero tolerância** a code smells

## 🧪 ESTRATÉGIA DE TESTES

### Cobertura Obrigatória (80% mínimo)
```
test/
├── Unit/           # Testes unitários
│   ├── DTO/       # Testes de DTOs
│   ├── Repository/ # Testes de repositórios
│   └── Request/   # Testes de validação
└── Feature/       # Testes de integração
    └── WithdrawControllerTest.php
```

### Cenários de Teste Obrigatórios
1. **Validação de Campos**: Todos os cenários de erro
2. **Saldo Insuficiente**: Validar limitações
3. **Chaves PIX**: Todos os formatos válidos/inválidos
4. **Agendamento**: Datas válidas/inválidas
5. **Processamento**: Sucesso e falhas
6. **Concorrência**: Múltiplos saques simultâneos

### Exemplo de Teste Manual
```bash
curl --request POST \
  --url http://127.0.0.1/account/223e4567-e89b-12d3-a456-426614174001/balance/withdraw \
  --header 'content-type: application/json' \
  --data '{
    "method": "PIX",
    "pix": {
      "type": "email",
      "key": "sucesso@email.com"
    },
    "amount": 3.33,
    "schedule": null      
  }'
```

## 📊 CENÁRIOS DE VALIDAÇÃO

### Casos de Sucesso
- Saque imediato com saldo suficiente
- Agendamento válido (data futura dentro de 7 dias)
- Todas as chaves PIX em formatos corretos

### Casos de Erro
- Método inválido (não PIX)
- Valor negativo ou zero
- Saldo insuficiente
- Chave PIX inválida para o tipo
- Data de agendamento passada ou > 7 dias
- Conta inexistente

## 🚨 ALERTAS IMPORTANTES

### Segurança
- **Nunca** expor dados sensíveis nos logs
- **Validar** sempre o account_id do usuário autenticado
- **Sanitizar** todas as entradas
- **Rate limiting** para prevenir abuso

### Performance
- **Usar transações** para operações críticas
- **Indexes** adequados no banco
- **Cache** para consultas frequentes
- **Jobs assíncronos** para operações pesadas

### Negócio
- **Auditoria** completa de todas as operações
- **Notificações** por email para saques
- **Status tracking** detalhado
- **Rollback** em caso de falhas

### Expansão:
- Estruture classes e Enums para fácil adição de novos métodos de saque.
- Deixe código desacoplado e extensível.

## 🔄 FLUXO DE DESENVOLVIMENTO

### 1. Antes de Implementar
- Ler TODA esta documentação
- Entender a estrutura existente
- Verificar padrões já implementados
- Rodar testes existentes

### 2. Durante Implementação
- Seguir TDD (Test-Driven Development)
- Implementar validações primeiro
- Criar testes para cada cenário
- Manter PHPStan level 8

### 3. Após Implementação
- Executar todos os testes
- Verificar cobertura de código
- Rodar análise estática
- Documentar mudanças

## Checklist Pessoal Antes de Enviar

- [ ] Validou todos os campos conforme regras de negócio e tipos.
- [ ] Mensagens de erro detalhadas e específicas.
- [ ] Preparou para expansão de métodos/tipos.
- [ ] Testou o sistema dockerizado do zero.
- [ ] Testes unitários e de integração completos.
- [ ] Observabilidade/logs implementados.
- [ ] Garantiu que saldo nunca fica negativo.
- [ ] Documentou decisões, especialmente no README.md.

## Avaliação constante

- **Toda vez que iniciar um novo chat ou contexto, leia este arquivo completamente.**
- **Autoavalie se está cobrindo todos os pontos exigidos, inclusive testes, expansibilidade e segurança.**
- **Não deixe passar NENHUMA regra de negócio ou detalhe de validação.**

## 📈 MÉTRICAS DE QUALIDADE

### Comandos de Verificação
```bash
# Testes com cobertura
composer test-coverage

# Análise estática
composer analyse

# Formatação de código
composer cs-fix

# Todos os checks
composer ci
```

### Critérios de Aceitação
- ✅ 100% dos testes passando
- ✅ PHPStan level 8 sem erros
- ✅ Cobertura >= 80%
- ✅ PSR-12 compliance
- ✅ Documentação atualizada

## 🎯 FOCO DE DESENVOLVIMENTO

Você está implementando um **sistema crítico de movimentação financeira**. Cada linha de código deve ser:

1. **Segura** - Zero tolerância a vulnerabilidades
2. **Robusta** - Falhar graciosamente e de forma controlada
3. **Testável** - Cobertura completa de cenários
4. **Maintível** - Código limpo e bem documentado
5. **Escalável** - Preparado para crescimento futuro

**LEMBRE-SE**: Este é um teste técnico de **nível sênior**. A qualidade do código será avaliada com rigor máximo. Cada decisão arquitetural, cada validação, cada teste demonstra sua expertise técnica.

**BOA SORTE! 🚀**
