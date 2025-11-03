# Script para limpar cache do Delphi e resolver erro F2051 no Windows 11
# Execute como Administrador se necessario

Write-Host "================================================" -ForegroundColor Cyan
Write-Host "  Limpeza de Cache do Delphi - Windows 11" -ForegroundColor Cyan
Write-Host "  Resolvendo: F2051 Unit System.SysUtils" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""

# Parar processos do Delphi e Emissor (se estiverem rodando)
Write-Host "[1/6] Verificando processos rodando..." -ForegroundColor Yellow
$processos = Get-Process | Where-Object {$_.Name -like "*bds*" -or $_.Name -like "*dcc*" -or $_.Name -like "*msbuild*" -or $_.Name -like "*Emissor*"}
if ($processos) {
    Write-Host "  [AVISO] Encontrados processos rodando. Tentando fechar..." -ForegroundColor Yellow
    $processos | Stop-Process -Force -ErrorAction SilentlyContinue
    Start-Sleep -Seconds 2
    Write-Host "  [OK] Processos fechados" -ForegroundColor Green
} else {
    Write-Host "  [OK] Nenhum processo encontrado" -ForegroundColor Green
}

# Remover executavel (.exe) - importante para recompilar do zero no Windows 11
Write-Host "[2/6] Removendo executavel (.exe)..." -ForegroundColor Yellow
$exeFiles = @()
$exeFiles += Get-ChildItem -Path ".\Win32\Debug" -Filter "*.exe" -ErrorAction SilentlyContinue
$exeFiles += Get-ChildItem -Path ".\Win32\Release" -Filter "*.exe" -ErrorAction SilentlyContinue
$exeFiles += Get-ChildItem -Path ".\Win64\Debug" -Filter "*.exe" -ErrorAction SilentlyContinue
$exeFiles += Get-ChildItem -Path ".\Win64\Release" -Filter "*.exe" -ErrorAction SilentlyContinue
$exeFiles += Get-ChildItem -Path "." -Filter "Emissor.exe" -ErrorAction SilentlyContinue

if ($exeFiles.Count -gt 0) {
    $exeFiles | Remove-Item -Force -ErrorAction SilentlyContinue
    Write-Host "  [OK] Removidos $($exeFiles.Count) arquivo(s) .exe" -ForegroundColor Green
    Write-Host "     (O .exe sera recriado quando voce fizer Rebuild All)" -ForegroundColor Gray
} else {
    Write-Host "  [INFO] Nenhum arquivo .exe encontrado" -ForegroundColor Gray
}

# Limpar arquivos .dcu
Write-Host "[3/6] Removendo arquivos compilados (.dcu)..." -ForegroundColor Yellow
$dcuFiles = @()
$dcuFiles += Get-ChildItem -Path ".\Win32" -Recurse -Filter "*.dcu" -ErrorAction SilentlyContinue
$dcuFiles += Get-ChildItem -Path ".\Win64" -Recurse -Filter "*.dcu" -ErrorAction SilentlyContinue

if ($dcuFiles.Count -gt 0) {
    $dcuFiles | Remove-Item -Force -ErrorAction SilentlyContinue
    Write-Host "  [OK] Removidos $($dcuFiles.Count) arquivos .dcu" -ForegroundColor Green
} else {
    Write-Host "  [INFO] Nenhum arquivo .dcu encontrado" -ForegroundColor Gray
}

# Limpar cache do projeto
Write-Host "[4/6] Removendo arquivos de cache..." -ForegroundColor Yellow
$cacheFiles = @()
$cacheFiles += Get-ChildItem -Path "." -Filter "*.identcache" -ErrorAction SilentlyContinue
$cacheFiles += Get-ChildItem -Path "." -Filter "*.local" -ErrorAction SilentlyContinue
$cacheFiles += Get-ChildItem -Path "." -Filter "*.stat" -ErrorAction SilentlyContinue
$cacheFiles += Get-ChildItem -Path "." -Filter "*.~*" -ErrorAction SilentlyContinue

