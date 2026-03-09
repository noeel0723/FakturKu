<?php
class DashboardController extends Controller {
    public function index(): void {
        $invoiceModel = new Invoice();
        $stats = $invoiceModel->getDashboardStats();
        $recentInvoices = $invoiceModel->findAllWithClient();
        $recentInvoices = array_slice($recentInvoices, 0, 10);

        $clientModel = new Client();
        $clientCount = $clientModel->count();

        $productModel = new Product();
        $productCount = $productModel->count();

        $this->view('dashboard/index', [
            'stats'          => $stats,
            'recentInvoices' => $recentInvoices,
            'clientCount'    => $clientCount,
            'productCount'   => $productCount,
        ]);
    }
}
