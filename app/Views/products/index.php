<!DOCTYPE html>
<html>
<head>
    <title><?= esc($title) ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #333; }
        .btn { padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
        .btn:hover { background: #0056b3; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        th { background: #f4f4f4; }
        .actions a { margin-right: 10px; }
    </style>
</head>
<body>
    <h1><?= esc($title) ?></h1>
    
    <a href="/joms-ci4/public/products/add" class="btn">Add New Product</a>
    
    <?php if (session()->getFlashdata('success')): ?>
        <div style="background: #d4edda; color: #155724; padding: 10px; margin: 10px 0; border-radius: 5px;">
            <?= session()->getFlashdata('success') ?>
        </div>
    <?php endif; ?>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Product Code</th>
                <th>Product Name</th>
                <th>Type</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?= esc($product['id']) ?></td>
                        <td><?= esc($product['product_code'] ?? 'N/A') ?></td>
                        <td><?= esc($product['product_name'] ?? 'N/A') ?></td>
                        <td><?= esc($product['product_type_id'] ?? 'N/A') ?></td>
                        <td class="actions">
                            <a href="/joms-ci4/public/products/edit/<?= $product['id'] ?>">Edit</a>
                            <a href="/joms-ci4/public/products/delete/<?= $product['id'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">No products found</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <p><a href="/joms-ci4/public/">Back to Home</a> | <a href="/joms-ci4/public/orders">Orders</a></p>
</body>
</html>
