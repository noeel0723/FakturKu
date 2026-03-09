# FakturKu — Sistem Faktur & Tagihan UMKM

Sistem invoice & billing untuk UMKM berbasis PHP native dengan fitur:
- CRUD Klien, Produk/Jasa
- Pembuatan Invoice dengan nomor otomatis (atomic/concurrent-safe)
- Multi-currency (IDR, USD, EUR, dll.) + konversi kurs otomatis
- Generate PDF invoice + kirim email
- Pembayaran online via **Stripe** dan **Midtrans**
- Webhook handler + verifikasi signature + idempotency
- Catat pembayaran manual
- Audit log semua perubahan
- Dashboard ringkasan bisnis

---

## Persyaratan Sistem

- PHP 8.1+
- MySQL 5.7+ / MariaDB 10.3+
- Apache dengan mod_rewrite (Laragon/XAMPP/MAMP)
- cURL extension (untuk payment gateway & exchange rates)
- OpenSSL extension

---

## Instalasi

### 1. Clone / Extract

```bash
cd d:\laragon\www
git clone <repo-url> FakturKu
# atau extract zip ke folder FakturKu
```

### 2. Konfigurasi Environment

```bash
cd FakturKu
copy .env.example .env
# Edit .env sesuai konfigurasi database dan API keys Anda
```

### 3. Buat Database & Jalankan Migration

```sql
CREATE DATABASE fakturku CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```bash
# Import migration
mysql -u root -p fakturku < migrations/001_init.sql
```

### 4. Setup Virtual Host (Laragon)

Laragon biasanya otomatis membuat virtual host. Pastikan bisa diakses di:
```
http://fakturku.test/public/
```

Atau set `APP_URL` di `.env`:
```
APP_URL=http://localhost/FakturKu/public
```

### 5. Test

Buka browser → `http://localhost/FakturKu/public/`

---

## Setup Payment Gateway

### Stripe (Sandbox)

