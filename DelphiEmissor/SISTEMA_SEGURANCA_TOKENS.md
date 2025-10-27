# 🔐 Sistema de Segurança com Tokens - Emissor Delphi

## 📋 Visão Geral

O emissor Delphi agora possui um sistema robusto de segurança baseado em tokens de autenticação. Todos os endpoints de emissão de notas fiscais requerem um token válido para funcionar.

## 🛡️ Endpoints Protegidos

### ✅ **Endpoints Públicos (sem autenticação)**
- `GET /api/status` - Verificação de status do emissor

### 🔒 **Endpoints Protegidos (requerem token)**
- `POST /api/emitir-nfe` - Emissão de NFe
- `POST /api/cancelar-nfe` - Cancelamento de NFe
- `POST /api/carta-correcao` - Carta de Correção Eletrônica
- `POST /api/inutilizar-nfe` - Inutilização de NFe
- `POST /api/emitir-nfse` - Emissão de NFSe

## 🔧 Configuração de Tokens

### **Arquivo de Tokens: `tokens.txt`**

O arquivo `tokens.txt` é criado automaticamente na pasta do executável do emissor:

```
# Arquivo de tokens válidos para o emissor
# Um token por linha
# Linhas iniciadas com # são comentários

# Token padrão (altere este valor)
qfiscal_default_token_2025

# Adicione seus tokens aqui
meu_token_secreto_123
outro_token_valido_456
```

### **Como Adicionar Novos Tokens**

1. **Edite o arquivo `tokens.txt`**
2. **Adicione um token por linha**
3. **Reinicie o emissor** para carregar os novos tokens
4. **Configure o token no Laravel** em `/admin/delphi-config`

## 📡 Como Usar no Laravel

### **1. Configurar Token no Admin**
Defina as credenciais e o esquema de autenticação em `/admin/delphi-config` (ou via `.env`):

```env
SERVICES_DELPI_URL=http://127.0.0.1:18080
SERVICES_DELPHI_AUTH=x-token   # opções: bearer | x-token | query | none
SERVICES_DELPHI_TOKEN=qfiscal_default_token_2025
```

Equivalente via UI:
```
URL:   http://127.0.0.1:18080
Auth:  x-token
Token: qfiscal_default_token_2025
```

### **2. Esquemas de Autenticação Suportados**
O ERP tenta os esquemas abaixo (nessa ordem, conforme preferência configurada):

- `bearer`: header `Authorization: Bearer <token>`
- `x-token`: header `X-Token: <token>` (também envia `X-Authorization` e `X-Api-Token`)
- `query`: parâmetro de query `?token=<token>`
- `none`: sem autenticação (apenas para desenvolvimento)

Para o seu ambiente, recomendamos usar **X-Token**.

### **3. Exemplo de Envio (ilustrativo)**
```php
$token = Setting::get('services.delphi.token');
$auth  = Setting::get('services.delphi.auth', 'x-token');
$http  = Http::timeout($timeout);

if ($token) {
    if ($auth === 'bearer') {
        $http = $http->withHeaders(['Authorization' => 'Bearer '.$token]);
    } elseif ($auth === 'x-token') {
        $http = $http->withHeaders([
            'X-Token' => $token,
            'X-Authorization' => $token,
            'X-Api-Token' => $token,
        ]);
    } elseif ($auth === 'query') {
        $url .= (str_contains($url,'?')?'&':'?').'token='.urlencode($token);
    }
}

$response = $http->post($url.'/api/emitir-nfe', $payload);
```

## 📊 Logs de Segurança

### **Arquivo de Log: `logs/security_YYYY-MM-DD.log`**

O sistema registra todos os eventos de segurança:

```
[2025-01-06 14:30:15] STARTUP: Emissor iniciado com sistema de segurança
[2025-01-06 14:31:22] AUTH_FAILED: Tentativa de emissão NFe sem token válido - IP: 192.168.1.100
[2025-01-06 14:32:45] NFE_EMIT: NFe emitida com sucesso - IP: 192.168.1.100
[2025-01-06 14:33:12] NFE_CANCEL: NFe cancelada com sucesso - IP: 192.168.1.100
```

### **Tipos de Eventos Logados**
- `STARTUP` - Inicialização do emissor
- `AUTH_FAILED` - Tentativa de acesso sem token válido
- `NFE_EMIT` - NFe emitida com sucesso
- `NFE_CANCEL` - NFe cancelada com sucesso
- `NFE_CCE` - Carta de correção emitida
- `NFE_INUTIL` - NFe inutilizada
- `NFSE_EMIT` - NFSe emitida

## 🚨 Respostas de Erro

### **Token Ausente ou Inválido**
```json
{
  "error": "Token de autenticação inválido ou ausente"
}
```
**Status HTTP:** `401 Unauthorized`

### **Token Válido**
```json
{
  "ok": true,
  "message": "NFe emitida com sucesso",
  "numero": "123",
  "chave": "35250114200166000187550010000001234567890123"
}
```
**Status HTTP:** `200 OK`

## 🔄 Modo Desenvolvimento

Se o arquivo `tokens.txt` não existir ou estiver vazio, o emissor funciona em **modo desenvolvimento** (sem autenticação). Isso facilita testes locais.

## 🛠️ Manutenção

### **Alterar Token Padrão**
1. Edite `tokens.txt`
2. Altere o token `qfiscal_default_token_2025`
3. Reinicie o emissor
4. Atualize a configuração no Laravel

### **Adicionar Múltiplos Tokens**
```txt
# Tokens para diferentes ambientes
token_homologacao_123
token_producao_456
token_teste_789
```

### **Remover Token**
1. Comente ou remova a linha do token em `tokens.txt`
2. Reinicie o emissor

## 🔒 Boas Práticas

### **Segurança**
- ✅ Use tokens longos e complexos
- ✅ Troque tokens regularmente
- ✅ Monitore logs de segurança
- ✅ Use tokens diferentes para homologação/produção

### **Produção**
- ✅ Desative modo desenvolvimento
- ✅ Configure tokens únicos por cliente
- ✅ Implemente rotação de tokens
- ✅ Monitore tentativas de acesso não autorizado

## 📞 Suporte

Em caso de problemas:
1. Verifique os logs em `logs/security_*.log`
2. Confirme se o token está correto em ambos os sistemas
3. Teste com `GET /api/status` (não requer token)
4. Verifique se o arquivo `tokens.txt` existe e está correto

---

ℹ️ Observações
- Para geração de DANFE a partir de um XML já autorizado, o ERP pode solicitar ao emissor com payload contendo `xml_path` e `gerar_pdf=true`. Certifique-se de que o token e o esquema de autenticação estejam corretos (recomendado: `X-Token`).
- Se aparecer “Unsupported Authorization Scheme” nos logs do emissor, ajuste o esquema no ERP para `x-token` ou habilite o envio via parâmetro `?token=`.

---

**🎯 Sistema implementado com sucesso! O emissor agora está protegido contra acesso não autorizado.**
