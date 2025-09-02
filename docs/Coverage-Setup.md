# Configuração de Cobertura de Testes

Este documento explica como funciona a geração e análise de cobertura de testes no projeto.

## Problema com Codecov

O erro que você estava enfrentando:

```
Error: There was an error fetching the storage URL during POST: 429 - Rate limit reached
```

Indica que o Codecov atingiu o limite de taxa para uploads anônimos (sem token).

## Soluções Implementadas

### 1. Upload Opcional do Codecov

O workflow agora trata o upload do Codecov como **opcional**:

```yaml
- name: Upload coverage reports (optional)
  uses: codecov/codecov-action@v4
  with:
    fail_ci_if_error: false  # Não falha o CI se der erro
  continue-on-error: true    # Continua mesmo se falhar
```

### 2. Condições Inteligentes

O upload só acontece em situações importantes:
- Push para branch `main`
- Pull Requests

Isso reduz a frequência de uploads e ajuda a evitar rate limits.

### 3. Relatório de Cobertura no Log

Criado script `scripts/extract-coverage.php` que extrai e exibe informações de cobertura diretamente no log do GitHub Actions:

```bash
📊 **COVERAGE REPORT**
==================================================
✅ Total Coverage: 87.5% (245/280 elements)
✅ Statement Coverage: 85.2% (198/232)
⚠️  Method Coverage: 76.4% (42/55)

📈 **SUMMARY**
Files: 15
Classes: 12
Methods: 55
Statements: 232
```

### 4. Artefatos do GitHub Actions

Os relatórios de cobertura são salvos como artefatos do GitHub Actions:

```yaml
- name: Upload coverage artifacts
  uses: actions/upload-artifact@v4
  with:
    name: coverage-reports
    path: |
      ./runtime/coverage/clover.xml
      ./runtime/coverage/html/
```

Isso permite:
- ✅ Baixar relatórios HTML completos
- ✅ Acesso aos dados mesmo se Codecov falhar
- ✅ Histórico de 30 dias dos relatórios

## Como Acessar os Relatórios

### 1. No Log do GitHub Actions

O resumo da cobertura aparece diretamente no log:

```
📊 **COVERAGE ANALYSIS**
📊 **COVERAGE REPORT**
==================================================
✅ Total Coverage: 87.5% (245/280 elements)
```

### 2. Nos Artefatos

1. Vá para a aba **Actions** do repositório
2. Clique no run específico
3. Role para baixo até **Artifacts**
4. Baixe `coverage-reports`
5. Extraia e abra `html/index.html` no navegador

### 3. Via Codecov (quando funcionar)

Se o upload para o Codecov funcionar, você pode acessar em:
`https://codecov.io/gh/SOSTheBlack/test-tecnofit`

## Scripts de Cobertura

### `scripts/extract-coverage.php`

Extrai informações detalhadas do arquivo `clover.xml`:

```bash
php scripts/extract-coverage.php ./runtime/coverage/clover.xml
```

**Funcionalidades:**
- ✅ Percentual de cobertura geral
- ✅ Cobertura por tipo (statements, methods, classes)
- ✅ Lista arquivos com baixa cobertura
- ✅ Indicadores visuais (✅ ⚠️ ❌)

## Configuração Local

Para gerar relatórios localmente:

```bash
# Com Docker
docker-compose exec hyperf composer test-coverage

# Visualizar relatório
docker-compose exec hyperf php scripts/extract-coverage.php ./runtime/coverage/clover.xml

# Abrir relatório HTML (depois de copiar do container)
docker cp $(docker-compose ps -q hyperf):/opt/www/runtime/coverage/html ./coverage-html
open coverage-html/index.html
```

## Metas de Cobertura

### Atual
- **Mínimo**: 80% (configurado no projeto)
- **Ideal**: 90%+ para código crítico

### Por Componente
- **Controllers**: 90%+
- **Services/UseCases**: 95%+
- **Rules/Validations**: 100%
- **Models**: 85%+
- **Repositories**: 90%+

## Troubleshooting

### Codecov Rate Limit
- ✅ **Solução**: Upload é opcional, CI continua funcionando
- ✅ **Alternativa**: Use artefatos do GitHub Actions
- 🔧 **Future**: Configurar `CODECOV_TOKEN` nos secrets do repo

### Arquivo de Cobertura Não Encontrado
```bash
# Verificar se os testes geram cobertura
composer test-coverage

# Verificar se o arquivo foi criado
ls -la runtime/coverage/
```

### Baixa Cobertura
```bash
# Ver arquivos com baixa cobertura
php scripts/extract-coverage.php ./runtime/coverage/clover.xml

# Ver relatório HTML detalhado
open runtime/coverage/html/index.html
```

## Configuração do Codecov (Opcional)

Se quiser configurar o token do Codecov:

1. Vá para https://codecov.io/
2. Conecte o repositório
3. Copie o token
4. Adicione como secret `CODECOV_TOKEN` no GitHub
5. Descomente a linha no workflow:
   ```yaml
   env:
     CODECOV_TOKEN: ${{ secrets.CODECOV_TOKEN }}
   ```

## Benefícios da Abordagem

1. **✅ Robustez**: CI nunca falha por problemas de cobertura
2. **✅ Visibilidade**: Relatórios sempre disponíveis nos logs
3. **✅ Flexibilidade**: Múltiplas formas de acessar os dados
4. **✅ Histórico**: Artefatos mantidos por 30 dias
5. **✅ Debug**: Scripts locais para análise detalhada
