<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'ProductTypes::index');

// Product Types
$routes->get('product-types', 'ProductTypes::index');
$routes->get('product-types/create', 'ProductTypes::create');
$routes->post('product-types/store', 'ProductTypes::store');
$routes->get('product-types/edit/(:num)', 'ProductTypes::edit/$1');
$routes->post('product-types/update/(:num)', 'ProductTypes::update/$1');
$routes->get('product-types/delete/(:num)', 'ProductTypes::delete/$1');

// Bodies
$routes->get('bodies', 'Bodies::index');
$routes->get('bodies/create', 'Bodies::create');
$routes->post('bodies/store', 'Bodies::store');
$routes->get('bodies/edit/(:num)', 'Bodies::edit/$1');
$routes->post('bodies/update/(:num)', 'Bodies::update/$1');
$routes->get('bodies/delete/(:num)', 'Bodies::delete/$1');

// Variations
$routes->get('variations', 'Variations::index');
$routes->get('variations/create', 'Variations::create');
$routes->post('variations/store', 'Variations::store');
$routes->get('variations/edit/(:num)', 'Variations::edit/$1');
$routes->post('variations/update/(:num)', 'Variations::update/$1');
$routes->get('variations/delete/(:num)', 'Variations::delete/$1');

// Departments
$routes->get('departments', 'Departments::index');
$routes->get('departments/create', 'Departments::create');
$routes->post('departments/store', 'Departments::store');
$routes->get('departments/edit/(:num)', 'Departments::edit/$1');
$routes->post('departments/update/(:num)', 'Departments::update/$1');
$routes->get('departments/delete/(:num)', 'Departments::delete/$1');

// Parts
$routes->get('parts', 'Parts::index');
$routes->get('parts/create', 'Parts::create');
$routes->post('parts/store', 'Parts::store');
$routes->get('parts/edit/(:num)', 'Parts::edit/$1');
$routes->post('parts/update/(:num)', 'Parts::update/$1');
$routes->get('parts/delete/(:num)', 'Parts::delete/$1');

// Podies
$routes->get('podies', 'Podies::index');
$routes->get('podies/create', 'Podies::create');
$routes->post('podies/store', 'Podies::store');
$routes->get('podies/edit/(:num)', 'Podies::edit/$1');
$routes->post('podies/update/(:num)', 'Podies::update/$1');
$routes->get('podies/delete/(:num)', 'Podies::delete/$1');

// Clients
$routes->get('clients', 'Clients::index');
$routes->get('clients/create', 'Clients::create');
$routes->post('clients/store', 'Clients::store');
$routes->get('clients/edit/(:num)', 'Clients::edit/$1');
$routes->post('clients/update/(:num)', 'Clients::update/$1');
$routes->get('clients/delete/(:num)', 'Clients::delete/$1');

// Stamps
$routes->get('stamps', 'Stamps::index');
$routes->get('stamps/create', 'Stamps::create');
$routes->post('stamps/store', 'Stamps::store');
$routes->get('stamps/edit/(:num)', 'Stamps::edit/$1');
$routes->post('stamps/update/(:num)', 'Stamps::update/$1');
$routes->get('stamps/delete/(:num)', 'Stamps::delete/$1');

// Products
$routes->get('products', 'Products::index');
$routes->get('products/create', 'Products::create');
$routes->post('products/store', 'Products::store');
$routes->get('products/view/(:num)', 'Products::view/$1');
$routes->get('products/edit/(:num)', 'Products::edit/$1');
$routes->post('products/update/(:num)', 'Products::update/$1');
$routes->get('products/delete/(:num)', 'Products::delete/$1');
$routes->get('products/cbom/(:num)', 'Products::cbom/$1');
$routes->post('products/saveCbom/(:num)', 'Products::saveCbom/$1');
$routes->post('products/getVariations', 'Products::getVariations');
$routes->post('products/addPattern/(:num)', 'Products::addPattern/$1');
$routes->get('products/deletePattern/(:num)', 'Products::deletePattern/$1');
$routes->post('products/savePatternChanges/(:num)', 'Products::savePatternChanges/$1');
$routes->get('products/duplicate/(:num)', 'Products::duplicate/$1');
$routes->post('products/importBomTemplate/(:num)', 'Products::importBomTemplate/$1');
$routes->post('products/importCbomTemplate/(:num)', 'Products::importCbomTemplate/$1');
$routes->post('products/importTemplateToPattern/(:num)', 'Products::importTemplateToPattern/$1');
$routes->post('products/updatePattern/(:num)', 'Products::updatePattern/$1');

