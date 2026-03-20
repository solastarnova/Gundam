<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$scriptPath = __FILE__;
$scriptDir = dirname($scriptPath);
$documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

$relativePath = str_replace($documentRoot, '', $scriptDir);

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>建立管理員帳號 - 路徑診斷</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .container { max-width: 1000px; margin: 0 auto; }
        .success { color: green; background: #e8f5e9; padding: 15px; border-radius: 5px; }
        .error { color: red; background: #ffebee; padding: 15px; border-radius: 5px; }
        .warning { color: #856404; background: #fff3cd; padding: 15px; border-radius: 5px; }
        .info { background: #e3f2fd; padding: 15px; border-radius: 5px; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class='container'>";

echo "<h1>🔧 路徑診斷資訊</h1>";

echo "<table>";
echo "<tr><th>項目</th><th>值</th></tr>";
echo "<tr><td>__FILE__</td><td>" . __FILE__ . "</td></tr>";
echo "<tr><td>__DIR__</td><td>" . __DIR__ . "</td></tr>";
echo "<tr><td>DOCUMENT_ROOT</td><td>" . ($documentRoot ?: '未设置') . "</td></tr>";
echo "<tr><td>REQUEST_URI</td><td>" . $requestUri . "</td></tr>";
echo "<tr><td>SCRIPT_NAME</td><td>" . $scriptName . "</td></tr>";
echo "<tr><td>相對路徑</td><td>" . $relativePath . "</td></tr>";
echo "</table>";

echo "<h2>📁 檔案檢查</h2>";
echo "<table>";
echo "<tr><th>檔案</th><th>狀態</th></tr>";

$filesToCheck = [
    'bootstrap.php' => __DIR__ . '/bootstrap.php',
    'index.php' => __DIR__ . '/index.php',
    '.env' => __DIR__ . '/.env',
    '.env.example' => __DIR__ . '/.env.example',
    'app/Core/Database.php' => __DIR__ . '/app/Core/Database.php',
    'routes/web.php' => __DIR__ . '/routes/web.php'
];

foreach ($filesToCheck as $name => $path) {
    $exists = file_exists($path);
    $color = $exists ? 'green' : 'red';
    $status = $exists ? '✅ 存在' : '❌ 不存在';
    echo "<tr><td>$name</td><td style='color: $color'>$status</td></tr>";
}
echo "</table>";

echo "<h2>🚀 嘗試載入 bootstrap.php</h2>";

$bootstrapPath = __DIR__ . '/bootstrap.php';
if (file_exists($bootstrapPath)) {
    echo "<p class='success'>✅ 找到 bootstrap.php，正在載入...</p>";
    require_once $bootstrapPath;
    echo "<p class='success'>✅ bootstrap.php 載入成功</p>";
} else {
    echo "<p class='error'>❌ 找不到 bootstrap.php</p>";
    echo "<p>請確認檔案位置。可能的情況：</p>";
    echo "<ul>";
    echo "<li>當前目錄: " . __DIR__ . "</li>";
    echo "<li>期望位置: " . $bootstrapPath . "</li>";
    echo "</ul>";
    
    echo "<h3>🔍 搜尋 bootstrap.php</h3>";
    $searchDirs = [__DIR__, dirname(__DIR__), $documentRoot];
    foreach ($searchDirs as $dir) {
        if (is_dir($dir)) {
            $files = glob($dir . '/*.php');
            foreach ($files as $file) {
                if (basename($file) == 'bootstrap.php') {
                    echo "<p class='success'>✅ 在 $file 找到 bootstrap.php</p>";
                }
            }
        }
    }
}

if (file_exists($bootstrapPath)) {
    try {
        $pdo = \App\Core\Database::getConnection();
        echo "<h2>👤 建立管理員帳號</h2>";
        
        $admin_username = 'admin';
        $admin_password = 'admin123';
        
        $stmt = $pdo->query("SHOW TABLES LIKE 'admins'");
        if ($stmt->rowCount() == 0) {
            echo "<p>建立管理員資料表...</p>";
            $sql = "CREATE TABLE IF NOT EXISTS `admins` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `username` varchar(50) NOT NULL,
                `password` varchar(255) NOT NULL,
                `last_login` datetime DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id`),
                UNIQUE KEY `username` (`username`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            $pdo->exec($sql);
            echo "<p class='success'>✅ 管理員資料表建立成功</p>";
        }
        
        $hashedPassword = password_hash($admin_password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
        $stmt->execute([$admin_username]);
        
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE username = ?");
            $stmt->execute([$hashedPassword, $admin_username]);
        echo "<p class='success'>✅ 管理員密碼已更新</p>";
        } else {
            $stmt = $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
            $stmt->execute([$admin_username, $hashedPassword]);
            echo "<p class='success'>✅ 管理員帳號建立成功</p>";
        }
        
        echo "<p><strong>用戶名:</strong> $admin_username</p>";
        echo "<p><strong>密碼:</strong> $admin_password</p>";
        echo "<p><strong>登入地址:</strong> <a href='/admin/login'>/admin/login</a></p>";
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ 錯誤: " . $e->getMessage() . "</p>";
    }
}

echo "<hr>";
echo "<p class='warning'><strong>⚠️ 重要：使用後請立即刪除此檔案！</strong></p>";
echo "<p><a href='javascript:void(0)' onclick='if(confirm(\"確定刪除？\")) window.location.href=\"?delete=1\"'>點擊刪除此檔案</a></p>";

if (isset($_GET['delete']) && $_GET['delete'] == 1) {
    if (unlink(__FILE__)) {
        echo "<p class='success'>✅ 檔案已刪除</p>";
        echo "<script>setTimeout(function() { window.location.href = '/'; }, 2000);</script>";
    } else {
        echo "<p class='error'>❌ 檔案刪除失敗，請手動刪除</p>";
    }
}

echo "</div></body></html>";