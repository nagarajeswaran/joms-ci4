<!DOCTYPE html>
<html>
<head>
    <title><?= esc($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; }
        .container { max-width: 1200px; margin: 20px auto; }
        .card { margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .card-header { background: #007bff; color: white; font-weight: 600; }
        .card-header.bom-header { background: #28a745; }
        .card-header.cbom-header { background: #17a2b8; }
        .nav-links { margin-bottom: 20px; }
        .nav-links a { margin-right: 15px; text-decoration: none; }
        .bom-row, .cbom-row { background: #f8f9fa; padding: 15px; margin-bottom: 10px; border-radius: 5px; border: 1px solid #dee2e6; }
        .remove-btn { color: red; cursor: pointer; }
        .variation-checkboxes { max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 5px; }
        .variation-checkboxes label { display: block; margin-bottom: 5px; }
        .quantity-inputs { display: flex; flex-wrap: wrap; gap: 10px; }
        .quantity-input-group { display: flex; align-items: center; gap: 5px; }
        .quantity-input-group input { width: 80px; }
        .error { color: red; font-size: 12px; }
        #loading { display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.3); z-index: 9999; }
    </style>
</head>
<body>
    <div id="loading">
        <div class="spinner-border text-primary" role="status"></div>
        <span class="ms-2">Saving...</span>
    </div>
    
    <div class="container">
        <div class="nav-links">
            <a href="/joms-ci4/public/">Home</a> |
            <a href="/joms-ci4/public/products">Back to Products</a>
        </div>
        
        <h2 class="mb-4"><?= esc($title) ?></h2>
        
        <div id="alert-container"></div>
        
        <form id="productForm" enctype="multipart/form-data">
            <?= csrf_field() ?>
            
            <!-- Basic Product Info -->
            <div class="card">
                <div class="card-header">Product Information</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Product Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" id="name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tamil Name</label>
                            <input type="text" class="form-control" name="tamil_name" id="tamil_name">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Product Type <span class="text-danger">*</span></label>
                            <select class="form-select" name="product_type_id" id="product_type_id" required>
                                <option value="">-- Select Type --</option>
                                <?php foreach ($productTypes as $type): ?>
                                    <option value="<?= $type['id'] ?>" data-variations="<?= esc($type['variations']) ?>">
                                        <?= esc($type['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Body</label>
                            <select class="form-select" name="body_id" id="body_id">
                                <option value="">-- Select Body --</option>
                                <?php foreach ($bodies as $body): ?>
                                    <option value="<?= $body['id'] ?>"><?= esc($body['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Pidi</label>
                            <input type="text" class="form-control" name="pidi" id="pidi">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Main Part</label>
                            <select class="form-select" name="main_part_id" id="main_part_id">
                                <option value="">-- Select Main Part --</option>
                                <?php foreach ($parts as $part): ?>
                                    <?php if (!empty($part['is_main_part'])): ?>
                                        <option value="<?= $part['id'] ?>"><?= esc($part['name']) ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Product Image</label>
                            <input type="file" class="form-control" name="image" id="image" accept="image/*">
                        </div>
                    </div>
                    
                    <!-- Variations Checkboxes (loaded dynamically based on product type) -->
                    <div class="row" id="variationsContainer" style="display: none;">
                        <div class="col-12 mb-3">
                            <label class="form-label">Available Variations</label>
                            <div class="variation-checkboxes" id="variationsList">
                                <!-- Loaded via AJAX -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Bill of Materials Section -->
            <div class="card">
                <div class="card-header bom-header d-flex justify-content-between align-items-center">
                    <span>BILL OF MATERIAL</span>
                    <button type="button" class="btn btn-light btn-sm" id="addBomBtn">+ Add New</button>
                </div>
                <div class="card-body">
                    <div id="bomContainer">
                        <!-- BOM rows will be added here -->
                    </div>
                    <div id="bomError" class="error"></div>
                </div>
            </div>
            
            <!-- Customize Bill of Materials Section -->
            <div class="card">
                <div class="card-header cbom-header d-flex justify-content-between align-items-center">
                    <span>CUSTOMIZE BILL OF MATERIAL</span>
                    <button type="button" class="btn btn-light btn-sm" id="addCbomBtn">+ Add New</button>
                </div>
                <div class="card-body">
                    <div id="cbomContainer">
                        <!-- CBOM rows will be added here -->
                    </div>
                    <div id="cbomError" class="error"></div>
                </div>
            </div>
            
            <!-- Submit -->
            <div class="card">
                <div class="card-body">
                    <button type="submit" class="btn btn-primary btn-lg">Save Product</button>
                    <a href="/joms-ci4/public/products" class="btn btn-secondary btn-lg ms-2">Cancel</a>
                </div>
            </div>
        </form>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Store all data for JavaScript use
        const allParts = <?= json_encode($parts) ?>;
        const allPodies = <?= json_encode($podies) ?>;
        const allVariations = <?= json_encode($variations) ?>;
        let currentVariations = []; // Variations for selected product type
        let bomCounter = 0;
        let cbomCounter = 0;
        
        // Base URLs
        const baseUrl = '/joms-ci4/public/products';
        
        // On product type change, load variations
        document.getElementById('product_type_id').addEventListener('change', function() {
            const productTypeId = this.value;
            
            if (!productTypeId) {
                document.getElementById('variationsContainer').style.display = 'none';
                currentVariations = [];
                return;
            }
            
            // AJAX to get variations
            fetch(`${baseUrl}/get_variations_by_product_type`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `product_type_id=${productTypeId}&<?= csrf_token() ?>=<?= csrf_hash() ?>`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    currentVariations = data.variations;
                    renderVariationCheckboxes(data.variations);
                    document.getElementById('variationsContainer').style.display = 'block';
                }
            })
            .catch(error => console.error('Error:', error));
        });
        
        function renderVariationCheckboxes(variations) {
            const container = document.getElementById('variationsList');
            container.innerHTML = '';
            
            variations.forEach(v => {
                container.innerHTML += `
                    <label>
                        <input type="checkbox" name="selected_variations[]" value="${v.id}"> 
                        ${v.name} (${v.group_name || 'N/A'})
                    </label>
                `;
            });
        }
        
        // Add BOM Row
        document.getElementById('addBomBtn').addEventListener('click', function() {
            addBomRow();
        });
        
        function addBomRow() {
            const container = document.getElementById('bomContainer');
            const index = bomCounter++;
            
            let partsOptions = '<option value="">Select Part</option>';
            allParts.forEach(p => {
                partsOptions += `<option value="${p.id}">${p.name}</option>`;
            });
            
            let podiOptions = '<option value="">Select Podi</option>';
            allPodies.forEach(p => {
                podiOptions += `<option value="${p.id}">${p.name}</option>`;
            });
            
            let variationOptions = '<option value="">Select Variation</option>';
            if (currentVariations.length > 0) {
                currentVariations.forEach(v => {
                    variationOptions += `<option value="${v.id}">${v.name}</option>`;
                });
            } else {
                allVariations.forEach(v => {
                    variationOptions += `<option value="${v.id}">${v.name}</option>`;
                });
            }
            
            const html = `
                <div class="bom-row" id="bomRow${index}">
                    <div class="row">
                        <div class="col-md-2 mb-2">
                            <label class="form-label">Part</label>
                            <select class="form-select" name="bom_part_id[]">
                                ${partsOptions}
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="form-label">Variation</label>
                            <select class="form-select" name="bom_variation_id[]">
                                ${variationOptions}
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="form-label">Podi</label>
                            <select class="form-select" name="bom_podi_id[]">
                                ${podiOptions}
                            </select>
                        </div>
                        <div class="col-md-1 mb-2">
                            <label class="form-label">Part Pcs</label>
                            <input type="number" class="form-control" name="bom_part_pcs[]" step="0.01">
                        </div>
                        <div class="col-md-1 mb-2">
                            <label class="form-label">Podi Pcs</label>
                            <input type="number" class="form-control" name="bom_podi_pcs[]" step="0.01">
                        </div>
                        <div class="col-md-1 mb-2">
                            <label class="form-label">Scale</label>
                            <input type="number" class="form-control" name="bom_scale[]" step="0.01">
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="form-label">Main Group</label>
                            <select class="form-select" name="bom_main_group[]">
                                <option value="All">All</option>
                                <option value="Plain">Plain</option>
                                <option value="Bunch">Bunch</option>
                            </select>
                        </div>
                        <div class="col-md-1 mb-2 d-flex align-items-end">
                            <button type="button" class="btn btn-danger btn-sm" onclick="removeBomRow(${index})">X</button>
                        </div>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', html);
        }
        
        function removeBomRow(index) {
            document.getElementById(`bomRow${index}`).remove();
        }
        
        // Add CBOM Row
        document.getElementById('addCbomBtn').addEventListener('click', function() {
            addCbomRow();
        });
        
        function addCbomRow() {
            const container = document.getElementById('cbomContainer');
            const index = cbomCounter++;
            
            let partsOptions = '<option value="">Select Part</option>';
            allParts.forEach(p => {
                partsOptions += `<option value="${p.id}">${p.name}</option>`;
            });
            
            let podiOptions = '<option value="">Select Podi</option>';
            allPodies.forEach(p => {
                podiOptions += `<option value="${p.id}">${p.name}</option>`;
            });
            
            // Create quantity inputs for each variation
            let quantityInputs = '';
            const variationsToUse = currentVariations.length > 0 ? currentVariations : allVariations.slice(0, 10);
            variationsToUse.forEach(v => {
                quantityInputs += `
                    <div class="quantity-input-group">
                        <label>${v.name}:</label>
                        <input type="hidden" name="cbom_var_id_${index}[]" value="${v.id}">
                        <input type="number" class="form-control form-control-sm" name="cbom_qty_${index}[]" step="0.01" placeholder="Qty">
                    </div>
                `;
            });
            
            const html = `
                <div class="cbom-row" id="cbomRow${index}">
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <label class="form-label">Part Name</label>
                            <select class="form-select" name="cbom_part_name[]">
                                ${partsOptions}
                            </select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="form-label">Podi Name</label>
                            <select class="form-select" name="cbom_podi_name[]">
                                ${podiOptions}
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="form-label">Main Group</label>
                            <select class="form-select" name="cbom_main_group[]">
                                <option value="All" selected>All</option>
                                <option value="Plain">Plain</option>
                                <option value="Bunch">Bunch</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="form-label">Quantity per Variation</label>
                            <div class="quantity-inputs">
                                ${quantityInputs}
                            </div>
                        </div>
                        <div class="col-md-1 mb-2 d-flex align-items-start pt-4">
                            <button type="button" class="btn btn-danger btn-sm" onclick="removeCbomRow(${index})">X</button>
                        </div>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', html);
        }
        
        function removeCbomRow(index) {
            document.getElementById(`cbomRow${index}`).remove();
        }
        
        // Form submission
        document.getElementById('productForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            document.getElementById('loading').style.display = 'block';
            
            fetch(`${baseUrl}/save_product`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('loading').style.display = 'none';
                
                const alertContainer = document.getElementById('alert-container');
                if (data.success) {
                    alertContainer.innerHTML = `
                        <div class="alert alert-success">
                            ${data.message}. Redirecting...
                        </div>
                    `;
                    setTimeout(() => {
                        window.location.href = '/joms-ci4/public/products';
                    }, 1500);
                } else {
                    alertContainer.innerHTML = `
                        <div class="alert alert-danger">
                            ${data.message}
                        </div>
                    `;
                }
            })
            .catch(error => {
                document.getElementById('loading').style.display = 'none';
                console.error('Error:', error);
                document.getElementById('alert-container').innerHTML = `
                    <div class="alert alert-danger">
                        An error occurred. Please try again.
                    </div>
                `;
            });
        });
        
        // Add initial empty rows
        addBomRow();
        addCbomRow();
    </script>
</body>
</html>