// Pattern Names
$routes->get('pattern-names', 'PatternNames::index');
$routes->get('pattern-names/create', 'PatternNames::create');
$routes->post('pattern-names/store', 'PatternNames::store');
$routes->get('pattern-names/edit/(:num)', 'PatternNames::edit/$1');
$routes->post('pattern-names/update/(:num)', 'PatternNames::update/$1');
$routes->get('pattern-names/delete/(:num)', 'PatternNames::delete/$1');

// BOM Templates
$routes->get('templates', 'Templates::index');
$routes->get('templates/create', 'Templates::create');
$routes->post('templates/store', 'Templates::store');
$routes->get('templates/view/(:num)', 'Templates::view/$1');
$routes->get('templates/edit/(:num)', 'Templates::edit/$1');
$routes->post('templates/update/(:num)', 'Templates::update/$1');
$routes->get('templates/delete/(:num)', 'Templates::delete/$1');
$routes->post('templates/getVariationsByType', 'Templates::getVariationsByType');

// Orders
$routes->get('orders', 'Orders::index');
$routes->get('orders/create', 'Orders::create');
$routes->post('orders/store', 'Orders::store');
$routes->get('orders/view/(:num)', 'Orders::view/$1');
$routes->get('orders/edit/(:num)', 'Orders::edit/$1');
$routes->post('orders/update/(:num)', 'Orders::update/$1');
$routes->get('orders/delete/(:num)', 'Orders::delete/$1');
$routes->post('orders/addItem/(:num)', 'Orders::addItem/$1');
$routes->get('orders/removeItem/(:num)', 'Orders::removeItem/$1');
$routes->post('orders/saveItemQty/(:num)', 'Orders::saveItemQty/$1');
$routes->post('orders/saveItemQtyAjax/(:num)', 'Orders::saveItemQtyAjax/$1');
$routes->get('orders/updateStatus/(:num)/(:alpha)', 'Orders::updateStatus/$1/$2');
$routes->get('orders/preview/(:num)', 'Orders::preview/$1');
$routes->post('orders/confirm/(:num)', 'Orders::confirm/$1');
$routes->get('orders/mainPartSetup/(:num)', 'Orders::mainPartSetup/$1');
$routes->post('orders/saveMainPartSetup/(:num)', 'Orders::saveMainPartSetup/$1');
$routes->get('orders/partRequirements/(:num)', 'Orders::partRequirements/$1');
$routes->get('orders/productPartRequirements/(:num)/item/(:num)', 'Orders::productPartRequirements/$1/$2');
$routes->get('orders/productPartRequirementsPdf/(:num)/item/(:num)', 'Orders::productPartRequirementsPdf/$1/$2');
$routes->get('orders/partCalcDetail/(:num)/(:num)', 'Orders::partCalcDetail/$1/$2');
$routes->get('orders/partCalcDetail/(:num)/(:num)/(:num)', 'Orders::partCalcDetail/$1/$2/$3');
$routes->post('orders/getProductWeightData', 'Orders::getProductWeightData');
$routes->get('orders/orderSheet/(:num)', 'Orders::orderSheet/$1');
$routes->post('orders/updateMasterWeights/(:num)', 'Orders::updateMasterWeights/$1');
$routes->get('orders/touchAnalysis/(:num)',        'Orders::touchAnalysis/$1');
$routes->post('orders/fromLowStock',           'Orders::fromLowStock');
$routes->get('orders/split/(:num)',    'Orders::splitOrder/$1');
$routes->post('orders/do-split/(:num)','Orders::doSplit/$1');
$routes->get('orders/merge-preview',   'Orders::mergePreview');
$routes->post('orders/do-merge',        'Orders::doMerge');
$routes->get('orders/orderSheetPdf/(:num)',        'Orders::orderSheetPdf/$1');
$routes->get('orders/orderSheetSlipPdf/(:num)',    'Orders::orderSheetSlipPdf/$1');
$routes->get('orders/orderSheetSlipWithPartsPdf/(:num)', 'Orders::orderSheetSlipWithPartsPdf/$1');
$routes->get('orders/partRequirementsPdf/(:num)',  'Orders::partRequirementsPdf/$1');
$routes->post('orders/saveTouchAnalysis/(:num)',   'Orders::saveTouchAnalysis/$1');
$routes->post('orders/searchProducts', 'Orders::searchProducts');
$routes->get('orders/combined-main-part-setup',    'Orders::combinedMainPartSetup');
$routes->post('orders/combined-part-requirements', 'Orders::combinedPartRequirements');
$routes->post('orders/getProductPatterns', 'Orders::getProductPatterns');
$routes->post('orders/getProductVariations', 'Orders::getProductVariations');

