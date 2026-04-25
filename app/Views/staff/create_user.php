<?= $this->extend(session()->has('user') ? 'layouts/main' : 'layouts/staff') ?>
<?= $this->section('content') ?>

<div class="card staff-card">
    <div class="card-body p-3">
        <div class="fw-bold fs-5 mb-3">Create Staff User</div>
        <?php if (!session()->get('staff_logged_in') && !session()->has('user')): ?>
            <div class="alert alert-info">
                Create the first staff account here, then sign in from
                <a href="<?= base_url('staff/login') ?>" class="alert-link">staff login</a>.
            </div>
        <?php endif; ?>
        <form method="post" action="<?= base_url('staff/users/create') ?>">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" value="<?= esc(old('username')) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" value="<?= esc(old('name')) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= esc(old('email')) ?>" placeholder="Optional">
            </div>
            <div class="mb-3">
                <label class="form-label">Role</label>
                <select name="role" class="form-select">
                    <option value="staff" <?= old('role', 'staff') === 'staff' ? 'selected' : '' ?>>Staff</option>
                    <option value="touch" <?= old('role') === 'touch' ? 'selected' : '' ?>>Touch</option>
                    <option value="stock" <?= old('role') === 'stock' ? 'selected' : '' ?>>Stock</option>
                    <option value="touch_booking" <?= old('role') === 'touch_booking' ? 'selected' : '' ?>>Touch Booking</option>
                    <option value="stock_lookup" <?= old('role') === 'stock_lookup' ? 'selected' : '' ?>>Stock Lookup</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="active" <?= old('status', 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="1" <?= old('status') === '1' ? 'selected' : '' ?>>1</option>
                    <option value="inactive" <?= old('status') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    <option value="0" <?= old('status') === '0' ? 'selected' : '' ?>>0</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary w-100">Create User</button>
        </form>
    </div>
</div>

<?= $this->endSection() ?>