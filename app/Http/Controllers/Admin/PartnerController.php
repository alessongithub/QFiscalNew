<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\User;
use App\Models\PartnerUser;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Models\SmtpConfig;
use App\Http\Controllers\Admin\EmailTestController;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use Illuminate\Database\QueryException;

class PartnerController extends Controller
{
    public function index()
    {
        $partners = Partner::orderByDesc('id')->paginate(15);
        return view('admin.partners.index', compact('partners'));
    }

    public function create()
    {
        return view('admin.partners.create');
    }

    public function store(Request $request)
    {
		$data = $request->validate([
            'name' => ['required','string','max:150'],
            'slug' => ['required','string','max:100','alpha_dash', Rule::unique('partners','slug')],
            'domain' => ['nullable','string','max:190', Rule::unique('partners','domain')],
			'cnpj' => ['nullable','string','max:20'],
			'crc' => ['nullable','string','max:50'],
			'contact_name' => ['nullable','string','max:150'],
			'contact_email' => ['nullable','email','max:190'],
			'contact_phone' => ['nullable','string','max:50'],
            'commission_percent' => ['nullable','numeric','between:0,1'],
            'theme' => ['nullable', Rule::in(['light','dark'])],
			'primary_color' => ['nullable','string','max:7'],
			'secondary_color' => ['nullable','string','max:7'],
			'logo' => ['nullable','image','max:2048'],
            'active' => ['nullable','boolean'],
        ]);
        $data['commission_percent'] = $data['commission_percent'] ?? 0.3000;
        $data['theme'] = $data['theme'] ?? 'light';
        $data['active'] = (bool)($data['active'] ?? false);
        // normalizar cores para formato #RRGGBB
        if (!empty($data['primary_color'])) {
            $c = trim($data['primary_color']);
            if ($c[0] !== '#') { $c = '#' . $c; }
            $data['primary_color'] = $c;
        }
        if (!empty($data['secondary_color'])) {
            $c = trim($data['secondary_color']);
            if ($c[0] !== '#') { $c = '#' . $c; }
            $data['secondary_color'] = $c;
        }
		// normalizar CNPJ/telefone
		if (!empty($data['cnpj'])) { $data['cnpj'] = preg_replace('/[^0-9]/','',$data['cnpj']); }
		if (!empty($data['contact_phone'])) { $data['contact_phone'] = preg_replace('/[^0-9]/','',$data['contact_phone']); }
		// upload logo
		if ($request->hasFile('logo')) {
			$path = $request->file('logo')->store('partners/logos','public');
			$data['logo_path'] = $path;
		}
		Partner::create($data);
        return redirect()->route('admin.partners.index')->with('success','Parceiro criado.');
    }

    public function edit(Partner $partner)
    {
        return view('admin.partners.edit', compact('partner'));
    }

