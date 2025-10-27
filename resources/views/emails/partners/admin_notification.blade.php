<div style="font-family: Arial, sans-serif; background:#f6f8fb; padding:24px; color:#111827;">
  <table width="100%" cellpadding="0" cellspacing="0" style="max-width:700px; margin:0 auto; background:#ffffff; border:1px solid #e5e7eb; border-radius:8px; overflow:hidden;">
    <tr>
      <td style="padding:16px 20px; background:#111827; color:#ffffff;">
        <strong style="font-size:18px;">Nova inscrição de parceiro</strong>
      </td>
    </tr>
    <tr>
      <td style="padding:20px;">
        <div style="margin-bottom:8px;"><strong>Contabilidade:</strong> {{ $partner->name }}</div>
        <div style="margin-bottom:8px;"><strong>Slug:</strong> {{ $partner->slug }}</div>
        <div style="margin-bottom:8px;"><strong>CNPJ:</strong> {{ $partner->cnpj }}</div>
        @if(!empty($partner->crc))<div style="margin-bottom:8px;"><strong>CRC:</strong> {{ $partner->crc }}</div>@endif
        <div style="margin-bottom:8px;"><strong>Responsável:</strong> {{ $partner->contact_name }}</div>
        <div style="margin-bottom:8px;"><strong>E-mail:</strong> {{ $partner->contact_email }}</div>
        @if(!empty($partner->contact_phone))<div style="margin-bottom:8px;"><strong>Telefone:</strong> {{ $partner->contact_phone }}</div>@endif
        <div style="margin-top:12px; font-size:12px; color:#6b7280;">Recebido em {{ optional($partner->applied_at)->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i') }}</div>
      </td>
    </tr>
  </table>
</div>


