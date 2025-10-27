# 🔍 **REVISÃO DA INTEGRAÇÃO QFISCAL ERP ↔ EMISSOR DELPHI**

## 📋 **RESUMO EXECUTIVO**

Esta revisão analisa a compatibilidade entre as estruturas de dados do ERP QFiscal (Laravel/MySQL) e o Emissor Delphi (Firebird), identificando inconsistências e propondo ajustes para garantir uma integração perfeita.

**Status da Comunicação:** ✅ **OK**  
**URL:** `http://localhost:18080`  
**HTTP:** 200 — OK: **sim**

---

## 🗄️ **ANÁLISE DE COMPATIBILIDADE**

### **📦 TABELA PRODUTOS**

#### **Inconsistências Identificadas:**

| Campo Laravel | Campo Firebird | Status | Observação |
|---------------|----------------|--------|------------|
| `id` | `ID_PRODUTO` | ✅ Compatível | |
| `name` | `DS_NOME` | ✅ Compatível | |
| `sku` | `CD_INTERNO` | ✅ Compatível | |
| `ean` | `CD_GTIN` | ✅ Compatível | |
| `unit` | `CD_UNIDADE` | ✅ Compatível | |
| `ncm` | `CD_NCM` | ⚠️ **DIFERENÇA** | Laravel: VARCHAR(8) vs Firebird: VARCHAR(20) |
| `cest` | `CD_CEST` | ⚠️ **DIFERENÇA** | Laravel: VARCHAR(7) vs Firebird: VARCHAR(20) |
| `origin` | `CD_ORIGEM_PRODUTO` | ⚠️ **DIFERENÇA** | Laravel: VARCHAR(2) vs Firebird: INTEGER |
| `price` | `VR_VENDA` | ✅ Compatível | |

#### **Campos Adicionais no Laravel (não mapeados):**
- `cfop` (VARCHAR(4))
- `csosn` (VARCHAR(3))
- `cst_icms` (VARCHAR(3))
- `cst_pis` (VARCHAR(2))
- `cst_cofins` (VARCHAR(2))
- `aliquota_icms` (DECIMAL(5,2))
- `aliquota_pis` (DECIMAL(5,2))
- `aliquota_cofins` (DECIMAL(5,2))

### **👤 TABELA CLIENTES**

#### **Inconsistências Identificadas:**

| Campo Laravel | Campo Firebird | Status | Observação |
|---------------|----------------|--------|------------|
| `id` | `ID_PESSOA` | ✅ Compatível | |
| `name` | `DS_RAZAOSOCIAL_NOME` | ✅ Compatível | |
| `cpf_cnpj` | `NR_CNPJ_CPF` | ✅ Compatível | |
| `ie_rg` | `NR_IE_RG` | ✅ Compatível | |
| `type` | `FG_TIPOPESSOA` | ⚠️ **DIFERENÇA** | Laravel: 'pf'/'pj' vs Firebird: 'PESSOA FÍSICA'/'JURÍDICA' |
| `address` | `DS_ENDERECO` | ✅ Compatível | |
| `number` | `NR_NUMERO` | ✅ Compatível | |
| `complement` | `DS_COMPLEMENTO` | ✅ Compatível | |
| `neighborhood` | `DS_BAIRRO` | ✅ Compatível | |
| `city` | `DS_MUNICIPIO` | ✅ Compatível | |
| `state` | `CD_UF` | ✅ Compatível | |
| `zip_code` | `NR_CEP` | ⚠️ **DIFERENÇA** | Laravel: VARCHAR(255) vs Firebird: VARCHAR(8) |
| `phone` | `NR_TELEFONE1` | ✅ Compatível | |
| `email` | `DS_EMAIL` | ✅ Compatível | |
| `consumidor_final` | `FG_CONSUMIDOR_FINAL` | ⚠️ **DIFERENÇA** | Laravel: 'S'/'N' vs Firebird: VARCHAR(25) |
| `codigo_ibge` | `CD_MUNICIPIO` | ⚠️ **DIFERENÇA** | Laravel: VARCHAR(7) vs Firebird: INTEGER |

---

## 🔧 **AJUSTES NECESSÁRIOS**

### **1. Mapeamento de Tipos de Pessoa**

**Problema:** Diferença na representação de tipos de pessoa.

**Solução Laravel → Delphi:**
```php
// No controller de emissão de NFe
$tipoPessoa = $cliente->type === 'pf' ? 'PESSOA FÍSICA' : 'JURÍDICA';
```

