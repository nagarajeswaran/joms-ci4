<!DOCTYPE html>
<html>
<head>
    <title><?= esc($title) ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-bottom: 20px; }
        .btn { padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin-bottom: 20px; }
        .btn:hover { background: #0056b3; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: 600; }
        tr:hover { background: #f8f9fa; }
        .actions a { margin-right: 10px; color: #007bff; text-decoration: none; }
        .actions a:hover { text-decoration: underline; }
        .status { padding: 4px 8px; border-radius: 4px; font-size: 12px; }
        .status-pending { background: #ffc107; color: #000; }
        .status-processing { background: #17a2b8; color: white; }
        .status-completed { background: #28a745; color: white; }
        .nav { margin-bottom: 20px; }
        .nav a { margin-right: 15px; color: #007bff; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav">
            <a href="/joms-ci4/public/">Home</a> |
            <a href="/joms-ci4/public/orders">Orders</a> |
            <a href="/joms-ci4/public/products">Products</a> |
            <a href="/joms-ci4/public/login">Login</a>
        </div>
        
        <h1><?= esc($title) ?></h1>
        
        <a href="/joms-ci4/public/orders/add_order" class="btn">+ Add New Order</a>
        
        <?php if (session()->getFlashdata('success')): ?>
            <div style="background: #d4edda; color: #155724; padding: 12px; margin: 10px 0; border-radius: 5px; border: 1px solid #c3e6cb;">
                <?= session()->getFlashdata('success') ?>
            </div>
        <?php endif; ?>
        
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Title</th>
                    <th>Client</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($orders) && count($orders) > 0): ?>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?= esc($order['id']) ?></td>
                            <td><?= esc($order['title'] ?? 'N/A') ?></td>
                            <td><?= esc($order['client_id'] ?? 'N/A') ?></td>
                            <td>
                                <span class="status status-<?= esc($order['status'] ?? 'pending') ?>">
                                    <?= esc(ucfirst($order['status'] ?? 'Pending')) ?>
                                </span>
                            </td>
                            <td><?= esc($order['created_at'] ?? date('Y-m-d')) ?></td>
                            <td class="actions">
                                <a href="/joms-ci4/public/orders/view/<?= $order['id'] ?>">View</a>
                                <a href="/joms-ci4/public/orders/edit/<?= $order['id'] ?>">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: #666;">
                            <p>No orders found</p>
                            <a href="/joms-ci4/public/orders/add_order" class="btn">Create Your First Order</a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <p style="margin-top: 20px; color: #666;">
            Total Orders: <strong><?= count($orders) ?></strong>
        </p>
    </div>
</body>
</html>
