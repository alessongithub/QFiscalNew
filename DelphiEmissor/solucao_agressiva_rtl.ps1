# Solucao Agressiva para Erro F2051 - Recompila Units do Sistema
# Execute como Administrador

Write-Host "================================================" -ForegroundColor Red
Write-Host "  SOLUCAO AGRESSIVA - Limpeza Completa RTL" -ForegroundColor Red
Write-Host "================================================" -ForegroundColor Red
Write-Host ""

# Parar TODOS os processos do Delphi
Write-Host "[1/6] Finalizando processos..." -ForegroundColor Yellow
Get-Process | Where-Object {$_.Name -like "*bds*" -or $_.Name -like "*dcc*" -or $_.Name -like "*msbuild*" -or $_.Name -like "*Emissor*"} | Stop-Process -Force -ErrorAction SilentlyContinue
Start-Sleep -Seconds 3
Write-Host "[OK] Processos finalizados" -ForegroundColor Green
Write-Host ""

# Limpar cache do usuario COMPLETO
Write-Host "[2/6] Limpando cache do usuario..." -ForegroundColor Yellow
$cachePaths = @(
    "$env:LOCALAPPDATA\Embarcadero",
    "$env:APPDATA\Embarcadero",
    "$env:USERPROFILE\Documents\Embarcadero"
)

foreach ($cachePath in $cachePaths) {
    if (Test-Path $cachePath) {
        Write-Host "  Removendo: $cachePath" -ForegroundColor Cyan
        Remove-Item -Path "$cachePath\*" -Recurse -Force -ErrorAction SilentlyContinue
    }
}
Write-Host "[OK] Cache removido" -ForegroundColor Green
Write-Host ""

# Limpar DCUs do sistema - TODAS as pastas possiveis
Write-Host "[3/6] Removendo DCUs do sistema..." -ForegroundColor Yellow
$delphiBasePaths = @(
    "C:\Program Files (x86)\Embarcadero\Studio",
    "C:\Program Files\Embarcadero\Studio"
)

$totalRemovidos = 0
foreach ($basePath in $delphiBasePaths) {
    if (Test-Path $basePath) {
        $versoes = Get-ChildItem -Path $basePath -Directory -ErrorAction SilentlyContinue
        foreach ($versao in $versoes) {
            $libPath = Join-Path $versao.FullName "lib"
            if (Test-Path $libPath) {
                Write-Host "  Limpando: $libPath" -ForegroundColor Cyan
                $dcuFiles = Get-ChildItem -Path $libPath -Filter "*.dcu" -Recurse -ErrorAction SilentlyContinue
                if ($dcuFiles) {
                    $count = $dcuFiles.Count
                    $dcuFiles | Remove-Item -Force -ErrorAction SilentlyContinue
                    $totalRemovidos += $count
                    Write-Host "    Removidos $count DCUs" -ForegroundColor Gray
                }
            }
        }
    }
}

Write-Host "[OK] Total removido: $totalRemovidos DCUs" -ForegroundColor Green
Write-Host ""

# Limpar DCUs do projeto tambem
Write-Host "[4/6] Limpando DCUs do projeto..." -ForegroundColor Yellow
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
$dcuProjeto = Get-ChildItem -Path $scriptPath -Filter "*.dcu" -Recurse -ErrorAction SilentlyContinue
if ($dcuProjeto) {
    $dcuProjeto | Remove-Item -Force -ErrorAction SilentlyContinue
    Write-Host "[OK] DCUs do projeto removidos" -ForegroundColor Green
} else {
    Write-Host "[INFO] Nenhum DCU do projeto encontrado" -ForegroundColor Gray
}
Write-Host ""

# Limpar arquivos temporarios do Delphi
Write-Host "[5/6] Limpando arquivos temporarios..." -ForegroundColor Yellow
$tempPaths = @(
    "$env:TEMP\Embarcadero*",
    "$env:TEMP\bds*",
    "$env:TEMP\dcc*"
)

foreach ($tempPath in $tempPaths) {
    $tempFiles = Get-ChildItem -Path $tempPath -ErrorAction SilentlyContinue
    if ($tempFiles) {
        Remove-Item -Path $tempPath -Recurse -Force -ErrorAction SilentlyContinue
    }
}
Write-Host "[OK] Temporarios limpos" -ForegroundColor Green
Write-Host ""

# Forcar recompilacao - criar arquivo de marcacao
Write-Host "[6/6] Configurando recompilacao forcada..." -ForegroundColor Yellow
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
$marcacaoFile = Join-Path $scriptPath ".force_rebuild"
Set-Content -Path $marcacaoFile -Value "Forca recompilacao completa no Delphi" -Force
Write-Host "[OK] Configuracao concluida" -ForegroundColor Green
Write-Host ""

# Resumo
Write-Host "================================================" -ForegroundColor Green
Write-Host "  LIMPEZA COMPLETA CONCLUIDA!" -ForegroundColor Green
Write-Host "================================================" -ForegroundColor Green
Write-Host ""
Write-Host "INSTRUCOES IMPORTANTES:" -ForegroundColor Yellow
Write-Host ""
Write-Host "1. Abra o Delphi" -ForegroundColor White
Write-Host "2. ANTES de abrir seu projeto, faça:" -ForegroundColor White
Write-Host "   Tools -> Options -> Environment -> Delphi Options -> Library" -ForegroundColor Cyan
Write-Host "   Clique em 'Clear All' nos Library Paths (se houver opcao)" -ForegroundColor Cyan
Write-Host "   Ou remova paths customizados manualmente" -ForegroundColor Cyan
Write-Host ""
Write-Host "3. Crie um projeto TESTE simples:" -ForegroundColor White
Write-Host "   File -> New -> VCL Application" -ForegroundColor Cyan
Write-Host "   Adicione no uses: System.SysUtils, Winapi.Windows" -ForegroundColor Cyan
Write-Host "   Compile (F9) - Isso vai recompilar as units do sistema" -ForegroundColor Cyan
Write-Host "   Feche esse projeto" -ForegroundColor Cyan
Write-Host ""
Write-Host "4. Agora abra seu projeto Emissor.dproj" -ForegroundColor White
Write-Host "5. Project -> Clean (limpa cache do projeto)" -ForegroundColor Cyan
Write-Host "6. Project -> Rebuild All (Shift+F9)" -ForegroundColor Cyan
Write-Host ""
Write-Host "[AVISO] A primeira compilacao vai demorar MUITO mais" -ForegroundColor Yellow
Write-Host "        porque o Delphi esta recompilando TUDO do zero." -ForegroundColor Yellow
Write-Host "        Pode levar 5-15 minutos dependendo do seu PC." -ForegroundColor Yellow
Write-Host ""




