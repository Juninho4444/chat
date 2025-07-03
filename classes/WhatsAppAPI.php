<?php
require_once 'config/config.php';

class WhatsAppAPI {
    private $api_url;
    private $api_key;
    
    public function __construct() {
        $this->api_url = EVOLUTION_API_URL;
        $this->api_key = EVOLUTION_API_KEY;
    }
    
    private function makeRequest($endpoint, $method = 'GET', $data = null) {
        $url = $this->api_url . $endpoint;
        
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
        $data = [
            'instanceName' => $instanceName,
            'token' => $this->api_key,
            'qrcode' => true,
            'webhook' => SITE_URL . '/webhook/whatsapp.php'
        ];
        
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
}
?>