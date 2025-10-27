<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use App\Models\Setting;

class CertificateController extends Controller
{
    /**
     * List certificates installed on Windows
     */
    public function listInstalledCertificates(): JsonResponse
    {
        try {
            // Verificar se estamos no Windows
            if (PHP_OS_FAMILY !== 'Windows') {
                return response()->json([
                    'success' => false,
                    'certificates' => [],
                    'message' => 'Esta funcionalidade só está disponível no Windows'
                ], 400);
            }

            // Listar certificados do Windows usando PowerShell
            $certificates = $this->getWindowsCertificates();
            
            return response()->json([
                'success' => true,
                'certificates' => $certificates,
                'message' => 'Certificados listados com sucesso'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'certificates' => [],
                'message' => 'Erro ao listar certificados: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get certificates from Windows certificate store using PowerShell
     */
    private function getWindowsCertificates(): array
    {
        try {
            // PowerShell script para listar certificados digitais
            $psScript = '
                $certificates = @()
                $stores = @("My", "Root", "CA", "TrustedPublisher")
                $locations = @("CurrentUser", "LocalMachine")
                
                foreach ($location in $locations) {
                  foreach ($store in $stores) {
                    try {
                        $certStore = New-Object System.Security.Cryptography.X509Certificates.X509Store($store, $location)
                        $certStore.Open("ReadOnly")
                        
                        foreach ($cert in $certStore.Certificates) {
                            # Verificar se é um certificado digital válido
                            $isDigitalCert = $false
                            
                            # Verificar se tem chave privada (A1)
                            if ($cert.HasPrivateKey) {
                                $isDigitalCert = $true
                            }
                            
                            # Verificar extensões de uso de chave para assinatura digital
                            $keyUsageExt = $cert.Extensions["2.5.29.37"]  # Enhanced Key Usage
                            if ($keyUsageExt) {
                                $keyUsageBytes = $keyUsageExt.Format($false)
                                if ($keyUsageBytes -match "1\.3\.6\.1\.5\.5\.7\.3\.1|1\.3\.6\.1\.5\.5\.7\.3\.2|1\.3\.6\.1\.5\.5\.7\.3\.4") {
                                    $isDigitalCert = $true
                                }
                            }
                            
                            # Verificar se não está expirado
                            if ($cert.NotAfter -lt (Get-Date)) {
                                continue
                            }
                            
                            if ($isDigitalCert) {
                                $certInfo = @{
                                    serial_number = $cert.SerialNumber
                                    subject_name = $cert.Subject
                                    issuer_name = $cert.Issuer
                                    valid_from = $cert.NotBefore.ToString("yyyy-MM-dd")
                                    valid_until = $cert.NotAfter.ToString("yyyy-MM-dd")
                                    certificate_type = if ($cert.HasPrivateKey) { "A1" } else { "A3" }
                                    thumbprint = $cert.Thumbprint
                                    store = $store
                                    location = $location
                                    has_private_key = $cert.HasPrivateKey
                                }
                                $certificates += $certInfo
                            }
                        }
                        $certStore.Close()
                    } catch {
                        # Ignorar erros de store específico
                        continue
                    }
                  }
                }
                
                # Remover duplicatas baseado no thumbprint
                $uniqueCerts = $certificates | Sort-Object thumbprint -Unique
                
                # Converter para JSON
                $uniqueCerts | ConvertTo-Json -Depth 3
            ';

            // Executar PowerShell
            $command = 'powershell.exe -ExecutionPolicy Bypass -Command "' . str_replace('"', '\"', $psScript) . '"';
            $output = shell_exec($command);
            
            // Log para debug
            \Log::info('PowerShell output for certificates:', [
                'command' => $command,
                'output' => $output,
                'output_length' => strlen($output ?? '')
            ]);
            
            if (empty($output)) {
                \Log::warning('PowerShell retornou output vazio para certificados');
                return [];
            }

            // Decodificar JSON
            $certificates = json_decode($output, true);
            
            if (!is_array($certificates)) {
                return [];
            }

            // Formatar dados para o frontend
            $formattedCertificates = [];
            foreach ($certificates as $cert) {
                $formattedCertificates[] = [
                    'serial_number' => $cert['serial_number'] ?? '',
                    'subject_name' => $this->extractCNFromSubject($cert['subject_name'] ?? ''),
                    'issuer_name' => $this->extractCNFromSubject($cert['issuer_name'] ?? ''),
                    'valid_from' => $cert['valid_from'] ?? '',
                    'valid_until' => $cert['valid_until'] ?? '',
                    'certificate_type' => $cert['certificate_type'] ?? 'A1',
                    'thumbprint' => $cert['thumbprint'] ?? '',
                    'store' => $cert['store'] ?? 'My'
                ];
            }

            return $formattedCertificates;
            
        } catch (\Exception $e) {
            \Log::error('Erro ao listar certificados Windows: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Extract CN (Common Name) from certificate subject
     */
    private function extractCNFromSubject(string $subject): string
    {
        if (empty($subject)) {
            return '';
        }

        // Procurar por CN= no subject
        if (preg_match('/CN=([^,]+)/i', $subject, $matches)) {
            return trim($matches[1]);
        }

        return $subject;
    }
    
    /**
     * Test PowerShell connectivity
     */
    public function testPowerShell(): JsonResponse
    {
        try {
            if (PHP_OS_FAMILY !== 'Windows') {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta funcionalidade só está disponível no Windows'
                ], 400);
            }

            // Teste simples do PowerShell
            $testCommand = 'powershell.exe -ExecutionPolicy Bypass -Command "Get-Date | ConvertTo-Json"';
            $output = shell_exec($testCommand);
            
            return response()->json([
                'success' => true,
                'output' => $output,
                'message' => 'PowerShell está funcionando'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao testar PowerShell: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get certificate details by serial number
     */
    public function getCertificateDetails(Request $request): JsonResponse
    {
        $request->validate([
            'serial_number' => 'required|string'
        ]);
        
        try {
            $delphiUrl = Setting::getGlobal('services.delphi.url', 'http://localhost:18080');
            $timeout = Setting::getGlobal('services.delphi.timeout', 30);
            
            $response = Http::timeout($timeout)
                ->get($delphiUrl . '/api/certificates/details', [
                    'serial_number' => $request->serial_number
                ]);
            
            if ($response->successful()) {
                $data = $response->json();
                return response()->json([
                    'success' => true,
                    'certificate' => $data['certificate'] ?? null,
                    'message' => 'Detalhes do certificado obtidos com sucesso'
                ]);
            }
            
            return response()->json([
                'success' => false,
                'certificate' => null,
                'message' => 'Erro ao obter detalhes do certificado'
            ], 500);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'certificate' => null,
                'message' => 'Erro ao obter detalhes: ' . $e->getMessage()
            ], 500);
        }
    }
}
