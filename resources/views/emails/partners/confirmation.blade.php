<div style="font-family: Arial, sans-serif; background:#f6f8fb; padding:24px; color:#111827;">
  <table width="100%" cellpadding="0" cellspacing="0" style="max-width:700px; margin:0 auto; background:#ffffff; border:1px solid #e5e7eb; border-radius:8px; overflow:hidden;">
    <tr>
      <td style="padding:16px 20px; background:#065f46; color:#ffffff;">
        <strong style="font-size:18px;">QFiscal</strong>
        <div style="font-size:12px; opacity:.9;">Programa de Parceria</div>
      </td>
    </tr>
    <tr>
      <td style="padding:20px;">
        <h2 style="margin:0 0 10px; font-size:18px;">Olá {{ $partner->contact_name ?? $partner->name }},</h2>
        <p style="margin:0 0 12px; font-size:14px; line-height:1.6;">Recebemos sua inscrição no Programa de Parceria QFiscal.</p>
        <p style="margin:0 0 12px; font-size:14px; line-height:1.6;">Nossa equipe fará a validação do CNPJ (e CRC, quando aplicável) e retornará em breve para continuidade do processo.</p>
        <div style="margin-top:14px; padding:12px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px;">
          <div style="font-weight:600; margin-bottom:6px;">Dados enviados</div>
          <div><strong>Contabilidade:</strong> {{ $partner->name }}</div>
          <div><strong>Slug desejado:</strong> {{ $partner->slug }}</div>
          <div><strong>CNPJ:</strong> {{ $partner->cnpj }}</div>
          @if(!empty($partner->crc))<div><strong>CRC:</strong> {{ $partner->crc }}</div>@endif
          <div><strong>Responsável:</strong> {{ $partner->contact_name }}</div>
          <div><strong>E-mail:</strong> {{ $partner->contact_email }}</div>
          @if(!empty($partner->contact_phone))<div><strong>Telefone:</strong> {{ $partner->contact_phone }}</div>@endif
        </div>
        <p style="margin-top:16px; font-size:12px; color:#6b7280;">Atenciosamente,<br>Equipe QFiscal</p>
      </td>
    </tr>
  </table>
</div>