1. Buat akun di [stripe.com](https://stripe.com)
2. Ambil **test keys** dari Dashboard → Developers → API Keys
3. Isi di `.env`:
   ```
   PAYMENT_PROVIDER=stripe
   STRIPE_SECRET=sk_test_...
   STRIPE_PUBLISHABLE=pk_test_...
   ```
4. Setup Webhook:
   - Dashboard → Developers → Webhooks → Add endpoint
   - URL: `https://yourdomain.com/FakturKu/public/payments/webhook`
   - Events: `checkout.session.completed`
   - Copy Signing secret → STRIPE_WEBHOOK_SECRET

### Midtrans (Sandbox)

1. Daftar di [midtrans.com](https://midtrans.com)
2. Masuk ke Sandbox Dashboard
3. Settings → Access Keys → copy Server Key & Client Key
4. Isi di `.env`:
   ```
   PAYMENT_PROVIDER=midtrans
   MIDTRANS_SERVER_KEY=SB-Mid-server-...
   MIDTRANS_CLIENT_KEY=SB-Mid-client-...
   MIDTRANS_IS_PRODUCTION=false
   ```
5. Setup Notification URL:
   - Settings → Configuration → Notification URL
   - URL: `https://yourdomain.com/FakturKu/public/payments/webhook`

### Catatan Penting
- Webhook URL **harus HTTPS** di production
- Untuk testing lokal, gunakan [ngrok](https://ngrok.com) atau [Stripe CLI](https://stripe.com/docs/stripe-cli)

---

## Exchange Rate / Kurs

### Otomatis via Cron

```bash
# Jalankan manual:
php cron/fetch_rates.php

# Setup crontab (Linux) - setiap hari jam 6 pagi:
0 6 * * * php /path/to/FakturKu/cron/fetch_rates.php

# Windows Task Scheduler:
# Action: php.exe
# Arguments: D:\laragon\www\FakturKu\cron\fetch_rates.php
```

### API yang Didukung
- [exchangerate.host](https://exchangerate.host) (gratis)
- [Open Exchange Rates](https://openexchangerates.org)
- API lain yang mengembalikan format `{"rates": {"USD": 0.000063}}`

---

## Testing

### Test Concurrency Nomor Invoice

```bash
php tests/concurrency_test.php
```

Tes ini akan:
1. Membuat 10 invoice secara bersamaan (concurrent)
2. Memverifikasi semua nomor invoice unik
3. Menguji idempotency key pada payments (duplikat ditolak)

### Simulate Webhook (curl)

#### Stripe Webhook Test

```bash
# Install Stripe CLI, lalu:
stripe listen --forward-to localhost/FakturKu/public/payments/webhook
stripe trigger checkout.session.completed
```

Atau manual dengan curl (tanpa signature verification):

```bash
curl -X POST http://localhost/FakturKu/public/payments/webhook \
  -H "Content-Type: application/json" \
  -d '{
    "type": "checkout.session.completed",
    "data": {
      "object": {
        "id": "cs_test_123",
        "amount_total": 1000000,
        "payment_method_types": ["card"],
        "metadata": {
          "invoice_id": "1",
          "invoice_number": "INV-2026/03-0001",
          "idempotency_key": "test_key_123"
        }
      }
    }
  }'
```

#### Midtrans Webhook Test

```bash
curl -X POST http://localhost/FakturKu/public/payments/webhook \
  -H "Content-Type: application/json" \
  -d '{
    "transaction_id": "txn_test_123",
    "order_id": "INV-2026/03-0001-abcd1234",
    "transaction_status": "settlement",
    "fraud_status": "accept",
    "status_code": "200",
    "gross_amount": "1000000.00",
    "payment_type": "bank_transfer",
    "signature_key": "<generated_sha512>"
  }'
```

Untuk generate signature Midtrans:
```
SHA512(order_id + status_code + gross_amount + server_key)
```

---

## Struktur Folder

```
FakturKu/
├── app/
│   ├── controllers/          # Controller (Dashboard, Client, Product, Invoice, Payment)
│   ├── core/                 # Database, Router, Controller, Model base classes
│   ├── helpers/              # number_helper.php (format, konversi)
│   ├── models/               # Client, Product, Invoice, InvoiceItem, Payment, Currency, ExchangeRate, AuditLog
│   ├── services/             # CurrencyService, InvoiceService, PaymentService, PdfService, MailService
│   └── views/                # Template HTML (dashboard, clients, products, invoices, payments)
├── config/
│   ├── app.php               # Konfigurasi utama + .env loader
│   ├── mail.php              # Config SMTP
│   └── payment.php           # Config payment gateway
├── cron/
│   └── fetch_rates.php       # Cron job: fetch exchange rates
├── migrations/
│   └── 001_init.sql          # Schema database + seed currencies
├── public/
│   ├── .htaccess             # URL rewriting
│   └── index.php             # Entry point + routing
├── storage/
│   └── invoices/             # Generated invoice HTML/PDF files
├── tests/
│   └── concurrency_test.php  # Test concurrent invoice numbering + idempotency
├── .env.example              # Template konfigurasi
├── .gitignore
└── README.md
```

---

## Alur Pembayaran Online

```
1. User buat Invoice (currency X)
   └─> Simpan exchange_rate X→BASE + total_in_base

2. User klik "Bayar Online"
   └─> POST /payments/checkout
       └─> Buat Stripe Checkout Session / Midtrans Snap
       └─> Simpan payment record (status: pending, idempotency_key)
       └─> Redirect ke halaman checkout gateway

3. User bayar di gateway
   └─> Gateway redirect ke /payments/success

4. Gateway kirim webhook POST /payments/webhook
   └─> Verify signature (Stripe/Midtrans)
   └─> Check idempotency (skip jika duplikat)
   └─> Update payment status → success
   └─> Update invoice amount_paid & status
   └─> Audit log

5. Jika webhook replay (duplikat)
   └─> Deteksi via provider_payment_id / idempotency_key
   └─> Return status: "duplicate" (200 OK, no action)
```

---

## Keamanan

- Webhook signature verification (HMAC untuk Stripe, SHA512 untuk Midtrans)
- Secret keys hanya di `.env` (tidak di-commit ke repo)
- HTTPS wajib untuk webhook di production
- Idempotency key pada setiap checkout + webhook
- UNIQUE constraint di database untuk mencegah duplikat
- Prepared statements (PDO) untuk mencegah SQL injection
- `htmlspecialchars()` untuk output (mencegah XSS)
- Atomic invoice numbering (INSERT ON DUPLICATE KEY + transaction)
- Audit log untuk semua perubahan status

---

## Lisensi

MIT License