if ($cacheFiles.Count -gt 0) {
    $cacheFiles | Remove-Item -Force -ErrorAction SilentlyContinue
    Write-Host "  [OK] Removidos $($cacheFiles.Count) arquivos de cache" -ForegroundColor Green
} else {
    Write-Host "  [INFO] Nenhum arquivo de cache encontrado" -ForegroundColor Gray
}

# Limpar pastas de build (mantendo XMLs e XSDs)
Write-Host "[5/6] Limpando outras pastas de build..." -ForegroundColor Yellow
$pastasBuild = @(
    ".\Win32\Debug",
    ".\Win32\Release",
    ".\Win64\Debug",
    ".\Win64\Release"
)

$arquivosRemovidos = 0
foreach ($pasta in $pastasBuild) {
    if (Test-Path $pasta) {
        $arquivos = Get-ChildItem -Path $pasta -Recurse -File | Where-Object {
            $_.Extension -ne ".xml" -and 
            $_.Extension -ne ".xsd" -and
            $_.Extension -ne ".pas" -and
            $_.Extension -ne ".dfm" -and
            $_.Extension -ne ".exe" -and
            $_.Extension -ne ".dcu"
        }
        if ($arquivos) {
            $arquivos | Remove-Item -Force -ErrorAction SilentlyContinue
            $arquivosRemovidos += $arquivos.Count
        }
    }
}

if ($arquivosRemovidos -gt 0) {
    Write-Host "  [OK] Removidos $arquivosRemovidos arquivos de build" -ForegroundColor Green
} else {
    Write-Host "  [INFO] Nenhum arquivo de build encontrado" -ForegroundColor Gray
}

# Resumo final
Write-Host ""
Write-Host "================================================" -ForegroundColor Green
Write-Host "  [OK] Limpeza concluida com sucesso!" -ForegroundColor Green
Write-Host "================================================" -ForegroundColor Green
Write-Host ""
Write-Host "Proximos passos:" -ForegroundColor Yellow
Write-Host "  1. Abra o Delphi" -ForegroundColor White
Write-Host "  2. Abra o projeto Emissor.dproj" -ForegroundColor White
Write-Host "  3. Va em: Project -> Rebuild All (Shift+F9)" -ForegroundColor White
Write-Host "  4. Aguarde a recompilacao completa (sera gerado novo .exe)" -ForegroundColor White
Write-Host ""
Write-Host "Dica: Se o erro persistir, verifique:" -ForegroundColor Cyan
Write-Host "  - Versao do projeto corresponde a versao do Delphi" -ForegroundColor Gray
Write-Host "  - Library paths nao apontam para versoes antigas" -ForegroundColor Gray
Write-Host "  - Windows 11 tem todas as atualizacoes instaladas" -ForegroundColor Gray
Write-Host ""

# Perguntar se quer abrir o Delphi
$abrir = Read-Host "Deseja abrir o projeto no Delphi agora? (S/N)"
if ($abrir -eq "S" -or $abrir -eq "s") {
    $delphiPaths = @(
        "C:\Program Files (x86)\Embarcadero\Studio\*\bin\bds.exe",
        "C:\Program Files\Embarcadero\Studio\*\bin\bds.exe"
    )
    
    $delphiEncontrado = $false
    foreach ($path in $delphiPaths) {
        $bds = Get-Item $path -ErrorAction SilentlyContinue | Select-Object -First 1
        if ($bds) {
            Start-Process -FilePath $bds.FullName -ArgumentList "Emissor.dproj"
            Write-Host "[OK] Abrindo projeto no Delphi..." -ForegroundColor Green
            $delphiEncontrado = $true
            break
        }
    }
    
    if (-not $delphiEncontrado) {
        Write-Host "[AVISO] Delphi nao encontrado automaticamente. Abra manualmente." -ForegroundColor Yellow
    }
}
