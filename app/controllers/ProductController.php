<?php
class ProductController extends Controller {
    private Product $productModel;

    public function __construct() {
        parent::__construct();
        $this->productModel = new Product();
    }

    public function index(): void {
        $products = $this->productModel->findAll('name ASC');
        $this->view('products/index', ['products' => $products]);
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
        ];

        if (!$data['name'] || $data['unit_price'] <= 0) {
            $this->setFlash('danger', 'Nama dan harga wajib diisi.');
            $currencies = (new Currency())->findActive();
            $this->view('products/form', ['product' => $data, 'currencies' => $currencies]);
            return;
        }

        $this->productModel->create($data);
        $this->setFlash('success', 'Produk berhasil ditambahkan.');
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
        ];

        if (!$data['name'] || $data['unit_price'] <= 0) {
            $this->setFlash('danger', 'Nama dan harga wajib diisi.');
            $data['id'] = $id;
            $currencies = (new Currency())->findActive();
            $this->view('products/form', ['product' => $data, 'currencies' => $currencies]);
            return;
        }

        $this->productModel->update((int)$id, $data);
        $this->setFlash('success', 'Produk berhasil diperbarui.');
        $this->redirect('products');
    }

    public function delete(string $id): void {
        $this->productModel->delete((int)$id);
        $this->setFlash('success', 'Produk berhasil dihapus.');
        $this->redirect('products');
    }
}
