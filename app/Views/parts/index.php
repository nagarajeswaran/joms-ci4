<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>
<?php
$sortDir  = $sortDir ?? 'ASC';
$sortCol  = $sortCol ?? 'name';
$q        = $q ?? '';
$deptFilter = $deptFilter ?? '';
$mainFilter = $mainFilter ?? '';

function partSortUrl($col, $cur, $dir, $q, $dept, $main) {
    $newDir = ($cur === $col && $dir === 'ASC') ? 'DESC' : 'ASC';
    return base_url('parts') . '?' . http_build_query(['q' => $q, 'dept' => $dept, 'main' => $main, 'sort' => $col, 'dir' => $newDir]);
}
function partSortIcon($col, $cur, $dir) {
    if ($cur !== $col) return '<i class="bi bi-arrow-down-up text-muted" style="font-size:10px;"></i>';
    return $dir === 'ASC' ? '<i class="bi bi-sort-up"></i>' : '<i class="bi bi-sort-down"></i>';
}
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="mb-0"><?= count($items) ?> Parts</h6>
    <a href="<?= base_url('parts/create') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> Add New</a>
</div>

<form method="get" action="<?= base_url('parts') ?>" class="card card-body py-2 mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <input type="text" name="q" value="<?= esc($q) ?>" class="form-control form-control-sm" placeholder="Search name / Tamil name...">
        </div>
        <div class="col-md-3">
            <select name="dept" class="form-select form-select-sm">
                <option value="">All Departments</option>
                <?php foreach ($departments as $d): ?>
                <option value="<?= $d['id'] ?>" <?= $deptFilter == $d['id'] ? 'selected' : '' ?>><?= esc($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="main" class="form-select form-select-sm">
                <option value="">Main Part: All</option>
                <option value="1" <?= $mainFilter === '1' ? 'selected' : '' ?>>Main Part: Yes</option>
                <option value="0" <?= $mainFilter === '0' ? 'selected' : '' ?>>Main Part: No</option>
            </select>
        </div>
        <input type="hidden" name="sort" value="<?= esc($sortCol) ?>">
        <input type="hidden" name="dir" value="<?= esc($sortDir) ?>">
        <div class="col-md-auto">
            <button type="submit" class="btn btn-secondary btn-sm"><i class="bi bi-search"></i> Filter</button>
            <a href="<?= base_url('parts') ?>" class="btn btn-outline-secondary btn-sm ms-1">Reset</a>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0" style="font-size:13px;">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th><a href="<?= partSortUrl('name', $sortCol, $sortDir, $q, $deptFilter, $mainFilter) ?>" class="text-white text-decoration-none">Name <?= partSortIcon('name', $sortCol, $sortDir) ?></a></th>
                    <th>Tamil Name</th>
                    <th><a href="<?= partSortUrl('weight', $sortCol, $sortDir, $q, $deptFilter, $mainFilter) ?>" class="text-white text-decoration-none">Weight/Pc <?= partSortIcon('weight', $sortCol, $sortDir) ?></a></th>
                    <th>Pcs/Inch</th>
                    <th><a href="<?= partSortUrl('is_main_part', $sortCol, $sortDir, $q, $deptFilter, $mainFilter) ?>" class="text-white text-decoration-none">Main Part? <?= partSortIcon('is_main_part', $sortCol, $sortDir) ?></a></th>
                    <th><a href="<?= partSortUrl('department_name', $sortCol, $sortDir, $q, $deptFilter, $mainFilter) ?>" class="text-white text-decoration-none">Department <?= partSortIcon('department_name', $sortCol, $sortDir) ?></a></th>
                    <th>Default Podi</th>
                    <th><a href="<?= partSortUrl('gatti', $sortCol, $sortDir, $q, $deptFilter, $mainFilter) ?>" class="text-white text-decoration-none">Gatti/Kg <?= partSortIcon('gatti', $sortCol, $sortDir) ?></a></th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $i => $item): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= esc($item['name']) ?></td>
                    <td><?= esc($item['tamil_name'] ?? '') ?></td>
                    <td><?= esc($item['weight'] ?? '') ?></td>
                    <td><?= esc($item['pcs'] ?? '') ?></td>
                    <td><?= ($item['is_main_part'] ?? 0) ? '<span class="badge bg-success">Yes</span>' : 'No' ?></td>
                    <td><?= esc($item['department_name'] ?? '') ?></td>
                    <td><?= esc($item['podi_name'] ?? '') ?></td>
                    <td><?= esc($item['gatti'] ?? '') ?></td>
                    <td>
                        <a href="<?= base_url('parts/edit/' . $item['id']) ?>" class="btn btn-warning btn-sm"><i class="bi bi-pencil"></i></a>
                        <a href="<?= base_url('parts/delete/' . $item['id']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete?')"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($items)): ?><tr><td colspan="10" class="text-center text-muted py-4">No parts found</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $this->endSection() ?>
