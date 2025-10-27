# 🤖 PROMPT DE CORREÇÃO PARA GPT-5

## 📋 CONTEXTO

Você é um especialista em Delphi e integração com ACBr para emissão de NFe. Você recebeu um código legado que está **falhando na validação SEFAZ** devido a múltiplas inconsistências no XML gerado.

---

## 🎯 OBJETIVO

Refatorar completamente a função `EmitirNFeJSON` no arquivo `DelphiEmissor/Un_principal.pas` para garantir que:

1. ✅ O XML gerado seja **sempre válido** segundo schema NFe 4.00
2. ✅ Todos os valores tributários sejam **consistentes** entre itens e totais
3. ✅ O XML seja **aprovado pela SEFAZ** sem rejeições
4. ✅ O código seja **manutenível** e **eficiente**

---

## 📚 DOCUMENTAÇÃO FORNECIDA

Você tem acesso completo a:

1. **`ANALISE_UN_PRINCIPAL.md`** - Análise técnica detalhada identificando 10 inconsistências críticas
2. **`FLUXO_EMISSAO_NFE_PROBLEMAS.md`** - Diagramas visuais mostrando os problemas de fluxo
3. **`DelphiEmissor/Un_principal.pas`** - Código fonte atual (3258 linhas)

### Principais Problemas Identificados:

#### 🔴 CRÍTICO #1: Duplicação de Lógica
- **Linha 1932-2027:** Calcula tributos de todos os itens
- **Linha 2043-2108:** **DUPLICA** exatamente a mesma lógica
- **Impacto:** 2x processamento, risco de valores divergentes

#### 🔴 CRÍTICO #2: Sincronização Invertida (linha 1894-1925)
```pascal
// ❌ ERRADO: Copia valores do TOTAL para o ITEM
var totBCStr := Copy(totBlock, totBC1+4, totBC2 - (totBC1+4));
// ... depois copia para o primeiro item apenas
icBlk := Copy(icBlk, 1, b1+4) + totBCStr + Copy(icBlk, b2, MaxInt);
```
**Esperado:** Calcular cada item individualmente, depois SOMAR no total.

#### 🔴 CRÍTICO #3: Múltiplos LoadFromFile (6+ vezes)
- **Linhas:** 1412, 1481, 1497, 1546, 1637, 2178
- **Problema:** ACBr regenera XML internamente, descartando ajustes manuais

#### 🔴 CRÍTICO #4: Manipulação String de XML
- **Linhas:** 1709-2168 (460 linhas de manipulação string!)
- **Problema:** `Pos()`, `Copy()`, `StringReplace()` podem corromper XML

#### 🔴 CRÍTICO #5: Código Morto (linha 2184-2315)
```pascal
if False then try
  // 130 linhas de código DESATIVADO
end;
```

---

## 🎯 ESTRATÉGIA DE CORREÇÃO

### FASE 1: SIMPLIFICAR E CONSOLIDAR ⭐⭐⭐⭐⭐

**Ação:** Eliminar TODAS as manipulações string de XML. Trabalhar APENAS com o objeto ACBr.

**Antes (ERRADO):**
```pascal
// Manipula XML como string
var XmlMem := ACBrNFe1.NotasFiscais.Items[0].XML;
XmlMem := StringReplace(XmlMem, '<indPres>0</indPres>', '<indPres>1</indPres>', [rfReplaceAll]);
TFile.WriteAllText(path, XmlMem);
ACBrNFe1.NotasFiscais.LoadFromFile(path); // ← Recarrega!
```

**Depois (CORRETO):**
```pascal
// Manipula objeto ACBr
with ACBrNFe1.NotasFiscais.Items[0].NFe do
begin
  Ide.indPres := pcPresencial; // ou use o enum correto
end;
// XML será gerado corretamente pelo ACBr na hora de assinar/enviar
```

---

### FASE 2: CORRIGIR FLUXO DE CÁLCULO ⭐⭐⭐⭐⭐

