# Script para limpar DCUs do sistema do Delphi (RTL)
# Execute como Administrador
# ATENCAO: Este script remove DCUs do sistema do Delphi. O Delphi vai recompilar automaticamente.

Write-Host "================================================" -ForegroundColor Cyan
Write-Host "  Limpeza de DCUs do Sistema do Delphi (RTL)" -ForegroundColor Cyan
Write-Host "  Resolvendo: F2051 System.SysUtils" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "[AVISO] Este script vai remover arquivos .dcu do sistema do Delphi." -ForegroundColor Yellow
Write-Host "Os arquivos .pas (codigo fonte) NAO serao removidos." -ForegroundColor Yellow
Write-Host "O Delphi vai recompilar automaticamente na proxima vez que voce compilar." -ForegroundColor Yellow
Write-Host ""

$confirmar = Read-Host "Deseja continuar? (S/N)"
if ($confirmar -ne "S" -and $confirmar -ne "s") {
    Write-Host "Operacao cancelada." -ForegroundColor Yellow
    exit
}

# Parar processos do Delphi
Write-Host "[1/4] Verificando processos do Delphi..." -ForegroundColor Yellow
$processos = Get-Process | Where-Object {$_.Name -like "*bds*" -or $_.Name -like "*dcc*" -or $_.Name -like "*msbuild*"}
if ($processos) {
    Write-Host "  [AVISO] Encontrados processos do Delphi rodando. Tentando fechar..." -ForegroundColor Yellow
    $processos | Stop-Process -Force -ErrorAction SilentlyContinue
    Start-Sleep -Seconds 3
    Write-Host "  [OK] Processos fechados" -ForegroundColor Green
} else {
    Write-Host "  [OK] Nenhum processo encontrado" -ForegroundColor Green
}

# Detectar versao do Delphi automaticamente
Write-Host "[2/4] Detectando versao do Delphi..." -ForegroundColor Yellow
$delphiPaths = @(
    "C:\Program Files (x86)\Embarcadero\Studio\*",
    "C:\Program Files\Embarcadero\Studio\*"
)

$delphiEncontrado = $false
$delphiPath = $null

foreach ($basePath in $delphiPaths) {
    $studios = Get-Item $basePath -ErrorAction SilentlyContinue
    if ($studios) {
        foreach ($studio in $studios) {
            $libPath = Join-Path $studio.FullName "lib"
            if (Test-Path $libPath) {
                $delphiPath = $libPath
                $delphiEncontrado = $true
                Write-Host "  [OK] Delphi encontrado em: $libPath" -ForegroundColor Green
                break
            }
        }
    }
    if ($delphiEncontrado) { break }
}

if (-not $delphiEncontrado) {
    Write-Host "  [ERRO] Delphi nao encontrado automaticamente." -ForegroundColor Red
    Write-Host "  Digite o caminho manualmente ou instale o Delphi." -ForegroundColor Yellow
    exit
}

# Remover DCUs do sistema
Write-Host "[3/4] Removendo DCUs do sistema..." -ForegroundColor Yellow
$pastasDCU = @(
    "Win32\debug",
    "Win32\release",
    "Win64\debug",
    "Win64\release"
)

$totalRemovidos = 0
foreach ($pasta in $pastasDCU) {
    $pathCompleto = Join-Path $delphiPath $pasta
    if (Test-Path $pathCompleto) {
        $dcuFiles = Get-ChildItem -Path $pathCompleto -Filter "*.dcu" -Recurse -ErrorAction SilentlyContinue
        if ($dcuFiles) {
            $count = $dcuFiles.Count
            $dcuFiles | Remove-Item -Force -ErrorAction SilentlyContinue
            $totalRemovidos += $count
            Write-Host "  [OK] Removidos $count arquivos .dcu de $pasta" -ForegroundColor Green
        } else {
            Write-Host "  [INFO] Nenhum .dcu encontrado em $pasta" -ForegroundColor Gray
        }
    }
}

if ($totalRemovidos -gt 0) {
    Write-Host "  [OK] Total: $totalRemovidos arquivos .dcu removidos" -ForegroundColor Green
} else {
    Write-Host "  [INFO] Nenhum arquivo .dcu encontrado para remover" -ForegroundColor Gray
}

# Limpar cache do usuario
Write-Host "[4/4] Limpando cache do usuario..." -ForegroundColor Yellow
$cachePaths = @(
    "$env:LOCALAPPDATA\Embarcadero",
    "$env:APPDATA\Embarcadero"
)

foreach ($cachePath in $cachePaths) {
    if (Test-Path $cachePath) {
        $dcuCache = Get-ChildItem -Path $cachePath -Filter "*.dcu" -Recurse -ErrorAction SilentlyContinue
        if ($dcuCache) {
            $count = $dcuCache.Count
            $dcuCache | Remove-Item -Force -ErrorAction SilentlyContinue
            Write-Host "  [OK] Removidos $count arquivos .dcu do cache" -ForegroundColor Green
        }
    }
}

# Resumo final
Write-Host ""
Write-Host "================================================" -ForegroundColor Green
Write-Host "  [OK] Limpeza concluida!" -ForegroundColor Green
Write-Host "================================================" -ForegroundColor Green
Write-Host ""
Write-Host "Proximos passos:" -ForegroundColor Yellow
Write-Host "  1. Abra o Delphi" -ForegroundColor White
Write-Host "  2. Abra o projeto Emissor.dproj" -ForegroundColor White
Write-Host "  3. Va em: Project -> Rebuild All (Shift+F9)" -ForegroundColor White
Write-Host "  4. O Delphi vai recompilar todas as units do sistema automaticamente" -ForegroundColor White
Write-Host "  5. A primeira compilacao pode demorar mais (recompilando tudo)" -ForegroundColor White
Write-Host ""
Write-Host "[INFO] A primeira vez que compilar pode demorar mais tempo" -ForegroundColor Cyan
Write-Host "       pois o Delphi esta recompilando todas as units do sistema." -ForegroundColor Cyan
Write-Host ""


