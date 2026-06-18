<?php
// Bật hiển thị lỗi (chỉ dùng cho môi trường dev)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Cấu hình Header để Client biết đây là API trả về JSON
header('Content-Type: application/json; charset=utf-8');
// Cho phép CORS (Cross-Origin Resource Sharing) nếu sau này gọi từ domain khác
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../vendor/autoload.php';

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
// Chỉ chấp nhận request dạng POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["status" => "error", "message" => "Chỉ chấp nhận phương thức POST."]);
    exit;
}

// 1. Đọc dữ liệu JSON từ request body
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

// Kiểm tra JSON có hợp lệ không
if (!$input) {
    http_response_code(400); // Bad Request
    echo json_encode(["status" => "error", "message" => "Dữ liệu JSON không hợp lệ."]);
    exit;
}

$originalUrl = $input['original_url'] ?? '';
$customAlias = $input['custom_alias'] ?? null;
$expiresInDays = $input['expires_in_days'] ?? null;

// 2. Validate URL gốc
if (empty($originalUrl) || !filter_var($originalUrl, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "URL gốc không hợp lệ. Vui lòng nhập đúng định dạng http/https."]);
    exit;
}

// Hàm hỗ trợ tạo chuỗi ngẫu nhiên (Base62: chữ hoa, chữ thường, số)
function generateShortCode($length = 6) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $index = rand(0, strlen($characters) - 1);
        $randomString .= $characters[$index];
    }
    return $randomString;
}

try {
    $shortCode = null;

    // 3. Xử lý Custom Alias (nếu người dùng có nhập)
    if (!empty($customAlias)) {
        // Sửa :alias thành :alias1 và :alias2
        $stmt = $pdo->prepare("SELECT id FROM urls WHERE custom_alias = :alias1 OR short_code = :alias2 LIMIT 1");
        $stmt->execute([
            'alias1' => $customAlias,
            'alias2' => $customAlias
        ]);
        if ($stmt->fetch()) {
            http_response_code(409); // Conflict
            echo json_encode(["status" => "error", "message" => "Alias '$customAlias' đã được sử dụng. Vui lòng chọn tên khác."]);
            exit;
        }
        $customAlias = trim($customAlias);
    } else {
        // 4. Nếu không có Alias, tạo mã ngẫu nhiên không trùng lặp
        $isUnique = false;
        while (!$isUnique) {
            $shortCode = generateShortCode();
            $stmt = $pdo->prepare("SELECT id FROM urls WHERE short_code = :code1 OR custom_alias = :code2 LIMIT 1");
            $stmt->execute([
                'code1' => $shortCode,
                'code2' => $shortCode
            ]);
            if (!$stmt->fetch()) {
                $isUnique = true; // Tìm được mã chưa ai dùng
            }
        }
    }

    // 5. Xử lý thời gian hết hạn (Expiration Date)
    $expiresAt = null;
    if (!empty($expiresInDays) && is_numeric($expiresInDays)) {
        $expiresAt = date('Y-m-d H:i:s', strtotime("+$expiresInDays days"));
    }

    // --- BẮT ĐẦU PHẦN TẠO QR CODE ---
    $finalCode = $customAlias ? $customAlias : $shortCode;
    $shortUrl = "http://nanolink.local/" . $finalCode;

    // Tạo QR Code từ link rút gọn
    $qrResult = Builder::create()
        ->writer(new PngWriter())
        ->writerOptions([])
        ->data($shortUrl)
        ->encoding(new Encoding('UTF-8'))
        ->errorCorrectionLevel(ErrorCorrectionLevel::High)
        ->size(300)
        ->margin(10)
        ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
        ->build();

    // Lấy chuỗi định dạng Base64 (VD: data:image/png;base64,iVBORw0K...)
    $qrCodeBase64 = $qrResult->getDataUri();
    // --- KẾT THÚC PHẦN TẠO QR CODE ---

    // 6. Lưu dữ liệu vào Database (Cập nhật thêm cột qr_code_base64)
    $insertStmt = $pdo->prepare("
        INSERT INTO urls (original_url, short_code, custom_alias, qr_code_base64, expires_at) 
        VALUES (:original_url, :short_code, :custom_alias, :qr_code_base64, :expires_at)
    ");
    
    $insertStmt->execute([
        'original_url'   => $originalUrl,
        'short_code'     => $shortCode,
        'custom_alias'   => $customAlias,
        'qr_code_base64' => $qrCodeBase64, // Lưu chuỗi này vào DB
        'expires_at'     => $expiresAt
    ]);

    $lastInsertId = $pdo->lastInsertId();

    // 7. Chuẩn bị dữ liệu trả về (Thêm trường qr_code)
    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => "Tạo link rút gọn thành công!",
        "data" => [
            "id" => $lastInsertId,
            "original_url" => $originalUrl,
            "short_url" => $shortUrl,
            "code" => $finalCode,
            "expires_at" => $expiresAt,
            "qr_code" => $qrCodeBase64 // Trả thẳng Base64 về cho Client
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(["status" => "error", "message" => "Lỗi Database: " . $e->getMessage()]);
}