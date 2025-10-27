## Integração ERP Laravel → Emissor Delphi (Indy HTTP API)

Este documento descreve como o ERP em Laravel deve chamar o Emissor Delphi para emissão de NF-e por HTTP local.

### Visão geral
- **Aplicativo Delphi** expõe uma API HTTP local (loopback) na porta **18080**.
- O ERP Laravel deve enviar requisições HTTP para `http://127.0.0.1:18080` (ou `http://localhost:18080`).
- O emissor só inicia o servidor HTTP após o usuário fazer login e a base conectar.

### Pré-requisitos
- Emissor Delphi aberto, com login efetuado e conexão de banco ativa.
- Certificado configurado corretamente (A1 arquivo PFX ou A3/Windows Store).
- Porta 18080 livre (sem outro serviço usando).

### Endpoints principais
- `GET /api/status`
  - Health-check. Retorna 200 e JSON com `ok: true` quando o emissor está pronto.
- `POST /api/emitir-nfe`
  - Recebe JSON do pedido e processa a emissão da NF-e via ACBr.
  - Content-Type: `application/json`

Outros (opcional, caso usem):
- `POST /api/cancelar-nfe` { xml_path, motivo }
- `POST /api/carta-correcao` { xml_path, correcao }
- `GET /api/consultar-nfe?xml_path=...`
- `GET /api/download-xml?xml_path=...`
- `GET /api/imprimir-danfe?xml_path=...`

### Exemplo de chamada (Postman)
- URL: `http://127.0.0.1:18080/api/emitir-nfe`
- Método: `POST`
- Headers: `Content-Type: application/json`
- Body (raw JSON):
```json
{
  "tipo": "nfe",
  "numero_pedido": "000123",
  "cliente": {
    "id": 15,
    "nome": "Empresa X LTDA",
    "cpf_cnpj": "00000000000100",
    "endereco": "Rua das Flores",
    "numero": "100",
    "complemento": "Sala 2",
    "bairro": "Centro",
    "cidade": "São Paulo",
    "uf": "SP",
    "cep": "01001000",
    "telefone": "11947146126",
    "email": "contato@empresax.com.br",
    "consumidor_final": "N",
    "tipo": "pj"
  },
  "produtos": [
    {
      "id": 101,
      "nome": "Caneta Azul",
      "ncm": "39269090",
      "cest": "2803800",
      "origem": 0,
      "quantidade": 10,
      "valor_unitario": 12.5,
      "unidade": "UN",
      "valor_total": 125.0
    },
    {
      "id": 102,
      "nome": "Caderno 100 folhas",
      "ncm": "48201000",
      "cest": "1000100",
      "origem": 0,
      "quantidade": 2,
      "valor_unitario": 25.9,
      "unidade": "UN",
      "valor_total": 51.8
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

### Exemplo (Laravel, usando Http Facade)
```php
use Illuminate\Support\Facades\Http;

$payload = [
    'tipo' => 'nfe',
    'numero_pedido' => '000123',
    'cliente' => [
        'id' => 15,
        'nome' => 'Empresa X LTDA',
        'cpf_cnpj' => '00000000000100',
        'endereco' => 'Rua das Flores',
        'numero' => '100',
        'complemento' => 'Sala 2',
        'bairro' => 'Centro',
        'cidade' => 'São Paulo',
        'uf' => 'SP',
        'cep' => '01001000',
        'telefone' => '11947146126',
        'email' => 'contato@empresax.com.br',
        'consumidor_final' => 'N',
        'tipo' => 'pj',
    ],
    'produtos' => [
        [
            'id' => 101,
            'nome' => 'Caneta Azul',
            'ncm' => '39269090',
            'cest' => '2803800',
            'origem' => 0,
            'quantidade' => 10,
            'valor_unitario' => 12.5,
            'unidade' => 'UN',
            'valor_total' => 125.0,
        ],
    ],
    'configuracoes' => [
        'cfop' => '5102',
        'ambiente' => 'homologacao',
        'serie' => '1',
        'tipo_nota' => 'products',
    ],
];