// Stock Management
$routes->get('stock', 'Stock::index');
$routes->get('stock/entry', 'Stock::entry');
$routes->post('stock/get-patterns', 'Stock::getPatterns');
$routes->post('stock/get-entry-grid', 'Stock::getEntryGrid');
$routes->post('stock/get-transfer-patterns', 'Stock::getTransferPatterns');
$routes->post('stock/get-transfer-stock', 'Stock::getTransferStock');
$routes->post('stock/save-entry', 'Stock::saveEntry');
$routes->get('stock/qr-image/(:num)/(:num)/(:num)', 'Stock::qrImage/$1/$2/$3');
$routes->get('stock/labels/(:num)', 'Stock::labels/$1');
$routes->get('stock/scan', 'Stock::scan');
$routes->post('stock/get-stock-info', 'Stock::getStockInfo');
$routes->post('stock/deduct', 'Stock::deduct');
$routes->post('stock/bulk-deduct', 'Stock::bulkDeduct');
$routes->get('stock/transfer', 'Stock::transfer');
$routes->post('stock/save-transfer', 'Stock::saveTransfer');
$routes->get('stock/low-stock', 'Stock::lowStock');
$routes->post('stock/set-min-qty', 'Stock::setMinQty');
$routes->get('stock/audit-log', 'Stock::auditLog');
$routes->get('stock/label-generate', 'Stock::labelGenerate');
$routes->get('stock/qr-registry',    'Stock::qrRegistry');
$routes->post('stock/bulk-generate-qr', 'Stock::bulkGenerateQr');
$routes->get('products/migrate-pattern-cbom',           'Products::migratePatternCbom');
$routes->get('products/patternCbom/(:num)',              'Products::patternCbom/$1');
$routes->post('products/savePatternCbomChanges/(:num)', 'Products::savePatternCbomChanges/$1');
$routes->post('products/importPatternToPattern/(:num)', 'Products::importPatternToPattern/$1');
$routes->get('products/bulkEdit',                        'Products::bulkEdit');
$routes->get('products/bulkExportCsv',                   'Products::bulkExportCsv');
$routes->post('products/bulkPreview',                    'Products::bulkPreview');
$routes->post('products/bulkConfirm',                    'Products::bulkConfirm');
$routes->get('products/imageGallery',                    'Products::imageGallery');
$routes->post('products/ajaxUploadProductImage/(:num)',  'Products::ajaxUploadProductImage/$1');
$routes->post('products/ajaxUploadPatternImage/(:num)',  'Products::ajaxUploadPatternImage/$1');
$routes->get('stock/fix-qr-dupes',      'Stock::fixQrDupes');
$routes->get('stock/min-stock',       'Stock::minStock');
$routes->post('stock/save-min-stock', 'Stock::saveMinStock');
$routes->post('stock/generate-labels', 'Stock::generateLabels');
$routes->get('stock/generate-labels', function() { return redirect()->to('stock/label-generate'); });


