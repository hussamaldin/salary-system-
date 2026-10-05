<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "../config.php";

header("Content-Type: application/json; charset=UTF-8");

// ==================================================
// DELETE - حذف منتج
// ==================================================

if ($_SERVER["REQUEST_METHOD"] === "DELETE") {

    $data = json_decode(
        file_get_contents("php://input"),
        true
    );

    $id = intval($data["id"] ?? 0);

    if ($id <= 0) {

        echo json_encode([
            "success" => false,
            "error" => "رقم المنتج غير صحيح"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $stmt = $conn->prepare("
        DELETE FROM products
        WHERE id = ?
    ");

    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {

        echo json_encode([
            "success" => true,
            "message" => "تم حذف المنتج بنجاح"
        ], JSON_UNESCAPED_UNICODE);

    } else {

        echo json_encode([
            "success" => false,
            "error" => "فشل حذف المنتج"
        ], JSON_UNESCAPED_UNICODE);
    }

    $stmt->close();

    exit;
}


// ==================================================
// GET - جلب المنتجات
// ==================================================

if ($_SERVER["REQUEST_METHOD"] === "GET") {

    $id = intval($_GET["id"] ?? 0);


    // -------------------------------
    // جلب منتج واحد
    // -------------------------------

    if ($id > 0) {

        $stmt = $conn->prepare(
            "SELECT * FROM products WHERE id = ?"
        );

        $stmt->bind_param("i", $id);

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

        echo json_encode(
            $product,
            JSON_UNESCAPED_UNICODE
        );

        exit;
    }


    // -------------------------------
    // جلب كل المنتجات
    // -------------------------------

    $result = $conn->query(
        "SELECT * FROM products ORDER BY id DESC"
    );

    if (!$result) {

        echo json_encode([
            "success" => false,
            "error" => $conn->error
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $products = [];

    while ($row = $result->fetch_assoc()) {

        $products[] = $row;
    }

    echo json_encode(
        $products,
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


// ==================================================
// POST - إضافة أو تعديل منتج
// ==================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $data = json_decode(
        file_get_contents("php://input"),
        true
    );


    if (!$data) {

        echo json_encode([
            "success" => false,
            "error" => "لم تصل بيانات المنتج"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }


    // ==================================================
    // إذا كان هناك ID → تعديل المنتج
    // ==================================================

    if (isset($data["id"]) && intval($data["id"]) > 0) {

        $id = intval($data["id"]);

        $name =
            $data["name"] ?? "";

        $purchase_price =
            $data["purchase_price"] ?? 0;

        $sale_price =
            $data["sale_price"] ?? 0;

        $quantity =
            $data["quantity"] ?? 0;

        $min_quantity =
            $data["min_quantity"] ?? 5;


        $stmt = $conn->prepare("
            UPDATE products
            SET
                name = ?,
                purchase_price = ?,
                sale_price = ?,
                quantity = ?,
                min_quantity = ?
            WHERE id = ?
        ");


        if (!$stmt) {

            echo json_encode([
                "success" => false,
                "error" => "فشل إنشاء استعلام التعديل",
                "details" => $conn->error
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }


        $stmt->bind_param(
            "sddiii",
            $name,
            $purchase_price,
            $sale_price,
            $quantity,
            $min_quantity,
            $id
        );


        if (!$stmt->execute()) {

            echo json_encode([
                "success" => false,
                "error" => "فشل تعديل المنتج",
                "details" => $stmt->error
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }


        echo json_encode([
            "success" => true,
            "message" => "تم تعديل المنتج بنجاح"
        ], JSON_UNESCAPED_UNICODE);

        $stmt->close();

        exit;
    }


    // ==================================================
    // لا يوجد ID → إضافة منتج جديد
    // ==================================================

    $name =
        $data["name"] ?? "";

    $purchase_price =
        $data["purchase_price"] ?? 0;

    $sale_price =
        $data["sale_price"] ?? 0;

    $quantity =
        $data["quantity"] ?? 0;

    $min_quantity =
        $data["min_quantity"] ?? 5;


    $stmt = $conn->prepare("
        INSERT INTO products
        (
            name,
            purchase_price,
            sale_price,
            quantity,
            min_quantity
        )
        VALUES (?, ?, ?, ?, ?)
    ");


    if (!$stmt) {

        echo json_encode([
            "success" => false,
            "error" => "فشل إنشاء الاستعلام",
            "details" => $conn->error
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }


    $stmt->bind_param(
        "sddii",
        $name,
        $purchase_price,
        $sale_price,
        $quantity,
        $min_quantity
    );


    if (!$stmt->execute()) {

        echo json_encode([
            "success" => false,
            "error" => "فشل إضافة المنتج",
            "details" => $stmt->error
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }


    echo json_encode([
        "success" => true,
        "message" => "تمت إضافة المنتج بنجاح"
    ], JSON_UNESCAPED_UNICODE);

    $stmt->close();

    exit;
}


// ==================================================
// أي طريقة طلب أخرى
// ==================================================

echo json_encode([
    "success" => false,
    "error" => "طريقة الطلب غير صحيحة"
], JSON_UNESCAPED_UNICODE);
?>
