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
        
        error_log("Making request to: " . $url);
        error_log("Method: " . $method);
        if ($data) {
            error_log("Data: " . json_encode($data));
        }
        
        $headers = [
            'Content-Type: application/json',
            'apikey: ' . $this->api_key
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        error_log("Response code: " . $httpCode);
        error_log("Response: " . $response);
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
        
        return [
            'status_code' => $httpCode,
            'data' => json_decode($response, true)
        ];
    }
    
    public function createInstance($instanceName) {
        // Dados simplificados para criação da instância
        $data = [
            'instanceName' => $instanceName,
            'qrcode' => true
        ];
        
        // Adicionar webhook apenas se SITE_URL estiver definido
        if (defined('SITE_URL') && SITE_URL) {
            $data['webhook'] = SITE_URL . '/webhook/whatsapp.php';
        }
        
        error_log("Creating instance with data: " . json_encode($data));
        
        return $this->makeRequest('/instance/create', 'POST', $data);
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
}
?>