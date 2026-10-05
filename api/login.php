<?php

require_once "../config.php";

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    echo json_encode([
        "success" => false,
        "error" => "طريقة الطلب غير صحيحة"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


$data = json_decode(
    file_get_contents("php://input"),
    true
);

$username = trim($data["username"] ?? "");
$password = $data["password"] ?? "";

if ($username === "" || $password === "") {

    echo json_encode([
        "success" => false,
        "error" => "أدخل اسم المستخدم وكلمة المرور"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$stmt = $conn->prepare("
    SELECT id, username, password
    FROM users
    WHERE username = ?
    LIMIT 1
");

$stmt->bind_param("s", $username);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {

    echo json_encode([
        "success" => false,
        "error" => "اسم المستخدم أو كلمة المرور غير صحيحة"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$user = $result->fetch_assoc();

if (!password_verify($password, $user["password"])) {

    echo json_encode([
        "success" => false,
        "error" => "اسم المستخدم أو كلمة المرور غير صحيحة"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

echo json_encode([
    "success" => true,
    "message" => "تم تسجيل الدخول بنجاح",
    "user" => [
        "id" => $user["id"],
        "username" => $user["username"]
    ]
], JSON_UNESCAPED_UNICODE);
?>