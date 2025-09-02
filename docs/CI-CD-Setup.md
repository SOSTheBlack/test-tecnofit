# Configuração de CI/CD - GitHub Actions

Este documento explica as configurações específicas para fazer os testes funcionarem no ambiente de CI/CD do GitHub Actions.

## Problema Identificado

O erro original ocorria porque o Hyperf estava tentando conectar ao host `mysql` (configurado para Docker) no ambiente do GitHub Actions, onde o MySQL está rodando em `127.0.0.1`.

```
SQLSTATE[HY000] [2002] php_network_getaddresses: getaddrinfo for mysql failed: Temporary failure in name resolution
```

## Soluções Implementadas

### 1. Arquivo `.env.ci` Específico para CI

Criado arquivo `.env.ci` com configurações otimizadas para o ambiente de CI:

```bash
# Database configuration for CI
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tecnofit_pix_test
DB_USERNAME=root
DB_PASSWORD=root

# Cache/Queue configurados para evitar dependências externas
CACHE_DRIVER=array
QUEUE_CONNECTION=sync
MAIL_MAILER=array
```

### 2. Configuração Dinâmica do Banco

Modificado `config/autoload/databases.php` para detectar automaticamente o ambiente:

```php
// Configuração dinâmica baseada no ambiente
$isTestingEnv = env('APP_ENV') === 'testing';
$defaultTestHost = $isTestingEnv ? '127.0.0.1' : 'mysql';
```

### 3. Workflow do GitHub Actions Atualizado

- **Serviço MySQL**: Configurado com `root/root` para simplicidade
- **Variáveis de Ambiente**: Consistentes em todos os passos
- **Teste de Conectividade**: Scripts adicionados para verificar conexão antes dos testes

### 4. Scripts de Diagnóstico

Criados scripts para facilitar o debug:

- `scripts/test-db-connection.sh`: Testa conectividade do banco
- `scripts/test-config.php`: Verifica configurações da aplicação

## Variáveis de Ambiente Importantes

### Desenvolvimento Local (Docker)
```bash
DB_HOST=mysql
DB_USERNAME=tecnofit  
DB_PASSWORD=tecnofit123
```

### CI/CD (GitHub Actions)
```bash
DB_HOST=127.0.0.1
DB_USERNAME=root
DB_PASSWORD=root
```

## Estrutura do Workflow

```yaml
services:
  mysql:
    image: mysql:8.0
    env:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: tecnofit_pix_test
    ports:
      - 3306:3306

steps:
  - name: Copy environment file
    run: cp .env.ci .env
    
  - name: Test database connection
    run: ./scripts/test-db-connection.sh
    
  - name: Run database migrations
    run: php bin/hyperf.php migrate
    
  - name: Run tests with coverage
    run: composer test-coverage
```

## Testes de Validação

Para verificar se tudo está funcionando:

```bash
# Local (Docker)
docker-compose exec hyperf composer test

# Verificar configuração específica
docker-compose exec hyperf php scripts/test-config.php
```

## Troubleshooting

### Erro de Conexão com Banco
1. Verificar se as variáveis de ambiente estão corretas
2. Executar `scripts/test-db-connection.sh` 
3. Verificar se o serviço MySQL está rodando
4. Confirmar que as migrações foram executadas

### Testes Falhando no CI mas Passando Localmente
1. Comparar arquivos `.env.ci` vs `.env.example`
2. Verificar se as dependências (Redis, MySQL) estão configuradas
3. Confirmar que `APP_ENV=testing` está definido

### Cache/Sessão Interferindo
- No CI usamos `CACHE_DRIVER=array` e `QUEUE_CONNECTION=sync`
- Evita dependências externas e problemas de concorrência

## Benefícios da Abordagem

1. **Flexibilidade**: Mesmo código funciona em Docker e CI
2. **Manutenibilidade**: Configurações centralizadas
3. **Debugabilidade**: Scripts de diagnóstico incluídos
4. **Simplicidade**: Reduz dependências externas no CI
5. **Robustez**: Fallbacks automáticos por ambiente