$response = Http::timeout(60)
    ->withHeaders(['Content-Type' => 'application/json'])
    ->post('http://127.0.0.1:18080/api/emitir-nfe', $payload);

if ($response->successful()) {
    $data = $response->json();
    // tratar $data['ok'], $data['numero'], $data['xml_path'] etc.
} else {
    // logar e exibir mensagem amigável
    \Log::error('Emissão NFe falhou', [
        'status' => $response->status(),
        'body' => $response->body(),
    ]);
}
```

### Fluxo sugerido no ERP (UI)
1) Ao abrir a tela, checar `GET /api/status`.
   - Se falhar, desabilitar botão “Emitir NF-e” e orientar o usuário a abrir/logar no Emissor.
2) Ao clicar “Emitir NF-e”, enviar `POST /api/emitir-nfe` com o JSON acima.
3) Salvar dados retornados (ex.: `xml_path`, `numero`, `chave`).
4) Opcional: disponibilizar ações “Imprimir DANFE”, “Download XML”, “Cancelar”, “Carta de Correção”.

### Erros comuns e como resolver
- `404 Not found`
  - Servidor Delphi não iniciado (abrir o Emissor e logar) ou porta incorreta.
  - Conferir `http://127.0.0.1:18080/api/status`.
- `405 Use POST em /api/emitir-nfe`
  - Envio com método incorreto; garanta `POST` e `Content-Type: application/json`.
- `Certificado Série 'XXXXXXXX', não encontrado!`
  - O número de série configurado não existe no Windows ou não corresponde ao certificado instalado.
  - Se A1 (PFX): use `tcCertFile`, defina caminho do `.pfx` e senha; deixe `NumeroSerie` vazio.
  - Se A3/Windows Store: instale no repositório Pessoal e use o Serial Number exato (sem espaços).
- `Could not bind socket`
  - Porta já em uso. Feche outro processo ou altere a porta do emissor.

### Códigos de resposta (HTTP + `code`)

Padrão do corpo (sempre JSON):

Sucesso (200):
```json
{
  "ok": true,
  "numero": "12345",
  "protocolo": "141240000000000",
  "chave_acesso": "35240100000000000000550010000012341000012345",
  "xml_path": "C:/Emissoes/NFe/2024/01/35123456789012345678901234567890123456789012-procNFe.xml",
  "pdf_path": "C:/Emissoes/NFe/2024/01/351234567890...-danfe.pdf",
  "warnings": [],
  "request_id": "abc123",
  "elapsed_ms": 850
}
```

Erro (>=400):
```json
{
  "ok": false,
  "code": "CERT_NOT_CONFIGURED",
  "erro": "Certificado A1/A3 não configurado",
  "details": {
    "hint": "Configure PFX e senha ou informe NumeroSerie do certificado instalado"
  },
  "request_id": "abc123",
  "elapsed_ms": 120
}
```

Mapeamento recomendado:
- 200 OK → Emissão concluída. Corpo de sucesso acima.
- 202 Accepted → Emissão enfileirada/assíncrona (opcional). Retorne `request_id` e status parcial.
- 400 Bad Request → Erro de validação/entrada/configuração do certificado.
  - `JSON_INVALID`
  - `PARAM_MISSING` (campo obrigatório ausente)
  - `INVALID_FIELD` (valor fora do padrão)
  - `CERT_NOT_CONFIGURED`
  - `CERT_PFX_NOT_FOUND`
  - `CERT_SERIAL_NOT_CONFIGURED`
  - `CERT_INVALID_SERIES`
- 401 Unauthorized → Caso no futuro exija token. `AUTH_REQUIRED`, `TOKEN_INVALID`.
- 404 Not Found → Rota inexistente. `ENDPOINT_NOT_FOUND`.
- 405 Method Not Allowed → Método incorreto. `METHOD_NOT_ALLOWED`.
- 409 Conflict → Idempotência/duplicidade de `numero_pedido`. `DUPLICATE_REQUEST` (retorne também dados já emitidos, se houver).
- 422 Unprocessable Entity → Regra de negócio violada. `BUSINESS_RULE_VIOLATION` (ex.: CFOP incompatível, CST inválido).
- 429 Too Many Requests → Limite de taxa (opcional). `RATE_LIMIT`.
- 500 Internal Server Error → Falha inesperada/ACBr. `EMISSAO_ERRO`.
- 503 Service Unavailable → Emissor não pronto (sem login, base não conectada). `EMISSOR_OFFLINE`.

