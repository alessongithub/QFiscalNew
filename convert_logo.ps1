Add-Type -AssemblyName System.Drawing

# Carrega a imagem PNG
$img = [System.Drawing.Image]::FromFile('logo\logo.png')

# Cria um novo bitmap
$bmp = New-Object System.Drawing.Bitmap($img)

# Salva como BMP
$bmp.Save('logo\logo.bmp', [System.Drawing.Imaging.ImageFormat]::Bmp)

# Limpa a memória
$img.Dispose()
$bmp.Dispose()

Write-Host "Logo convertida para BMP com sucesso!"

