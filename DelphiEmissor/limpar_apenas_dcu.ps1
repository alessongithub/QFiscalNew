# Script para limpar APENAS DCUs (nao mexe em Library Paths)
# Seguro para manter ACBr e outros componentes instalados

Write-Host "================================================" -ForegroundColor Cyan
Write-Host "  Limpeza APENAS de DCUs (Mantem Library Paths)" -ForegroundColor Cyan
Write-Host "  Seguro para ACBr e outros componentes" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "[INFO] Este script remove APENAS arquivos .dcu (compilados)" -ForegroundColor Yellow
Write-Host "       NAO mexe nos Library Paths!" -ForegroundColor Yellow
Write-Host "       ACBr e outros componentes continuam funcionando!" -ForegroundColor Green
Write-Host ""

# Parar processos do Delphi
Write-Host "[1/5] Verificando processos..." -ForegroundColor Yellow
$processos = Get-Process | Where-Object {$_.Name -like "*bds*" -or $_.Name -like "*dcc*" -or $_.Name -like "*msbuild*" -or $_.Name -like "*Emissor*"}
if ($processos) {
    Write-Host "  [AVISO] Processos encontrados. Fechando..." -ForegroundColor Yellow
    $processos | Stop-Process -Force -ErrorAction SilentlyContinue
    Start-Sleep -Seconds 3
    Write-Host "  [OK] Processos fechados" -ForegroundColor Green
} else {
    Write-Host "  [OK] Nenhum processo encontrado" -ForegroundColor Green
}
Write-Host ""

# Limpar DCUs do projeto
Write-Host "[2/5] Removendo DCUs do projeto..." -ForegroundColor Yellow
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
$dcuProjeto = Get-ChildItem -Path $scriptPath -Filter "*.dcu" -Recurse -ErrorAction SilentlyContinue
if ($dcuProjeto) {
    $count = $dcuProjeto.Count
    $dcuProjeto | Remove-Item -Force -ErrorAction SilentlyContinue
    Write-Host "  [OK] Removidos $count DCUs do projeto" -ForegroundColor Green
} else {
    Write-Host "  [INFO] Nenhum DCU do projeto encontrado" -ForegroundColor Gray
}

# Remover executavel do projeto tambem
$exeProjeto = Get-ChildItem -Path $scriptPath -Filter "*.exe" -Recurse -ErrorAction SilentlyContinue
if ($exeProjeto) {
    $exeProjeto | Remove-Item -Force -ErrorAction SilentlyContinue
    Write-Host "  [OK] Executavel do projeto removido" -ForegroundColor Green
}
Write-Host ""

# Limpar cache do projeto
Write-Host "[3/5] Limpando cache do projeto..." -ForegroundColor Yellow
$cacheFiles = @(
    "*.identcache",
    "*.local",
    "*.stat",
    "*.~*"
)

$cacheRemovidos = 0
foreach ($pattern in $cacheFiles) {
    $files = Get-ChildItem -Path $scriptPath -Filter $pattern -ErrorAction SilentlyContinue
    if ($files) {
        $files | Remove-Item -Force -ErrorAction SilentlyContinue
        $cacheRemovidos += $files.Count
    }
}

if ($cacheRemovidos -gt 0) {
    Write-Host "  [OK] Removidos $cacheRemovidos arquivos de cache" -ForegroundColor Green
} else {
    Write-Host "  [INFO] Nenhum arquivo de cache encontrado" -ForegroundColor Gray
}
Write-Host ""

# Limpar DCUs do sistema (RTL)
Write-Host "[4/5] Removendo DCUs do sistema do Delphi..." -ForegroundColor Yellow
Write-Host "  [INFO] Isso NAO afeta Library Paths do ACBr!" -ForegroundColor Cyan

$delphiBasePaths = @(
    "C:\Program Files (x86)\Embarcadero\Studio",
    "C:\Program Files\Embarcadero\Studio"
)

$totalDCUsRemovidos = 0
$delphiEncontrado = $false