    public function update(Request $request, Partner $partner)
    {
        $data = $request->validate([
            'name' => ['required','string','max:150'],
            'slug' => ['required','string','max:100','alpha_dash', Rule::unique('partners','slug')->ignore($partner->id)],
            'domain' => ['nullable','string','max:190', Rule::unique('partners','domain')->ignore($partner->id)],
			'cnpj' => ['nullable','string','max:20'],
			'crc' => ['nullable','string','max:50'],
			'contact_name' => ['nullable','string','max:150'],
			'contact_email' => ['nullable','email','max:190'],
			'contact_phone' => ['nullable','string','max:50'],
            'commission_percent' => ['nullable','numeric','between:0,1'],
            'theme' => ['nullable', Rule::in(['light','dark'])],
			'primary_color' => ['nullable','string','max:7'],
			'secondary_color' => ['nullable','string','max:7'],
			'logo' => ['nullable','image','max:2048'],
            'active' => ['nullable','boolean'],
        ]);
        $data['commission_percent'] = $data['commission_percent'] ?? 0.3000;
        $data['theme'] = $data['theme'] ?? 'light';
        $data['active'] = (bool)($data['active'] ?? false);
        // normalizar cores para formato #RRGGBB
        if (!empty($data['primary_color'])) {
            $c = trim($data['primary_color']);
            if ($c[0] !== '#') { $c = '#' . $c; }
            $data['primary_color'] = $c;
        }
        if (!empty($data['secondary_color'])) {
            $c = trim($data['secondary_color']);
            if ($c[0] !== '#') { $c = '#' . $c; }
            $data['secondary_color'] = $c;
        }
		// normalizar CNPJ/telefone
		if (!empty($data['cnpj'])) { $data['cnpj'] = preg_replace('/[^0-9]/','',$data['cnpj']); }
		if (!empty($data['contact_phone'])) { $data['contact_phone'] = preg_replace('/[^0-9]/','',$data['contact_phone']); }
		
		// Verificar se o email já existe em outro parceiro (exceto o atual)
		if (!empty($data['contact_email'])) {
			$existingPartner = Partner::where('contact_email', $data['contact_email'])
				->where('id', '!=', $partner->id)
				->first();
			
			if ($existingPartner) {
				return redirect()->route('admin.partners.edit', $partner)
					->with('error', 'O e-mail informado já está cadastrado para outro parceiro. Por favor, use outro e-mail.')
					->withInput();
			}
		}
		
		// upload logo
		if ($request->hasFile('logo')) {
			$path = $request->file('logo')->store('partners/logos','public');
			$data['logo_path'] = $path;
		}
		$partner->update($data);
        // Se acabou de ativar, enviar convite se não houver usuário de parceiro
        if ($partner->active) {
            // Verificar se já existe um usuário para este parceiro
            $existingPartnerUser = PartnerUser::where('partner_id', $partner->id)->first();
            
            // Se não existe usuário para o parceiro e tem email, tentar criar
            if (!$existingPartnerUser && $partner->contact_email) {
                // Verificar se já existe um usuário com esse email que pertence a este parceiro
                $userWithEmailForThisPartner = PartnerUser::where('email', $partner->contact_email)
                    ->where('partner_id', $partner->id)
                    ->first();
                
                // Se não existe usuário com esse email para este parceiro, criar
                if (!$userWithEmailForThisPartner) {
                    // Verificar se o email já existe em outro usuário de outro parceiro
                    $existingEmailUser = PartnerUser::where('email', $partner->contact_email)
                        ->where('partner_id', '!=', $partner->id)
                        ->first();
                    
                    if ($existingEmailUser) {
                        return redirect()->route('admin.partners.edit', $partner)
                            ->with('error', 'O e-mail informado já está cadastrado no sistema. Por favor, use outro e-mail.')
                            ->withInput();
                    }
                    
                    // Criar usuário
                    $token = Str::random(40);
                    try {
                        $user = PartnerUser::create([
                            'name' => $partner->contact_name ?: $partner->name,
                            'email' => $partner->contact_email,
                            'password' => bcrypt(Str::random(12)),
                            'partner_id' => $partner->id,
                            'invite_token' => $token,
                        ]);
                        
                        // E-mail de convite com link para setar senha
                        $active = SmtpConfig::where('is_active', true)->first();
                        $host = (string) ($active->host ?? env('MAIL_HOST', '127.0.0.1'));
                        $port = (int) ($active->port ?? (int) env('MAIL_PORT', 2525));
                        $username = (string) ($active->username ?? env('MAIL_USERNAME'));
                        $password = (string) ($active->password ?? env('MAIL_PASSWORD'));
                        $encryption = strtolower((string) ($active->encryption ?? (env('MAIL_ENCRYPTION') ?: 'tls')));
                        $fromAddress = (string) ($active->from_address ?? env('MAIL_FROM_ADDRESS'));
                        $fromName = (string) ($active->from_name ?? (env('MAIL_FROM_NAME') ?: config('app.name')));
                        $inviteUrl = route('partner.set_password', ['token' => $token]);
                        $html = view('emails.partners.invite', compact('partner','inviteUrl'))->render();
                        try {
                            $mailer = new PHPMailer(true);
                            EmailTestController::configureMailer($mailer, $host, $port, $username, $password, $encryption, $fromAddress, $fromName);
                            $mailer->addAddress($partner->contact_email, $partner->contact_name ?: $partner->name);
                            $mailer->isHTML(true);
                            $mailer->Subject = 'Acesso ao Painel do Parceiro - QFiscal';
                            $mailer->Body = $html;
                            $mailer->AltBody = strip_tags($html);
                            $mailer->send();
                        } catch (PHPMailerException $e) {}
                    } catch (QueryException $e) {
                        // Tratar erro de email duplicado
                        if ($e->getCode() == 23000) {
                            $errorCode = $e->errorInfo[1] ?? null;
                            if ($errorCode == 1062) {
                                // Verificar se o email já pertence ao próprio usuário deste parceiro
                                $partnerUser = PartnerUser::where('partner_id', $partner->id)
                                    ->where('email', $partner->contact_email)
                                    ->first();
                                
                                if (!$partnerUser) {
                                    return redirect()->route('admin.partners.edit', $partner)
                                        ->with('error', 'O e-mail informado já está cadastrado no sistema. Por favor, use outro e-mail.')
                                        ->withInput();
                                }
                            } else {
                                return redirect()->route('admin.partners.edit', $partner)
                                    ->with('error', 'Ocorreu um erro ao salvar. Por favor, tente novamente.')
                                    ->withInput();
                            }
                        } else {
                            return redirect()->route('admin.partners.edit', $partner)
                                ->with('error', 'Ocorreu um erro ao salvar. Por favor, tente novamente.')
                                ->withInput();
                        }
                    }
                }
            }
        }
        return redirect()->route('admin.partners.index')->with('success','Parceiro atualizado. Convite enviado se aplicável.');
    }

