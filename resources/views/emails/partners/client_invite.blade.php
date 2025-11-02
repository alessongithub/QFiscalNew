<div style="font-family: Arial, sans-serif; background:#f6f8fb; padding:24px; color:#111827;">
  <table width="100%" cellpadding="0" cellspacing="0" style="max-width:700px; margin:0 auto; background:#ffffff; border:1px solid #e5e7eb; border-radius:8px; overflow:hidden;">
    <tr>
      <td style="padding:16px 20px; background:#1f2937; color:#ffffff;">
        <strong style="font-size:18px;">QFiscal</strong>
        <div style="font-size:12px; opacity:.9;">Convite de Cadastro - {{ $partner->name }}</div>
      </td>
    </tr>
    <tr>
      <td style="padding:20px;">
        <h2 style="margin:0 0 10px; font-size:18px;">Olá {{ $request->client_name }},</h2>
        <p style="margin:0 0 12px; font-size:14px; line-height:1.6;">{{ $message }}</p>
        <p style="margin:0 0 16px;">
          <a href="{{ $registerUrl }}" style="display:inline-block; background:#10b981; color:#ffffff; padding:10px 16px; border-radius:6px; text-decoration:none;">Cadastrar Agora</a>
        </p>
        <p style="margin:0 0 12px; font-size:12px; color:#6b7280;">Se o botão não funcionar, copie e cole este link no navegador:<br>{{ $registerUrl }}</p>
        <p style="margin:0 0 12px; font-size:12px; color:#6b7280;">Convite enviado por: <strong>{{ $partner->name }}</strong></p>
      </td>
    </tr>
  </table>
  <div style="text-align:center; font-size:12px; color:#6b7280; margin-top:8px;">Powered by QFiscal</div>
</div>