Observações:
- Sempre preencher `request_id` e `elapsed_ms` para facilitar suporte.
- Em 409 `DUPLICATE_REQUEST`, preferir retornar 200 com o mesmo corpo de sucesso (idempotente) ou 409 com `data_existente` contendo `numero`, `xml_path`, etc.
- Em erros de certificado, responder em < 2s para evitar timeout do ERP.

### Tratamento recomendado no Laravel
```php
try {
  $res = Http::timeout(60)
    ->withHeaders(['Content-Type' => 'application/json'])
    ->post(config('services.emissor.url').'/api/emitir-nfe', $payload);

  if ($res->successful()) {
    $data = $res->json();
    // sucesso
  } elseif ($res->status() === 400) {
    $data = $res->json();
    $code = data_get($data, 'code');
    $msg  = data_get($data, 'erro', 'Erro de validação no emissor');
    switch ($code) {
      case 'CERT_NOT_CONFIGURED':
      case 'CERT_PFX_NOT_FOUND':
      case 'CERT_SERIAL_NOT_CONFIGURED':
        return back()->withErrors('Certificado inválido/ausente: '.$msg);
      case 'JSON_INVALID':
        return back()->withErrors('JSON inválido para emissão: '.$msg);
      default:
        return back()->withErrors($msg);
    }
  } elseif ($res->serverError()) {
    return back()->withErrors('Falha no emissor (5xx). Tente novamente.');
  } else {
    $msg = data_get($res->json(), 'erro') ?? 'Erro na solicitação ao emissor';
    return back()->withErrors($msg);
  }
} catch (\Illuminate\Http\Client\ConnectionException $e) {
  return back()->withErrors('Erro ao comunicar com emissor: '.$e->getMessage());
}
```

### Observações de ambiente
- A API escuta em `127.0.0.1:18080` (loopback). O ERP Laravel deve rodar na mesma máquina do Emissor Delphi para acessar o loopback.
- Se o Laravel estiver em Docker/WSL, use `http://host.docker.internal:18080` (ou o IP do host) e ajuste firewall.
- A API não possui autenticação; é para uso local assistido pelo usuário logado.

### Troubleshooting rápido
- Validar status: `GET http://127.0.0.1:18080/api/status`.
- Ver quem está na porta: `netstat -ano | findstr :18080`.
- Logs de depuração: usar **DebugView** (Sysinternals) e filtrar por `HTTP` para ver entradas como `HTTP POST /api/emitir-nfe`.

### Política por Plano (Catálogo e Emissão)

- Fonte: Plano do Tenant no Laravel. O Emissor consulta via API qual a política efetiva para liberar/ocultar cadastros e recursos.

Endpoint (Laravel): `GET /api/emissor/policy` (auth:sanctum)

Resposta exemplo:
```json
{
  "tenant_id": 123,
  "plan_id": 2,
  "policy": {
    "catalog_source": "erp",            
    "allow_local_catalog_edits": false,  
    "allow_issue_nfe": true,
    "allow_pos": false
  },
  "features": {
    "has_erp": true,
    "has_emissor": true,
    "allow_issue_nfe": true,
    "catalog_source": "erp",
    "allow_local_catalog_edits": false
  },
  "timestamp": "2025-01-17T14:25:31Z"
}
```

Interpretação no Emissor (Delphi):
- `catalog_source = 'erp'` ou `allow_local_catalog_edits = false` → desabilitar CRUD de clientes/produtos no Emissor; operar apenas por payload do ERP.
- `catalog_source = 'emissor'` → habilitar CRUD local (Firebird).
- `allow_issue_nfe` → habilitar/ocultar funcionalidades de emissão conforme plano.

