<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "../config.php";

header("Content-Type: application/json; charset=UTF-8");
if ($_SERVER["REQUEST_METHOD"] === "DELETE") {

    $data = json_decode(
        file_get_contents("php://input"),
        true
    );

    $id = intval($data["id"] ?? 0);

    if ($id <= 0) {

        echo json_encode([
            "success" => false,
            "error" => "رقم العميل غير صحيح"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $stmt = $conn->prepare("
        DELETE FROM customers
        WHERE id = ?
    ");

    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {

        echo json_encode([
            "success" => true,
            "message" => "تم حذف العميل بنجاح"
        ], JSON_UNESCAPED_UNICODE);

    } else {

        echo json_encode([
            "success" => false,
            "error" => "فشل حذف العميل"
        ], JSON_UNESCAPED_UNICODE);
    }

    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "GET") {

    $result = $conn->query(
        "SELECT * FROM customers ORDER BY id DESC"
    );

    if (!$result) {
        echo json_encode([
            "success" => false,
            "error" => $conn->error
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $customers = [];

    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }

    echo json_encode($customers, JSON_UNESCAPED_UNICODE);
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
            "error" => "لم تصل بيانات العميل"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $name = trim($data["name"] ?? "");
    $phone = trim($data["phone"] ?? "");
    $email = trim($data["email"] ?? "");

    if ($name === "" || $phone === "") {
        echo json_encode([
            "success" => false,
            "error" => "اسم العميل ورقم الهاتف مطلوبان"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $conn->prepare(
        "INSERT INTO customers (name, phone, email)
         VALUES (?, ?, ?)"
    );

    if (!$stmt) {
        echo json_encode([
            "success" => false,
            "error" => $conn->error
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt->bind_param(
        "sss",
        $name,
        $phone,
        $email
    );

    if (!$stmt->execute()) {
        echo json_encode([
            "success" => false,
            "error" => $stmt->error
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        "success" => true,
        "message" => "تمت إضافة العميل بنجاح"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


echo json_encode([
    "success" => false,
    "error" => "طريقة الطلب غير صحيحة"
], JSON_UNESCAPED_UNICODE);

?>