<?php
require_once __DIR__ . '/../config/config.php';

class WhatsAppAPI {
    private $api_url;
    private $api_key;
    
    public function __construct() {
        $this->api_url = EVOLUTION_API_URL;
        $this->api_key = EVOLUTION_API_KEY;
        
        // Log de depuração
        error_log("WhatsApp API initialized with URL: " . $this->api_url);
        error_log("API Key: " . substr($this->api_key, 0, 10) . "...");
    }
    
    private function makeRequest($endpoint, $method = 'GET', $data = null) {
        $url = $this->api_url . $endpoint;
        
        error_log("=== API REQUEST DEBUG ===");
        error_log("Making request to: " . $url);
        error_log("Method: " . $method);
        if ($data) {
            error_log("Data: " . json_encode($data, JSON_PRETTY_PRINT));
        }
        
        $headers = [
            'Content-Type: application/json',
            'apikey: ' . $this->api_key
        ];
        
        error_log("Headers: " . json_encode($headers));
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                $json_data = json_encode($data);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
                error_log("JSON payload: " . $json_data);
            }
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $info = curl_getinfo($ch);
        
        error_log("=== API RESPONSE DEBUG ===");
        error_log("Response code: " . $httpCode);
        error_log("Response: " . $response);
        error_log("Content type: " . $info['content_type']);
        error_log("Total time: " . $info['total_time']);
        
        if ($error) {
            error_log("cURL error: " . $error);
        }
        
        curl_close($ch);
        
        if ($error) {
            return [
                'status_code' => 0,
                'data' => ['error' => $error]
            ];
        }
        