**Implementar fluxo correto:**

```pascal
function CalcularTributosNFe(): Boolean;
var
  i: Integer;
  totalVProd, totalVDesc, totalBC, totalICMS: Double;
  totalPIS, totalCOFINS: Double;
  baseNet: Double;
begin
  Result := False;
  
  with ACBrNFe1.NotasFiscais.Items[0].NFe do
  begin
    totalVProd := 0;
    totalVDesc := 0;
    totalBC := 0;
    totalICMS := 0;
    totalPIS := 0;
    totalCOFINS := 0;
    
    // 1️⃣ CALCULAR TRIBUTOS DE CADA ITEM
    for i := 0 to Det.Count - 1 do
    begin
      // Base líquida = vProd - vDesc
      baseNet := Det[i].Prod.vProd - Det[i].Prod.vDesc;
      if baseNet < 0 then baseNet := 0;
      
      // ICMS do item
      with Det[i].Imposto.ICMS do
      begin
        vBC := baseNet;
        pICMS := 18.00; // TODO: Parametrizar por UF/CFOP
        vICMS := Round2(baseNet * (pICMS / 100.0));
      end;
      
      // PIS do item
      with Det[i].Imposto.PIS do
      begin
        vBC := baseNet;
        pPIS := 1.65; // TODO: Parametrizar por regime
        vPIS := Round2(baseNet * (pPIS / 100.0));
      end;
      
      // COFINS do item
      with Det[i].Imposto.COFINS do
      begin
        vBC := baseNet;
        pCOFINS := 7.60; // TODO: Parametrizar por regime
        vCOFINS := Round2(baseNet * (pCOFINS / 100.0));
      end;
      
      // Acumula para o total
      totalVProd := totalVProd + Det[i].Prod.vProd;
      totalVDesc := totalVDesc + Det[i].Prod.vDesc;
      totalBC := totalBC + Det[i].Imposto.ICMS.vBC;
      totalICMS := totalICMS + Det[i].Imposto.ICMS.vICMS;
      totalPIS := totalPIS + Det[i].Imposto.PIS.vPIS;
      totalCOFINS := totalCOFINS + Det[i].Imposto.COFINS.vCOFINS;
    end;
    
    // 2️⃣ ATUALIZAR TOTAIS
    with Total.ICMSTot do
    begin
      vProd := totalVProd;
      vDesc := totalVDesc;
      vBC := totalBC;
      vICMS := totalICMS;
      vPIS := totalPIS;
      vCOFINS := totalCOFINS;
      
      // vNF = vProd - vDesc + frete + seguro + outros
      vNF := vProd - vDesc + vFrete + vSeg + vOutro;
    end;
    
    // 3️⃣ VALIDAR: vNF deve bater com soma dos pagamentos
    var somaVPag := 0.0;
    for i := 0 to pag.Count - 1 do
      somaVPag := somaVPag + pag.Items[i].vPag;
    
    if Abs(Total.ICMSTot.vNF - somaVPag) > 0.01 then
    begin
      // Ajuste fino: diferença vai para vDesc ou vOutro
      var diff := Total.ICMSTot.vNF - somaVPag;
      if diff > 0 then
      begin
        Total.ICMSTot.vDesc := Total.ICMSTot.vDesc + diff;
        Total.ICMSTot.vNF := somaVPag;
      end;
    end;
    
    Result := True;
  end;
end;
```

---

### FASE 3: GARANTIR PAGAMENTOS VÁLIDOS ⭐⭐⭐⭐⭐

**Problema Atual:** `tPag` não é preenchido no objeto ACBr.

