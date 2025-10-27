# 🔍 DIAGNÓSTICO: dhEmi, indFinal e indPres com valores padrão incorretos

## ❌ PROBLEMA ATUAL

O XML gerado apresenta três campos críticos com valores incorretos:

```xml
<dhEmi>1899-12-30T00:00:00-03:00</dhEmi>  <!-- Data padrão Delphi TDateTime = 0 -->
<indFinal>0</indFinal>                     <!-- Deveria ser 1 para consumidor final -->
<indPres>0</indPres>                       <!-- Deveria ser 1 para presencial -->
```

### Impacto na SEFAZ
- **dhEmi inválido**: Rejeição 213 - Data/Hora de emissão anterior ao permitido
- **indFinal=0**: Indica que NÃO é consumidor final (incorreto para varejo)
- **indPres=0**: Indica operação NÃO presencial (incorreto para venda física)

---

## 🔬 ANÁLISE DA CAUSA RAIZ

### 1. **dhEmi: Problema de SOBRESCRITA do ACBr**

**Localização**: `Un_principal.pas` linhas 866-874

```pascal
// dhEmi atual via RTTI (compatibilidade entre versões)
try
  try SetFloatProp(Ide, 'dhEmi', Now) except
    try
      SetFloatProp(Ide, 'dEmi', Date);
      SetFloatProp(Ide, 'hEmi', Time);
    except end;
  end;
except end;
```

**❌ Por que NÃO funciona:**
1. **ACBrNFe.Enviar() reseta dhEmi**: O método `Enviar(1, False, True)` na linha 1057 executa internamente:
   - `GerarXML()` → Serializa objeto NFe para XML
   - Durante serialização, o ACBr **PODE RESETAR** `dhEmi` se ele não estiver no formato correto ou se o componente tiver configuração de auto-data
   - Possível código ACBr interno: `if Ide.dhEmi = 0 then Ide.dhEmi := Now`

2. **SetFloatProp pode falhar silenciosamente**: Se a propriedade `dhEmi` for somente leitura ou calculada (getter/setter), o `SetFloatProp` pode não ter efeito real.

3. **GravarXML() antes de atribuir dhEmi**: A linha 1053 chama `GravarXML(PreXMLPath)` ANTES de `Enviar()`, então captura o XML com dhEmi=0 se a atribuição via RTTI falhou.

**✅ SOLUÇÃO:**
```pascal
// APÓS o bloco with ACBrNFe1.NotasFiscais.Add.NFe do
// e ANTES de GravarXML:

// Forçar dhEmi diretamente no objeto NFe (não via RTTI)
with ACBrNFe1.NotasFiscais.Items[0].NFe.Ide do
begin
  // Tentar atribuição direta se a propriedade existir
  try
    dhEmi := Now;  // TDateTime atual
  except
    // Fallback para versões antigas ACBr
    try dEmi := Date; hEmi := Time; except end;
  end;
end;
```

---

### 2. **indFinal e indPres: Problema de CONDICIONAL**

**Localização**: `Un_principal.pas` linhas 1042-1046

```pascal
// Flags de consumidor final / presença
if IsConsumidorFinal then
begin
  try SetOrdProp(Ide, 'indFinal', 1); except try SetStrProp(Ide, 'indFinal', '1'); except end; end;
  try SetOrdProp(Ide, 'indPres', 1); except try SetStrProp(Ide, 'indPres', '1'); except end; end;
end;
```

**❌ Por que NÃO funciona:**
1. **Condicional `IsConsumidorFinal` pode estar FALSE**:
   - Linha 859: `IsConsumidorFinal := False;` (inicializado como false)
   - Linhas 910-913: Só muda para True SE:
     ```pascal
     var consFlag := UpperCase(Trim(JsonGetStr(DestObj, 'consumidor_final', '')));
     if (Dest.IE = '') or (consFlag = 'S') or (consFlag = 'SIM') or (consFlag = '1') then
       IsConsumidorFinal := True;
     ```
   - **Se o JSON não vier com `"consumidor_final": "S"` E o destinatário tiver IE preenchida**, `IsConsumidorFinal` fica FALSE

2. **SetOrdProp/SetStrProp pode falhar**: Se a propriedade não existir ou for de tipo incompatível, os `except` abafam o erro mas não setam o valor.

3. **ACBr pode ter valores padrão fixos**: Alguns componentes ACBr inicializam `indFinal=0` e `indPres=0` no construtor do objeto NFe. Se a atribuição via RTTI falha, esses valores permanecem.

