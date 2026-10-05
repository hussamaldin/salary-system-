<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "../config.php";

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER["REQUEST_METHOD"] === "GET") {

    $result = $conn->query(
        "SELECT * FROM suppliers ORDER BY id DESC"
    );

    if (!$result) {
        echo json_encode([
            "success" => false,
            "error" => $conn->error
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $suppliers = [];

    while ($row = $result->fetch_assoc()) {
        $suppliers[] = $row;
    }

    echo json_encode($suppliers, JSON_UNESCAPED_UNICODE);
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
            "error" => "لم تصل بيانات المورد"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $name = trim($data["name"] ?? "");
    $phone = trim($data["phone"] ?? "");
    $email = trim($data["email"] ?? "");
    $address = trim($data["address"] ?? "");

    if ($name === "" || $phone === "") {
        echo json_encode([
            "success" => false,
            "error" => "اسم المورد ورقم الهاتف مطلوبان"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $conn->prepare(
        "INSERT INTO suppliers
        (name, phone, email, address)
        VALUES (?, ?, ?, ?)"
    );

    if (!$stmt) {
        echo json_encode([
            "success" => false,
            "error" => $conn->error
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt->bind_param(
        "ssss",
        $name,
        $phone,
        $email,
        $address
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
        "message" => "تمت إضافة المورد بنجاح"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


echo json_encode([
    "success" => false,
    "error" => "طريقة الطلب غير صحيحة"
], JSON_UNESCAPED_UNICODE);

?>