<?php
class ClientController extends Controller {
    private Client $clientModel;

    public function __construct() {
        parent::__construct();
        $this->clientModel = new Client();
    }

    public function index(): void {
        $search = $_GET['search'] ?? '';
        $clients = $search ? $this->clientModel->search($search) : $this->clientModel->findAll('name ASC');
        $this->view('clients/index', ['clients' => $clients, 'search' => $search]);
    }

    public function create(): void {
        $this->view('clients/form', ['client' => null]);
    }

    public function store(): void {
        $data = [
            'name'    => trim($_POST['name'] ?? ''),
            'email'   => trim($_POST['email'] ?? ''),
            'phone'   => trim($_POST['phone'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'company' => trim($_POST['company'] ?? ''),
        ];

        if (!$data['name'] || !$data['email']) {
            $this->setFlash('danger', 'Name and email are required.');
            $this->view('clients/form', ['client' => $data]);
            return;
        }

        $this->clientModel->create($data);
        AuditLog::log('client', (int)$this->db->lastInsertId(), 'created', null, $data);
        $this->setFlash('success', 'Client created successfully.');
        $this->redirect('clients');
    }

    public function edit(string $id): void {
        $client = $this->clientModel->find((int)$id);
        if (!$client) { $this->redirect('clients'); return; }
        $this->view('clients/form', ['client' => $client]);
    }

    public function update(string $id): void {
        $data = [
            'name'    => trim($_POST['name'] ?? ''),
            'email'   => trim($_POST['email'] ?? ''),
            'phone'   => trim($_POST['phone'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'company' => trim($_POST['company'] ?? ''),
        ];

        if (!$data['name'] || !$data['email']) {
            $this->setFlash('danger', 'Name and email are required.');
            $data['id'] = $id;
            $this->view('clients/form', ['client' => $data]);
            return;
        }

        $old = $this->clientModel->find((int)$id);
        $this->clientModel->update((int)$id, $data);
        AuditLog::log('client', (int)$id, 'updated', $old, $data);
        $this->setFlash('success', 'Client updated successfully.');
        $this->redirect('clients');
    }

    public function delete(string $id): void {
        $old = $this->clientModel->find((int)$id);
        $this->clientModel->delete((int)$id);
        AuditLog::log('client', (int)$id, 'deleted', $old, null);
        $this->setFlash('success', 'Client deleted successfully.');
        $this->redirect('clients');
    }
}
