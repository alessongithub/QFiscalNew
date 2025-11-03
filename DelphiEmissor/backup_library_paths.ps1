# Script para fazer backup dos Library Paths do Delphi
# Execute ANTES de limpar qualquer coisa!

Write-Host "================================================" -ForegroundColor Cyan
Write-Host "  Backup de Library Paths do Delphi" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "[INFO] Este script cria backup dos Library Paths do Delphi." -ForegroundColor Yellow
Write-Host "       Execute ANTES de limpar ou modificar qualquer coisa!" -ForegroundColor Yellow
Write-Host ""

# Localizar arquivo de configuração do Delphi
$delphiVersion = Read-Host "Digite a versao do Delphi (ex: 21.0 para Delphi 12, ou encontre em Help->About)"

$configPaths = @(
    "$env:APPDATA\Embarcadero\BDS\$delphiVersion\",
    "$env:LOCALAPPDATA\Embarcadero\BDS\$delphiVersion\",
    "$env:USERPROFILE\Documents\Embarcadero\Studio\$delphiVersion\"
)

$configEncontrado = $false
$configFile = $null

Write-Host "[1/3] Procurando arquivo de configuracao do Delphi..." -ForegroundColor Yellow

foreach ($basePath in $configPaths) {
    if (Test-Path $basePath) {
        $envFile = Get-ChildItem -Path $basePath -Filter "*.env" -Recurse -ErrorAction SilentlyContinue | Select-Object -First 1
        if ($envFile) {
            $configFile = $envFile.FullName
            $configEncontrado = $true
            Write-Host "  [OK] Arquivo encontrado: $configFile" -ForegroundColor Green
            break
        }
    }
}

if (-not $configEncontrado) {
    Write-Host "  [AVISO] Arquivo de configuracao nao encontrado automaticamente." -ForegroundColor Yellow
    Write-Host "  [INFO] Library Paths estao salvos no Registro do Windows." -ForegroundColor Cyan
    Write-Host ""
    Write-Host "  Para fazer backup manual:" -ForegroundColor Yellow
    Write-Host "  1. Abra o Delphi" -ForegroundColor White
    Write-Host "  2. Tools -> Options -> Environment Options -> Delphi Options -> Library" -ForegroundColor White
    Write-Host "  3. Copie todos os Library Paths para um arquivo de texto" -ForegroundColor White
    Write-Host "  4. Salve como 'library_paths_backup.txt'" -ForegroundColor White
    Write-Host ""
    
    $manual = Read-Host "Voce quer tentar extrair do Registro do Windows? (S/N)"
    if ($manual -eq "S" -or $manual -eq "s") {
        # Tentar extrair do registro
        $regPath = "HKCU:\Software\Embarcadero\BDS\$delphiVersion\Library\Win32"
        if (Test-Path $regPath) {
            Write-Host "  [OK] Extraindo do Registro..." -ForegroundColor Cyan
            $paths = (Get-ItemProperty -Path $regPath -Name "Search Path" -ErrorAction SilentlyContinue).'Search Path'
            if ($paths) {
                $backupFile = Join-Path (Get-Location) "library_paths_backup_registry.txt"
                $paths | Out-File -FilePath $backupFile -Encoding UTF8
                Write-Host "  [OK] Backup salvo em: $backupFile" -ForegroundColor Green
            }
        }
    }
    exit
}

# Criar backup do arquivo de configuração
Write-Host "[2/3] Criando backup do arquivo de configuracao..." -ForegroundColor Yellow
$backupFile = Join-Path (Get-Location) "delphi_config_backup_$(Get-Date -Format 'yyyyMMdd_HHmmss').env"
Copy-Item -Path $configFile -Destination $backupFile -Force
Write-Host "  [OK] Backup salvo em: $backupFile" -ForegroundColor Green

# Tentar extrair Library Paths do arquivo
Write-Host "[3/3] Tentando extrair Library Paths do arquivo..." -ForegroundColor Yellow
if (Test-Path $backupFile) {
    $content = Get-Content $backupFile -Raw
    if ($content -match "Library.*Path" -or $content -match "Search.*Path") {
        $pathsFile = Join-Path (Get-Location) "library_paths_backup_$(Get-Date -Format 'yyyyMMdd_HHmmss').txt"
        # Tentar extrair paths
        $content | Select-String -Pattern "Path.*=" | Out-File -FilePath $pathsFile -Encoding UTF8
        Write-Host "  [OK] Library Paths extraidos para: $pathsFile" -ForegroundColor Green
    }
}

Write-Host ""
Write-Host "================================================" -ForegroundColor Green
Write-Host "  [OK] Backup concluido!" -ForegroundColor Green
Write-Host "================================================" -ForegroundColor Green
Write-Host ""
Write-Host "IMPORTANTE:" -ForegroundColor Yellow
Write-Host "  Guarde os arquivos de backup criados!" -ForegroundColor White
Write-Host "  Se precisar restaurar, voce tera os caminhos salvos." -ForegroundColor White
Write-Host ""
Write-Host "RECOMENDACAO:" -ForegroundColor Cyan
Write-Host "  Tambem tire um screenshot dos Library Paths no Delphi:" -ForegroundColor White
Write-Host "  Tools -> Options -> Environment Options -> Delphi Options -> Library" -ForegroundColor Gray
Write-Host ""


