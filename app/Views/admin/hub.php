<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
    .zone-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        cursor: pointer;
        text-decoration: none;
        color: inherit;
        display: block;
    }
    .zone-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        color: inherit;
    }
    .zone-card .card-body {
        padding: 28px 24px;
        text-align: center;
    }
    .zone-card .zone-icon {
        font-size: 40px;
        margin-bottom: 14px;
        display: block;
    }
    .zone-card .zone-title {
        font-size: 17px;
        font-weight: 700;
        margin-bottom: 8px;
    }
    .zone-card .zone-desc {
        font-size: 13px;
        color: #6c757d;
        margin: 0;
    }
    .zone-card-manufacturing { border-top: 4px solid #e67e22; }
    .zone-card-manufacturing .zone-icon { color: #e67e22; }
    .zone-card-product-stock { border-top: 4px solid #27ae60; }
    .zone-card-product-stock .zone-icon { color: #27ae60; }
    .zone-card-masters { border-top: 4px solid #8e44ad; }
    .zone-card-masters .zone-icon { color: #8e44ad; }
    .zone-card-all { border-top: 4px solid #2980b9; }
    .zone-card-all .zone-icon { color: #2980b9; }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="text-center mb-4">
        <h4 class="fw-bold">Welcome to JOMS</h4>
        <p class="text-muted">Select a module zone to get started</p>
    </div>

    <div class="row g-4 justify-content-center" style="max-width: 900px; margin: 0 auto;">
        <?php foreach ($zones as $key => $zone): ?>
            <div class="col-sm-6 col-lg-3">
                <a href="<?= base_url('admin/zone/' . $key) ?>" class="zone-card card zone-card-<?= esc($key) ?>" data-turbo="false">
                    <div class="card-body">
                        <i class="bi <?= esc($zone['icon']) ?> zone-icon"></i>
                        <div class="zone-title"><?= esc($zone['label']) ?></div>
                        <p class="zone-desc"><?= esc($zone['desc']) ?></p>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?= $this->endSection() ?>
