# Solucao AGRESSIVA - Remove TODOS os DCUs possiveis
# Execute como Administrador
# ATENCAO: Isso vai remover TODOS os DCUs, forçando recompilacao completa

Write-Host "================================================" -ForegroundColor Red
Write-Host "  SOLUCAO AGRESSIVA - Remover TODOS os DCUs" -ForegroundColor Red
Write-Host "================================================" -ForegroundColor Red
Write-Host ""
Write-Host "[AVISO] Este script remove TODOS os DCUs encontrados." -ForegroundColor Yellow
Write-Host "        O Delphi vai ter que recompilar TUDO do zero." -ForegroundColor Yellow
Write-Host "        A primeira compilacao vai demorar MUITO!" -ForegroundColor Yellow
Write-Host ""

$confirmar = Read-Host "Continuar? (S/N)"
if ($confirmar -ne "S" -and $confirmar -ne "s") {
    Write-Host "Operacao cancelada." -ForegroundColor Yellow
    exit
}

# Parar TODOS os processos
Write-Host "[1/8] Finalizando TODOS os processos..." -ForegroundColor Yellow
Get-Process | Where-Object {
    $_.Name -like "*bds*" -or 
    $_.Name -like "*dcc*" -or 
    $_.Name -like "*msbuild*" -or 
    $_.Name -like "*Emissor*" -or
    $_.Name -like "*rad*"
} | Stop-Process -Force -ErrorAction SilentlyContinue
Start-Sleep -Seconds 5
Write-Host "[OK] Processos finalizados" -ForegroundColor Green
Write-Host ""

# Remover DCUs do sistema - TODAS as versoes
Write-Host "[2/8] Removendo DCUs do sistema (TODAS as versoes)..." -ForegroundColor Yellow
$basePaths = @(
    "C:\Program Files (x86)\Embarcadero\Studio",
    "C:\Program Files\Embarcadero\Studio"
)

$totalDCUs = 0
foreach ($basePath in $basePaths) {
    if (Test-Path $basePath) {
        $versoes = Get-ChildItem -Path $basePath -Directory -ErrorAction SilentlyContinue
        foreach ($versao in $versoes) {
            Write-Host "  Processando versao: $($versao.Name)" -ForegroundColor Cyan
            
            # Limpar lib\Win32 e Win64
            $libPath = Join-Path $versao.FullName "lib"
            if (Test-Path $libPath) {
                $pastas = @("Win32\debug", "Win32\release", "Win64\debug", "Win64\release")
                foreach ($pasta in $pastas) {
                    $pathCompleto = Join-Path $libPath $pasta
                    if (Test-Path $pathCompleto) {
                        $dcus = Get-ChildItem -Path $pathCompleto -Filter "*.dcu" -Recurse -ErrorAction SilentlyContinue
                        if ($dcus) {
                            $count = $dcus.Count
                            $dcus | Remove-Item -Force -ErrorAction SilentlyContinue
                            $totalDCUs += $count
                            Write-Host "    Removidos $count DCUs de $pasta" -ForegroundColor Gray
                        }
                    }
                }
            }
            
            # Limpar também bin (se houver DCUs lá)
            $binPath = Join-Path $versao.FullName "bin"
            if (Test-Path $binPath) {
                $dcus = Get-ChildItem -Path $binPath -Filter "*.dcu" -Recurse -ErrorAction SilentlyContinue
                if ($dcus) {
                    $count = $dcus.Count
                    $dcus | Remove-Item -Force -ErrorAction SilentlyContinue
                    $totalDCUs += $count
                    Write-Host "    Removidos $count DCUs de bin\" -ForegroundColor Gray
                }
            }
        }
    }
}

Write-Host "[OK] Total de DCUs do sistema removidos: $totalDCUs" -ForegroundColor Green
Write-Host ""

# Remover DCUs do cache do usuario - COMPLETO
Write-Host "[3/8] Removendo cache do usuario..." -ForegroundColor Yellow
$cachePaths = @(
    "$env:LOCALAPPDATA\Embarcadero",
    "$env:APPDATA\Embarcadero",
    "$env:USERPROFILE\Documents\Embarcadero"
)

foreach ($cachePath in $cachePaths) {
    if (Test-Path $cachePath) {
        Write-Host "  Limpando: $cachePath" -ForegroundColor Cyan
        # Remover TODOS os DCUs do cache
        $dcus = Get-ChildItem -Path $cachePath -Filter "*.dcu" -Recurse -ErrorAction SilentlyContinue
        if ($dcus) {
            $count = $dcus.Count
            $dcus | Remove-Item -Force -ErrorAction SilentlyContinue
            Write-Host "    Removidos $count DCUs do cache" -ForegroundColor Gray
        }
        # Tambem limpar arquivos temporarios
        $temps = Get-ChildItem -Path $cachePath -Filter "*.*~" -Recurse -ErrorAction SilentlyContinue
        if ($temps) {
            $temps | Remove-Item -Force -ErrorAction SilentlyContinue
        }
    }
}
Write-Host "[OK] Cache limpo" -ForegroundColor Green
Write-Host ""

# Remover DCUs do projeto
Write-Host "[4/8] Removendo DCUs do projeto..." -ForegroundColor Yellow
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
$dcusProjeto = Get-ChildItem -Path $scriptPath -Filter "*.dcu" -Recurse -ErrorAction SilentlyContinue
if ($dcusProjeto) {
    $count = $dcusProjeto.Count
    $dcusProjeto | Remove-Item -Force -ErrorAction SilentlyContinue
    Write-Host "[OK] Removidos $count DCUs do projeto" -ForegroundColor Green
} else {
    Write-Host "[INFO] Nenhum DCU do projeto encontrado" -ForegroundColor Gray
}
Write-Host ""

