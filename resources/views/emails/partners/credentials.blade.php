<div style="font-family: Arial, sans-serif; background:#f6f8fb; padding:24px; color:#111827;">
  <table width="100%" cellpadding="0" cellspacing="0" style="max-width:700px; margin:0 auto; background:#ffffff; border:1px solid #e5e7eb; border-radius:8px; overflow:hidden;">
    <tr>
      <td style="padding:16px 20px; background:#111827; color:#ffffff;">
        <strong style="font-size:18px;">Acesso ao Painel do Parceiro</strong>
      </td>
    </tr>
    <tr>
      <td style="padding:20px;">
        <p style="margin:0 0 12px;">Olá {{ $partner->contact_name ?? $partner->name }},</p>
        <p style="margin:0 0 12px;">Seu acesso ao painel do parceiro foi liberado. Utilize as credenciais abaixo e altere a senha no primeiro acesso.</p>
        <div style="margin:12px 0; padding:12px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px;">
          <div><strong>Login:</strong> {{ $user->email }}</div>
          <div><strong>Senha temporária:</strong> {{ $plain }}</div>
          <div><strong>URL:</strong> {{ route('partner.login') }}</div>
        </div>
        <p style="margin:0 0 12px;">Após logar, acesse seu perfil para trocar a senha:</p>
        <p style="margin:0 0 16px;"><a href="{{ $profileUrl }}" style="display:inline-block; background:#10b981; color:#fff; padding:10px 16px; border-radius:6px; text-decoration:none;">Trocar senha</a></p>
        <p style="margin:0; font-size:12px; color:#6b7280;">Powered by QFiscal</p>
      </td>
    </tr>
  </table>
</div>


