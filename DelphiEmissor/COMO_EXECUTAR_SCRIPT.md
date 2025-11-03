# 🚀 Como Executar o Script de Limpeza

## 📋 **MÉTODO 1: PowerShell (Mais Rápido)**

### Passo 1: Abrir PowerShell na pasta do projeto
1. Abra o **Windows Explorer** (File Explorer)
2. Navegue até: `C:\xampp-novo\htdocs\emissor\qfiscal\DelphiEmissor`
3. Clique com o botão direito na pasta (ou na barra de endereço)
4. Selecione **"Abrir no Terminal"** ou **"Abrir no PowerShell"**

### Passo 2: Executar o script
Digite o comando:

```powershell
.\limpar_delphi.ps1
```

**OU** se der erro de permissão, use:

```powershell
powershell -ExecutionPolicy Bypass -File .\limpar_delphi.ps1
```

---

## 📋 **MÉTODO 2: PowerShell como Administrador**

### Passo 1: Abrir PowerShell como Admin
1. Pressione `Win + X`
2. Selecione **"Windows PowerShell (Administrador)"** ou **"Terminal (Administrador)"**
3. Navegue até a pasta:
   ```powershell
   cd "C:\xampp-novo\htdocs\emissor\qfiscal\DelphiEmissor"
   ```

### Passo 2: Executar o script
```powershell
.\limpar_delphi.ps1
```

---

## 📋 **MÉTODO 3: Menu de Contexto (Atalho)**

### Passo 1: Criar atalho (opcional)
1. Clique com botão direito no arquivo `limpar_delphi.ps1`
2. Selecione **"Criar atalho"**
3. Clique com botão direito no atalho → **Propriedades**
4. No campo **Destino**, altere para:
   ```
   powershell.exe -ExecutionPolicy Bypass -File "C:\xampp-novo\htdocs\emissor\qfiscal\DelphiEmissor\limpar_delphi.ps1"
   ```
5. Clique em **OK**

### Passo 2: Executar
- Clique duas vezes no atalho

---

## 📋 **MÉTODO 4: Linha de Comando (CMD)**

### Passo 1: Abrir CMD
1. Pressione `Win + R`
2. Digite `cmd` e pressione Enter
3. Navegue até a pasta:
   ```cmd
   cd "C:\xampp-novo\htdocs\emissor\qfiscal\DelphiEmissor"
   ```

### Passo 2: Executar
```cmd
powershell -ExecutionPolicy Bypass -File limpar_delphi.ps1
```

---

## ⚠️ **Se der erro de "ExecutionPolicy"**

Se aparecer erro como:
```
cannot be loaded because running scripts is disabled on this system
```

### Solução 1: Executar com bypass (rápido)
```powershell
powershell -ExecutionPolicy Bypass -File .\limpar_delphi.ps1
```

### Solução 2: Habilitar execução de scripts (permanente)
Execute no PowerShell como Administrador:

```powershell
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
```

Depois execute o script normalmente:
```powershell
.\limpar_delphi.ps1
```

---

## ✅ **O que acontece quando roda o script?**

O script vai:
1. ✅ Verificar processos do Delphi/Emissor rodando
2. ✅ Fechar processos se necessário
3. ✅ Remover arquivos `.exe`
4. ✅ Remover arquivos `.dcu`
5. ✅ Limpar cache (`.identcache`, `.local`)
6. ✅ Limpar outras pastas de build
7. ✅ Mostrar resumo do que foi feito
8. ✅ Perguntar se quer abrir o Delphi automaticamente

---

## 📝 **Depois de executar o script**

1. Abra o **Delphi**
2. Abra o projeto **Emissor.dproj**
3. Vá em: **Project → Rebuild All** (ou `Shift+F9`)
4. Aguarde a recompilação completa
5. O novo `.exe` será gerado compatível com Windows 11

---

## 🎯 **Resumo Rápido**

**Mais fácil:**
```powershell
cd "C:\xampp-novo\htdocs\emissor\qfiscal\DelphiEmissor"
.\limpar_delphi.ps1
```

**Se der erro de permissão:**
```powershell
powershell -ExecutionPolicy Bypass -File .\limpar_delphi.ps1
```

