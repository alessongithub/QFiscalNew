# 🔧 Limpar Library Paths Mantendo ACBr - Guia Seguro

## ⚠️ **IMPORTANTE: NÃO Remova os Paths do ACBr!**

Se você instalou ACBr manualmente, seus paths estão salvos em Library Paths. **NÃO apague tudo!**

---

## ✅ **SOLUÇÃO SEGURA: Identificar e Manter Paths do ACBr**

### **PASSO 1: Anotar Paths do ACBr ANTES de Fazer Qualquer Coisa**

1. Abra o Delphi
2. **Tools → Options → Environment Options → Delphi Options → Library**
3. Veja a lista de **Library paths**
4. **Anote TODOS os paths que contêm "ACBr"** ou caminhos do ACBr

**Exemplo de paths do ACBr (anote os seus):**
```
C:\Program Files (x86)\ACBr\ACBrNFe\Source
C:\Program Files (x86)\ACBr\ACBrDFe\Source
C:\Users\[SEU_USUARIO]\Documents\Embarcadero\Studio\[VERSAO]\ACBr
```

**Ou tire uma captura de tela dos Library Paths!**

---

### **PASSO 2: Identificar Paths Problemáticos**

Olhe na lista de Library Paths e identifique:

**Paths SUSPEITOS (que podem ser removidos):**
- Paths que apontam para versões ANTIGAS do Delphi
  - Ex: `C:\Program Files (x86)\Embarcadero\Studio\20.0\...` (se você usa 21.0)
- Paths que apontam para pastas de OUTROS projetos
- Paths que apontam para unidades não existentes
- Paths com "old", "backup", "temp" no nome

**Paths do ACBr (NÃO REMOVER):**
- Qualquer path que contenha "ACBr" no caminho
- Paths para componentes ACBr instalados

**Paths Padrão do Delphi (NÃO REMOVER):**
- `$(BDS)\lib\Win32\release`
- `$(BDS)\lib\Win32\debug`
- `$(BDS)\source\...`
- Paths padrão do sistema

---

### **PASSO 3: Criar Backup dos Library Paths**

1. No Delphi: **Tools → Options → Environment Options → Delphi Options → Library**
2. Abra o Bloco de Notas
3. Copie e cole todos os Library Paths para o Bloco de Notas
4. Salve como `library_paths_backup.txt`
5. Destaque os paths do ACBr no backup

---

### **PASSO 4: Remover APENAS Paths Suspeitos (Manualmente)**

1. No Delphi: **Tools → Options → Environment Options → Delphi Options → Library**
2. Na lista de **Library paths**, identifique paths suspeitos:
   - Versões antigas do Delphi
   - Pastas que não existem mais
   - Paths de outros projetos
3. **Selecione apenas os paths suspeitos**
4. Clique em **Remove** (um por vez para evitar remover o errado)
5. **NÃO remova paths que contêm "ACBr"**
6. **NÃO remova paths padrão do Delphi**

---

### **PASSO 5: Se Precisar Limpar Tudo (Última Opção)**

**SÓ FAÇA ISSO se você tiver anotado os paths do ACBr!**

1. **Tools → Options → Environment Options → Delphi Options → Library**
2. Anote TODOS os Library paths (ou tire screenshot)
3. Se houver botão "Clear All" ou similar, use (mas lembre-se de adicionar ACBr depois)
4. **OU** remova paths suspeitos um por um (mais seguro)

5. **Adicionar paths do ACBr novamente:**
   - Clique em **Add...**
   - Adicione cada path do ACBr que você anotou
   - Verifique se os caminhos ainda existem

---

## ✅ **ALTERNATIVA: Limpar Apenas DCUs (Mantendo Paths Intactos)**

Você pode limpar os DCUs sem mexer nos Library Paths:

### **Opção 1: Limpar Apenas DCUs do Sistema**
Siga os passos anteriores para limpar DCUs, mas **NÃO mexa** nos Library Paths.

### **Opção 2: Verificar se Paths do ACBr Estão Corretos**

1. No Delphi: **Tools → Options → Environment Options → Delphi Options → Library**
2. Verifique se cada path do ACBr:
   - Existe fisicamente no disco
   - Aponta para a pasta correta
   - Tem permissão de leitura

3. Se algum path estiver inválido:
   - Remova apenas esse path específico
   - Adicione o caminho correto

---

## 📋 **Checklist Antes de Limpar Library Paths**

- [ ] ✅ Anotei todos os paths do ACBr
- [ ] ✅ Tirei screenshot dos Library Paths (backup)
- [ ] ✅ Identifiquei quais paths são suspeitos
- [ ] ✅ Verifiquei se os paths do ACBr ainda existem no disco
- [ ] ✅ Tenho certeza de qual caminho usar para ACBr

---

## 🎯 **Recomendação FINAL**

**NÃO limpe todos os Library Paths!**

Ao invés disso:

1. ✅ **Mantenha os paths do ACBr** (anotados)
2. ✅ **Remova apenas paths suspeitos** (versões antigas, caminhos inválidos)
3. ✅ **Limpe os DCUs** do sistema (isso não afeta Library Paths)
4. ✅ **Recompile o projeto** - o Delphi vai recompilar tudo, mantendo os paths do ACBr

---

## 💡 **Se Perder os Paths do ACBr**

Se por acaso você remover os paths do ACBr:

1. Verifique onde instalou o ACBr:
   - Geralmente em: `C:\Program Files (x86)\ACBr\...`
   - Ou em: `C:\Users\[SEU_USUARIO]\Documents\Embarcadero\Studio\[VERSAO]\ACBr`

2. Adicione os paths manualmente:
   - **Tools → Options → Environment Options → Delphi Options → Library**
   - Clique em **Add...**
   - Navegue até a pasta `Source` de cada componente ACBr
   - Adicione cada pasta Source separadamente:
     - ACBrNFe\Source
     - ACBrDFe\Source
     - ACBrValidador\Source
     - Etc.

---

## 🚨 **IMPORTANTE**

**A limpeza de DCUs NÃO afeta Library Paths!**

Você pode:
- ✅ Limpar DCUs do sistema
- ✅ Limpar DCUs do projeto
- ✅ Limpar cache do Delphi
- ✅ Recompilar tudo

**Tudo isso mantendo seus Library Paths do ACBr intactos!**

**O problema do erro F2051 está nos DCUs, não nos Library Paths.**

---

## 📝 **Resumo**

1. **Anote os paths do ACBr** (antes de qualquer coisa)
2. **NÃO remova paths do ACBr** dos Library Paths
3. **Limpe apenas os DCUs** (não afeta paths)
4. **Remova apenas paths suspeitos** (se necessário, e com cuidado)
5. **Recompile o projeto** - ACBr vai continuar funcionando




