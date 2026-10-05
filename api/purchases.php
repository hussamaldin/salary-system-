<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "../config.php";

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER["REQUEST_METHOD"] === "GET") {

    $result = $conn->query("
        SELECT
            purchases.id,
            purchases.product_id,
            purchases.supplier_id,
            purchases.quantity,
            purchases.price,
            purchases.total,
            purchases.created_at,
            products.name AS product_name,
            suppliers.name AS supplier_name
        FROM purchases
        LEFT JOIN products
            ON purchases.product_id = products.id
        LEFT JOIN suppliers
            ON purchases.supplier_id = suppliers.id
        ORDER BY purchases.id DESC
    ");

    if (!$result) {
        echo json_encode([
            "success" => false,
            "error" => $conn->error
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $purchases = [];

    while ($row = $result->fetch_assoc()) {
        $purchases[] = $row;
    }

    echo json_encode($purchases, JSON_UNESCAPED_UNICODE);
    exit;
}


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $data = json_decode(
        file_get_contents("php://input"),
        true
    );

    if (!$data) {
        echo json_encode([
            "success" => false,
            "error" => "لم تصل بيانات الشراء"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $product_id = intval($data["product_id"] ?? 0);
    $supplier_id = intval($data["supplier_id"] ?? 0);
    $quantity = intval($data["quantity"] ?? 0);
    $price = floatval($data["price"] ?? 0);

    if ($product_id <= 0 || $quantity <= 0 || $price <= 0) {
        echo json_encode([
            "success" => false,
            "error" => "بيانات الشراء غير صحيحة"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $total = $quantity * $price;

    $stmt = $conn->prepare("
        INSERT INTO purchases
        (product_id, supplier_id, quantity, price, total)
        VALUES (?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        echo json_encode([
            "success" => false,
            "error" => $conn->error
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt->bind_param(
        "iiidd",
        $product_id,
        $supplier_id,
        $quantity,
        $price,
        $total
    );

    if (!$stmt->execute()) {
        echo json_encode([
            "success" => false,
            "error" => $stmt->error
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $conn->prepare("
        UPDATE products
        SET quantity = quantity + ?,
            purchase_price = ?
        WHERE id = ?
    ");

    $stmt->bind_param(
        "idi",
        $quantity,
        $price,
        $product_id
    );

    if (!$stmt->execute()) {
        echo json_encode([
            "success" => false,
            "error" => "تم تسجيل الشراء لكن فشل تحديث المخزون"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        "success" => true,
        "message" => "تم تسجيل الشراء وتحديث المخزون بنجاح",
        "total" => $total
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


echo json_encode([
    "success" => false,
    "error" => "طريقة الطلب غير صحيحة"
], JSON_UNESCAPED_UNICODE);

?>