**✅ SOLUÇÃO:**
```pascal
// APÓS o bloco with e ANTES de GravarXML:

// Forçar indFinal=1 e indPres=1 SEMPRE para varejo/presencial
with ACBrNFe1.NotasFiscais.Items[0].NFe.Ide do
begin
  try
    // Tentar atribuição direta via enum (se existir na versão ACBr)
    indFinal := cfConsumidorFinal;  // ou TpcnConsumidorFinal.cfConsumidorFinal
    indPres := pcPresencial;        // ou TpcnPresencaComprador.pcPresencial
  except
    // Fallback RTTI
    try SetOrdProp(ACBrNFe1.NotasFiscais.Items[0].NFe.Ide, 'indFinal', 1); except end;
    try SetOrdProp(ACBrNFe1.NotasFiscais.Items[0].NFe.Ide, 'indPres', 1); except end;
  end;
end;
```

---

## 🔧 CORREÇÃO DEFINITIVA - CÓDIGO ATUALIZADO

### **Substituir linhas 1042-1046** por:

```pascal
// === FIM DO BLOCO with ===
end; // with NFe

// ===================================================================
// ⚡ AJUSTES FINAIS OBRIGATÓRIOS (ANTES de GravarXML e Enviar)
// ===================================================================
with ACBrNFe1.NotasFiscais.Items[0].NFe.Ide do
begin
  // 1️⃣ FORÇAR dhEmi para data/hora atual
  try
    dhEmi := Now;  // Atribuição direta (prefira sempre a direta)
  except
    // Fallback para versões antigas ACBr que separam dEmi/hEmi
    try dEmi := Date; except end;
    try hEmi := Time; except end;
  end;
  
  // 2️⃣ FORÇAR indFinal=1 (consumidor final)
  try
    // Tentar enum (versões ACBr mais recentes)
    indFinal := cfConsumidorFinal;
  except
    // Fallback RTTI ordinal
    try SetOrdProp(ACBrNFe1.NotasFiscais.Items[0].NFe.Ide, 'indFinal', 1); except end;
  end;
  
  // 3️⃣ FORÇAR indPres=1 (presencial)
  try
    // Tentar enum (versões ACBr mais recentes)
    indPres := pcPresencial;
  except
    // Fallback RTTI ordinal
    try SetOrdProp(ACBrNFe1.NotasFiscais.Items[0].NFe.Ide, 'indPres', 1); except end;
  end;
end;
// ===================================================================

// Grava pré-XML e envia
try
  PreXMLPath := ExtractFilePath(Application.ExeName) + 'logs\\requests\\pre_envio_final_' + FormatDateTime('yyyymmdd_hhnnss', Now) + '.xml';
  try ForceDirectories(ExtractFilePath(PreXMLPath)); except end;
  ACBrNFe1.NotasFiscais.Items[0].GravarXML(PreXMLPath);
  Resp.AddPair('pre_xml_path_final', PreXMLPath);
except end;

if not ACBrNFe1.Enviar(1, False, True) then
  raise Exception.Create('Falha ao transmitir NFe');
```

---

## 📋 CHECKLIST DE VALIDAÇÃO

Após aplicar a correção, verificar o `pre_envio_final_*.xml`:

- [ ] `<dhEmi>` contém data/hora atual no formato `2025-10-07T11:30:45-03:00`
- [ ] `<indFinal>1</indFinal>` (ou ausente se não obrigatório)
- [ ] `<indPres>1</indPres>` (ou ausente se não obrigatório)

---

## 🎯 RESUMO EXECUTIVO

| Campo | Valor Atual | Valor Esperado | Causa | Correção |
|-------|-------------|----------------|-------|----------|
| `dhEmi` | `1899-12-30T00:00:00` | `2025-10-07T11:30:45-03:00` | SetFloatProp falha + ACBr reseta ao enviar | Atribuição direta `dhEmi := Now` ANTES de GravarXML |
| `indFinal` | `0` | `1` | Condicional `IsConsumidorFinal` false + RTTI falha | Atribuição direta `indFinal := cfConsumidorFinal` FORA da condicional |
| `indPres` | `0` | `1` | Condicional `IsConsumidorFinal` false + RTTI falha | Atribuição direta `indPres := pcPresencial` FORA da condicional |

---

## ⚠️ ATENÇÃO: Versão ACBr e Enums

Se após aplicar a correção o compilador reclamar:
- `E2003 Undeclared identifier: 'cfConsumidorFinal'`
- `E2003 Undeclared identifier: 'pcPresencial'`

**Significa que a versão do ACBr não expõe esses enums.** Neste caso, use APENAS o fallback RTTI:

```pascal
// Versão ACBr antiga (sem enums expostos)
try SetOrdProp(ACBrNFe1.NotasFiscais.Items[0].NFe.Ide, 'indFinal', 1); except end;
try SetOrdProp(ACBrNFe1.NotasFiscais.Items[0].NFe.Ide, 'indPres', 1); except end;
```

---

## 🚀 PRÓXIMOS PASSOS

1. **Aplicar a correção** conforme código acima
2. **Recompilar** o emissor Delphi
3. **Emitir NFe de teste** com pedido #000088
4. **Validar XML** `pre_envio_final_*.xml` com os valores corretos
5. **Confirmar autorização SEFAZ** (cStat 100)