foreach ($basePath in $delphiBasePaths) {
    if (Test-Path $basePath) {
        $versoes = Get-ChildItem -Path $basePath -Directory -ErrorAction SilentlyContinue
        foreach ($versao in $versoes) {
            $libPath = Join-Path $versao.FullName "lib"
            if (Test-Path $libPath) {
                $delphiEncontrado = $true
                Write-Host "  Processando: $libPath" -ForegroundColor Cyan
                
                $pastasDCU = @("Win32\debug", "Win32\release", "Win64\debug", "Win64\release")
                foreach ($pasta in $pastasDCU) {
                    $pathCompleto = Join-Path $libPath $pasta
                    if (Test-Path $pathCompleto) {
                        $dcuFiles = Get-ChildItem -Path $pathCompleto -Filter "*.dcu" -Recurse -ErrorAction SilentlyContinue
                        if ($dcuFiles) {
                            $count = $dcuFiles.Count
                            $dcuFiles | Remove-Item -Force -ErrorAction SilentlyContinue
                            $totalDCUsRemovidos += $count
                            Write-Host "    Removidos $count DCUs de $pasta" -ForegroundColor Gray
                        }
                    }
                }
            }
        }
    }
}

if ($delphiEncontrado) {
    if ($totalDCUsRemovidos -gt 0) {
        Write-Host "  [OK] Total removido: $totalDCUsRemovidos DCUs do sistema" -ForegroundColor Green
    } else {
        Write-Host "  [INFO] Nenhum DCU do sistema encontrado" -ForegroundColor Gray
    }
} else {
    Write-Host "  [AVISO] Delphi nao encontrado automaticamente" -ForegroundColor Yellow
    Write-Host "          DCUs do sistema nao foram removidos" -ForegroundColor Yellow
}
Write-Host ""

# Limpar cache do usuario
Write-Host "[5/5] Limpando cache do usuario..." -ForegroundColor Yellow
$cachePaths = @(
    "$env:LOCALAPPDATA\Embarcadero",
    "$env:APPDATA\Embarcadero"
)

foreach ($cachePath in $cachePaths) {
    if (Test-Path $cachePath) {
        # Remover apenas arquivos .dcu do cache, nao tudo
        $dcuCache = Get-ChildItem -Path $cachePath -Filter "*.dcu" -Recurse -ErrorAction SilentlyContinue
        if ($dcuCache) {
            $count = $dcuCache.Count
            $dcuCache | Remove-Item -Force -ErrorAction SilentlyContinue
            Write-Host "  [OK] Removidos $count DCUs do cache do usuario" -ForegroundColor Green
        }
    }
}
Write-Host ""

# Resumo final
Write-Host "================================================" -ForegroundColor Green
Write-Host "  [OK] Limpeza concluida!" -ForegroundColor Green
Write-Host "================================================" -ForegroundColor Green
Write-Host ""
Write-Host "IMPORTANTE:" -ForegroundColor Yellow
Write-Host "  [OK] Library Paths foram MANTIDOS intactos!" -ForegroundColor Green
Write-Host "  [OK] ACBr e outros componentes continuam funcionando!" -ForegroundColor Green
Write-Host ""
Write-Host "PROXIMOS PASSOS:" -ForegroundColor Yellow
Write-Host ""
Write-Host "OPCAO 1 - Direto (mais rapido):" -ForegroundColor Cyan
Write-Host "  1. Abra o Delphi" -ForegroundColor White
Write-Host "  2. Abra o projeto Emissor.dproj" -ForegroundColor White
Write-Host "  3. Project -> Clean" -ForegroundColor White
Write-Host "  4. Project -> Rebuild All (Shift+F9)" -ForegroundColor White
Write-Host "  5. O Delphi vai recompilar TUDO automaticamente" -ForegroundColor White
Write-Host ""
Write-Host "OPCAO 2 - Com projeto de teste (mais seguro):" -ForegroundColor Cyan
Write-Host "  1. Abra o Delphi" -ForegroundColor White
Write-Host "  2. File -> New -> VCL Application (projeto teste)" -ForegroundColor White
Write-Host "  3. Adicione no uses: System.SysUtils, Winapi.Windows" -ForegroundColor White
Write-Host "  4. Compile (F9) - forca recompilacao do RTL" -ForegroundColor White
Write-Host "  5. Feche o projeto teste" -ForegroundColor White
Write-Host "  6. Abra Emissor.dproj" -ForegroundColor White
Write-Host "  7. Project -> Rebuild All" -ForegroundColor White
Write-Host ""
Write-Host "RECOMENDACAO:" -ForegroundColor Yellow
Write-Host "  Tente primeiro OPCAO 1 (mais rapido)." -ForegroundColor White
Write-Host "  Se der erro, use OPCAO 2 (projeto teste)." -ForegroundColor White
Write-Host ""
Write-Host "[INFO] A primeira compilacao vai demorar mais" -ForegroundColor Cyan
Write-Host "       (Delphi esta recompilando tudo do zero)" -ForegroundColor Cyan
Write-Host ""




