<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div id="content" class="content">
    <div class="panel panel-inverse">
        <div class="panel-heading">
            <h4 class="panel-title">Add Product</h4>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-12">
                    <div id="form_messages"></div>
                    <form id="add_product_form" method="post" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        
                        <!-- Basic Product Info -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="control-label" for="name">Product Name</label>
                                    <input type="text" class="form-control" name="name" id="name" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="control-label" for="tamil_name">Tamil Name</label>
                                    <input type="text" class="form-control" name="tamil_name" id="tamil_name">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="control-label" for="product_type">Product Type</label>
                                    <select class="form-control" name="product_type" id="product_type" required>
                                        <option value="">Select Type</option>
                                        <?php foreach ($productTypes as $type): ?>
                                            <option value="<?= $type['id'] ?>"><?= esc($type['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="control-label" for="body">Body</label>
                                    <select class="form-control" name="body" id="body">
                                        <option value="">Select Body</option>
                                        <?php foreach ($bodies as $body): ?>
                                            <option value="<?= $body['id'] ?>"><?= esc($body['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="control-label" for="pidi">Pidi</label>
                                    <input type="text" class="form-control" name="pidi" id="pidi">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="control-label" for="main_part">Main Part</label>
                                    <select class="form-control" name="main_part" id="main_part">
                                        <option value="">Select Main Part</option>
                                        <?php foreach ($parts as $part): ?>
                                            <option value="<?= $part['id'] ?>"><?= esc($part['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label" for="product_image">Image</label>
                                    <input type="file" class="form-control" name="product_image" id="product_image">
                                </div>
                            </div>
                        </div>
                        
                        <!-- BILL OF MATERIAL Section -->
                        <div class="design_section mt-4">
                            <div class="row">
                                <div class="col-md-12 mb-2">
                                    <h4 style="background:#f0f0f0; padding:10px; border-radius:4px;">BILL OF MATERIAL</h4>
                                </div>
                            </div>
                            <div id="bill_of_material_container">
                                <div class="bill_of_material_row" data-index="0">
                                    <div class="row mb-2" style="background:#fafafa; padding:10px; border-radius:4px; margin:5px 0;">
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label class="control-label">Part Name</label>
                                                <select class="form-control" name="bom_part_name[]">
                                                    <option value="">Select Part</option>
                                                    <?php foreach ($parts as $part): ?>
                                                        <option value="<?= $part['id'] ?>"><?= esc($part['name']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-1">
                                            <div class="form-group">
                                                <label class="control-label">No. of Pcs</label>
                                                <input class="form-control" type="text" name="bom_part_pcs[]">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label class="control-label">Scale</label>
                                                <select class="form-control" name="bom_scale[]">
                                                    <option value="">Select Scale</option>
                                                    <option value="Per Inch">Per Inch</option>
                                                    <option value="Per Pair">Per Pair</option>
                                                    <option value="Per Kanni">Per Kanni</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label class="control-label">Main Group</label>
                                                <select class="form-control" name="bom_main_group[]">
                                                    <option value="All" selected>All</option>
                                                    <option value="Plain">Plain</option>
                                                    <option value="Bunch">Bunch</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label class="control-label">Variation Group</label>
                                                <select class="form-control bom_variation_group" name="bom_variation_group[0][]" multiple style="height:70px;">
                                                    <option value="Small">Small</option>
                                                    <option value="Medium">Medium</option>
                                                    <option value="Large">Large</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label class="control-label">Podi Name</label>
                                                <select class="form-control" name="bom_podi[]">
                                                    <option value="">Select Podi</option>
                                                    <?php foreach ($podies as $podi): ?>
                                                        <option value="<?= $podi['id'] ?>"><?= esc($podi['name']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-1">
                                            <div class="form-group">
                                                <label class="control-label">Podi Pcs</label>
                                                <input class="form-control" type="text" name="bom_podi_pcs[]">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <button type="button" class="btn btn-primary btn-sm" id="add_new_bom">+ Add BOM Row</button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- CUSTOMIZE BILL OF MATERIAL Section -->
                        <div class="design_section mt-4">
                            <div class="row">
                                <div class="col-md-12 mb-2">
                                    <h4 style="background:#f0f0f0; padding:10px; border-radius:4px;">CUSTOMIZE BILL OF MATERIAL</h4>
                                </div>
                            </div>
                            <div id="customize_bom_container">
                                <div class="customize_bom_row" data-index="0">
                                    <div class="row mb-2" style="background:#fafafa; padding:10px; border-radius:4px; margin:5px 0;">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="control-label">Part Name</label>
                                                <select class="form-control" name="cbom_part_name[]">
                                                    <option value="">Select Part</option>
                                                    <?php foreach ($parts as $part): ?>
                                                        <option value="<?= $part['id'] ?>"><?= esc($part['name']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group mt-2">
                                                <label class="control-label">Podi Name</label>
                                                <select class="form-control" name="cbom_podi_name[]">
                                                    <option value="">Select Podi</option>
                                                    <?php foreach ($podies as $podi): ?>
                                                        <option value="<?= $podi['id'] ?>"><?= esc($podi['name']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="control-label">Quantity Details (per Variation)</label>
                                            <div class="cbom_quantity_grid" data-row="0">
                                                <p class="text-muted">Select a Product Type above to load variation quantities</p>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="control-label">Main Group</label>
                                                <select class="form-control" name="cbom_main_group[]">
                                                    <option value="All" selected>All</option>
                                                    <option value="Plain">Plain</option>
                                                    <option value="Bunch">Bunch</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <button type="button" class="btn btn-primary btn-sm" id="add_new_cbom">+ Add CBOM Row</button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-success btn-lg">Save Product</button>
                                <a href="<?= base_url('products') ?>" class="btn btn-secondary btn-lg">Cancel</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.design_section { border: 1px solid #ddd; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
.form-group { margin-bottom: 10px; }
.form-group label { font-weight: 600; font-size: 12px; margin-bottom: 3px; display: block; }
.cbom_quantity_grid table { width: 100%; font-size: 12px; }
.cbom_quantity_grid th, .cbom_quantity_grid td { padding: 5px; text-align: center; }
.cbom_quantity_grid input { width: 60px; text-align: center; }
.remove-row { color: red; cursor: pointer; font-size: 18px; float: right; }
#ajax_debug { background: #ffe; border: 1px solid #cc0; padding: 10px; margin: 10px 0; font-size: 12px; display: none; }
</style>

<script>
var baseUrl = '<?= rtrim(base_url(), "/") ?>/';
var csrfName = '<?= csrf_token() ?>';
var csrfHash = '<?= csrf_hash() ?>';
var bomRowIndex = 1;
var cbomRowIndex = 1;
var currentVariations = [];

var partsOptions = '';
var podiesOptions = '';
<?php foreach ($parts as $part): ?>
partsOptions += '<option value="<?= $part['id'] ?>"><?= addslashes($part['name']) ?></option>';
<?php endforeach; ?>
<?php foreach ($podies as $podi): ?>
podiesOptions += '<option value="<?= $podi['id'] ?>"><?= addslashes($podi['name']) ?></option>';
<?php endforeach; ?>

document.getElementById('product_type').addEventListener('change', function() {
    var productTypeId = this.value;
    if (!productTypeId) {
        currentVariations = [];
        updateAllCbomQuantityGrids();
        return;
    }
    
    var url = baseUrl + 'products/get_variations_by_product_type';
    console.log('AJAX URL:', url);
    console.log('Product Type ID:', productTypeId);
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'product_type_id=' + productTypeId + '&' + csrfName + '=' + csrfHash
    })
    .then(function(response) {
        console.log('Response status:', response.status);
        if (!response.ok) {
            return response.text().then(function(text) {
                console.error('Error response:', text);
                throw new Error('HTTP ' + response.status + ': ' + text.substring(0, 200));
            });
        }
        return response.json();
    })
    .then(function(data) {
        console.log('AJAX Response:', data);
        if (data.success && data.variations) {
            currentVariations = data.variations;
            updateAllCbomQuantityGrids();
            
            if (data.csrf_hash) {
                csrfHash = data.csrf_hash;
            }
        } else {
            console.log('No variations found or success=false');
            currentVariations = [];
            updateAllCbomQuantityGrids();
        }
    })
    .catch(function(error) {
        console.error('AJAX Error:', error);
        alert('Error loading variations: ' + error.message + '\nCheck browser console (F12) for details.');
    });
});

function updateAllCbomQuantityGrids() {
    document.querySelectorAll('.cbom_quantity_grid').forEach(function(grid) {
        var rowIndex = grid.dataset.row;
        updateCbomQuantityGrid(grid, rowIndex);
    });
}

function updateCbomQuantityGrid(grid, rowIndex) {
    if (currentVariations.length === 0) {
        grid.innerHTML = '<p class="text-muted">Select a Product Type above to load variation quantities</p>';
        return;
    }
    
    var html = '<table class="table table-bordered table-sm"><thead><tr><th>Variation</th><th>Part Qty</th><th>Podi Qty</th></tr></thead><tbody>';
    currentVariations.forEach(function(v) {
        html += '<tr>';
        html += '<td>' + v.name + '</td>';
        html += '<td><input type="text" class="form-control form-control-sm" name="cbom_part_quantity_' + v.id + '[]" style="width:80px;"></td>';
        html += '<td><input type="text" class="form-control form-control-sm" name="cbom_podi_quantity_' + v.id + '[]" style="width:80px;"></td>';
        html += '</tr>';
    });
    html += '</tbody></table>';
    grid.innerHTML = html;
}

document.getElementById('add_new_bom').addEventListener('click', function() {
    var container = document.getElementById('bill_of_material_container');
    
    var html = '<div class="bill_of_material_row" data-index="' + bomRowIndex + '">' +
        '<div class="row mb-2" style="background:#fafafa; padding:10px; border-radius:4px; margin:5px 0;">' +
        '<div class="col-md-2"><div class="form-group"><label class="control-label">Part Name</label>' +
        '<select class="form-control" name="bom_part_name[]"><option value="">Select Part</option>' + partsOptions + '</select></div></div>' +
        '<div class="col-md-1"><div class="form-group"><label class="control-label">No. of Pcs</label>' +
        '<input class="form-control" type="text" name="bom_part_pcs[]"></div></div>' +
        '<div class="col-md-2"><div class="form-group"><label class="control-label">Scale</label>' +
        '<select class="form-control" name="bom_scale[]"><option value="">Select Scale</option>' +
        '<option value="Per Inch">Per Inch</option><option value="Per Pair">Per Pair</option><option value="Per Kanni">Per Kanni</option></select></div></div>' +
        '<div class="col-md-2"><div class="form-group"><label class="control-label">Main Group</label>' +
        '<select class="form-control" name="bom_main_group[]"><option value="All" selected>All</option>' +
        '<option value="Plain">Plain</option><option value="Bunch">Bunch</option></select></div></div>' +
        '<div class="col-md-2"><div class="form-group"><label class="control-label">Variation Group</label>' +
        '<select class="form-control bom_variation_group" name="bom_variation_group[' + bomRowIndex + '][]" multiple style="height:70px;">' +
        '<option value="Small">Small</option><option value="Medium">Medium</option><option value="Large">Large</option></select></div></div>' +
        '<div class="col-md-2"><div class="form-group"><label class="control-label">Podi Name</label>' +
        '<select class="form-control" name="bom_podi[]"><option value="">Select Podi</option>' + podiesOptions + '</select></div></div>' +
        '<div class="col-md-1"><div class="form-group"><label class="control-label">Podi Pcs</label>' +
        '<input class="form-control" type="text" name="bom_podi_pcs[]">' +
        '<span class="remove-row" onclick="this.closest(\'.bill_of_material_row\').remove()">X</span></div></div>' +
        '</div></div>';
    
    container.insertAdjacentHTML('beforeend', html);
    bomRowIndex++;
});

document.getElementById('add_new_cbom').addEventListener('click', function() {
    var container = document.getElementById('customize_bom_container');
    
    var html = '<div class="customize_bom_row" data-index="' + cbomRowIndex + '">' +
        '<div class="row mb-2" style="background:#fafafa; padding:10px; border-radius:4px; margin:5px 0;">' +
        '<div class="col-md-3"><div class="form-group"><label class="control-label">Part Name</label>' +
        '<select class="form-control" name="cbom_part_name[]"><option value="">Select Part</option>' + partsOptions + '</select></div>' +
        '<div class="form-group mt-2"><label class="control-label">Podi Name</label>' +
        '<select class="form-control" name="cbom_podi_name[]"><option value="">Select Podi</option>' + podiesOptions + '</select></div></div>' +
        '<div class="col-md-6"><label class="control-label">Quantity Details (per Variation)</label>' +
        '<div class="cbom_quantity_grid" data-row="' + cbomRowIndex + '"></div></div>' +
        '<div class="col-md-3"><div class="form-group"><label class="control-label">Main Group</label>' +
        '<select class="form-control" name="cbom_main_group[]"><option value="All" selected>All</option>' +
        '<option value="Plain">Plain</option><option value="Bunch">Bunch</option></select></div>' +
        '<span class="remove-row" onclick="this.closest(\'.customize_bom_row\').remove()">X</span></div>' +
        '</div></div>';
    
    container.insertAdjacentHTML('beforeend', html);
    
    var newGrid = container.querySelector('.customize_bom_row[data-index="' + cbomRowIndex + '"] .cbom_quantity_grid');
    updateCbomQuantityGrid(newGrid, cbomRowIndex);
    
    cbomRowIndex++;
});

document.getElementById('add_product_form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    var formData = new FormData(this);
    var msgDiv = document.getElementById('form_messages');
    
    msgDiv.innerHTML = '<div class="alert alert-info">Saving product...</div>';
    
    fetch(baseUrl + 'products/save_product', {
        method: 'POST',
        body: formData
    })
    .then(function(response) {
        if (!response.ok) {
            return response.text().then(function(text) {
                throw new Error('HTTP ' + response.status);
            });
        }
        return response.json();
    })
    .then(function(data) {
        if (data.error === 0) {
            msgDiv.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
            if (data.redirect) {
                setTimeout(function() {
                    window.location.href = data.redirect;
                }, 1500);
            }
        } else {
            msgDiv.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
        }
    })
    .catch(function(error) {
        msgDiv.innerHTML = '<div class="alert alert-danger">Error: ' + error.message + '</div>';
    });
});
</script>
<?= $this->endSection() ?>
