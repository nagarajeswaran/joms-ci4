<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login - JOMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { min-height:100vh; margin:0; display:flex; align-items:center; justify-content:center; background:linear-gradient(180deg,#0f172a,#1e293b); font-family:'Segoe UI',sans-serif; }
        .login-box { width:min(100%, 420px); background:#fff; border-radius:24px; padding:24px; box-shadow:0 16px 36px rgba(15,23,42,.25); margin:16px; }
        .login-box h1 { font-size:28px; font-weight:800; margin-bottom:6px; }
        .login-box p { color:#64748b; margin-bottom:20px; }
        .form-control, .btn { min-height:52px; border-radius:16px; }
        .help-list { margin: 0 0 18px; padding-left: 18px; color:#475569; font-size:14px; }
        .help-list li { margin-bottom: 6px; }
    </style>
</head>
<body>
    <div class="login-box">
        <h1>Staff Login</h1>
        <p>Touch booking and stock lookup access</p>
        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>
        <div class="alert alert-info">
            You can log in using either:
            <ul class="help-list mb-0 mt-2">
                <li><strong>Username</strong> you created</li>
                <li>or <strong>Name</strong> if that is what you remember</li>
            </ul>
        </div>
        <form method="post" action="">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Username or Name</label>
                <input type="text" name="username" class="form-control" value="<?= esc(old('username')) ?>" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Sign In</button>
        </form>
        <div class="mt-3 text-center">
            <a href="<?= base_url('staff/users/create') ?>">Create first staff user</a>
        </div>
    </div>
</body>
</html>