**Correção:**
```pascal
// Na montagem dos pagamentos (linha ~1230)
var Pays := JsonGetArr(J, 'pagamentos');
if Assigned(Pays) then
begin
  for pj := 0 to Pays.Count - 1 do
  begin
    var PayObj := Pays.Items[pj] as TJSONObject;
    with pag.New do
    begin
      // ✅ ADICIONAR: definir tPag
      var tPagStr := JsonGetStr(PayObj, 'forma', '01');
      if tPagStr = '01' then tPag := fpDinheiro
      else if tPagStr = '02' then tPag := fpCheque
      else if tPagStr = '03' then tPag := fpCartaoCredito
      else if tPagStr = '04' then tPag := fpCartaoDebito
      else if tPagStr = '05' then tPag := fpCreditoLoja
      else tPag := fpDinheiro; // default
      
      vPag := StrToFloatDef(JsonGetStr(PayObj, 'valor', '0'), 0.0);
    end;
  end;
end
else
begin
  // Garantia: se não vier pagamento, cria um padrão
  with pag.New do
  begin
    tPag := fpDinheiro;
    vPag := Total.ICMSTot.vNF;
  end;
end;
```

---

### FASE 4: AJUSTES COMPLEMENTARES ⭐⭐⭐

**4.1 - dhEmi (Data/Hora de Emissão)**
```pascal
// Já na montagem do objeto (não via string!)
Ide.dhEmi := Now();
```

**4.2 - indPres (Indicador de Presença)**
```pascal
// Para consumidor final
if IsConsumidorFinal then
  Ide.indPres := pcPresencial; // 1
```

**4.3 - indFinal (Consumidor Final)**
```pascal
if (Dest.IE = '') or IsConsumidorFinal then
  Ide.indFinal := cfConsumidorFinal; // 1
```

**4.4 - Remover IBSCBS**
```pascal
// NÃO injetar IBSCBS para NFe 4.00
// Apenas remover se vier no JSON:
// (não fazer nada, ACBr não gera por padrão)
```

---

### FASE 5: SIMPLIFICAR FLUXO FINAL ⭐⭐⭐⭐

**Novo fluxo (simples e eficiente):**

```pascal
function TForm1.EmitirNFeJSON(const JSONData: string): string;
var
  J, EmitObj, DestObj, Conf, ItemObj: TJSONObject;
  Itens: TJSONArray;
  i: Integer;
  Resp: TJSONObject;
begin
  Resp := TJSONObject.Create;
  try
    try
      // 1️⃣ PARSE JSON
      J := TJSONObject(TJSONObject.ParseJSONValue(JSONData));
      if not Assigned(J) then
        raise Exception.Create('JSON inválido');
      
      // 2️⃣ CONFIGURAR ACBr (certificado, ambiente, UF)
      ConfigurarACBr(J);
      
      // 3️⃣ MONTAR OBJETO NFe COMPLETO
      ACBrNFe1.NotasFiscais.Clear;
      with ACBrNFe1.NotasFiscais.Add.NFe do
      begin
        MontarIDE(J);
        MontarEmitente(J);
        MontarDestinatario(J);
        MontarItens(J);
        MontarTransporte(J);
        MontarPagamentos(J);
      end;
      
      // 4️⃣ CALCULAR TRIBUTOS (item→total)
      if not CalcularTributosNFe() then
        raise Exception.Create('Erro ao calcular tributos');
      
      // 5️⃣ VALIDAR OBJETO (antes de gerar XML)
      ValidarObjetoNFe();
      
      // 6️⃣ ASSINAR E ENVIAR
      if not ACBrNFe1.Enviar(1, False, True) then
        raise Exception.Create('Falha ao transmitir NFe');
      
      // 7️⃣ RETORNAR RESULTADO
      Chave := ACBrNFe1.NotasFiscais.Items[0].NFe.infNFe.ID;
      Protocolo := ACBrNFe1.WebServices.Retorno.Protocolo;
      XMLPath := ACBrNFe1.NotasFiscais.Items[0].NomeArq;
      
      Resp.AddPair('ok', TJSONBool.Create(True));
      Resp.AddPair('chave', Chave);
      Resp.AddPair('protocolo', Protocolo);
      Resp.AddPair('xml_path', XMLPath);
      
    except
      on E: Exception do
      begin
        Resp.AddPair('ok', TJSONBool.Create(False));
        Resp.AddPair('error', E.Message);
      end;
    end;
    Result := Resp.ToString;
  finally
    Resp.Free;
  end;
end;
```