**Solução Delphi → Laravel:**
```delphi
// No Delphi, converter para padrão Laravel
if SameText(FG_TIPOPESSOA, 'PESSOA FÍSICA') then
  TipoLaravel := 'pf'
else
  TipoLaravel := 'pj';
```

### **2. Mapeamento de Consumidor Final**

**Problema:** Laravel usa 'S'/'N', Firebird usa VARCHAR(25).

**Solução Laravel → Delphi:**
```php
$consumidorFinal = $cliente->consumidor_final === 'S' ? 'CONSUMIDOR FINAL' : 'REVENDA';
```

**Solução Delphi → Laravel:**
```delphi
// No Delphi
if SameText(FG_CONSUMIDOR_FINAL, 'CONSUMIDOR FINAL') then
  ConsumidorFinal := 'S'
else
  ConsumidorFinal := 'N';
```

### **3. Código IBGE vs Código Município**

**Problema:** Laravel armazena código IBGE como string, Firebird como integer.

**Solução Laravel → Delphi:**
```php
$codigoMunicipio = (int) $cliente->codigo_ibge;
```

**Solução Delphi → Laravel:**
```delphi
// No Delphi, converter integer para string
CD_IBGE := IntToStr(CD_MUNICIPIO);
```

### **4. Tamanhos de Campos**

**Problema:** Diferenças nos tamanhos máximos dos campos.

**Ajustes necessários:**

#### **NCM:**
- Laravel: VARCHAR(8) → **AUMENTAR** para VARCHAR(20)
- Firebird: VARCHAR(20) ✅

#### **CEST:**
- Laravel: VARCHAR(7) → **AUMENTAR** para VARCHAR(20)
- Firebird: VARCHAR(20) ✅

#### **CEP:**
- Laravel: VARCHAR(255) → **REDUZIR** para VARCHAR(8)
- Firebird: VARCHAR(8) ✅

#### **Origem do Produto:**
- Laravel: VARCHAR(2) → **ALTERAR** para INTEGER
- Firebird: INTEGER ✅

---

## 📝 **PAYLOAD JSON ATUALIZADO**

### **Estrutura Recomendada para Emissão:**

```json
{
  "tipo": "nfe",
  "numero_pedido": "000123",
  "tenant_id": 1,
  "cliente": {
    "id": 10,
    "nome": "Cliente Teste",
    "cpf_cnpj": "12345678909",
    "tipo": "JURIDICA",
    "endereco": "Rua A",
    "numero": "123",
    "complemento": null,
    "bairro": "Centro",
    "cidade": "São Paulo",
    "uf": "SP",
    "cep": "01001000",
    "telefone": "11999999999",
    "email": "cliente@teste.com",
    "consumidor_final": "CONSUMIDOR FINAL",
    "codigo_municipio": 3550308
  },
  "produtos": [
    {
      "id": 99,
      "nome": "Produto X",
      "codigo_interno": "PROD001",
      "codigo_barras": "7891234567890",
      "ncm": "84713012",
      "cest": null,
      "origem": 0,
      "unidade": "UN",
      "quantidade": 2,
      "valor_unitario": 1500.00,
      "valor_total": 3000.00,
      "cfop": "5102",
      "cst_icms": "102",
      "aliquota_icms": 18.00
    }
  ],
  "configuracoes": {
    "cfop": "5102",
    "ambiente": "homologacao",
    "serie": "1",
    "tipo_nota": "products"
  }
}
```

---

## 🚀 **IMPLEMENTAÇÃO RECOMENDADA**

### **1. Migração Laravel (Ajustar Tamanhos)**

```php
// Nova migration para ajustar tamanhos
Schema::table('products', function (Blueprint $table) {
    $table->string('ncm', 20)->change();  // Aumentar de 8 para 20
    $table->string('cest', 20)->change(); // Aumentar de 7 para 20
    $table->integer('origin')->change();  // Mudar de VARCHAR(2) para INTEGER
});

Schema::table('clients', function (Blueprint $table) {
    $table->string('zip_code', 8)->change(); // Reduzir de 255 para 8
});
```

### **2. Controller de Emissão NFe**

