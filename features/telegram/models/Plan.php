<?php
/*
 * ============================================================
 *  Made by Bapan | Date: 5/4/2026
 *  All credits belongs to Bapan
 *  For any kind of software development job, cheat, website
 *  or panel development — contact Bapan:
 *  Telegram: https://t.me/bapanff
 *  Official Channel: https://t.me/mocosn
 * ============================================================
 */
class Plan {
    public static function listActive() {
        $pdo = Database::connect();
        return $pdo->query("SELECT * FROM bot_plans WHERE is_active = 1 ORDER BY sort_order, price_paise")->fetchAll();
    }

    public static function listAll() {
        $pdo = Database::connect();
        return $pdo->query("SELECT * FROM bot_plans ORDER BY sort_order, price_paise")->fetchAll();
    }

    public static function getById($id) {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT * FROM bot_plans WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function create($data) {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("INSERT INTO bot_plans (name, description, duration_days, price_paise, discount_price_paise, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['name'],
            $data['description'] ?? '',
            $data['duration_days'],
            $data['price_paise'],
            $data['discount_price_paise'] ?: null,
            $data['is_active'] ?? 1,
            $data['sort_order'] ?? 0
        ]);
        return $pdo->lastInsertId();
    }

    public static function update($id, $data) {
        $pdo = Database::connect();
        $allowed = ['name','description','duration_days','price_paise','discount_price_paise','is_active','sort_order'];
        $sets = [];
        $vals = [];
        foreach ($data as $k => $v) {
            if (in_array($k, $allowed)) {
                $sets[] = "$k = ?";
                $vals[] = ($k === 'discount_price_paise' && !$v) ? null : $v;
            }
        }
        if (empty($sets)) return false;
        $vals[] = $id;
        $pdo->prepare("UPDATE bot_plans SET " . implode(', ', $sets) . " WHERE id = ?")->execute($vals);
        return true;
    }

    public static function delete($id) {
        $pdo = Database::connect();
        $pdo->prepare("DELETE FROM bot_plans WHERE id = ?")->execute([$id]);
        return true;
    }

    public static function getEffectivePrice($plan) {
        return $plan['discount_price_paise'] ?: $plan['price_paise'];
    }

    public static function getDiscountPercent($plan) {
        if (!$plan['discount_price_paise'] || $plan['discount_price_paise'] >= $plan['price_paise']) return 0;
        return round((1 - $plan['discount_price_paise'] / $plan['price_paise']) * 100);
    }
}
