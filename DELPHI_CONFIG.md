# 🔧 **CONFIGURAÇÃO DO DELPHI EMISSOR**

## 📋 **VARIÁVEIS DE AMBIENTE**

Adicione estas linhas ao seu arquivo `.env`:

```env
# Configuração do Emissor Delphi
DELPHI_EMISSOR_URL=http://localhost:18080
DELPHI_EMISSOR_TIMEOUT=30
```

## 🔍 **VERIFICAÇÃO DE CONEXÃO**

Para testar se o Delphi está respondendo, você pode:

1. **Acessar diretamente:** http://localhost:18080/api/status
2. **Usar curl:**
   ```bash
   curl http://localhost:18080/api/status
   ```

## 📊 **STATUS ATUAL**

- ✅ **URL configurada:** http://localhost:18080
- ✅ **HTTP 200:** Sim (conforme admin/dashboard)
- ✅ **Comunicação:** Funcionando

## 🚀 **PRÓXIMOS PASSOS**

1. **Implementar no Delphi:**
   - Endpoint `/api/emitir-nfe`
   - Processamento do payload JSON
   - Emissão via ACBr
   - Retorno da resposta

2. **Testar integração:**
   - Emitir NFe pelo ERP
   - Verificar comunicação
   - Validar resposta

## 📞 **SUPORTE**

Se houver problemas de conexão:
- Verifique se o Delphi está rodando na porta 18080
- Confirme se o firewall não está bloqueando
- Teste a URL diretamente no navegador
