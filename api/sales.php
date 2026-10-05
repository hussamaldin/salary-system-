<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "../config.php";

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER["REQUEST_METHOD"] === "GET") {

    $result = $conn->query("
        SELECT 
            sales.id,
            sales.product_id,
            sales.customer_id,
            sales.quantity,
            sales.price,
            sales.total,
            sales.created_at,
            products.name AS product_name
        FROM sales
        LEFT JOIN products ON sales.product_id = products.id
        ORDER BY sales.id DESC
    ");

    if (!$result) {
        echo json_encode([
            "success" => false,
            "error" => $conn->error
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $sales = [];

    while ($row = $result->fetch_assoc()) {
        $sales[] = $row;
    }

    echo json_encode($sales, JSON_UNESCAPED_UNICODE);
    exit;
}


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data) {
        echo json_encode([
            "success" => false,
            "error" => "لم تصل بيانات البيع"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $product_id = intval($data["product_id"] ?? 0);
    $customer_id = intval($data["customer_id"] ?? 0);
    $quantity = intval($data["quantity"] ?? 0);
    $price = floatval($data["price"] ?? 0);

    if ($product_id <= 0 || $quantity <= 0 || $price <= 0) {
        echo json_encode([
            "success" => false,
            "error" => "بيانات البيع غير صحيحة"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // جلب كمية المنتج الحالية
    $stmt = $conn->prepare(
        "SELECT quantity FROM products WHERE id = ?"
    );

    $stmt->bind_param("i", $product_id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode([
            "success" => false,
            "error" => "المنتج غير موجود"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $product = $result->fetch_assoc();

    if ($product["quantity"] < $quantity) {
        echo json_encode([
            "success" => false,
            "error" => "الكمية الموجودة في المخزون غير كافية"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $total = $quantity * $price;

    // إضافة عملية البيع
    $stmt = $conn->prepare(
        "INSERT INTO sales
        (product_id, customer_id, quantity, price, total)
        VALUES (?, ?, ?, ?, ?)"
    );

    $stmt->bind_param(
        "iiidd",
        $product_id,
        $customer_id,
        $quantity,
        $price,
        $total
    );

    if (!$stmt->execute()) {
        echo json_encode([
            "success" => false,
            "error" => "فشل تسجيل عملية البيع",
            "details" => $stmt->error
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // خصم الكمية من المخزون
    $stmt = $conn->prepare(
        "UPDATE products
         SET quantity = quantity - ?
         WHERE id = ?"
    );

    $stmt->bind_param("ii", $quantity, $product_id);
    $stmt->execute();

    echo json_encode([
        "success" => true,
        "message" => "تم تسجيل البيع بنجاح",
        "total" => $total
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


echo json_encode([
    "success" => false,
    "error" => "طريقة الطلب غير صحيحة"
], JSON_UNESCAPED_UNICODE);

?>