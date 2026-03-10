<?php
class ProductController extends Controller {
    private Product $productModel;

    public function __construct() {
        parent::__construct();
        $this->productModel = new Product();
    }

    public function index(): void {
        $search = trim($_GET['search'] ?? '');
        $currency = trim($_GET['currency'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $sort = trim($_GET['sort'] ?? 'name_asc');

        $products = $this->productModel->findFiltered($search ?: null, $currency ?: null, $status ?: null, $sort ?: 'name_asc');
        $currencies = (new Currency())->findActive();

        $this->view('products/index', [
            'products' => $products,
            'search' => $search,
            'currencyFilter' => $currency,
            'statusFilter' => $status,
            'sortFilter' => $sort,
            'currencies' => $currencies,
        ]);
    }

    public function create(): void {
        $currencies = (new Currency())->findActive();
        $this->view('products/form', ['product' => null, 'currencies' => $currencies]);
    }

    public function store(): void {
        $data = [
            'name'        => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'unit_price'  => (float)($_POST['unit_price'] ?? 0),
            'currency'    => $_POST['currency'] ?? BASE_CURRENCY,
            'unit'        => trim($_POST['unit'] ?? 'pcs'),
            'is_active'   => (int)($_POST['is_active'] ?? 1),
        ];

        if (!$data['name'] || $data['unit_price'] <= 0) {
            $this->setFlash('danger', 'Product name and price are required.');
            $currencies = (new Currency())->findActive();
            $this->view('products/form', ['product' => $data, 'currencies' => $currencies]);
            return;
        }

        $this->productModel->create($data);
        $this->setFlash('success', 'Product created successfully.');
        $this->redirect('products');
    }

    public function edit(string $id): void {
        $product = $this->productModel->find((int)$id);
        if (!$product) { $this->redirect('products'); return; }
        $currencies = (new Currency())->findActive();
        $this->view('products/form', ['product' => $product, 'currencies' => $currencies]);
    }

    public function update(string $id): void {
        $data = [
            'name'        => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'unit_price'  => (float)($_POST['unit_price'] ?? 0),
            'currency'    => $_POST['currency'] ?? BASE_CURRENCY,
            'unit'        => trim($_POST['unit'] ?? 'pcs'),
            'is_active'   => (int)($_POST['is_active'] ?? 1),
        ];

        if (!$data['name'] || $data['unit_price'] <= 0) {
            $this->setFlash('danger', 'Product name and price are required.');
            $data['id'] = $id;
            $currencies = (new Currency())->findActive();
            $this->view('products/form', ['product' => $data, 'currencies' => $currencies]);
            return;
        }

        $this->productModel->update((int)$id, $data);
        $this->setFlash('success', 'Product updated successfully.');
        $this->redirect('products');
    }

    public function delete(string $id): void {
        $this->productModel->delete((int)$id);
        $this->setFlash('success', 'Product deleted successfully.');
        $this->redirect('products');
    }
}
