<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div></div>
    <a href="<?= base_url('users/create') ?>" class="btn btn-primary">
        <i class="bi bi-person-plus"></i> Create User
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:13px;">
                <thead>
                    <tr>
                        <th class="ps-3">Username</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Modules</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">No users found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <?php
                                $moduleList = array_filter(array_map('trim', explode(',', (string) ($u['modules'] ?? ''))));
                                $isAdmin    = $u['role'] === 'admin';
                                $isActive   = $u['status'] === 'active' || $u['status'] === '1';
                                $currentId  = session()->get('user')['id'] ?? 0;
                            ?>
                            <tr>
                                <td class="ps-3 fw-semibold"><?= esc($u['username']) ?></td>
                                <td><?= esc($u['name'] ?: '-') ?></td>
                                <td><?= esc($u['email'] ?: '-') ?></td>
                                <td>
                                    <?php if ($isAdmin): ?>
                                        <span class="badge bg-danger">Admin</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">User</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($isAdmin): ?>
                                        <span class="badge bg-dark">All Modules</span>
                                    <?php elseif (empty($moduleList)): ?>
                                        <span class="text-muted">None</span>
                                    <?php else: ?>
                                        <?php foreach ($moduleList as $mod): ?>
                                            <span class="badge" style="background:#3498db;font-size:11px;">
                                                <?= esc($modules[$mod] ?? $mod) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($isActive): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted"><?= esc(date('d M Y', strtotime($u['created_at'] ?: 'now'))) ?></td>
                                <td class="text-end pe-3">
                                    <a href="<?= base_url('users/edit/' . $u['id']) ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <?php if ((int) $u['id'] !== (int) $currentId): ?>
                                        <form method="post" action="<?= base_url('users/delete/' . $u['id']) ?>" class="d-inline"
                                              onsubmit="return confirm('Delete user <?= esc($u['username']) ?>?')">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
