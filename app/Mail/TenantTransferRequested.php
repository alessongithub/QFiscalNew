<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\TenantBalance;
use App\Models\TenantTransferSetting;

class TenantTransferRequested extends Mailable
{
    use Queueable, SerializesModels;

    public TenantBalance $balance;
    public ?TenantTransferSetting $transferSettings;

    public function __construct(TenantBalance $balance, ?TenantTransferSetting $transferSettings)
    {
        $this->balance = $balance->load(['tenant','receivable']);
        $this->transferSettings = $transferSettings;
    }

    public function build()
    {
        return $this->subject('Solicitação de Transferência - ' . optional($this->balance->tenant)->name)
            ->view('emails.admin.tenant_transfer_requested');
    }
}