```php
class NFeController extends Controller
{
    public function emitir(Request $request)
    {
        $pedido = $request->validate([
            'numero_pedido' => 'required|string',
            'cliente_id' => 'required|exists:clients,id',
            'produtos' => 'required|array|min:1'
        ]);

        $cliente = Client::findOrFail($pedido['cliente_id']);
        
        // Converter dados para formato Delphi
        $payload = [
            'tipo' => 'nfe',
            'numero_pedido' => $pedido['numero_pedido'],
            'tenant_id' => auth()->user()->tenant_id,
            'cliente' => [
                'id' => $cliente->id,
                'nome' => $cliente->name,
                'cpf_cnpj' => $cliente->cpf_cnpj,
                'tipo' => $cliente->type === 'pf' ? 'PESSOA FÍSICA' : 'JURÍDICA',
                'endereco' => $cliente->address,
                'numero' => $cliente->number,
                'complemento' => $cliente->complement,
                'bairro' => $cliente->neighborhood,
                'cidade' => $cliente->city,
                'uf' => $cliente->state,
                'cep' => $cliente->zip_code,
                'telefone' => $cliente->phone,
                'email' => $cliente->email,
                'consumidor_final' => $cliente->consumidor_final === 'S' ? 'CONSUMIDOR FINAL' : 'REVENDA',
                'codigo_municipio' => (int) $cliente->codigo_ibge
            ],
            'produtos' => $this->formatarProdutos($pedido['produtos']),
            'configuracoes' => [
                'cfop' => '5102',
                'ambiente' => config('app.env') === 'production' ? 'producao' : 'homologacao',
                'serie' => '1',
                'tipo_nota' => 'products'
            ]
        ];

        // Enviar para Delphi
        $response = Http::post(config('services.delphi.url') . '/api/emitir-nfe', $payload);
        
        if ($response->successful()) {
            return response()->json($response->json());
        }
        
        return response()->json(['error' => 'Erro na emissão'], 500);
    }

    private function formatarProdutos($produtos)
    {
        return collect($produtos)->map(function ($item) {
            $produto = Product::findOrFail($item['product_id']);
            
            return [
                'id' => $produto->id,
                'nome' => $produto->name,
                'codigo_interno' => $produto->sku,
                'codigo_barras' => $produto->ean,
                'ncm' => $produto->ncm,
                'cest' => $produto->cest,
                'origem' => (int) $produto->origin,
                'unidade' => $produto->unit,
                'quantidade' => $item['quantity'],
                'valor_unitario' => $item['unit_price'],
                'valor_total' => $item['quantity'] * $item['unit_price'],
                'cfop' => $produto->cfop ?: '5102',
                'cst_icms' => $produto->cst_icms,
                'aliquota_icms' => $produto->aliquota_icms
            ];
        })->toArray();
    }
}
```

### **3. Configuração do Serviço**

```php
// config/services.php
'delphi' => [
    'url' => env('DELPHI_EMISSOR_URL', 'http://localhost:18080'),
    'timeout' => env('DELPHI_EMISSOR_TIMEOUT', 30),
],
```

---

## ✅ **CHECKLIST DE IMPLEMENTAÇÃO**

### **Laravel (ERP QFiscal)**
- [ ] Ajustar tamanhos dos campos NCM, CEST, CEP
- [ ] Alterar campo `origin` de VARCHAR(2) para INTEGER
- [ ] Implementar controller de emissão NFe
- [ ] Adicionar conversão de tipos (pf/pj → PESSOA FÍSICA/JURÍDICA)
- [ ] Adicionar conversão de consumidor_final (S/N → CONSUMIDOR FINAL/REVENDA)
- [ ] Adicionar conversão de código IBGE (string → integer)
- [ ] Configurar URL do Delphi no `.env`

### **Delphi (Emissor)**
- [ ] Implementar endpoint `/api/emitir-nfe`
- [ ] Implementar conversão de tipos de pessoa
- [ ] Implementar conversão de consumidor final
- [ ] Implementar conversão de código município
- [ ] Validar campos obrigatórios
- [ ] Retornar resposta padronizada (número, protocolo, xml_path)

### **Testes**
- [ ] Teste de comunicação HTTP
- [ ] Teste de mapeamento de dados
- [ ] Teste de emissão de NFe
- [ ] Teste de tratamento de erros
- [ ] Teste de idempotência (tenant_id + numero_pedido)

---

## 🎯 **PRÓXIMOS PASSOS**

1. **Implementar ajustes de banco de dados** (migrações)
2. **Desenvolver controller de emissão NFe**
3. **Implementar endpoint no Delphi**
4. **Realizar testes de integração**
5. **Documentar fluxo completo**

---

## 📞 **SUPORTE**

Para dúvidas sobre a integração:
- **Laravel:** Verificar logs em `storage/logs/`
- **Delphi:** Verificar console do aplicativo
- **Comunicação:** Testar endpoint `/api/status`

---

*Documento de revisão criado em Janeiro 2025*  
*Versão: 1.0*  
*Status: Em análise*
