# Script para verificar e corrigir versao do projeto
# Delphi 23.0 = Delphi 12.1

Write-Host "================================================" -ForegroundColor Cyan
Write-Host "  Verificando Versao do Projeto" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""

$projetoPath = Join-Path (Get-Location) "Emissor.dproj"

if (-not (Test-Path $projetoPath)) {
    Write-Host "[ERRO] Arquivo Emissor.dproj nao encontrado!" -ForegroundColor Red
    Write-Host "Execute este script na pasta DelphiEmissor" -ForegroundColor Yellow
    exit
}

Write-Host "[1/3] Lendo arquivo do projeto..." -ForegroundColor Yellow
$conteudo = Get-Content $projetoPath -Raw

# Verificar versao atual
if ($conteudo -match '<ProjectVersion>(\d+\.\d+)</ProjectVersion>') {
    $versaoAtual = $matches[1]
    Write-Host "  Versao atual do projeto: $versaoAtual" -ForegroundColor Cyan
} else {
    Write-Host "  [AVISO] Versao do projeto nao encontrada" -ForegroundColor Yellow
}

# Versao correta para Delphi 23.0 (12.1)
$versaoCorreta = "20.1"
# Delphi 23.0 usa versao 20.1 internamente

Write-Host ""
Write-Host "[2/3] Verificando compatibilidade..." -ForegroundColor Yellow

# Verificar se precisa atualizar
if ($versaoAtual -ne $versaoCorreta) {
    Write-Host "  [AVISO] Versao do projeto ($versaoAtual) pode estar incompativel!" -ForegroundColor Yellow
    Write-Host "  Versao esperada para Delphi 23.0: $versaoCorreta" -ForegroundColor Cyan
    
    $atualizar = Read-Host "Deseja atualizar o projeto para versao $versaoCorreta? (S/N)"
    
    if ($atualizar -eq "S" -or $atualizar -eq "s") {
        # Criar backup
        $backupPath = "$projetoPath.backup_$(Get-Date -Format 'yyyyMMdd_HHmmss')"
        Copy-Item -Path $projetoPath -Destination $backupPath -Force
        Write-Host "  [OK] Backup criado: $backupPath" -ForegroundColor Green
        
        # Atualizar versao
        $conteudo = $conteudo -replace '<ProjectVersion>\d+\.\d+</ProjectVersion>', "<ProjectVersion>$versaoCorreta</ProjectVersion>"
        $conteudo | Set-Content -Path $projetoPath -Encoding UTF8 -NoNewline
        Write-Host "  [OK] Versao atualizada para $versaoCorreta" -ForegroundColor Green
    } else {
        Write-Host "  [INFO] Versao mantida como $versaoAtual" -ForegroundColor Gray
    }
} else {
    Write-Host "  [OK] Versao do projeto esta correta ($versaoCorreta)" -ForegroundColor Green
}

Write-Host ""
Write-Host "[3/3] Verificando outras configuracoes..." -ForegroundColor Yellow

# Verificar se tem configuracao de compilacao
if ($conteudo -match 'BuildAllProjects') {
    Write-Host "  [OK] Configuracao de build encontrada" -ForegroundColor Green
}

Write-Host ""
Write-Host "================================================" -ForegroundColor Green
Write-Host "  [OK] Verificacao concluida!" -ForegroundColor Green
Write-Host "================================================" -ForegroundColor Green
Write-Host ""
Write-Host "PROXIMOS PASSOS:" -ForegroundColor Yellow
Write-Host "  1. Abra o Delphi" -ForegroundColor White
Write-Host "  2. File -> New -> VCL Application (projeto teste)" -ForegroundColor White
Write-Host "  3. Adicione: System.SysUtils, Winapi.Windows, System.Messaging" -ForegroundColor White
Write-Host "  4. Compile (F9) - aguarde recompilacao completa" -ForegroundColor White
Write-Host "  5. Feche o projeto teste" -ForegroundColor White
Write-Host "  6. Abra Emissor.dproj" -ForegroundColor White
Write-Host "  7. Project -> Rebuild All" -ForegroundColor White
Write-Host ""




