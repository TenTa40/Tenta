<?php
// پنل مدیریت ساده ربات‌های هایرایز
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

class SimpleBotManager {
    private $db_file = 'bots.json';
    private $master_key = 'hr_master_key_2024_tenta';
    
    public function __construct() {
        $this->initDatabase();
    }
    
    private function initDatabase() {
        if (!file_exists($this->db_file)) {
            file_put_contents($this->db_file, json_encode(['bots' => []], JSON_PRETTY_PRINT));
        }
    }
    
    private function loadBots() {
        $data = json_decode(file_get_contents($this->db_file), true);
        return $data['bots'] ?? [];
    }
    
    private function saveBots($bots) {
        $data = ['bots' => $bots, 'updated' => date('Y-m-d H:i:s')];
        file_put_contents($this->db_file, json_encode($data, JSON_PRETTY_PRINT));
    }
    
    public function handleRequest() {
        $action = $_GET['action'] ?? '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'register_bot') {
            return $this->registerBot();
        } elseif ($action === 'get_bots') {
            return $this->getBots();
        } else {
            return $this->showInfo();
        }
    }
    
    private function showInfo() {
        $bots = $this->loadBots();
        echo json_encode([
            'status' => 'active',
            'total_bots' => count($bots),
            'message' => 'پنل مدیریت ربات‌های هایرایز'
        ]);
    }
    
    private function registerBot() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || $input['key'] !== $this->master_key) {
            http_response_code(401);
            echo json_encode(['error' => 'کلید نامعتبر']);
            return;
        }
        
        $bots = $this->loadBots();
        $room_id = $input['room_id'];
        $bot_id = $input['bot_id'];
        
        // بررسی تکراری نبودن
        foreach ($bots as $bot) {
            if ($bot['room_id'] === $room_id || $bot['bot_id'] === $bot_id) {
                echo json_encode(['error' => 'ربات یا اتاق تکراری است']);
                return;
            }
        }
        
        // ثبت ربات جدید
        $new_bot = [
            'id' => uniqid(),
            'bot_id' => $bot_id,
            'name' => $input['name'] ?? 'ربات سخنگو',
            'room_id' => $room_id,
            'token' => $input['token'] ?? '',
            'owners' => $input['owners'] ?? [],
            'registered_at' => date('Y-m-d H:i:s')
        ];
        
        $bots[] = $new_bot;
        $this->saveBots($bots);
        
        echo json_encode([
            'message' => 'ربات با موفقیت ثبت شد',
            'bot_id' => $bot_id,
            'total_bots' => count($bots)
        ]);
    }
    
    private function getBots() {
        $bots = $this->loadBots();
        echo json_encode([
            'bots' => $bots,
            'total' => count($bots)
        ]);
    }
}

// اجرای سیستم
$manager = new SimpleBotManager();
$manager->handleRequest();
?>
