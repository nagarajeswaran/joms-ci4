<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'JOMS CI4' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .panel { background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .panel-heading { background: #4a4a4a; color: white; padding: 15px; border-radius: 8px 8px 0 0; }
        .panel-title { margin: 0; font-size: 18px; }
        .panel-body { padding: 20px; }
        .navbar { background: #333; padding: 10px 20px; margin-bottom: 20px; }
        .navbar a { color: white; text-decoration: none; margin-right: 20px; }
        .navbar a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="<?= base_url('products') ?>">Products</a>
        <a href="<?= base_url('orders') ?>">Orders</a>
        <a href="<?= base_url('parts') ?>">Parts</a>
        <a href="<?= base_url() ?>">Home</a>
    </div>
    <div class="container-fluid">
        <?= $this->renderSection('content') ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
