<?php

class Auth {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function register($username, $fullname, $password, $email) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $this->db->prepare("INSERT INTO users (username, password, name, email) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$username, $hashed_password, $fullname, $email]);
    }
    
    public function login($username, $password) {
        $stmt = $this->db->prepare("SELECT id, password, is_admin FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            return [
                'id' => $user['id'],
                'is_admin' => $user['is_admin']
            ];
        }
        
        return false;
    }
}

?>