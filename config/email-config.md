# Configuração de Email - QFiscal

## Configurações necessárias no arquivo .env

Adicione as seguintes configurações ao seu arquivo `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=seu-email@gmail.com
MAIL_PASSWORD=sua-senha-de-app
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=seu-email@gmail.com
MAIL_FROM_NAME="QFiscal"
```

## Configuração para Gmail

1. Ative a verificação em duas etapas na sua conta Google
2. Gere uma senha de app:
   - Vá em Configurações da Conta Google
   - Segurança
   - Verificação em duas etapas
   - Senhas de app
   - Gere uma nova senha para "Email"

3. Use essa senha no campo `MAIL_PASSWORD`

## Configuração para outros provedores

### Outlook/Hotmail
```env
MAIL_HOST=smtp-mail.outlook.com
MAIL_PORT=587
```

### Yahoo
```env
MAIL_HOST=smtp.mail.yahoo.com
MAIL_PORT=587
```

### Provedor próprio
```env
MAIL_HOST=seu-servidor-smtp.com
MAIL_PORT=587
MAIL_USERNAME=seu-usuario
MAIL_PASSWORD=sua-senha
```

## Teste de Email

Para testar se o email está funcionando, execute:

```bash
php artisan tinker
```

E depois:

```php
Mail::raw('Teste de email', function($message) {
    $message->to('seu-email@exemplo.com')
            ->subject('Teste QFiscal');
});
```

## Notas Importantes

- O email será enviado automaticamente após o cadastro
- O link de ativação é válido por 24 horas
- Em produção, considere usar serviços como SendGrid, Mailgun ou Amazon SES 