---

## 🔧 FUNÇÕES AUXILIARES NECESSÁRIAS

### 1. ConfigurarACBr
```pascal
procedure ConfigurarACBr(J: TJSONObject);
var
  Conf: TJSONObject;
begin
  Conf := JsonGetObj(J, 'cert');
  if Assigned(Conf) then
  begin
    if JsonGetStr(Conf, 'serial', '') <> '' then
      ACBrNFe1.Configuracoes.Certificados.NumeroSerie := JsonGetStr(Conf, 'serial', '')
    else
    begin
      ACBrNFe1.Configuracoes.Certificados.ArquivoPFX := JsonGetStr(Conf, 'path');
      ACBrNFe1.Configuracoes.Certificados.Senha := JsonGetStr(Conf, 'password');
    end;
  end;
  
  Conf := JsonGetObj(J, 'configuracoes');
  if Assigned(Conf) then
  begin
    ACBrNFe1.Configuracoes.WebServices.UF := JsonGetStr(Conf, 'uf', 'SP');
    // Ambiente já está configurado no FormCreate
  end;
end;
```

### 2. MontarIDE
```pascal
procedure MontarIDE(J: TJSONObject);
begin
  with ACBrNFe1.NotasFiscais.Items[0].NFe.Ide do
  begin
    modelo := 55; // ou 65 para NFCe
    serie := JsonGetInt(J, 'serie', 1);
    nNF := JsonGetInt(J, 'numero_nfe', 1);
    dhEmi := Now();
    natOp := JsonGetStr(J, 'natOp', 'Venda de mercadoria');
    verProc := 'QFiscal-Delphi-1.0';
    
    // Consumidor final
    if IsConsumidorFinal then
    begin
      indFinal := cfConsumidorFinal;
      indPres := pcPresencial;
    end;
  end;
end;
```

### 3. ValidarObjetoNFe
```pascal
procedure ValidarObjetoNFe();
begin
  with ACBrNFe1.NotasFiscais.Items[0].NFe do
  begin
    // Validações básicas
    if Det.Count = 0 then
      raise Exception.Create('NFe deve ter ao menos 1 item');
    
    if pag.Count = 0 then
      raise Exception.Create('NFe deve ter ao menos 1 forma de pagamento');
    
    // Validação SEFAZ: vNF = soma(vPag)
    var somaVPag := 0.0;
    for var i := 0 to pag.Count - 1 do
      somaVPag := somaVPag + pag.Items[i].vPag;
    
    if Abs(Total.ICMSTot.vNF - somaVPag) > 0.01 then
      raise Exception.Create(Format(
        'Divergência: vNF=%.2f mas soma(vPag)=%.2f',
        [Total.ICMSTot.vNF, somaVPag]
      ));
  end;
end;
```

---

## 📊 CHECKLIST DE VALIDAÇÃO

Após implementar as correções, validar:

### ✅ Validações no Objeto (antes de gerar XML)
- [ ] `Det[i].Imposto.ICMS.vBC > 0` para todos os itens
- [ ] `Det[i].Imposto.ICMS.vICMS = vBC * (pICMS/100)` para todos
- [ ] `Total.ICMSTot.vBC = Σ(Det[i].Imposto.ICMS.vBC)`
- [ ] `Total.ICMSTot.vICMS = Σ(Det[i].Imposto.ICMS.vICMS)`
- [ ] `Total.ICMSTot.vDesc = Σ(Det[i].Prod.vDesc)`
- [ ] `Total.ICMSTot.vNF = Σ(pag[i].vPag)`
- [ ] `pag.Count > 0` e todos `pag[i].tPag` definidos

