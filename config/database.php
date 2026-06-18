<?php
// Cấu hình thông tin kết nối CSDL (Mặc định của XAMPP)
$host     = '127.0.0.1'; // Sử dụng IP thay vì 'localhost' để tăng tốc độ kết nối trên một số hệ điều hành
$db       = 'nanolink_db';
$user     = 'root';
$pass     = '';          // Mặc định của XAMPP để trống
$charset  = 'utf8mb4';   // Đảm bảo đồng bộ bảng mã hiển thị tiếng Việt và ký tự đặc biệt

// Data Source Name - Chuỗi cấu hình kết nối của PDO
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