    public function destroy(Partner $partner)
    {
        $partner->delete();
        return redirect()->route('admin.partners.index')->with('success','Parceiro removido.');
    }

    public function show(Partner $partner)
    {
        return view('admin.partners.show', compact('partner'));
    }

    public function sendInvite(Partner $partner)
    {
        $user = PartnerUser::firstOrCreate(
            ['email' => $partner->contact_email],
            [
                'name' => $partner->contact_name ?: $partner->name,
                'password' => bcrypt(Str::random(12)),
                'partner_id' => $partner->id,
            ]
        );
        if (empty($user->partner_id)) {
            $user->partner_id = $partner->id;
        }
        $token = Str::random(40);
        $user->invite_token = $token;
        $user->save();

        $active = SmtpConfig::where('is_active', true)->first();
        $host = (string) ($active->host ?? env('MAIL_HOST', '127.0.0.1'));
        $port = (int) ($active->port ?? (int) env('MAIL_PORT', 2525));
        $username = (string) ($active->username ?? env('MAIL_USERNAME'));
        $password = (string) ($active->password ?? env('MAIL_PASSWORD'));
        $encryption = strtolower((string) ($active->encryption ?? (env('MAIL_ENCRYPTION') ?: 'tls')));
        $fromAddress = (string) ($active->from_address ?? env('MAIL_FROM_ADDRESS'));
        $fromName = (string) ($active->from_name ?? (env('MAIL_FROM_NAME') ?: config('app.name')));
        $inviteUrl = route('partner.set_password', ['token' => $token]);
        $html = view('emails.partners.invite', compact('partner','inviteUrl'))->render();
        try {
            $mailer = new PHPMailer(true);
            EmailTestController::configureMailer($mailer, $host, $port, $username, $password, $encryption, $fromAddress, $fromName);
            $mailer->addAddress($partner->contact_email, $partner->contact_name ?: $partner->name);
            $mailer->isHTML(true);
            $mailer->Subject = 'Acesso ao Painel do Parceiro - QFiscal';
            $mailer->Body = $html;
            $mailer->AltBody = strip_tags($html);
            $mailer->send();
        } catch (PHPMailerException $e) {}

        return back()->with('success','Convite enviado para ' . $partner->contact_email);
    }

    public function sendCredentials(Partner $partner)
    {
        // Gera/atualiza usuário do parceiro e define senha aleatória
        $plain = Str::random(10);
        $user = PartnerUser::firstOrCreate(
            ['email' => $partner->contact_email],
            [
                'name' => $partner->contact_name ?: $partner->name,
                'password' => bcrypt($plain),
                'partner_id' => $partner->id,
            ]
        );
        // Se já existia, força nova senha
        if ($user->exists) {
            $user->password = bcrypt($plain);
            $user->partner_id = $partner->id;
        }
        $user->invite_token = null; // limpa fluxo de convite
        $user->save();

        // Envia e-mail com credenciais
        $active = SmtpConfig::where('is_active', true)->first();
        $host = (string) ($active->host ?? env('MAIL_HOST', '127.0.0.1'));
        $port = (int) ($active->port ?? (int) env('MAIL_PORT', 2525));
        $username = (string) ($active->username ?? env('MAIL_USERNAME'));
        $password = (string) ($active->password ?? env('MAIL_PASSWORD'));
        $encryption = strtolower((string) ($active->encryption ?? (env('MAIL_ENCRYPTION') ?: 'tls')));
        $fromAddress = (string) ($active->from_address ?? env('MAIL_FROM_ADDRESS'));
        $fromName = (string) ($active->from_name ?? (env('MAIL_FROM_NAME') ?: config('app.name')));
        $loginUrl = route('partner.login');
        $profileUrl = route('partner.password');
        $html = view('emails.partners.credentials', compact('partner','user','plain','loginUrl','profileUrl'))->render();
        try {
            $mailer = new PHPMailer(true);
            EmailTestController::configureMailer($mailer, $host, $port, $username, $password, $encryption, $fromAddress, $fromName);
            $mailer->addAddress($partner->contact_email, $partner->contact_name ?: $partner->name);
            $mailer->isHTML(true);
            $mailer->Subject = 'Acesso ao Painel do Parceiro - Credenciais';
            $mailer->Body = $html;
            $mailer->AltBody = strip_tags($html);
            $mailer->send();
        } catch (PHPMailerException $e) {}

        return back()->with('success','Credenciais enviadas para ' . $partner->contact_email);
    }
}


