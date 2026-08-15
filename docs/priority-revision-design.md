# Rancangan Paket Operasional Prioritas

## Prinsip Implementasi

Paket ini memperluas aplikasi tanpa mengubah atau menghapus transaksi historis. Semua pergerakan stok tetap dibukukan melalui `InventoryService::move()` dalam transaksi basis data, menggunakan row locking yang telah ada. Hak akses server-side akan memakai permission granular baru. Semua nilai moneter tetap disimpan sebagai decimal.

## Modul dan Alur

| Modul | Struktur data | Alur bisnis | Izin utama |
| --- | --- | --- | --- |
| Multi-outlet aktif | Penetapan store aktif di sesi, daftar outlet/gudang per pengguna | Pengguna memilih outlet aktif dari header; POS, stok, PO, dan dokumen baru disaring pada outlet tersebut | `stores.switch` |
| Transfer stok | `stock_transfers`, `stock_transfer_items` | Draft → Approved → Shipped → Received; pengiriman membuat `transfer_out`, penerimaan membuat `transfer_in` | `inventory.transfer` |
| Stocktake | `stock_counts`, `stock_count_items` | Draft → Counted → Approved; approval menghasilkan ledger `stock_count` untuk setiap selisih | `inventory.count` |
| Expense | Tabel `expenses` yang sudah ada | Catat biaya, filter per outlet, pembaruan kas register untuk biaya tunai | `expenses.view`, `expenses.create`, `expenses.approve` |
| Import/export CSV | Tanpa tabel tambahan; import langsung terverifikasi | Unduh template/ekspor katalog; preview CSV tervalidasi; commit menolak SKU/barcode duplikat | `products.import`, `products.export` |
| Quotation & sales order | `quotations`, `quotation_items`, `sales_orders`, `sales_order_items` | Draft quotation → accepted → convert order; order dapat dikonversi ke POS manual | `sales.quote`, `sales.order` |

## Batas Operasional

Transfer stok mengurangi stok sumber hanya saat dikirim dan menambah stok tujuan hanya saat diterima. Stocktake memakai snapshot kuantitas sistem sebagai referensi, menyimpan kuantitas fisik, dan menghitung penyesuaian hanya setelah approval. Expense tunai dicatat pula sebagai cash movement bila memiliki register session aktif.

Outlet aktif tersimpan pada session sehingga tidak memerlukan perubahan identitas login. Aplikasi tetap menampilkan fallback outlet pertama yang diizinkan bila sesi belum memilih outlet.

## Data Dummy

Seeder revisi akan menambahkan outlet kedua, gudang kedua, register kedua, penugasan pengguna lintas outlet, transfer dengan status historis, stock count dengan selisih terkontrol, expense berstatus approved, quotation, sales order, serta produk CSV contoh. Data akan memakai `updateOrInsert` atau nomor dokumen unik agar aman dijalankan ulang.
