<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// ─── Auth (public — no filter required) ─────────────────────────────────────
$routes->get( '/login',  'Auth::login');
$routes->post('/login',  'Auth::doLogin');
$routes->get( '/logout', 'Auth::logout');
$routes->get( '/setup',  'Auth::setup');
$routes->post('/setup',  'Auth::doSetup');

// ─── User management (admin filter applied via Filters.php) ──────────────────
$routes->get( '/users',                  'Auth::users');
$routes->post('/users/create',           'Auth::createUser');
$routes->post('/users/delete/(:num)',    'Auth::deleteUser/$1');
$routes->post('/users/change-password', 'Auth::changePassword');

// ─── Profile (ทุก role) ────────────────────────────────────────────────────────
$routes->get( '/profile',       'Auth::profile');
$routes->post('/profile/save',  'Auth::doProfile');

// ─── Dashboard ───────────────────────────────────────────────────────────────
$routes->get('/',          'Dashboard::index');
$routes->get('/dashboard', 'Dashboard::index');

// ─── Products ────────────────────────────────────────────────────────────────
$routes->get( '/products',                  'Products::index');
$routes->get( '/products/create',           'Products::create');
$routes->post('/products/store',            'Products::store');
$routes->get( '/products/edit/(:num)',      'Products::edit/$1');
$routes->post('/products/update/(:num)',    'Products::update/$1');
$routes->post('/products/delete/(:num)',    'Products::delete/$1');

// AJAX endpoints
$routes->get('/products/search',            'Products::search');
$routes->get('/products/barcode',           'Products::getByBarcode');

// ─── Sales (POS) ─────────────────────────────────────────────────────────────
$routes->get( '/sales',                     'Sales::index');
$routes->post('/sales/add-to-cart',         'Sales::addToCart');
$routes->post('/sales/update-cart',         'Sales::updateCart');
$routes->post('/sales/remove-from-cart',    'Sales::removeFromCart');
$routes->post('/sales/set-member',          'Sales::setMember');
$routes->post('/sales/set-redeem-points',   'Sales::setRedeemPoints');
$routes->post('/sales/checkout',            'Sales::checkout');
$routes->get( '/sales/clear-cart',          'Sales::clearCart');
$routes->get( '/sales/hold',                'Sales::holdBill');
$routes->get( '/sales/resume/(:num)',       'Sales::resumeBill/$1');
$routes->get( '/sales/discard-held/(:num)', 'Sales::discardHeldBill/$1');
$routes->get( '/sales/search',              'Sales::searchProduct');
$routes->get( '/sales/barcode',             'Sales::getByBarcode');
$routes->get( '/sales/member-search',       'Sales::searchMember');

// ─── Members ─────────────────────────────────────────────────────────────────
$routes->get( '/members',                   'Members::index');
$routes->get( '/members/create',            'Members::create');
$routes->post('/members/store',             'Members::store');
$routes->get( '/members/edit/(:num)',       'Members::edit/$1');
$routes->post('/members/update/(:num)',     'Members::update/$1');
$routes->post('/members/delete/(:num)',     'Members::delete/$1');
$routes->get( '/members/search',            'Members::ajaxSearch');

// ─── Credit (ค้างชำระ / เงินเชื่อ) ───────────────────────────────────────────
$routes->get( '/credit',                 'Credit::index');
$routes->post('/credit/pay/(:num)',      'Credit::pay/$1');
$routes->get( '/credit/payments/(:num)', 'Credit::payments/$1');

// ─── Receipt ─────────────────────────────────────────────────────────────────
$routes->get('/receipt/(:num)',             'Receipt::view/$1');

// ─── Returns (คืนสินค้า) ─────────────────────────────────────────────────────
$routes->get( '/returns',               'Returns::index');
$routes->get( '/returns/create/(:num)', 'Returns::create/$1');
$routes->post('/returns/store',         'Returns::store');
$routes->post('/returns/approve/(:num)','Returns::approve/$1');
$routes->post('/returns/reject/(:num)', 'Returns::reject/$1');
$routes->get( '/returns/detail/(:num)', 'Returns::detail/$1');

