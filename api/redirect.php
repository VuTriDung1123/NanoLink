<?php
// Bật hiển thị lỗi để dễ debug trong quá trình phát triển (Local)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Require file cấu hình kết nối Database bằng PDO
require_once '../config/database.php';

// Lấy mã code từ URL (do file .htaccess truyền vào qua tham số ?code=)
$code = isset($_GET['code']) ? trim($_GET['code']) : '';

// Nếu không có mã code, dừng chương trình
if (empty($code)) {
    header("HTTP/1.0 400 Bad Request");
    die("<h1>400 Bad Request</h1><p>Mã liên kết không hợp lệ.</p>");
}

try {
    // 1. Tìm kiếm link trong database
    // Dùng LIMIT 1 để tối ưu tốc độ, truy vấn sẽ dừng ngay khi tìm thấy dòng đầu tiên khớp
    $stmt = $pdo->prepare("SELECT id, original_url, expires_at FROM urls WHERE short_code = :code OR custom_alias = :code LIMIT 1");
    $stmt->execute(['code' => $code]);
    $urlData = $stmt->fetch();

    // 2. Kiểm tra sự tồn tại của liên kết
    if (!$urlData) {
        header("HTTP/1.0 404 Not Found");
        die("<h1>404 Not Found</h1><p>Liên kết này không tồn tại trên hệ thống NanoLink.</p>");
    }

    // 3. Kiểm tra hạn sử dụng (nếu cột expires_at không rỗng)
    if (!empty($urlData['expires_at'])) {
        $expiresAt = strtotime($urlData['expires_at']);
        $now = time();
        if ($now > $expiresAt) {
            header("HTTP/1.0 410 Gone");
            die("<h1>410 Gone</h1><p>Liên kết này đã hết hạn sử dụng.</p>");
        }
    }

    // 4. Ghi nhận dữ liệu thống kê (Tracking)
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $referer   = $_SERVER['HTTP_REFERER'] ?? null;

    $insertStmt = $pdo->prepare("INSERT INTO clicks (url_id, ip_address, user_agent, referer) VALUES (:url_id, :ip_address, :user_agent, :referer)");
    $insertStmt->execute([
        'url_id'     => $urlData['id'],
        'ip_address' => $ipAddress,
        'user_agent' => $userAgent,
        'referer'    => $referer
    ]);

    // 5. Thực hiện chuyển hướng (Redirect)
    // Chúng ta sử dụng mã 302 (Found) thay vì 301 (Moved Permanently).
    // Mã 302 giúp tránh việc trình duyệt tự động cache (lưu trữ) luồng chuyển hướng quá mạnh, 
    // đảm bảo những lần click sau hệ thống vẫn bắt được request để tăng biến đếm thống kê.
    header("Location: " . $urlData['original_url'], true, 302);
    exit;

} catch (PDOException $e) {
    // Bắt lỗi liên quan đến Database
    header("HTTP/1.0 500 Internal Server Error");
    die("<h1>500 Error</h1><p>Lỗi hệ thống: " . $e->getMessage() . "</p>");
}