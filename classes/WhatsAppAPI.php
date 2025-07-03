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
        return $this->makeRequest("/instance/connect/{$instanceName}");
    }
    
    public function getInstanceStatus($instanceName) {
        return $this->makeRequest("/instance/connectionState/{$instanceName}");
    }
    
    public function sendMessage($instanceName, $phone, $message) {
        // Limpar o número de telefone
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Garantir que o número tenha o código do país
        if (!str_starts_with($phone, '55')) {
            $phone = '55' . $phone;
        }
        
        $data = [
            'number' => $phone,
            'textMessage' => [
                'text' => $message
            ]
        ];
        
        return $this->makeRequest("/message/sendText/{$instanceName}", 'POST', $data);
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
}
?>