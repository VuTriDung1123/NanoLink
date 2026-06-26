<?php
// Đường dẫn trỏ ra ngoài thư mục gốc để tìm file .env
$envPath = __DIR__ . '/../.env';

// Kiểm tra xem file .env có tồn tại không
if (file_exists($envPath)) {
    $env = parse_ini_file($envPath);
} else {
    die("Lỗi: Không tìm thấy file cấu hình môi trường (.env)");
}

// Lấy thông tin từ file .env
$host     = $env['DB_HOST']; 
$db       = $env['DB_NAME'];    
$user     = $env['DB_USER'];             
$pass     = $env['DB_PASS'];           
$charset  = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// Cấu hình các thuộc tính quan trọng cho PDO để tối ưu hiệu năng và bảo mật
$options = [
    // 1. Ném ra ngoại lệ (Exception) khi gặp lỗi, giúp dễ dàng try-catch và debug
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    
    // 2. Mặc định trả kết quả truy vấn về dạng mảng kết hợp (Associative Array) ví dụ: $row['original_url']
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    
    // 3. Tắt tính năng giả lập Prepared Statements (Emulate Prepares). 
    // Điều này buộc MySQL thực thi Prepared Statements thật sự ở phía server, tối ưu bảo mật SQL Injection.
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Khởi tạo đối tượng PDO
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Dòng này dùng để kiểm tra lúc mới cài đặt, khi chạy ổn định bạn nên comment lại hoặc xóa đi
    // echo "Kết nối Database thành công!"; 
    
} catch (\PDOException $e) {
    // Trong môi trường Production (Thực tế), KHÔNG ĐƯỢC hiển thị $e->getMessage() ra màn hình vì dễ lộ cấu trúc thư mục/mật khẩu.
    // Tuy nhiên trong lúc đang phát triển (Local Development), chúng ta bật lên để dễ bắt lỗi.
    die("Lỗi kết nối Cơ sở dữ liệu: " . $e->getMessage());
}