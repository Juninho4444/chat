<?php
require_once 'config/database.php';

class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $name;
    public $email;
    public $password;
    public $phone;
    public $plan_id;
    public $whatsapp_instance;
    public $whatsapp_connected;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET name=:name, email=:email, password=:password, phone=:phone, plan_id=:plan_id";
        
        $stmt = $this->conn->prepare($query);
        
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);
        
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":plan_id", $this->plan_id);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function login($email, $password) {
        $query = "SELECT id, name, email, password, plan_id, whatsapp_instance, whatsapp_connected 
                  FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if(password_verify($password, $row['password'])) {
                $this->id = $row['id'];
                $this->name = $row['name'];
                $this->email = $row['email'];
                $this->plan_id = $row['plan_id'];
                $this->whatsapp_instance = $row['whatsapp_instance'];
                $this->whatsapp_connected = $row['whatsapp_connected'];
                return true;
            }
        }
        return false;
    }

    public function updateWhatsAppInstance($instance_name) {
        $query = "UPDATE " . $this->table_name . " 
                  SET whatsapp_instance=:instance, whatsapp_connected=1 
                  WHERE id=:id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":instance", $instance_name);
        $stmt->bindParam(":id", $this->id);
        
        return $stmt->execute();
    }
}
?>