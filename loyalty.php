<?php

class LoyaltySystem {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function addPoints($user_id, $points) {
        $stmt = $this->db->prepare("UPDATE users SET loyalty_points = loyalty_points + ? WHERE id = ?");
        return $stmt->execute([$points, $user_id]);
    }
    
    public function getPoints($user_id) {
        $stmt = $this->db->prepare("SELECT loyalty_points FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return $result ? $result['loyalty_points'] : 0;
    }
    
    public function usePoints($user_id, $points) {
        $currentPoints = $this->getPoints($user_id);
        if ($currentPoints >= $points) {
            $stmt = $this->db->prepare("UPDATE users SET loyalty_points = loyalty_points - ? WHERE id = ?");
            return $stmt->execute([$points, $user_id]);
        }
        return false;
    }
    
    public function getLevel($user_id) {
        $points = $this->getPoints($user_id);
        if ($points >= 1000) return 'gold';
        if ($points >= 500) return 'silver';
        if ($points >= 100) return 'bronze';
        return 'basic';
    }
    
    public function getLevelBenefits($level) {
        $benefits = [
            'basic' => ['discount' => 0],
            'bronze' => ['discount' => 5],
            'silver' => ['discount' => 10],
            'gold' => ['discount' => 15]
        ];
        return $benefits[$level] ?? $benefits['basic'];
    }
    
    public function calculatePointsForPurchase($amount) {
        return floor($amount / 100); // 1 pont minden 100 Ft-ért
    }
    
    public function getPointsHistory($user_id, $limit = 10) {
        $stmt = $this->db->prepare("
            SELECT points, type, created_at 
            FROM loyalty_history 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$user_id, $limit]);
        return $stmt->fetchAll();
    }
    
    public function logPointsTransaction($user_id, $points, $type) {
        $stmt = $this->db->prepare("
            INSERT INTO loyalty_history (user_id, points, type, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        return $stmt->execute([$user_id, $points, $type]);
    }
    
    public function getAvailableRewards($user_id) {
        $points = $this->getPoints($user_id);
        $stmt = $this->db->prepare("
            SELECT * FROM loyalty_rewards 
            WHERE points_required <= ? 
            ORDER BY points_required DESC
        ");
        $stmt->execute([$points]);
        return $stmt->fetchAll();
    }
    
    public function redeemReward($user_id, $reward_id) {
        $stmt = $this->db->prepare("
            SELECT points_required 
            FROM loyalty_rewards 
            WHERE id = ?
        ");
        $stmt->execute([$reward_id]);
        $reward = $stmt->fetch();
        
        if ($reward && $this->usePoints($user_id, $reward['points_required'])) {
            $stmt = $this->db->prepare("
                INSERT INTO redeemed_rewards (user_id, reward_id, redeemed_at) 
                VALUES (?, ?, NOW())
            ");
            return $stmt->execute([$user_id, $reward_id]);
        }
        return false;
    }
    
    public function getRedeemedRewards($user_id) {
        $stmt = $this->db->prepare("
            SELECT r.*, rr.redeemed_at 
            FROM redeemed_rewards rr 
            JOIN loyalty_rewards r ON rr.reward_id = r.id 
            WHERE rr.user_id = ? 
            ORDER BY rr.redeemed_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }
}

?>