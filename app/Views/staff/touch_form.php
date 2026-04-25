<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>

<div class="card staff-card">
    <div class="card-body p-3">
        <form method="post" action="<?= base_url('staff/touch-booking/store') ?>">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Next Serial</label>
                <input type="text" class="form-control" value="<?= esc($nextSerial) ?>" readonly>
            </div>

            <div class="mb-3">
                <label class="form-label">Issue Weight (g)</label>
                <input type="number" step="0.0001" min="0.0001" name="issue_weight_g" class="form-control" value="<?= esc(old('issue_weight_g')) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Touch Shop</label>
                <select name="touch_shop_name" id="touchShopSelect" class="form-select">
                    <option value="">Select touch shop</option>
                    <?php foreach ($shopNames as $shop): ?>
                        <option value="<?= esc($shop['touch_shop_name']) ?>"><?= esc($shop['touch_shop_name']) ?></option>
                    <?php endforeach; ?>
                    <option value="__new__">Add new touch shop</option>
                </select>
                <input type="text" name="touch_shop_name_new" id="touchShopNew" class="form-control mt-2 d-none" placeholder="New touch shop name">
            </div>

            <div class="mb-3">
                <label class="form-label">Karigar</label>
                <select name="karigar_id" class="form-select">
                    <option value="">Select karigar</option>
                    <?php foreach ($karigars as $karigar): ?>
                        <option value="<?= $karigar['id'] ?>">
                            <?= esc($karigar['name']) ?><?= !empty($karigar['dept']) ? ' - ' . esc($karigar['dept']) : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Stamp</label>
                <select name="stamp_id" class="form-select">
                    <option value="">Select stamp</option>
                    <?php foreach ($stamps as $stamp): ?>
                        <option value="<?= $stamp['id'] ?>"><?= esc($stamp['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="3"><?= esc(old('notes')) ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary w-100">Save Touch Booking</button>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
const touchShopSelect = document.getElementById('touchShopSelect');
const touchShopNew = document.getElementById('touchShopNew');

touchShopSelect.addEventListener('change', function () {
    touchShopNew.classList.toggle('d-none', this.value !== '__new__');
    if (this.value === '__new__') {
        touchShopNew.focus();
    } else {
        touchShopNew.value = '';
    }
});
</script>
<?= $this->endSection() ?>