// Karigar
$routes->get('karigar', 'Karigar::index');
$routes->get('karigar/create', 'Karigar::create');
$routes->post('karigar/store', 'Karigar::store');
$routes->get('karigar/edit/(:num)', 'Karigar::edit/$1');
$routes->post('karigar/update/(:num)', 'Karigar::update/$1');
$routes->get('karigar/delete/(:num)', 'Karigar::delete/$1');
$routes->post('karigar/get-info', 'Karigar::getInfo');
$routes->post('karigar/(:num)/charge-rule/store', 'Karigar::storeChargeRule/$1');
$routes->get('karigar/charge-rule/(:num)/delete', 'Karigar::deleteChargeRule/$1');

// Raw Material Types
$routes->get('raw-material-types', 'RawMaterialType::index');
$routes->get('raw-material-types/create', 'RawMaterialType::create');
$routes->post('raw-material-types/store', 'RawMaterialType::store');
$routes->get('raw-material-types/edit/(:num)', 'RawMaterialType::edit/$1');
$routes->post('raw-material-types/update/(:num)', 'RawMaterialType::update/$1');
$routes->get('raw-material-types/delete/(:num)', 'RawMaterialType::delete/$1');

// Byproduct Types
$routes->get('byproduct-types', 'ByproductType::index');
$routes->get('byproduct-types/create', 'ByproductType::create');
$routes->post('byproduct-types/store', 'ByproductType::store');
$routes->get('byproduct-types/edit/(:num)', 'ByproductType::edit/$1');
$routes->post('byproduct-types/update/(:num)', 'ByproductType::update/$1');
$routes->get('byproduct-types/delete/(:num)', 'ByproductType::delete/$1');

// Kacha
$routes->get('kacha', 'Kacha::index');
$routes->get('kacha/create', 'Kacha::create');
$routes->post('kacha/store', 'Kacha::store');
$routes->get('kacha/edit/(:num)', 'Kacha::edit/$1');
$routes->post('kacha/update/(:num)', 'Kacha::update/$1');
$routes->get('kacha/delete/(:num)', 'Kacha::delete/$1');
$routes->get('kacha/view/(:num)', 'Kacha::view/$1');
$routes->post('kacha/list-available', 'Kacha::listAvailable');

// Raw Material Stock
$routes->get('raw-materials', 'RawMaterialStock::index');
$routes->get('raw-materials/create', 'RawMaterialStock::create');
$routes->post('raw-materials/store', 'RawMaterialStock::store');
$routes->get('raw-materials/delete/(:num)', 'RawMaterialStock::delete/$1');

// Gatti Stock
$routes->get('gatti-stock',                      'GattiStock::index');
$routes->get('gatti-stock/view/(:num)',           'GattiStock::view/$1');
$routes->post('gatti-stock/(:num)/update',        'GattiStock::update/$1');
$routes->get('gatti-stock/entry',                 'GattiStock::stockEntry');
$routes->post('gatti-stock/entry/save',           'GattiStock::saveEntry');
$routes->post('gatti-stock/log/(:num)/update',    'GattiStock::updateLogEntry/$1');
$routes->get('gatti-stock/log/(:num)/delete',     'GattiStock::deleteLogEntry/$1');

// Byproduct Stock
$routes->get('byproducts', 'ByproductStock::index');