        $decoded_response = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON decode error: " . json_last_error_msg());
            error_log("Raw response: " . $response);
        }
        
        return [
            'status_code' => $httpCode,
            'data' => $decoded_response
        ];
    }
    
    public function createInstance($instanceName) {
        error_log("=== CREATE INSTANCE DEBUG ===");
        error_log("Instance name: " . $instanceName);
        
        // Primeiro, vamos testar com o payload mínimo
        $data = [
            'instanceName' => $instanceName
        ];
        
        error_log("Trying minimal payload first...");
        $result = $this->makeRequest('/instance/create', 'POST', $data);
        
        // Se falhar, vamos tentar com mais parâmetros
        if ($result['status_code'] !== 200 && $result['status_code'] !== 201) {
            error_log("Minimal payload failed, trying with token...");
            
            $data = [
                'instanceName' => $instanceName,
                'token' => $this->api_key
            ];
            
            $result = $this->makeRequest('/instance/create', 'POST', $data);
            
            // Se ainda falhar, vamos tentar com todos os parâmetros
            if ($result['status_code'] !== 200 && $result['status_code'] !== 201) {
                error_log("Token payload failed, trying with all parameters...");
                
                $data = [
                    'instanceName' => $instanceName,
                    'token' => $this->api_key,
                    'qrcode' => true,
                    'integration' => 'WHATSAPP-BAILEYS'
                ];
                
                // Adicionar webhook apenas se SITE_URL estiver definido
                if (defined('SITE_URL') && SITE_URL && SITE_URL !== 'http://localhost') {
                    $data['webhook'] = SITE_URL . '/webhook/whatsapp.php';
                    error_log("Adding webhook: " . $data['webhook']);
                }
                
                $result = $this->makeRequest('/instance/create', 'POST', $data);
            }
        }
        
        error_log("Final result: " . json_encode($result));
        return $result;
    }
    
    public function getQRCode($instanceName) {
        error_log("=== GET QR CODE DEBUG ===");
        error_log("Requesting QR Code for instance: " . $instanceName);
        
        $result = $this->makeRequest("/instance/connect/{$instanceName}");
        
        error_log("QR Code API response status: " . $result['status_code']);
        error_log("QR Code API response data: " . json_encode($result['data']));
        
        // Verificar se temos o QR code na resposta
        if (isset($result['data']['base64'])) {
            error_log("QR Code base64 found, length: " . strlen($result['data']['base64']));
            error_log("QR Code base64 preview: " . substr($result['data']['base64'], 0, 50) . "...");
        } else {
            error_log("QR Code base64 NOT found in response");
            if (isset($result['data'])) {
                error_log("Available keys in response: " . implode(', ', array_keys($result['data'])));
            }
        }
        
        return $result;
    }
    
    public function getInstanceStatus($instanceName) {
        error_log("=== GET INSTANCE STATUS DEBUG ===");
        error_log("Checking status for instance: " . $instanceName);
        
        $result = $this->makeRequest("/instance/connectionState/{$instanceName}");
        
        error_log("Status API response: " . json_encode($result));
        
        return $result;
    }
    
    public function sendMessage($instanceName, $phone, $message) {
        error_log("=== SEND MESSAGE DEBUG ===");
        error_log("Instance: " . $instanceName);
        error_log("Original phone: " . $phone);
        error_log("Message: " . $message);
        
        // Limpar o número de telefone - remover todos os caracteres não numéricos
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        error_log("Cleaned phone: " . $cleanPhone);
        
        // Verificar se o número já tem código do país
        if (strlen($cleanPhone) === 11 && (str_starts_with($cleanPhone, '11') || str_starts_with($cleanPhone, '21') || str_starts_with($cleanPhone, '31'))) {
            // Número brasileiro sem código do país - adicionar 55
            $finalPhone = '55' . $cleanPhone;
        } elseif (strlen($cleanPhone) === 13 && str_starts_with($cleanPhone, '55')) {
            // Já tem código do país
            $finalPhone = $cleanPhone;
        } elseif (strlen($cleanPhone) === 10 || strlen($cleanPhone) === 11) {
            // Número brasileiro - adicionar código do país
            $finalPhone = '55' . $cleanPhone;
        } else {
            // Usar como está
            $finalPhone = $cleanPhone;
        }
        
        error_log("Final phone number: " . $finalPhone);
        
        // Tentar diferentes formatos de payload
        $payloads = [
            // Formato 1: Padrão Evolution API v2
            [
                'number' => $finalPhone,
                'textMessage' => [
                    'text' => $message
                ]
            ],
            // Formato 2: Alternativo com options
            [
                'number' => $finalPhone,
                'text' => $message
            ],
            // Formato 3: Com @c.us
            [
                'number' => $finalPhone . '@c.us',
                'textMessage' => [
                    'text' => $message
                ]
            ]
        ];
        
        foreach ($payloads as $index => $data) {
            error_log("Trying payload format " . ($index + 1) . ": " . json_encode($data));
            
            $result = $this->makeRequest("/message/sendText/{$instanceName}", 'POST', $data);
            
            error_log("Result for payload " . ($index + 1) . ": " . json_encode($result));
            
            // Se obteve sucesso (200 ou 201), retornar
            if ($result['status_code'] === 200 || $result['status_code'] === 201) {
                error_log("Message sent successfully with payload format " . ($index + 1));
                return $result;
            }
            
            // Se o erro não for relacionado ao formato, parar de tentar
            if ($result['status_code'] === 404 || $result['status_code'] === 401) {
                error_log("API error (404/401), stopping attempts");
                break;
            }
        }
        
        // Se chegou aqui, nenhum formato funcionou
        error_log("All payload formats failed");
        return $result; // Retorna o último resultado
    }
    
    public function sendBulkMessage($instanceName, $contacts, $message) {
        $results = [];
        foreach ($contacts as $contact) {
            $result = $this->sendMessage($instanceName, $contact['phone'], $message);
            $results[] = [
                'contact' => $contact,
                'result' => $result
            ];
            // Delay para evitar spam
            sleep(2);
        }
        return $results;
    }
    
    public function deleteInstance($instanceName) {
        return $this->makeRequest("/instance/delete/{$instanceName}", 'DELETE');
    }
    
    // Método para testar a conectividade da API
    public function testConnection() {
        return $this->makeRequest('/instance/fetchInstances');
    }
    
    // Método para listar instâncias existentes
    public function listInstances() {
        return $this->makeRequest('/instance/fetchInstances');
    }
    
    // Método para verificar se uma instância já existe
    public function instanceExists($instanceName) {
        $result = $this->listInstances();
        if ($result['status_code'] === 200 && isset($result['data'])) {
            foreach ($result['data'] as $instance) {
                if (isset($instance['instance']['instanceName']) && $instance['instance']['instanceName'] === $instanceName) {
                    return true;
                }
            }
        }
        return false;
    }
    
    // Método para obter informações detalhadas da instância
    public function getInstanceInfo($instanceName) {
        error_log("=== GET INSTANCE INFO DEBUG ===");
        error_log("Getting info for instance: " . $instanceName);
        
        $result = $this->makeRequest("/instance/fetchInstances/{$instanceName}");
        
        error_log("Instance info response: " . json_encode($result));
        
        return $result;
    }
    
    // Método para verificar se a instância está conectada
    public function isInstanceConnected($instanceName) {
        $status = $this->getInstanceStatus($instanceName);
        
        if ($status['status_code'] === 200) {
            $state = null;
            if (isset($status['data']['instance']['state'])) {
                $state = $status['data']['instance']['state'];
            } elseif (isset($status['data']['state'])) {
                $state = $status['data']['state'];
            }
            
            return $state === 'open';
        }
        
        return false;
    }
    
    // Método para obter informações do contato
    public function getContactInfo($instanceName, $phone) {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        
        if (!str_starts_with($cleanPhone, '55')) {
            $cleanPhone = '55' . $cleanPhone;
        }
        
        return $this->makeRequest("/chat/whatsappNumbers/{$instanceName}", 'POST', [
            'numbers' => [$cleanPhone]
        ]);
    }
}
?>