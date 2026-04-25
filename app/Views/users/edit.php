<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <strong>Edit User — <?= esc($user['username']) ?></strong>
            </div>
            <div class="card-body">
                <form method="post" action="<?= base_url('users/edit/' . $user['id']) ?>">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control bg-light" value="<?= esc($user['username']) ?>" readonly>
                        <div class="form-text">Username cannot be changed.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                               minlength="6" placeholder="Leave blank to keep current password">
                        <?php if (isset($errors['password'])): ?>
                            <div class="invalid-feedback"><?= esc($errors['password']) ?></div>
                        <?php endif; ?>
                        <div class="form-text">Minimum 6 characters. Leave blank to keep current password.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" value="<?= esc($user['name'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                               value="<?= esc($user['email'] ?? '') ?>">
                        <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback"><?= esc($errors['email']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Role <span class="text-danger">*</span></label>
                        <select name="role" id="roleSelect" class="form-select">
                            <option value="user"  <?= ($user['role'] ?? 'user') === 'user'  ? 'selected' : '' ?>>User (module-based access)</option>
                            <option value="admin" <?= ($user['role'] ?? 'user') === 'admin' ? 'selected' : '' ?>>Admin (full access)</option>
                        </select>
                    </div>

                    <div class="mb-3" id="module-section">
                        <label class="form-label">Module Access</label>
                        <div class="border rounded p-3">
                            <?php $checkedModules = (array) ($user['modules_array'] ?? []); ?>
                            <?php foreach ($modules as $key => $label): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="modules[]"
                                           id="mod_<?= esc($key) ?>" value="<?= esc($key) ?>"
                                           <?= in_array($key, $checkedModules, true) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="mod_<?= esc($key) ?>">
                                        <?= esc($label) ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="form-text">Select which modules this user can access.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active"   <?= ($user['status'] ?? 'active') === 'active'   ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= ($user['status'] ?? 'active') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <a href="<?= base_url('users') ?>" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    var roleSelect    = document.getElementById('roleSelect');
    var moduleSection = document.getElementById('module-section');

    function toggleModules() {
        moduleSection.style.display = roleSelect.value === 'admin' ? 'none' : '';
    }

    roleSelect.addEventListener('change', toggleModules);
    toggleModules();
})();
</script>
<?= $this->endSection() ?>