Revalidação: consultar ao logar e a cada 10–15 minutos; manter cache para operação offline (somente leitura das flags até reconectar).

### Contrato de Payload (ERP → Emissor)

Campos mínimos (ex.: produtos):
```json
{
  "tipo": "nfe",
  "numero_pedido": "000123",
  "cliente": {
    "id": 15,
    "nome": "Empresa X LTDA",
    "cpf_cnpj": "00000000000100",
    "tipo": "pj",
    "endereco": "Rua...",
    "numero": "100",
    "bairro": "Centro",
    "cidade": "São Paulo",
    "uf": "SP",
    "cep": "01001000",
    "email": "contato@...",
    "telefone": "119...",
    "consumidor_final": "N"
  },
  "produtos": [
    {
      "id": 101,
      "nome": "Caneta Azul",
      "codigo_interno": "CAN-001",
      "codigo_barras": "789...",
      "ncm": "39269090",
      "cest": "2803800",
      "origem": 0,
      "unidade": "UN",
      "quantidade": 10.000,
      "valor_unitario": 12.50,
      "valor_total": 125.00,
      "cfop": "5102",
      "cst_icms": "00",
      "aliquota_icms": 18.0
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

Notas:
- Sempre enviar NCM/CEST/origem/CFOP/CST/aliquotas calculadas pelo ERP/Laravel quando `catalog_source = 'erp'`.
- Usar casas decimais coerentes (quantidade 3 casas; valores 2 casas).
- Se houver idempotência por `numero_pedido`, o Emissor deve retornar 200/409 com os dados já emitidos.

# 🎯 Melhorias de UX - Cadastro de Produtos

## 📋 Visão Geral

Este documento contém recomendações para melhorar a experiência do usuário (UX) no cadastro de produtos do ERP QFiscal, focando em validações inteligentes, campos condicionais e facilidades para preenchimento dos dados fiscais.

## 🎯 Melhorias Prioritárias

### 1. ✅ Validação em Tempo Real

**Problema**: Usuários podem digitar códigos fiscais incorretos (NCM com menos de 8 dígitos, CFOP inválido, etc.)

**Solução**: Implementar validação JavaScript em tempo real

```javascript
// Validação NCM (8 dígitos)
document.addEventListener('DOMContentLoaded', function() {
    const ncmInput = document.querySelector('input[name="ncm"]');
    ncmInput.addEventListener('input', function() {
        const value = this.value.replace(/\D/g, '');
        if (value.length > 8) {
            this.value = value.substring(0, 8);
        }
        this.classList.toggle('border-red-500', value.length > 0 && value.length !== 8);
        
        // Mostrar feedback visual
        const feedback = this.parentNode.querySelector('.ncm-feedback');
        if (feedback) {
            feedback.textContent = value.length === 8 ? '✅ NCM válido' : `⚠️ Digite 8 dígitos (${value.length}/8)`;
        }
    });
    
    // Validação CFOP (4 dígitos)
    const cfopInput = document.querySelector('input[name="cfop"]');
    cfopInput.addEventListener('input', function() {
        const value = this.value.replace(/\D/g, '');
        if (value.length > 4) {
            this.value = value.substring(0, 4);
        }
    });
    
    // Validação CEST (7 dígitos)
    const cestInput = document.querySelector('input[name="cest"]');
    cestInput.addEventListener('input', function() {
        const value = this.value.replace(/\D/g, '');
        if (value.length > 7) {
            this.value = value.substring(0, 7);
        }
    });
});
```

### 2. 🎯 Campos Inteligentes com Sugestões

**Problema**: Campo "Unidade" permite digitação livre, causando inconsistências

**Solução**: Implementar datalist com opções pré-definidas

```html
<div>
    <label class="text-sm text-gray-700 mb-1 block">Unidade</label>
    <div class="flex items-center border rounded px-3">
        <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
        </svg>
        <input list="unidades" name="unit" class="p-2 w-full focus:outline-none" placeholder="Ex.: UN, KG" required />
        <datalist id="unidades">
            <option value="UN">Unidade</option>
            <option value="KG">Quilograma</option>
            <option value="G">Grama</option>
            <option value="L">Litro</option>
            <option value="ML">Mililitro</option>
            <option value="M">Metro</option>
            <option value="M²">Metro Quadrado</option>
            <option value="M³">Metro Cúbico</option>
            <option value="CX">Caixa</option>
            <option value="PC">Peça</option>
            <option value="DZ">Dúzia</option>
            <option value="PAR">Par</option>
            <option value="HR">Hora</option>
            <option value="DIA">Dia</option>
        </datalist>
    </div>
