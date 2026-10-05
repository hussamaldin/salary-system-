<?php

require_once "../config.php";

header("Content-Type: application/json; charset=UTF-8");


/* عرض المستخدمين */

if ($_SERVER["REQUEST_METHOD"] === "GET") {

    $result = $conn->query("
        SELECT id, username, created_at
        FROM users
        ORDER BY id DESC
    ");

    $users = [];

    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    echo json_encode(
        $users,
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/* إضافة أو حذف */

$data = json_decode(
    file_get_contents("php://input"),
    true
);


/* حذف مستخدم */

if ($_SERVER["REQUEST_METHOD"] === "DELETE") {

    $id = intval($data["id"] ?? 0);

    if ($id <= 0) {

        echo json_encode([
            "success" => false,
            "error" => "رقم المستخدم غير صحيح"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $stmt = $conn->prepare("
        DELETE FROM users
        WHERE id = ?
    ");

    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {

        echo json_encode([
            "success" => true,
            "message" => "تم حذف المستخدم"
        ], JSON_UNESCAPED_UNICODE);

    } else {

        echo json_encode([
            "success" => false,
            "error" => "فشل حذف المستخدم"
        ], JSON_UNESCAPED_UNICODE);
    }

    exit;
}


/* إضافة مستخدم */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    echo json_encode([
        "success" => false,
        "error" => "طريقة الطلب غير صحيحة"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$username = trim($data["username"] ?? "");
$password = $data["password"] ?? "";

if ($username === "" || $password === "") {

    echo json_encode([
        "success" => false,
        "error" => "أدخل اسم المستخدم وكلمة المرور"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if (strlen($password) < 4) {

    echo json_encode([
        "success" => false,
        "error" => "كلمة المرور يجب أن تكون 4 أحرف على الأقل"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$check = $conn->prepare("
    SELECT id
    FROM users
    WHERE username = ?
    LIMIT 1
");

$check->bind_param("s", $username);
$check->execute();

$result = $check->get_result();

if ($result->num_rows > 0) {

    echo json_encode([
        "success" => false,
        "error" => "اسم المستخدم موجود بالفعل"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$hashedPassword = password_hash(
    $password,
    PASSWORD_DEFAULT
);

$stmt = $conn->prepare("
    INSERT INTO users (username, password)
    VALUES (?, ?)
");

$stmt->bind_param(
    "ss",
    $username,
    $hashedPassword
);

if ($stmt->execute()) {

    echo json_encode([
        "success" => true,
        "message" => "تم إضافة المستخدم بنجاح"
    ], JSON_UNESCAPED_UNICODE);

} else {

    echo json_encode([
        "success" => false,
        "error" => "فشل إضافة المستخدم"
    ], JSON_UNESCAPED_UNICODE);
}

?>