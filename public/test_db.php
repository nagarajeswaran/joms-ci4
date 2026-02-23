<?php
// Direct database test
$conn = new mysqli('localhost', 'root', '', 'psboffic1_psboffi1_joms');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Test</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .success { background: #d4edda; padding: 15px; border-radius: 5px; }
        .error { background: #f8d7da; padding: 15px; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #f4f4f4; }
    </style>
</head>
<body>
    <h1>Database Connection Test</h1>
    
    <?php if ($conn->connect_error): ?>
        <div class="error">
            <h3>❌ Connection Failed</h3>
            <p><?= $conn->connect_error ?></p>
        </div>
    <?php else: ?>
        <div class="success">
            <h3>✅ Connection Successful!</h3>
            <p>MySQL Version: <?= $conn->server_info ?></p>
        </div>
        
        <h2>Database Tables:</h2>
        <table>
            <tr>
                <th>Table Name</th>
                <th>Row Count</th>
            </tr>
            <?php
            $tables = $conn->query("SHOW TABLES");
            while ($row = $tables->fetch_row()):
                $tableName = $row[0];
                $count = $conn->query("SELECT COUNT(*) as cnt FROM `$tableName`")->fetch_assoc()['cnt'];
            ?>
            <tr>
                <td><?= $tableName ?></td>
                <td><?= $count ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
    <?php endif; ?>
    
    <p><a href="welcome.html">← Back</a></p>
</body>
</html>
