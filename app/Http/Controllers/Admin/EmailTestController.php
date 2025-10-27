<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmtpConfig;
use Illuminate\Http\Request;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class EmailTestController extends Controller
{
    public function index()
    {
        $smtpConfig = SmtpConfig::where('is_active', true)->first();
        $templates = $this->getTemplates();
        return view('admin.email-test', compact('smtpConfig', 'templates'));
    }

    public function send(Request $request)
    {
        $validated = $request->validate([
            'to' => 'required|email',
            'subject' => 'required|string|max:255',
            'body' => 'nullable|string',
            'template' => 'nullable|string',
            // SMTP overrides (opcional)
            'smtp.host' => 'nullable|string',
            'smtp.port' => 'nullable|integer',
            'smtp.username' => 'nullable|string',
            'smtp.password' => 'nullable|string',
            'smtp.encryption' => 'nullable|in:tls,ssl',
            'smtp.from_address' => 'nullable|email',
            'smtp.from_name' => 'nullable|string',
        ]);

        $body = (string)($validated['body'] ?? '');
        $templateKey = $validated['template'] ?? '';
        if ($templateKey && empty($body)) {
            $templates = $this->getTemplates();
            if (isset($templates[$templateKey])) {
                $body = $templates[$templateKey]['html'];
                if (empty($validated['subject'])) {
                    $validated['subject'] = $templates[$templateKey]['subject'];
                }
            }
        }

        // Carregar SMTP ativo do banco
        $active = SmtpConfig::where('is_active', true)->first();
        // Se não houver configuração ativa, tentar montar pelos envs
        if (!$active) {
            $active = new SmtpConfig([
                'host' => env('MAIL_HOST', '127.0.0.1'),
                'port' => (int) env('MAIL_PORT', 2525),
                'username' => env('MAIL_USERNAME'),
                'password' => env('MAIL_PASSWORD'),
                'encryption' => env('MAIL_ENCRYPTION', 'tls'),
                'from_address' => env('MAIL_FROM_ADDRESS'),
                'from_name' => env('MAIL_FROM_NAME', config('app.name')),
                'is_active' => false,
            ]);
        }

        // Permitir override via formulário sem persistir, mas se estiver vazio usa banco/env
        $fallbackHost = (string) ($active->host ?? env('MAIL_HOST', '127.0.0.1'));
        $fallbackPort = (int) ($active->port ?? (int) env('MAIL_PORT', 2525));
        $fallbackUser = (string) ($active->username ?? env('MAIL_USERNAME'));
        $fallbackPass = (string) ($active->password ?? env('MAIL_PASSWORD'));
        $fallbackEnc  = strtolower((string) ($active->encryption ?? (env('MAIL_ENCRYPTION') ?: 'tls')));
        $fallbackFrom = (string) ($active->from_address ?? env('MAIL_FROM_ADDRESS'));
        $fallbackName = (string) ($active->from_name ?? (env('MAIL_FROM_NAME') ?: config('app.name')));

        $host = trim((string) $request->input('smtp.host', ''));
        if ($host === '') { $host = $fallbackHost; }
        // Normaliza host:porta
        if (preg_match('/^\s*\[?([^\]\:]+)\]?:(\d+)\s*$/', $host, $m)) {
            $host = $m[1];
            $fallbackPort = (int) $m[2];
        }
        $portRaw = trim((string) $request->input('smtp.port', ''));
        $port = ($portRaw !== '' && is_numeric($portRaw)) ? (int) $portRaw : (int) $fallbackPort;

        $username = trim((string) $request->input('smtp.username', ''));
        if ($username === '') { $username = $fallbackUser; }

        $password = trim((string) $request->input('smtp.password', ''));
        if ($password === '') { $password = $fallbackPass; }

        $encryption = strtolower(trim((string) $request->input('smtp.encryption', '')));
        if ($encryption === '') { $encryption = $fallbackEnc; }

        $fromAddress = trim((string) $request->input('smtp.from_address', ''));
        if ($fromAddress === '') { $fromAddress = $fallbackFrom; }

        $fromName = trim((string) $request->input('smtp.from_name', ''));
        if ($fromName === '') { $fromName = $fallbackName; }

        $mailer = new PHPMailer(true);
        try {
            // Debug opcional: ?debug=1
            $debug = '';
            if ($request->boolean('debug')) {
                $mailer->SMTPDebug = 2;
                $mailer->Debugoutput = function ($str, $level) use (&$debug) {
                    $debug .= "[{$level}] " . trim((string)$str) . "\n";
                };
            }
            self::configureMailer($mailer, $host, $port, $username, $password, $encryption, $fromAddress, $fromName);
            $mailer->addAddress($validated['to']);
            $mailer->isHTML(true);
            $mailer->Subject = $validated['subject'];
            $mailer->Body = $body !== '' ? $body : 'Teste de email.';
            $mailer->AltBody = strip_tags($mailer->Body);
            $mailer->send();
            return back()->with('success', 'E-mail enviado com sucesso para ' . $validated['to']);
        } catch (PHPMailerException $e) {
            $message = $e->getMessage();
            // Fallback automático: troca porta/cripto e tenta novamente
            if (stripos($message, 'Could not connect to SMTP host') !== false || stripos($message, 'Failed to connect') !== false) {
                try {
                    $altEnc = ($encryption === 'ssl') ? 'tls' : 'ssl';
                    $altPort = ($altEnc === 'ssl') ? 465 : 587;
                    $mailer = new PHPMailer(true);
                    self::configureMailer($mailer, $host, $altPort, $username, $password, $altEnc, $fromAddress, $fromName);
                    $mailer->addAddress($validated['to']);
                    $mailer->isHTML(true);
                    $mailer->Subject = $validated['subject'];
                    $mailer->Body = $body !== '' ? $body : 'Teste de email.';
                    $mailer->AltBody = strip_tags($mailer->Body);
                    $mailer->send();
                    return back()->with('success', 'E-mail enviado com sucesso para ' . $validated['to'] . ' (fallback)');
                } catch (PHPMailerException $e2) {
                    $meta = " host={$host} port={$altPort} enc={$altEnc} user={$username} from={$fromAddress}";
                    $full = 'Falha ao enviar: ' . $message . ' | Tentativa alternativa: ' . $e2->getMessage() . $meta;
                    if (!empty($debug)) { $full .= "\n\nDEBUG:\n" . $debug; }
                    return back()->withErrors(['email' => $full])->withInput();
                }
            }
            $meta = " host={$host} port={$port} enc={$encryption} user={$username} from={$fromAddress}";
            $full = 'Falha ao enviar: ' . $message . $meta;
            if (!empty($debug)) { $full .= "\n\nDEBUG:\n" . $debug; }
            return back()->withErrors(['email' => $full])->withInput();
        }
    }

    public static function configureMailer(PHPMailer $mailer, string $host, int $port, string $username, string $password, string $encryption, ?string $fromAddress, ?string $fromName): void
    {
        $mailer->isSMTP();
        $mailer->Host = $host;
        $mailer->SMTPAuth = true;
        $mailer->Username = $username ?? '';
        $mailer->Password = $password ?? '';
        $mailer->Port = $port;
        if (strtolower($encryption) === 'ssl' || (int)$port === 465) {
            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mailer->Port = 465;
        } else {
            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            if ((int)$port === 0) { $mailer->Port = 587; }
        }
        $mailer->CharSet = 'UTF-8';
        $mailer->SMTPAutoTLS = true;
        $mailer->Timeout = 25;
        $mailer->AuthType = '';
        $ehloDomain = parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST) ?: 'localhost';
        $mailer->Hostname = $ehloDomain;
        $mailer->Helo = $ehloDomain;
        if (!empty($fromAddress)) {
            $mailer->setFrom($fromAddress, $fromName ?: '');
        } elseif (!empty($username)) {
            $mailer->setFrom($username, $fromName ?: '');
        }
    }

    private function getTemplates(): array
    {
        return [
            'welcome' => [
                'label' => 'Boas-vindas',
                'subject' => 'Bem-vindo(a) ao QFiscal',
                'html' => '<h2>Bem-vindo(a)!</h2><p>Estamos felizes em ter você conosco.</p>'
            ],
            'password_reset' => [
                'label' => 'Recuperação de senha',
                'subject' => 'Instruções para redefinir sua senha',
                'html' => '<p>Recebemos sua solicitação de redefinição de senha. Se não foi você, ignore este e-mail.</p>'
            ],
            'payment_notice' => [
                'label' => 'Aviso de pagamento',
                'subject' => 'Confirmação de pagamento',
                'html' => '<p>Seu pagamento foi confirmado. Obrigado por sua preferência!</p>'
            ],
            'custom' => [
                'label' => 'Mensagem livre',
                'subject' => 'Mensagem',
                'html' => ''
            ],
        ];
    }
}