### ✅ Validações no XML Gerado
- [ ] `<dhEmi>` no formato correto: `YYYY-MM-DDThh:nn:ss-03:00`
- [ ] `<indPres>1</indPres>` para consumidor final
- [ ] `<indFinal>1</indFinal>` para consumidor final
- [ ] `<pag><detPag><tPag>01</tPag><vPag>...` existe
- [ ] `<xCpl>` não contém "null"
- [ ] Sem blocos `<IBSCBS>`

### ✅ Testes Funcionais
```json
// Teste 1: NFe simples (1 item, sem desconto)
{
  "produtos": [{
    "codigo": "001",
    "nome": "Produto Teste",
    "quantidade": "1",
    "valor_unitario": "100.00"
  }],
  "pagamentos": [{
    "forma": "01",
    "valor": "100.00"
  }]
}
```
**Esperado:**
- vBC (item) = 100.00
- vICMS (item) = 18.00
- vBC (total) = 100.00
- vICMS (total) = 18.00
- vNF = 100.00
- vPag = 100.00
- ✅ Aprovado SEFAZ

```json
// Teste 2: NFe com desconto
{
  "produtos": [{
    "codigo": "001",
    "nome": "Produto Teste",
    "quantidade": "1",
    "valor_unitario": "150.00",
    "vDesc": "8.00"
  }],
  "pagamentos": [{
    "forma": "01",
    "valor": "142.00"
  }]
}
```
**Esperado:**
- vProd (item) = 150.00
- vDesc (item) = 8.00
- vBC (item) = 142.00 (150 - 8)
- vICMS (item) = 25.56 (142 × 0.18)
- vBC (total) = 142.00
- vICMS (total) = 25.56
- vDesc (total) = 8.00
- vNF = 142.00
- vPag = 142.00
- ✅ Aprovado SEFAZ

---

## 🚨 REGRAS CRÍTICAS (NÃO VIOLAR!)

### 1️⃣ NUNCA manipular XML como string
```pascal
// ❌ PROIBIDO
var xml := ACBrNFe1.NotasFiscais.Items[0].XML;
xml := StringReplace(xml, 'antigo', 'novo', [rfReplaceAll]);

// ✅ CORRETO
with ACBrNFe1.NotasFiscais.Items[0].NFe do
  Ide.campo := novoValor;
```

### 2️⃣ NUNCA usar LoadFromFile após montar objeto
```pascal
// ❌ PROIBIDO
ACBrNFe1.NotasFiscais.LoadFromFile(path); // descarta objeto em memória

// ✅ CORRETO
// Apenas montar objeto e deixar ACBr gerar XML na hora de assinar
```

### 3️⃣ SEMPRE calcular item→total (nunca total→item)
```pascal
// ❌ PROIBIDO
Det[0].Imposto.ICMS.vBC := Total.ICMSTot.vBC; // copia total para item

// ✅ CORRETO
Total.ICMSTot.vBC := Σ(Det[i].Imposto.ICMS.vBC); // soma itens para total
```

### 4️⃣ SEMPRE validar antes de enviar
```pascal
// ✅ OBRIGATÓRIO
ValidarObjetoNFe(); // antes de ACBrNFe1.Enviar()
```

---

## 📝 DELIVERABLES ESPERADOS

### 1. Código Refatorado
- ✅ Arquivo `Un_principal.pas` com função `EmitirNFeJSON` reescrita
- ✅ Funções auxiliares criadas e documentadas
- ✅ Remoção de TODO código morto (linha 2184-2315)
- ✅ Remoção de TODAS manipulações string de XML

### 2. Testes Validados
- ✅ Teste 1 (simples) aprovado SEFAZ
- ✅ Teste 2 (com desconto) aprovado SEFAZ
- ✅ Teste 3 (múltiplos itens) aprovado SEFAZ

