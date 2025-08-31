<!-- Use this file to provide workspace-specific custom instructions to Copilot. For more details, visit https://code.visualstudio.com/docs/copilot/copilot-customization#_use-a-githubcopilotinstructionsmd-file -->

# Projeto Tecnofit - API REST Hyperf 3 para Saque via Pix

## Tecnologias
- PHP 8.2+ com Hyperf 3.1
- Docker & Docker Compose
- MySQL 8.0
- Redis 7
- Mailhog para captura de emails
- PHPUnit para testes
- PHPStan para análise estática

## Estrutura do Projeto
- Seguir padrões do Hyperf Skeleton
- Controllers, Services, Models organizados por domínio
- Middlewares para autenticação e validação
- Jobs/Listeners para processamento assíncrono
- Migrations e Seeders para banco de dados

## Funcionalidades Principais
- Solicitação e consulta de saques via Pix
- Integração simulada com provedor Pix(ServiceFakeAPI)
- Sistema de notificações por email
- Logs e auditoria de transações

## Qualidade de Código
- Cobertura mínima de testes: 80%
- PHPStan level 8
- PSR-12 para padrão de código
- CI/CD com GitHub Actions

## Checklist de Progresso
- [x] Clarify Project Requirements - Projeto Hyperf 3 API REST para saque Pix
- [x] Scaffold the Project - Estrutura base criada com Docker, Hyperf, configurações
- [x] Customize the Project - Estrutura básica implementada (controllers, models, migrations, testes)
- [x] Install Required Extensions - Extensões PHP/Docker instaladas (Intelephense, YAML, Composer)
- [x] Ensure Documentation is Complete - README e documentação criados
