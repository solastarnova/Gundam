<?php
/**
 * create_admin.php - 管理员账号创建工具（带路径诊断）
 */

// 开启错误显示
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 获取当前脚本的绝对路径
$scriptPath = __FILE__;
$scriptDir = dirname($scriptPath);
$documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

// 计算相对路径
$relativePath = str_replace($documentRoot, '', $scriptDir);

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>创建管理员账号 - 路径诊断</title>
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

echo "<h1>🔧 路径诊断信息</h1>";

echo "<table>";
echo "<tr><th>项目</th><th>值</th></tr>";
echo "<tr><td>__FILE__</td><td>" . __FILE__ . "</td></tr>";
echo "<tr><td>__DIR__</td><td>" . __DIR__ . "</td></tr>";
echo "<tr><td>DOCUMENT_ROOT</td><td>" . ($documentRoot ?: '未设置') . "</td></tr>";
echo "<tr><td>REQUEST_URI</td><td>" . $requestUri . "</td></tr>";
echo "<tr><td>SCRIPT_NAME</td><td>" . $scriptName . "</td></tr>";
echo "<tr><td>相对路径</td><td>" . $relativePath . "</td></tr>";
echo "</table>";

// 检查关键文件是否存在
echo "<h2>📁 文件检查</h2>";
echo "<table>";
echo "<tr><th>文件</th><th>状态</th></tr>";

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

// 尝试加载 bootstrap
echo "<h2>🚀 尝试加载 bootstrap.php</h2>";

$bootstrapPath = __DIR__ . '/bootstrap.php';
if (file_exists($bootstrapPath)) {
    echo "<p class='success'>✅ 找到 bootstrap.php，正在加载...</p>";
    require_once $bootstrapPath;
    echo "<p class='success'>✅ bootstrap.php 加载成功</p>";
} else {
    echo "<p class='error'>❌ 找不到 bootstrap.php</p>";
    echo "<p>请确认文件位置。可能的情况：</p>";
    echo "<ul>";
    echo "<li>当前目录: " . __DIR__ . "</li>";
    echo "<li>期望位置: " . $bootstrapPath . "</li>";
    echo "</ul>";
    
    // 尝试查找 bootstrap.php
    echo "<h3>🔍 搜索 bootstrap.php</h3>";
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

// 如果 bootstrap 加载成功，继续创建管理员
if (file_exists($bootstrapPath) && isset($pdo)) {
    try {
        echo "<h2>👤 创建管理员账号</h2>";
        
        $admin_username = 'admin';
        $admin_password = 'admin123';
        
        // 检查管理员表
        $stmt = $pdo->query("SHOW TABLES LIKE 'admins'");
        if ($stmt->rowCount() == 0) {
            echo "<p>创建管理员表...</p>";
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
            echo "<p class='success'>✅ 管理员表创建成功</p>";
        }
        
        // 生成密码
        $hashedPassword = password_hash($admin_password, PASSWORD_DEFAULT);
        
        // 插入或更新
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
        $stmt->execute([$admin_username]);
        
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE username = ?");
            $stmt->execute([$hashedPassword, $admin_username]);
            echo "<p class='success'>✅ 管理员密码已更新</p>";
        } else {
            $stmt = $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
            $stmt->execute([$admin_username, $hashedPassword]);
            echo "<p class='success'>✅ 管理员账号创建成功</p>";
        }
        
        echo "<p><strong>用户名:</strong> $admin_username</p>";
        echo "<p><strong>密码:</strong> $admin_password</p>";
        echo "<p><strong>登录地址:</strong> <a href='/admin/login'>/admin/login</a></p>";
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ 错误: " . $e->getMessage() . "</p>";
    }
}

echo "<hr>";
echo "<p class='warning'><strong>⚠️ 重要：使用后请立即删除此文件！</strong></p>";
echo "<p><a href='javascript:void(0)' onclick='if(confirm(\"确定删除？\")) window.location.href=\"?delete=1\"'>点击删除此文件</a></p>";

// 删除自己
if (isset($_GET['delete']) && $_GET['delete'] == 1) {
    if (unlink(__FILE__)) {
        echo "<p class='success'>✅ 文件已删除</p>";
        echo "<script>setTimeout(function() { window.location.href = '/'; }, 2000);</script>";
    } else {
        echo "<p class='error'>❌ 文件删除失败，请手动删除</p>";
    }
}

echo "</div></body></html>";