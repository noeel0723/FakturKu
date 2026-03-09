<?php
/**
 * FakturKu - Sistem Faktur & Tagihan UMKM
 * Entry point
 */

define('BASE_PATH', dirname(__DIR__));

// Autoload
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/helpers/number_helper.php';
require_once BASE_PATH . '/app/core/Database.php';
require_once BASE_PATH . '/app/core/Router.php';
require_once BASE_PATH . '/app/core/Controller.php';
require_once BASE_PATH . '/app/core/Model.php';

// Models
require_once BASE_PATH . '/app/models/Client.php';
require_once BASE_PATH . '/app/models/Product.php';
require_once BASE_PATH . '/app/models/Invoice.php';
require_once BASE_PATH . '/app/models/InvoiceItem.php';
require_once BASE_PATH . '/app/models/Payment.php';
require_once BASE_PATH . '/app/models/Currency.php';
require_once BASE_PATH . '/app/models/ExchangeRate.php';
require_once BASE_PATH . '/app/models/AuditLog.php';

// Services
require_once BASE_PATH . '/app/services/CurrencyService.php';
require_once BASE_PATH . '/app/services/InvoiceService.php';
require_once BASE_PATH . '/app/services/PaymentService.php';
require_once BASE_PATH . '/app/services/PdfService.php';
require_once BASE_PATH . '/app/services/MailService.php';

// Controllers
require_once BASE_PATH . '/app/controllers/DashboardController.php';
require_once BASE_PATH . '/app/controllers/ClientController.php';
require_once BASE_PATH . '/app/controllers/ProductController.php';
require_once BASE_PATH . '/app/controllers/InvoiceController.php';
require_once BASE_PATH . '/app/controllers/PaymentController.php';

// Start session
session_start();

// Route
$router = new Router();

// Dashboard
$router->get('', 'DashboardController', 'index');
$router->get('dashboard', 'DashboardController', 'index');

// Clients
$router->get('clients', 'ClientController', 'index');
$router->get('clients/create', 'ClientController', 'create');
$router->post('clients/store', 'ClientController', 'store');
$router->get('clients/edit/{id}', 'ClientController', 'edit');
$router->post('clients/update/{id}', 'ClientController', 'update');
$router->post('clients/delete/{id}', 'ClientController', 'delete');

// Products
$router->get('products', 'ProductController', 'index');
$router->get('products/create', 'ProductController', 'create');
$router->post('products/store', 'ProductController', 'store');
$router->get('products/edit/{id}', 'ProductController', 'edit');
$router->post('products/update/{id}', 'ProductController', 'update');
$router->post('products/delete/{id}', 'ProductController', 'delete');

// Invoices
$router->get('invoices', 'InvoiceController', 'index');
$router->get('invoices/create', 'InvoiceController', 'create');
$router->post('invoices/store', 'InvoiceController', 'store');
$router->get('invoices/show/{id}', 'InvoiceController', 'show');
$router->get('invoices/edit/{id}', 'InvoiceController', 'edit');
$router->post('invoices/update/{id}', 'InvoiceController', 'update');
$router->post('invoices/delete/{id}', 'InvoiceController', 'delete');
$router->get('invoices/pdf/{id}', 'InvoiceController', 'pdf');
$router->post('invoices/send/{id}', 'InvoiceController', 'sendEmail');

// Payments
$router->post('payments/checkout', 'PaymentController', 'checkout');
$router->post('payments/webhook', 'PaymentController', 'webhook');
$router->get('payments/{id}/status', 'PaymentController', 'status');
$router->get('payments/success', 'PaymentController', 'success');
$router->get('payments/cancel', 'PaymentController', 'cancel');
$router->get('payments/pending', 'PaymentController', 'pending');
$router->post('payments/record/{id}', 'PaymentController', 'recordManual');

$router->dispatch();