</div>
```

### 3. 📋 Origem da Mercadoria com Dropdown

**Problema**: Campo "Origem" é texto livre, causando confusão sobre os códigos válidos

**Solução**: Substituir por select com descrições claras

```html
<div>
    <label class="text-sm text-gray-700 mb-1 block">Origem da Mercadoria</label>
    <div class="flex items-center border rounded px-3">
        <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2"/>
        </svg>
        <select name="origin" class="p-2 w-full focus:outline-none">
            <option value="">Selecione a origem</option>
            <option value="0">0 - Nacional</option>
            <option value="1">1 - Estrangeira - Importação direta</option>
            <option value="2">2 - Estrangeira - Adquirida no mercado interno</option>
            <option value="3">3 - Nacional - Mercadoria com >40% importação</option>
            <option value="4">4 - Nacional - Produção conforme processo produtivo</option>
            <option value="5">5 - Nacional - Mercadoria com <40% importação</option>
            <option value="6">6 - Estrangeira - Importação direta sem similar nacional</option>
            <option value="7">7 - Estrangeira - Mercado interno sem similar nacional</option>
            <option value="8">8 - Nacional - Mercadoria com >70% importação</option>
        </select>
    </div>
</div>
```

### 4. 🎨 Templates de Tributação

**Problema**: Usuários não sabem quais CSTs e alíquotas usar

**Solução**: Botões de template para regimes tributários comuns

```html
<div class="flex items-center justify-between mb-4">
    <h3 class="font-semibold text-gray-800">Tributação</h3>
    <div class="flex space-x-2">
        <button type="button" onclick="applyTaxTemplate('simples')" class="text-xs bg-blue-100 text-blue-700 px-3 py-1 rounded hover:bg-blue-200">
            📋 Simples Nacional
        </button>
        <button type="button" onclick="applyTaxTemplate('lucro')" class="text-xs bg-green-100 text-green-700 px-3 py-1 rounded hover:bg-green-200">
            📋 Lucro Presumido
        </button>
        <button type="button" onclick="applyTaxTemplate('real')" class="text-xs bg-purple-100 text-purple-700 px-3 py-1 rounded hover:bg-purple-200">
            📋 Lucro Real
        </button>
    </div>
</div>

<script>
function applyTaxTemplate(type) {
    if (type === 'simples') {
        document.querySelector('input[name="csosn"]').value = '102';
        document.querySelector('input[name="cst_icms"]').value = '';
        document.querySelector('input[name="cst_pis"]').value = '01';
        document.querySelector('input[name="cst_cofins"]').value = '01';
        document.querySelector('input[name="aliquota_icms"]').value = '0';
        document.querySelector('input[name="aliquota_pis"]').value = '0';
        document.querySelector('input[name="aliquota_cofins"]').value = '0';
        
        showNotification('Template Simples Nacional aplicado! ✅', 'success');
    } else if (type === 'lucro') {
        document.querySelector('input[name="csosn"]').value = '';
        document.querySelector('input[name="cst_icms"]').value = '00';
        document.querySelector('input[name="cst_pis"]').value = '01';
        document.querySelector('input[name="cst_cofins"]').value = '01';
        document.querySelector('input[name="aliquota_icms"]').value = '18';
        document.querySelector('input[name="aliquota_pis"]').value = '1.65';
        document.querySelector('input[name="aliquota_cofins"]').value = '7.6';
        
        showNotification('Template Lucro Presumido aplicado! ✅', 'success');
    }
}