// ─── Settings ────────────────────────────────────────────────────────────────
$routes->get( '/settings',            'Settings::index');
$routes->post('/settings/save',       'Settings::save');
$routes->post('/settings/update-database', 'Settings::updateDatabase');

// ─── Tiers ───────────────────────────────────────────────────────────────────
$routes->get( '/tiers',               'Tiers::index');
$routes->post('/tiers/store',         'Tiers::store');
$routes->post('/tiers/update/(:num)', 'Tiers::update/$1');
$routes->post('/tiers/delete/(:num)', 'Tiers::delete/$1');

// ─── Reports ─────────────────────────────────────────────────────────────────
$routes->get('/reports',                   'Reports::index');
$routes->get('/reports/daily',             'Reports::daily');
$routes->get('/reports/profit',            'Reports::profit');
$routes->get('/reports/bill/(:num)',       'Reports::bill/$1');
$routes->get('/reports/export',            'Reports::export');
$routes->get('/reports/search',            'Reports::search');
$routes->get('/reports/monthly',           'Reports::monthly');

// ─── Stock adjustment ─────────────────────────────────────────────────────────
$routes->get( '/stock',                    'Stock::index');
$routes->post('/stock/adjust',             'Stock::adjust');
$routes->get( '/stock/log',               'Stock::log');
$routes->get( '/stock/receive',           'Stock::receive');
$routes->get( '/stock/product/(:num)',    'Stock::product/$1');

// ─── Void sale (admin only) ───────────────────────────────────────────────────
$routes->post('/sales/void/(:num)',        'Sales::voidSale/$1');

// ─── Cash Drawer ──────────────────────────────────────────────────────────────
$routes->get( '/cash-drawer',           'CashDrawer::index');
$routes->post('/cash-drawer/open',      'CashDrawer::open');
$routes->post('/cash-drawer/close',     'CashDrawer::close');
$routes->post('/cash-drawer/cash-in',   'CashDrawer::cashIn');
$routes->post('/cash-drawer/cash-out',  'CashDrawer::cashOut');
$routes->get( '/cash-drawer/log',       'CashDrawer::log');
$routes->get( '/cash-drawer/status',    'CashDrawer::status');

// ─── Wholesale / Quotation ────────────────────────────────────────────────────
$routes->get( '/wholesale',                     'Wholesale::index');
$routes->post('/wholesale/add-to-cart',         'Wholesale::addToCart');
$routes->post('/wholesale/update-cart',         'Wholesale::updateCart');
$routes->post('/wholesale/remove-from-cart',    'Wholesale::removeFromCart');
$routes->post('/wholesale/set-discount',        'Wholesale::setDiscount');
$routes->get( '/wholesale/search',              'Wholesale::searchProduct');
$routes->get( '/wholesale/barcode',             'Wholesale::getByBarcode');
$routes->post('/wholesale/save-quote',          'Wholesale::saveQuote');
$routes->get( '/wholesale/quotation/(:num)',    'Wholesale::viewQuotation/$1');
$routes->post('/wholesale/confirm/(:num)',      'Wholesale::confirmQuote/$1');
$routes->post('/wholesale/cancel/(:num)',       'Wholesale::cancelQuote/$1');
$routes->get( '/wholesale/list',               'Wholesale::listQuotations');
$routes->get( '/wholesale/delivery/(:num)',    'Wholesale::deliveryPrint/$1');
$routes->get( '/wholesale/clear-cart',         'Wholesale::clearCart');

// ─── Customer Display (no auth required — not in auth filter list) ──────────
$routes->get('/customer-display',          'CustomerDisplay::index');
$routes->get('/customer-display/data',     'CustomerDisplay::data');
$routes->get('/sales/cart-data',           'Sales::getCartJson');

// ─── Product Import ───────────────────────────────────────────────────────────
$routes->get( '/products/import',           'Products::importForm');
$routes->post('/products/import',           'Products::doImport');
$routes->get( '/products/import-template', 'Products::importTemplate');

// ─── Member History ───────────────────────────────────────────────────────────
$routes->get('/members/history/(:num)',    'Members::history/$1');