### 3. Documentação
- ✅ Comentários inline explicando lógica crítica
- ✅ Log das mudanças realizadas

---

## 🎯 MÉTRICAS DE SUCESSO

### Antes (Problemático):
- ❌ 15+ transformações do XML
- ❌ 6+ LoadFromFile
- ❌ 40+ manipulações string
- ❌ 142 operações para 10 itens
- ❌ Código: 2600+ linhas em uma função
- ❌ Taxa de rejeição SEFAZ: ~80%

### Depois (Esperado):
- ✅ 1 transformação (objeto→XML no envio)
- ✅ 0 LoadFromFile
- ✅ 0 manipulações string
- ✅ ~30 operações para 10 itens
- ✅ Código: ~500 linhas divididas em funções
- ✅ Taxa de rejeição SEFAZ: <5%

---

## 🔗 REFERÊNCIAS TÉCNICAS

### Schema NFe 4.00
- **Localização:** `DelphiEmissor/PL_010b_NT2025_002_v1.21/*.xsd`
- **Validação:** Usar ACBr schema validation antes de enviar

### Manual SEFAZ
- **Portal:** http://www.nfe.fazenda.gov.br/
- **Versão:** 4.00
- **Regras de validação:** Especialmente atenção a:
  - N12a (vDesc total deve bater com soma itens)
  - W03 (vNF deve bater com soma vPag)
  - N17 (vICMS deve ser vBC × pICMS)

### ACBr Trunk2
- **Documentação:** https://projetoacbr.com.br/
- **Componentes usados:** TACBrNFe, TACBrNFeDANFeRL

---

## 🚀 COMO PROCEDER

1. **Ler completamente** os documentos `ANALISE_UN_PRINCIPAL.md` e `FLUXO_EMISSAO_NFE_PROBLEMAS.md`

2. **Criar backup** do arquivo atual:
   ```bash
   cp Un_principal.pas Un_principal.pas.backup
   ```

3. **Implementar as correções** seguindo a ordem:
   - FASE 1: Eliminar manipulações string
   - FASE 2: Implementar fluxo correto de cálculo
   - FASE 3: Corrigir pagamentos
   - FASE 4: Ajustes complementares
   - FASE 5: Simplificar fluxo principal

4. **Compilar e testar** progressivamente:
   - Teste 1 (simples)
   - Teste 2 (com desconto)
   - Teste 3 (múltiplos itens)

5. **Validar** com SEFAZ Homologação antes de produção

6. **Documentar** mudanças e resultados

---

## ⚡ ATENÇÃO ESPECIAL

### Timezone (dhEmi)
```pascal
// ✅ Sempre usar timezone local (-03:00 para Brasil)
Ide.dhEmi := Now(); // ACBr cuida do timezone automaticamente
```

### Arredondamento
```pascal
// ✅ Sempre usar 2 casas decimais
function Round2(x: Double): Double;
begin
  Result := Round(x * 100) / 100;
end;
```

### Enums ACBr
```pascal
// ✅ Usar enums do ACBr, não strings
Ide.indPres := pcPresencial;  // não '1'
Ide.indFinal := cfConsumidorFinal; // não '1'
pag[0].tPag := fpDinheiro; // não '01'
```

---

## 🎬 CONCLUSÃO

Este prompt fornece **tudo que você precisa** para corrigir completamente o código e garantir aprovação SEFAZ.

**Princípios fundamentais:**
1. ✅ Manipular objeto, não string
2. ✅ Calcular item→total, não total→item
3. ✅ Validar antes de enviar
4. ✅ Simplicidade > Complexidade

**Resultado esperado:**
- Código limpo, manutenível e eficiente
- XML sempre válido
- 100% de aprovação SEFAZ (em condições normais)

---

**Boa sorte! 🚀**

*Se tiver dúvidas durante a implementação, consulte os documentos de análise ou peça esclarecimentos específicos.*

