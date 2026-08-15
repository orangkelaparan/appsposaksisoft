# Audit Revisi AksiSoft POS

**Tanggal audit:** 15 Agustus 2026  
**Dasar pembandingan:** `appsposaksisoft.md` dan source code aplikasi saat ini.

## Ringkasan

Aplikasi saat ini memiliki **inti POS, pembelian, stok, register, audit, autentikasi, dan RBAC yang berfungsi**. Namun, audit source code mengonfirmasi bahwa sejumlah kebutuhan besar master spesifikasi masih berupa **struktur basis data saja** atau belum memiliki rute, pengendali, layanan, maupun antarmuka operasional. Dengan demikian, dokumen revisi benar dalam substansi, tetapi terdapat beberapa nuansa penting: arsitektur multi-outlet, varian, customer group, pajak, dan expense **sudah memiliki basis skema**, meskipun belum menjadi modul bisnis yang dapat digunakan penuh.

| Area | Status aktual | Bukti source code |
| --- | --- | --- |
| Autentikasi, rate limit, audit, RBAC | **Fungsional** | Rute login/logout, middleware autentikasi/izin, `AuditService`, tabel roles/permissions. |
| POS inti, pembayaran tunggal, return, receipt | **Fungsional** | `PosController`, `SaleService`, layar `pos/index`; checkout dibungkus transaksi basis data. |
| Katalog, pelanggan, pemasok, PO, receiving, ledger stok | **Fungsional pada cakupan dasar** | `BackOfficeController`, `PurchaseService`, `InventoryService`. |
| Multi-store/outlet | **Skema tersedia, operasi belum multi-outlet** | Tabel `companies`, `stores`, `warehouses`, `registers`, `user_stores` tersedia; POS dan purchasing memilih store/warehouse aktif pertama. |
| Varian produk | **Skema dan data demo tersedia, belum terintegrasi** | Tabel `product_variants` dan data seeder ada; tidak ada rute/UI/POS/inventory per-varian. |
| Bundle dan produk timbang | **Belum tersedia** | `product_type` dibuat sebagai `simple` pada pembuatan produk dan tidak diproses dalam layanan POS. |
| Import/export produk | **Belum tersedia** | Tidak ada rute, controller, validasi preview, maupun layanan impor/ekspor. |
| Transfer stok dan stocktake | **Belum tersedia** | Tidak ada tabel workflow, rute, service, atau view transfer/count. |
| Customer group, price list, loyalty | **Sebagian skema tersedia, mesin bisnis belum** | Group dapat dipilih pada pelanggan; `default_discount`, `points`, dan `wholesale_price` tidak dipakai pada checkout. |
| Diskon lanjutan dan promosi | **Belum tersedia** | Checkout hanya menerima `discount_total` tetap di tingkat cart; tidak ada rules/promotions service. |
| Pajak dan pembulatan | **Skema tersedia, perhitungan belum** | Tabel `taxes` memiliki flag inclusive; `SaleService` menetapkan pajak dan rounding ke nol. |
| Quotation dan sales order | **Belum tersedia** | Tidak ada tabel/rute/controller/view dokumen tersebut. |
| Expense management | **Tabel dan demo tersedia, modul belum** | Tabel `expenses` dan seeded sample ada; tidak ada controller/rute/UI/approval. |
| Notifikasi | **Belum tersedia** | Tidak ada notification channel, tabel Laravel notifications, atau aturan low-stock/approval. |
| Invoice A4 dan PDF | **Belum tersedia** | Hanya view receipt termal tersedia. |
| REST API lengkap | **Terbatas** | Hanya endpoint pencarian produk dan checkout POS yang ada di `/api/v1/`. |
| 2FA dan installation wizard | **Belum tersedia** | Tidak ada rute, middleware, secret storage, wizard, atau setup state. |

## Temuan Teknis Penting

`SaleService` menjaga atomisitas checkout, locking produk, ledger stok, return, payment, dan audit. Namun layanan ini belum menghitung pajak, pembulatan, diskon berbasis customer group, harga bertingkat, promosi, loyalty, maupun split payment. `BackOfficeController` menyediakan produk sederhana, kontak, PO, receiving, stok, laporan, register, dan administrasi, tetapi belum menyediakan alur expense, transfer, stock count, quotation, sales order, import/export, dan approval workflow yang diperlukan master spesifikasi.

## Urutan Implementasi yang Direkomendasikan

Revisi sebaiknya diprioritaskan menurut integritas transaksi dan fondasi bisnis, bukan hanya menambah layar. Paket pertama yang paling bernilai adalah **multi-outlet operasional, transfer stok, stocktake, expense management, dan API terstruktur**. Paket berikutnya adalah **varian/bundle/weighted product, price list/customer group/loyalty, serta promotion-discount-tax-rounding engine**. Paket terakhir menangani **quotation/sales order, invoice A4/PDF, notifikasi, 2FA, import/export, dan installer**.

> **Catatan:** Audit ini memverifikasi source code yang ada, bukan hanya klaim README. Kesenjangan di atas harus ditutup melalui migrasi, layanan bisnis, izin, route, UI, pengujian, dan deployment secara bertahap.