# Remover executaveis do projeto
Write-Host "[5/8] Removendo executaveis..." -ForegroundColor Yellow
$exes = Get-ChildItem -Path $scriptPath -Filter "*.exe" -Recurse -ErrorAction SilentlyContinue
if ($exes) {
    $exes | Remove-Item -Force -ErrorAction SilentlyContinue
    Write-Host "[OK] Executaveis removidos" -ForegroundColor Green
}
Write-Host ""

# Limpar arquivos de cache do projeto
Write-Host "[6/8] Limpando cache do projeto..." -ForegroundColor Yellow
$cacheFiles = @("*.identcache", "*.local", "*.stat", "*.~*", "*.dcp")
foreach ($pattern in $cacheFiles) {
    $files = Get-ChildItem -Path $scriptPath -Filter $pattern -ErrorAction SilentlyContinue
    if ($files) {
        $files | Remove-Item -Force -ErrorAction SilentlyContinue
    }
}
Write-Host "[OK] Cache do projeto limpo" -ForegroundColor Green
Write-Host ""

# Limpar arquivos temporarios do Delphi
Write-Host "[7/8] Limpando arquivos temporarios..." -ForegroundColor Yellow
$tempPaths = @(
    "$env:TEMP\Embarcadero*",
    "$env:TEMP\bds*",
    "$env:TEMP\dcc*",
    "$env:TEMP\*.dcu"
)

foreach ($tempPath in $tempPaths) {
    $tempFiles = Get-ChildItem -Path $tempPath -ErrorAction SilentlyContinue
    if ($tempFiles) {
        $tempFiles | Remove-Item -Force -Recurse -ErrorAction SilentlyContinue
    }
}
Write-Host "[OK] Temporarios limpos" -ForegroundColor Green
Write-Host ""

# Verificar se ha multiplas instalacoes do Delphi
Write-Host "[8/8] Verificando instalacoes do Delphi..." -ForegroundColor Yellow
$installacoes = @()
$installacoes += Get-ChildItem "C:\Program Files (x86)\Embarcadero\Studio\" -Directory -ErrorAction SilentlyContinue
$installacoes += Get-ChildItem "C:\Program Files\Embarcadero\Studio\" -Directory -ErrorAction SilentlyContinue

if ($installacoes.Count -gt 1) {
    Write-Host "[AVISO] Encontradas $($installacoes.Count) instalacoes do Delphi:" -ForegroundColor Yellow
    foreach ($inst in $installacoes) {
        Write-Host "  - $($inst.FullName)" -ForegroundColor Cyan
    }
    Write-Host "[INFO] Certifique-se de usar a versao correta ao abrir o projeto" -ForegroundColor Yellow
} else {
    if ($installacoes.Count -eq 1) {
        Write-Host "[OK] Delphi encontrado: $($installacoes[0].FullName)" -ForegroundColor Green
    } else {
        Write-Host "[AVISO] Delphi nao encontrado automaticamente" -ForegroundColor Yellow
    }
}
Write-Host ""

# Resumo final
Write-Host "================================================" -ForegroundColor Green
Write-Host "  [OK] Limpeza AGRESSIVA concluida!" -ForegroundColor Green
Write-Host "================================================" -ForegroundColor Green
Write-Host ""
Write-Host "TOTAL REMOVIDO:" -ForegroundColor Yellow
Write-Host "  - $totalDCUs DCUs do sistema" -ForegroundColor White
Write-Host "  - Todos os DCUs do cache do usuario" -ForegroundColor White
Write-Host "  - Todos os DCUs do projeto" -ForegroundColor White
Write-Host "  - Todos os executaveis e caches" -ForegroundColor White
Write-Host ""
Write-Host "PROXIMOS PASSOS (IMPORTANTE):" -ForegroundColor Yellow
Write-Host ""
Write-Host "1. Abra o Delphi" -ForegroundColor White
Write-Host "2. Tools -> Options -> Environment Options -> Delphi Options -> Library" -ForegroundColor Cyan
Write-Host "   - Verifique se Library Paths estao corretos" -ForegroundColor Gray
Write-Host "   - Paths do ACBr devem estar presentes" -ForegroundColor Gray
Write-Host ""
Write-Host "3. File -> New -> VCL Application (PROJETO TESTE OBRIGATORIO)" -ForegroundColor White
Write-Host "   - Adicione no uses: System.SysUtils, Winapi.Windows, System.Messaging" -ForegroundColor Cyan
Write-Host "   - Compile (F9) - AGUARDE! Vai demorar 5-15 minutos" -ForegroundColor Cyan
Write-Host "   - O Delphi esta recompilando TODAS as units do sistema" -ForegroundColor Cyan
Write-Host "   - Se der erro, tente compilar novamente (F9)" -ForegroundColor Yellow
Write-Host "   - Feche o projeto teste" -ForegroundColor White
Write-Host ""
Write-Host "4. Abra seu projeto Emissor.dproj" -ForegroundColor White
Write-Host "5. Project -> Clean" -ForegroundColor Cyan
Write-Host "6. Project -> Rebuild All (Shift+F9)" -ForegroundColor Cyan
Write-Host "   - Vai demorar MUITO na primeira vez (recompilando tudo)" -ForegroundColor Yellow
Write-Host ""
Write-Host "[AVISO] A primeira compilacao pode levar 10-20 minutos!" -ForegroundColor Red
Write-Host "        NAO INTERROMPA o processo!" -ForegroundColor Red
Write-Host "        O Delphi esta recompilando TODO o RTL do zero!" -ForegroundColor Red
Write-Host ""




