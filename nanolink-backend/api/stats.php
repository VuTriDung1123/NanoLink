<?php
// Bật hiển thị lỗi để dễ debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Cấu hình Header trả về JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

require_once '../config/database.php';

// Chỉ chấp nhận request dạng GET
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Chỉ chấp nhận phương thức GET."]);
    exit;
}

// Lấy mã code từ URL param (?code=abc)
$code = isset($_GET['code']) ? trim($_GET['code']) : '';

if (empty($code)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Vui lòng cung cấp mã liên kết (code)."]);
    exit;
}

try {
    // 1. Kiểm tra xem link có tồn tại không và lấy id của link đó
    $stmt = $pdo->prepare("SELECT id, original_url, short_code, custom_alias, created_at FROM urls WHERE short_code = :code1 OR custom_alias = :code2 LIMIT 1");
    $stmt->execute([
        'code1' => $code,
        'code2' => $code
    ]);
    
    $urlData = $stmt->fetch();

    if (!$urlData) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Liên kết không tồn tại."]);
        exit;
    }

    $urlId = $urlData['id'];
    $finalCode = $urlData['custom_alias'] ? $urlData['custom_alias'] : $urlData['short_code'];

    // 2. Đếm tổng số lượt click
    $countStmt = $pdo->prepare("SELECT COUNT(id) as total_clicks FROM clicks WHERE url_id = :url_id");
    $countStmt->execute(['url_id' => $urlId]);
    $totalClicks = $countStmt->fetch()['total_clicks'];

    // 3. Lấy danh sách lịch sử click (giới hạn 50 lượt gần nhất để nhẹ API)
    $historyStmt = $pdo->prepare("SELECT ip_address, user_agent, referer, clicked_at FROM clicks WHERE url_id = :url_id ORDER BY clicked_at DESC LIMIT 50");
    $historyStmt->execute(['url_id' => $urlId]);
    $clickHistory = $historyStmt->fetchAll();

    // 4. Trả về kết quả JSON
    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "data" => [
            "code" => $finalCode,
            "original_url" => $urlData['original_url'],
            "total_clicks" => (int)$totalClicks,
            "created_at" => $urlData['created_at'],
            "recent_clicks" => $clickHistory
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Lỗi Database: " . $e->getMessage()]);
}