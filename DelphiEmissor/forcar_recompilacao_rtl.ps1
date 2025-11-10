# Script para FORCAR recompilacao completa do RTL
# Especifico para Delphi 23.0 (12.1)

Write-Host "================================================" -ForegroundColor Red
Write-Host "  FORCAR Recompilacao do RTL - Delphi 23.0" -ForegroundColor Red
Write-Host "================================================" -ForegroundColor Red
Write-Host ""
Write-Host "[AVISO] Este script remove TODOS os DCUs e forca" -ForegroundColor Yellow
Write-Host "        recompilacao completa do Runtime Library." -ForegroundColor Yellow
Write-Host ""

$confirmar = Read-Host "Continuar? (S/N)"
if ($confirmar -ne "S" -and $confirmar -ne "s") {
    exit
}

# Parar tudo
Write-Host "[1/6] Finalizando processos..." -ForegroundColor Yellow
Get-Process | Where-Object {
    $_.Name -like "*bds*" -or $_.Name -like "*dcc*" -or $_.Name -like "*msbuild*"
} | Stop-Process -Force -ErrorAction SilentlyContinue
Start-Sleep -Seconds 5
Write-Host "[OK]" -ForegroundColor Green

# Remover DCUs do Delphi 23.0 especificamente
Write-Host "[2/6] Removendo DCUs do Delphi 23.0..." -ForegroundColor Yellow
$delphiPath = "C:\Program Files (x86)\Embarcadero\Studio\23.0\lib"

if (Test-Path $delphiPath) {
    $pastas = @("Win32\debug", "Win32\release", "Win64\debug", "Win64\release")
    $total = 0
    
    foreach ($pasta in $pastas) {
        $pathCompleto = Join-Path $delphiPath $pasta
        if (Test-Path $pathCompleto) {
            Write-Host "  Limpando: $pasta" -ForegroundColor Cyan
            $dcus = Get-ChildItem -Path $pathCompleto -Filter "*.dcu" -Recurse -ErrorAction SilentlyContinue
            if ($dcus) {
                $count = $dcus.Count
                $dcus | Remove-Item -Force -ErrorAction SilentlyContinue
                $total += $count
                Write-Host "    Removidos $count DCUs" -ForegroundColor Gray
            }
        }
    }
    Write-Host "[OK] Total: $total DCUs removidos" -ForegroundColor Green
} else {
    Write-Host "[ERRO] Delphi 23.0 nao encontrado em $delphiPath" -ForegroundColor Red
}
Write-Host ""

# Limpar cache completo
Write-Host "[3/6] Limpando cache..." -ForegroundColor Yellow
$cachePaths = @(
    "$env:LOCALAPPDATA\Embarcadero\BDS\23.0",
    "$env:APPDATA\Embarcadero\BDS\23.0",
    "$env:USERPROFILE\Documents\Embarcadero\Studio\23.0"
)

foreach ($cachePath in $cachePaths) {
    if (Test-Path $cachePath) {
        Write-Host "  Removendo: $cachePath" -ForegroundColor Cyan
        Remove-Item -Path "$cachePath\*" -Recurse -Force -ErrorAction SilentlyContinue
    }
}
Write-Host "[OK]" -ForegroundColor Green
Write-Host ""

# Limpar DCUs do projeto
Write-Host "[4/6] Limpando projeto..." -ForegroundColor Yellow
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
Get-ChildItem -Path $scriptPath -Filter "*.dcu" -Recurse | Remove-Item -Force -ErrorAction SilentlyContinue
Get-ChildItem -Path $scriptPath -Filter "*.exe" -Recurse | Remove-Item -Force -ErrorAction SilentlyContinue
Remove-Item -Path "$scriptPath\*.identcache" -Force -ErrorAction SilentlyContinue
Remove-Item -Path "$scriptPath\*.local" -Force -ErrorAction SilentlyContinue
Write-Host "[OK]" -ForegroundColor Green
Write-Host ""

# Criar arquivo de marcacao para forcar recompilacao
Write-Host "[5/6] Criando marcacao de recompilacao forcada..." -ForegroundColor Yellow
$marcacao = Join-Path $scriptPath ".force_rtl_rebuild"
Set-Content -Path $marcacao -Value "Force RTL rebuild - $(Get-Date)" -Force
Write-Host "[OK]" -ForegroundColor Green
Write-Host ""

# Instrucoes finais
Write-Host "[6/6] Gerando instrucoes..." -ForegroundColor Yellow
Write-Host ""
Write-Host "================================================" -ForegroundColor Green
Write-Host "  [OK] Limpeza concluida!" -ForegroundColor Green
Write-Host "================================================" -ForegroundColor Green
Write-Host ""
Write-Host "INSTRUCOES CRITICAS:" -ForegroundColor Red
Write-Host ""
Write-Host "1. Abra o Delphi 23.0 (Execute como Administrador)" -ForegroundColor Yellow
Write-Host "   Botao direito em bds.exe -> Executar como administrador" -ForegroundColor Cyan
Write-Host ""
Write-Host "2. File -> New -> VCL Application" -ForegroundColor White
Write-Host "   Salve como TestRTL.dpr" -ForegroundColor Gray
Write-Host ""
Write-Host "3. No Unit1.pas, SUBSTITUA todo o codigo por:" -ForegroundColor White
Write-Host "   (copie exatamente o codigo abaixo)" -ForegroundColor Cyan
Write-Host ""
Write-Host "   unit Unit1;" -ForegroundColor Gray
Write-Host "   interface" -ForegroundColor Gray
Write-Host "   uses Winapi.Windows, System.SysUtils, System.Messaging;" -ForegroundColor Gray
Write-Host "   type TForm1 = class(TForm) end;" -ForegroundColor Gray
Write-Host "   var Form1: TForm1;" -ForegroundColor Gray
Write-Host "   implementation {$R *.dfm} end." -ForegroundColor Gray
Write-Host ""
Write-Host "4. Compile (F9) - AGUARDE 15-20 MINUTOS!" -ForegroundColor Yellow
Write-Host "   O Delphi vai recompilar TODO o RTL" -ForegroundColor Yellow
Write-Host "   Se der erro, tente F9 novamente (pode dar 2-3 tentativas)" -ForegroundColor Yellow
Write-Host ""
Write-Host "5. Se compilar com sucesso, feche o projeto teste" -ForegroundColor White
Write-Host ""
Write-Host "6. Abra Emissor.dproj" -ForegroundColor White
Write-Host "   Project -> Clean" -ForegroundColor Cyan
Write-Host "   Project -> Rebuild All (Shift+F9)" -ForegroundColor Cyan
Write-Host ""
Write-Host "[AVISO] A primeira compilacao pode levar 20+ minutos!" -ForegroundColor Red
Write-Host "        NAO INTERROMPA!" -ForegroundColor Red
Write-Host ""