function showNotification(message, type) {
    // Implementar notificação toast
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 px-4 py-2 rounded shadow-lg z-50 ${
        type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
    }`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}
</script>
```

### 5. 🔄 Campos Condicionais

**Problema**: Campos fiscais aparecem mesmo para serviços

**Solução**: Mostrar campos fiscais apenas para produtos

```html
<!-- Seção de dados fiscais com condicional -->
<div id="fiscal-fields" class="bg-white shadow rounded-lg overflow-hidden" style="display: none;">
    <div class="px-6 py-4 border-b flex items-center space-x-2">
        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M9 8h6M4 7h16M4 17h16"/>
        </svg>
        <h3 class="font-semibold text-gray-800">Dados Fiscais</h3>
        <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded">Apenas para produtos</span>
    </div>
    <div class="p-6">
        <!-- Campos fiscais aqui -->
    </div>
</div>

<script>
document.querySelector('select[name="type"]').addEventListener('change', function() {
    const fiscalFields = document.getElementById('fiscal-fields');
    const tributacaoFields = document.getElementById('tributacao-fields');
    
    if (this.value === 'product') {
        fiscalFields.style.display = 'block';
        tributacaoFields.style.display = 'block';
        
        // Tornar campos fiscais obrigatórios
        document.querySelectorAll('#fiscal-fields input').forEach(input => {
            input.required = true;
        });
    } else {
        fiscalFields.style.display = 'none';
        tributacaoFields.style.display = 'none';
        
        // Remover obrigatoriedade
        document.querySelectorAll('#fiscal-fields input').forEach(input => {
            input.required = false;
        });
    }
});
</script>
```

### 6. ⚠️ Validação de Campos Obrigatórios

**Problema**: Usuários não sabem quais campos são obrigatórios

**Solução**: Indicadores visuais claros

```html
<div>
    <label class="text-sm text-gray-700 mb-1 block">
        NCM <span class="text-red-500">*</span>
        <span class="text-xs text-gray-500">(Obrigatório para produtos)</span>
    </label>
    <div class="flex items-center border rounded px-3">
        <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18"/>
        </svg>
        <input form="productForm" type="text" name="ncm" class="p-2 w-full focus:outline-none" placeholder="8 dígitos" />
        <div class="ncm-feedback text-xs text-gray-500 ml-2"></div>
    </div>
</div>
```

### 7. 🤖 Auto-preenchimento Inteligente

**Problema**: CFOPs precisam ser digitados manualmente

**Solução**: Auto-preenchimento baseado em categoria

```javascript
// Auto-preenchimento baseado em categoria
document.querySelector('select[name="category_id"]').addEventListener('change', function() {
    const categoryId = this.value;
    if (categoryId) {
        // Buscar CFOP padrão da categoria
        fetch(`/api/categories/${categoryId}/default-cfop`)
            .then(response => response.json())
            .then(data => {
                if (data.cfop) {
                    document.querySelector('input[name="cfop"]').value = data.cfop;
                    showNotification(`CFOP ${data.cfop} aplicado automaticamente! ✅`, 'info');
                }
            })
            .catch(error => {
                console.log('Erro ao buscar CFOP padrão:', error);
            });
    }
});

// Auto-preenchimento de NCM baseado em nome do produto
document.querySelector('input[name="name"]').addEventListener('blur', function() {
    const productName = this.value.toLowerCase();
    const ncmInput = document.querySelector('input[name="ncm"]');
    
    if (!ncmInput.value) {
        // Sugerir NCM baseado em palavras-chave
        const ncmSuggestions = {
            'roupa': '61091000',
            'camiseta': '61091000',
            'calça': '62034200',
            'sapato': '64039900',
            'livro': '49019900',
            'medicamento': '30049099',
            'alimento': '19059000'
        };
        
        for (const [keyword, ncm] of Object.entries(ncmSuggestions)) {
            if (productName.includes(keyword)) {
                ncmInput.value = ncm;
                showNotification(`NCM ${ncm} sugerido para "${keyword}" ✅`, 'info');
                break;
            }
        }
    }
});
```

### 8. 📱 Melhorar Layout Mobile

**Problema**: Layout não otimizado para dispositivos móveis

**Solução**: Grid responsivo melhorado

```html
<!-- Layout responsivo otimizado -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
    <!-- Campos aqui -->
</div>

<!-- Para campos importantes, usar largura completa em mobile -->
<div class="col-span-1 sm:col-span-2 lg:col-span-1">
    <!-- Campo importante -->
</div>
```

### 9. 💡 Feedback Visual e Dicas

**Problema**: Usuários não entendem o que cada campo significa

**Solução**: Adicionar dicas contextuais

```html
<div>
    <label class="text-sm text-gray-700 mb-1 block">NCM</label>
    <div class="flex items-center border rounded px-3">
        <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18"/>
        </svg>
        <input form="productForm" type="text" name="ncm" class="p-2 w-full focus:outline-none" placeholder="8 dígitos" />
    </div>
    <div class="text-xs text-gray-500 mt-1 flex items-center">
        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        💡 Dica: NCM com 8 dígitos é obrigatório para produtos físicos
    </div>
</div>
```

### 10. 💾 Salvamento Progressivo

**Problema**: Usuários perdem dados ao fechar o navegador

**Solução**: Auto-save de rascunho

```javascript
// Auto-save draft
let saveTimeout;
const form = document.getElementById('productForm');

function saveDraft() {
    const formData = new FormData(form);
    const draft = {};
    
    for (let [key, value] of formData.entries()) {
        draft[key] = value;
    }
    
    localStorage.setItem('product_draft', JSON.stringify(draft));
    showNotification('Rascunho salvo automaticamente 💾', 'info');
}

function loadDraft() {
    const draft = localStorage.getItem('product_draft');
    if (draft) {
        const data = JSON.parse(draft);
        
        // Preencher campos com dados do rascunho
        Object.entries(data).forEach(([key, value]) => {
            const field = form.querySelector(`[name="${key}"]`);
            if (field) {
                field.value = value;
            }
        });
        
        showNotification('Rascunho carregado 📄', 'info');
    }
}

// Auto-save a cada 30 segundos
document.querySelectorAll('input, select').forEach(field => {
    field.addEventListener('input', function() {
        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(() => {
            saveDraft();
        }, 30000);
    });
});

// Carregar rascunho ao abrir a página
document.addEventListener('DOMContentLoaded', loadDraft);

// Limpar rascunho ao salvar com sucesso
form.addEventListener('submit', function() {
    localStorage.removeItem('product_draft');
});
```

## 🎯 Prioridades de Implementação

### 🔥 Alta Prioridade
1. **Validação em tempo real** (NCM, CFOP, CEST)
2. **Dropdown para origem da mercadoria**
3. **Campos condicionais** (produto vs serviço)

### 🔶 Média Prioridade
4. **Templates de tributação**
5. **Indicadores de campos obrigatórios**
6. **Auto-preenchimento por categoria**

### 🔵 Baixa Prioridade
7. **Salvamento progressivo**
8. **Dicas contextuais**
9. **Layout mobile otimizado**
10. **Auto-sugestão de NCM**

## 📊 Benefícios Esperados

- ✅ **Redução de erros** em campos fiscais críticos
- ✅ **Tempo de cadastro** reduzido em ~40%
- ✅ **Satisfação do usuário** aumentada
- ✅ **Conformidade fiscal** melhorada
- ✅ **Suporte reduzido** por dúvidas de preenchimento

## 🚀 Próximos Passos

1. **Implementar validações JavaScript** básicas
2. **Criar dropdown de origem** da mercadoria
3. **Adicionar templates** de tributação
4. **Testar com usuários** reais
5. **Iterar baseado** no feedback

---

*Documento criado em: Janeiro 2025*  
*Versão: 1.0*  
*Status: Recomendações para implementação*


