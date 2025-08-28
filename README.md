# Tecnofit Pix API

API REST para sistema de saque via Pix - Teste Técnico Tecnofit

## 🚀 Tecnologias

- **PHP 8.2+** com Hyperf 3.1
- **Docker & Docker Compose** para containerização
- **MySQL 8.0** como banco de dados principal
- **Redis 7** para cache e filas
- **Mailhog** para captura e visualização de emails
- **PHPUnit** para testes automatizados
- **PHPStan** para análise estática de código
- **GitHub Actions** para CI/CD

## 📁 Estrutura do Projeto

```
├── app/                    # Código da aplicação
│   ├── Controller/         # Controllers da API
│   ├── Model/             # Models Eloquent
│   ├── Service/           # Lógica de negócio
│   ├── Middleware/        # Middlewares HTTP
│   ├── Request/           # Form Requests para validação
│   ├── Exception/         # Exceptions customizadas
│   ├── Job/               # Jobs para processamento assíncrono
│   ├── Listener/          # Event Listeners
│   └── helpers.php        # Funções auxiliares globais
├── config/                # Arquivos de configuração
├── migrations/            # Migrations do banco de dados
├── test/                  # Testes automatizados
│   ├── Feature/           # Testes de integração
│   └── Unit/              # Testes unitários
├── docker/                # Configurações Docker
├── .github/workflows/     # Pipeline CI/CD
└── storage/               # Arquivos de armazenamento
```

## 🐋 Ambiente de Desenvolvimento

### Pré-requisitos

- Docker >= 20.0
- Docker Compose >= 2.0
- Git

### Configuração Inicial

1. **Clone o repositório:**
```bash
git clone <repository-url>
cd tecnofit-pix-api
```

2. **Configure as variáveis de ambiente:**
```bash
cp .env.example .env
```

3. **Suba os containers:**
```bash
docker-compose up -d
```

4. **Instale as dependências:**
```bash
docker-compose exec hyperf composer install
```

5. **Execute as migrations:**
```bash
docker-compose exec hyperf php bin/hyperf.php migrate
```

6. **Execute os seeders (opcional):**
```bash
docker-compose exec hyperf php bin/hyperf.php db:seed
```

### Serviços Disponíveis

| Serviço | URL | Descrição |
|---------|-----|-----------|
| API | http://localhost:9501 | API REST principal |
| Nginx | http://localhost:80 | Proxy reverso |
| Mailhog | http://localhost:8025 | Interface web para emails |
| MySQL | localhost:3306 | Banco de dados |
| Redis | localhost:6379 | Cache e filas |

## 🔧 Desenvolvimento

### Comandos Úteis

```bash
# Executar testes
docker-compose exec hyperf composer test

# Executar testes com cobertura
docker-compose exec hyperf composer test-coverage

# Análise estática com PHPStan
docker-compose exec hyperf composer analyse

# Formatar código com PHP CS Fixer
docker-compose exec hyperf composer cs-fix

# Iniciar servidor de desenvolvimento
docker-compose exec hyperf php bin/hyperf.php start

# Acessar container
docker-compose exec hyperf bash
```

### Estrutura da API

#### Endpoints Principais

```
GET  /api/               # Informações da API
GET  /api/health         # Health check
POST /api/auth/login     # Autenticação
POST /api/auth/register  # Registro de usuário
GET  /api/users/profile  # Perfil do usuário
POST /api/withdrawals    # Solicitar saque PIX
GET  /api/withdrawals    # Listar saques
GET  /api/withdrawals/{id} # Detalhes do saque
```

#### Autenticação

A API utiliza autenticação JWT. Para acessar endpoints protegidos:

1. Faça login via `POST /api/auth/login`
2. Use o token retornado no header `Authorization: Bearer {token}`

#### Formato de Resposta

```json
{
    "success": true,
    "data": {},
    "message": "Operação realizada com sucesso",
    "timestamp": "2024-01-01T12:00:00-03:00"
}
```

#### Códigos de Status

- `200` - Sucesso
- `201` - Criado com sucesso
- `400` - Dados inválidos
- `401` - Não autenticado
- `403` - Não autorizado
- `404` - Recurso não encontrado
- `422` - Erro de validação
- `500` - Erro interno do servidor

## 🧪 Testes

### Executar Testes

```bash
# Todos os testes
composer test

# Testes com cobertura
composer test-coverage

# Testes específicos
./vendor/bin/phpunit test/Feature/AuthTest.php
```

### Cobertura de Testes

O projeto mantém uma cobertura mínima de 80% para os principais casos de uso.

### Tipos de Teste

- **Unit Tests**: Testes unitários para classes individuais
- **Feature Tests**: Testes de integração para endpoints da API

## 📊 Qualidade de Código

### PHPStan

```bash
composer analyse
```

### PHP CS Fixer

```bash
# Verificar formatação
composer cs-fix -- --dry-run

# Corrigir formatação
composer cs-fix
```

### Pipeline CI/CD

O projeto utiliza GitHub Actions para:

- ✅ Análise estática (PHPStan)
- ✅ Verificação de formatação (PHP CS Fixer)
- ✅ Execução de testes
- ✅ Verificação de cobertura de testes
- ✅ Build da imagem Docker

## 🔒 Funcionalidades Implementadas

### Autenticação
- [x] Registro de usuários
- [x] Login com JWT
- [x] Validação de token
- [x] Middleware de autenticação

### Gestão de Usuários
- [x] CRUD de usuários
- [x] Validação de CPF
- [x] Verificação de email

### Sistema PIX
- [x] Solicitação de saque
- [x] Validação de chave PIX
- [x] Integração mockada com provedor
- [x] Consulta de status do saque

### Notificações
- [x] Envio de emails via Mailhog
- [x] Templates de email
- [x] Queue para processamento assíncrono

### Auditoria
- [x] Logs de transações
- [x] Histórico de operações
- [x] Rastreamento de usuários

## 🚀 Deploy

### Produção

1. Configure as variáveis de ambiente para produção
2. Execute as migrations
3. Configure SSL/TLS
4. Configure backup do banco de dados
5. Configure monitoramento

### Monitoramento

- Logs da aplicação: `storage/logs/`
- Metrics do Swoole via endpoints internos
- Health checks via `GET /api/health`

## 📝 Contribuição

1. Fork o projeto
2. Crie uma branch para sua feature (`git checkout -b feature/nova-funcionalidade`)
3. Commit suas mudanças (`git commit -am 'Adiciona nova funcionalidade'`)
4. Push para a branch (`git push origin feature/nova-funcionalidade`)
5. Abra um Pull Request

## 📄 Licença

Este projeto está sob a licença MIT. Veja o arquivo [LICENSE](LICENSE) para mais detalhes.

## 🆘 Suporte

Para questões e suporte:

- Abra uma [issue](../../issues) no GitHub
- Consulte a [documentação do Hyperf](https://hyperf.wiki)
- Entre em contato com a equipe de desenvolvimento
