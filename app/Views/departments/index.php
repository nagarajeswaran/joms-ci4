<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?= session()->getFlashdata('success') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Department Groups Section -->
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center py-2">
        <h6 class="mb-0">Department Groups</h6>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addGroupModal">
            <i class="bi bi-plus"></i> Add Group
        </button>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0">
            <thead><tr><th>#</th><th>Group Name</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($groups as $gi => $g): ?>
                <tr>
                    <td><?= $gi + 1 ?></td>
                    <td><?= esc($g['name']) ?></td>
                    <td>
                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editGroupModal<?= $g['id'] ?>">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <a href="<?= base_url('departments/groups/delete/' . $g['id']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this group?')">
                            <i class="bi bi-trash"></i>
                        </a>
                    </td>
                </tr>
                <!-- Edit Group Modal -->
                <div class="modal fade" id="editGroupModal<?= $g['id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-sm">
                        <div class="modal-content">
                            <div class="modal-header py-2">
                                <h6 class="modal-title">Edit Group</h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form action="<?= base_url('departments/groups/update/' . $g['id']) ?>" method="post">
                                <?= csrf_field() ?>
                                <div class="modal-body">
                                    <input type="text" class="form-control" name="name" value="<?= esc($g['name']) ?>" required>
                                </div>
                                <div class="modal-footer py-2">
                                    <button type="submit" class="btn btn-success btn-sm">Save</button>
                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($groups)): ?>
                <tr><td colspan="3" class="text-center text-muted py-3">No groups found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Departments Section -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="mb-0"><?= count($items) ?> Departments</h6>
    <a href="<?= base_url('departments/create') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> Add New</a>
</div>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead><tr><th>#</th><th>Name</th><th>Tamil Name</th><th>Group</th><th>Wastage</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($items as $i => $item): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= esc($item['name']) ?></td>
                    <td><?= esc($item['tamil_name'] ?? '') ?></td>
                    <td><?= esc($item['group_name'] ?? '') ?></td>
                    <td><?= esc($item['wastage'] ?? '') ?></td>
                    <td>
                        <a href="<?= base_url('departments/edit/' . $item['id']) ?>" class="btn btn-warning btn-sm"><i class="bi bi-pencil"></i></a>
                        <a href="<?= base_url('departments/delete/' . $item['id']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete?')"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($items)): ?><tr><td colspan="6" class="text-center text-muted py-4">No departments found</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Group Modal -->
<div class="modal fade" id="addGroupModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title">Add Group</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= base_url('departments/groups/store') ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <label class="form-label">Group Name</label>
                    <input type="text" class="form-control" name="name" placeholder="Enter group name" required>
                </div>
                <div class="modal-footer py-2">
                    <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-check"></i> Save</button>
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php $this->endSection() ?>