# Base Repository System

## Visão Geral

Este documento descreve o sistema de repositório base implementado no projeto Tecnofit PIX API. O sistema fornece funcionalidades genéricas para interação com Models através de uma arquitetura baseada em interfaces e herança.

## Estrutura Criada

### 1. Interface Base (`BaseRepositoryInterface`)

**Localização**: `app/Repository/Contract/BaseRepositoryInterface.php`

Define contratos para operações básicas de CRUD:
- `findById()` - Encontra por ID
- `findByIdOrFail()` - Encontra por ID ou gera exception
- `findBy()` - Encontra por critérios
- `findOneBy()` - Encontra um registro por critérios
- `findAll()` - Lista todos os registros
- `create()` - Cria novo registro
- `update()` - Atualiza registro
- `updateBy()` - Atualiza por critérios
- `delete()` - Remove registro
- `deleteBy()` - Remove por critérios
- `count()` - Conta registros
- `exists()` - Verifica existência
- `transaction()` - Executa transação

### 2. Classe Base (`BaseRepository`)

**Localização**: `app/Repository/BaseRepository.php`

Implementação abstrata que fornece:
- ✅ Operações CRUD genéricas
- ✅ Geração automática de UUID
- ✅ Suporte a transações
- ✅ Tratamento de erros padronizado
- ✅ Métodos auxiliares (paginação, ordenação)

### 3. Repositórios Existentes Atualizados

Os repositórios `AccountRepository` e `AccountWithdrawRepository` foram atualizados para:
- ✅ Estender `BaseRepository`
- ✅ Implementar interface específica que estende `BaseRepositoryInterface`
- ✅ Manter funcionalidades específicas
- ✅ Herdar funcionalidades genéricas

## Como Usar

### Criando um Novo Repositório

```php
<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\MinhaEntidade;
use App\Repository\Contract\MinhaEntidadeRepositoryInterface;

class MinhaEntidadeRepository extends BaseRepository implements MinhaEntidadeRepositoryInterface
{
    public function __construct(private MinhaEntidade $model = new MinhaEntidade())
    {
    }

    protected function getModel(): MinhaEntidade
    {
        return $this->model;
    }

    // Métodos específicos da entidade...
}
```

### Criando Interface Específica

```php
<?php

declare(strict_types=1);

namespace App\Repository\Contract;

use App\Model\MinhaEntidade;

interface MinhaEntidadeRepositoryInterface extends BaseRepositoryInterface
{
    public function findById(string $id): ?MinhaEntidade;
    
    // Métodos específicos da entidade...
}
```

## Funcionalidades Disponíveis

### Operações Básicas

```php
// Buscar por ID
$entity = $repository->findById('uuid-here');

// Buscar por ID ou falhar
$entity = $repository->findByIdOrFail('uuid-here');

// Buscar por critérios
$entities = $repository->findBy(['status' => 'active']);

// Buscar um por critérios
$entity = $repository->findOneBy(['email' => 'test@example.com']);

// Criar novo registro
$entity = $repository->create([
    'name' => 'Nome',
    'email' => 'email@example.com'
]);

// Atualizar registro
$repository->update('uuid-here', ['status' => 'inactive']);

// Deletar registro
$repository->delete('uuid-here');
```

### Operações Avançadas

```php
// Contar registros
$count = $repository->count(['status' => 'active']);

// Verificar existência
$exists = $repository->exists(['email' => 'test@example.com']);

// Paginação
$paginated = $repository->paginate(1, 10, ['status' => 'active']);

// Busca com ordenação
$entities = $repository->findByWithOrder(
    ['status' => 'active'], 
    ['created_at' => 'desc']
);

// Transação
$result = $repository->transaction(function() use ($repository) {
    $entity1 = $repository->create(['name' => 'Entity 1']);
    $entity2 = $repository->create(['name' => 'Entity 2']);
    return [$entity1, $entity2];
});
```

## Benefícios

### 1. **Reutilização de Código**
- Operações comuns implementadas uma única vez
- Redução de duplicação de código
- Padrão consistente em todo o projeto

### 2. **Facilidade de Expansão**
- Novos repositórios herdam automaticamente funcionalidades
- Foco em implementar apenas lógica específica
- Interface padronizada para todos os repositórios

### 3. **Manutenibilidade**
- Mudanças em operações básicas afetam todo o sistema
- Testes centralizados para funcionalidades comuns
- Documentação e exemplos unificados

### 4. **Segurança e Consistência**
- Geração automática de UUIDs
- Tratamento padronizado de erros
- Transações integradas

## Estrutura de Pastas

```
app/Repository/
├── BaseRepository.php                    # Classe base
├── AccountRepository.php                 # Repository específico
├── AccountWithdrawRepository.php         # Repository específico
└── Contract/
    ├── BaseRepositoryInterface.php       # Interface base
    ├── AccountRepositoryInterface.php    # Interface específica
    └── AccountWithdrawRepositoryInterface.php
```

## Testes

Os testes estão localizados em:
```
test/Unit/Repository/
└── BaseRepositoryTest.php               # Testes da classe base
```

### Executando Testes

```bash
# Teste específico do BaseRepository
docker-compose exec hyperf ./vendor/bin/phpunit test/Unit/Repository/BaseRepositoryTest.php

# Todos os testes de repositório
docker-compose exec hyperf ./vendor/bin/phpunit test/Unit/Repository/
```

## Convenções

### 1. **Nomenclatura**
- Repositórios: `{Entity}Repository`
- Interfaces: `{Entity}RepositoryInterface`
- Métodos: camelCase em inglês para genéricos, português para específicos

### 2. **Estrutura**
- Todas as interfaces devem estender `BaseRepositoryInterface`
- Todos os repositórios devem estender `BaseRepository`
- Método `getModel()` deve retornar o modelo específico

### 3. **Documentação**
- PHPDoc completo em todos os métodos
- Tipo de retorno específico nas interfaces
- Exceptions documentadas quando aplicável

## Próximos Passos

1. **Migrar repositórios existentes** para usar a nova estrutura base
2. **Criar testes específicos** para cada repositório
3. **Implementar cache** nas operações de leitura frequentes
4. **Adicionar logging** para operações críticas
5. **Criar métricas** de performance dos repositórios

## Exemplo Completo

Veja `AccountRepository` e `AccountWithdrawRepository` como exemplos de implementação completa seguindo os padrões estabelecidos.