// Part Batch Stock
$routes->get('part-stock', 'PartBatch::index');
$routes->get('part-stock/labels', 'PartBatch::labels');
$routes->post('part-stock/generate-batch-numbers', 'PartBatch::generateBatchNumbers');
$routes->post('part-stock/print-labels', 'PartBatch::printLabels');
$routes->get('part-stock/batch', 'PartBatch::index');
$routes->get('part-stock/batch/(:num)', 'PartBatch::view/$1');
$routes->get('part-stock/qr/(:num)', 'PartBatch::qrImage/$1');
$routes->get('part-stock/scan', 'PartBatch::scan');
$routes->get('part-stock/serial-settings', 'PartBatch::serialSettings');
$routes->post('part-stock/save-serial-settings', 'PartBatch::saveSerialSettings');
$routes->get('part-stock/lookup-batch', 'PartBatch::lookupBatch');
$routes->get( 'part-stock/entry',                        'PartBatch::stockEntry');
$routes->post('part-stock/entry/save',                   'PartBatch::saveEntryByBatchNumber');
$routes->get( 'part-stock/stock-log/(:num)/edit',        'PartBatch::editStockEntry/$1');
$routes->post('part-stock/stock-log/(:num)/update',      'PartBatch::updateStockEntry/$1');
$routes->get( 'part-stock/stock-log/(:num)/delete',      'PartBatch::deleteStockEntry/$1');
$routes->post('part-stock/batch/(:num)/entry',      'PartBatch::saveStockEntry/$1');

// Melt Jobs
$routes->get('melt-jobs', 'MeltJob::index');
$routes->get('melt-jobs/search-items', 'MeltJob::searchItems');
$routes->get('melt-jobs/create', 'MeltJob::create');
$routes->post('melt-jobs/store', 'MeltJob::store');
$routes->get('melt-jobs/view/(:num)', 'MeltJob::view/$1');
$routes->post('melt-jobs/add-input/(:num)', 'MeltJob::addInput/$1');
$routes->get('melt-jobs/delete-input/(:num)', 'MeltJob::deleteInput/$1');
$routes->post('melt-jobs/add-receive/(:num)', 'MeltJob::addReceive/$1');
$routes->get('melt-jobs/delete-receive/(:num)', 'MeltJob::deleteReceive/$1');
$routes->post('melt-jobs/post/(:num)', 'MeltJob::post/$1');

// Part Orders (PARTORD)
$routes->get('part-orders', 'PartOrder::index');
$routes->get('part-orders/create', 'PartOrder::create');
$routes->post('part-orders/store', 'PartOrder::store');
$routes->get('part-orders/view/(:num)', 'PartOrder::view/$1');
$routes->post('part-orders/add-issue/(:num)', 'PartOrder::addIssue/$1');
$routes->post('part-orders/issue/(:num)/update',   'PartOrder::updateIssue/$1');
$routes->get('part-orders/delete-issue/(:num)', 'PartOrder::deleteIssue/$1');
$routes->post('part-orders/add-receive/(:num)', 'PartOrder::addReceive/$1');
$routes->post('part-orders/receive/(:num)/update', 'PartOrder::updateReceive/$1');
$routes->get('part-orders/delete-receive/(:num)', 'PartOrder::deleteReceive/$1');
$routes->post('part-orders/post/(:num)', 'PartOrder::post/$1');
$routes->post('part-orders/(:num)/save-charge-overrides', 'PartOrder::saveChargeOverrides/$1');
$routes->get('part-orders/(:num)/reset-charge-overrides', 'PartOrder::resetChargeOverrides/$1');
$routes->post('part-orders/(:num)/update-notes', 'PartOrder::updateNotes/$1');
$routes->post('part-orders/(:num)/save-allocation',         'PartOrder::saveAllocation/$1');
$routes->get('part-orders/(:num)/delete-allocation/(:num)', 'PartOrder::deleteAllocation/$1/$2');
$routes->post('part-orders/(:num)/update-display-touch',    'PartOrder::updateDisplayTouch/$1');
$routes->get('part-orders/(:num)/manf-plan-pdf',            'PartOrder::manfPlanPdf/$1');

// Karigar Ledger
$routes->get('karigar-ledger', 'KarigarLedger::index');
$routes->get('karigar-ledger/(:num)', 'KarigarLedger::detail/$1');
$routes->get('karigar-ledger/(:num)/convert', 'KarigarLedger::convert/$1');
$routes->post('karigar-ledger/(:num)/store-convert', 'KarigarLedger::storeConvert/$1');
