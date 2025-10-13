<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Order {{ $order->no_order }}</title>
    <style>
        /* Reset sederhana */
        body, h1, h2, h3, p, table, th, td, div, span {
            margin: 0;
            padding: 0;
        }
        body {
            font-family: Arial, sans-serif;
            color: #333;
            padding: 0 40px;
            font-size: 12px;
            line-height: 1.4;
        }

        /* HEADER dengan tabel 2 kolom */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .header-left, .header-right {
            vertical-align: middle;
            padding: 0;
        }
        .header-left {
            width: 60%;
        }
        .header-right {
            width: 40%;
            text-align: right;
        }
        .company-left h2 {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .company-left p {
            font-size: 12px;
            color: #555;
        }
        /* Logo diperbesar supaya proporsional */
        .logo-nano {
            height: 120px;
            display: inline-block;
            margin-bottom: 8px;
        }
        .order-info p {
            margin: 2px 0;
            font-size: 12px;
            color: #333;
        }

        /* BAGIAN “BILL TO” */
        .bill-to {
            margin-top: 50px; /* beri jarak vertikal lebih besar */
        }
        .bill-to h3 {
            font-size: 14px;
            margin-bottom: 6px;
        }
        .bill-to p {
            margin: 2px 0;
            font-size: 12px;
        }

        /* BAGIAN “SALES & TOKO” */
        .sales {
            margin-top: 20px;
        }
        .sales h3 {
            font-size: 14px;
            margin-bottom: 6px;
        }
        .sales p {
            margin: 2px 0;
            font-size: 12px;
        }

        /* TABEL DAFTAR PRODUK */
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 12px;
        }
        table.items thead th {
            background: #f5f5f5;
            border-bottom: 2px solid #555;
            padding: 8px;
            text-align: left;
        }
        table.items tbody td {
            border-bottom: 1px solid #ddd;
            padding: 8px;
        }
        .text-right {
            text-align: right;
        }

        /* BAGIAN INFORMASI PEMBAYARAN & TOTALS dalam satu baris */
        .info-totals-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .info-cell {
            vertical-align: top;
            width: 60%;
            /* Di sini kita atur agar "Informasi Pembayaran & Pesanan" turun
               sampai sejajar dengan baris "Diskon 2" di tabel totals. */
            padding-top: 115px;
            padding-right: 20px;
        }
        .totals-cell {
            vertical-align: top;
            width: 40%;
            text-align: right;
        }

        /* STYLE untuk BAGIAN INFORMASI PEMBAYARAN & PESANAN */
        .order-info {
            margin: 0; /* margin diatur oleh sel tabel */
        }
        .order-info h3 {
            font-size: 14px;
            margin-bottom: 6px;
        }
        .order-info p {
            margin: 2px 0;
            font-size: 12px;
        }

        /* STYLE untuk TABEL TOTALS */
        .totals {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        .totals th, .totals td {
            padding: 6px;
        }
        .totals th {
            text-align: left;
            border: none;
        }
        .totals td {
            text-align: right;
            border: none;
        }
        .totals .no-border {
            border: none !important;
        }
        .totals .line-strong {
            border-top: 1px solid #333;
        }

        /* FOOTER TERIMA KASIH */
        footer {
            clear: both;
            margin-top: 80px;
            font-size: 11px;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>

    {{-- HEADER: gunakan tabel agar vertikal-align bekerja --}}
    <table class="header-table">
        <tr>
            {{-- Kiri: Nama PT & Alamat --}}
            <td class="header-left">
                <div class="company-left">
                    <h2>PT. Berdikari Inti Gemilang</h2>
                    <p>Jl. Contoh No.123, Jakarta • Telp: (021) 1234 5678</p>
                </div>
            </td>

            {{-- Kanan: Logo + Info order --}}
            <td class="header-right">
                <!-- Karena ini PDF, pakai public_path() supaya Dompdf bisa baca file fisik -->
                <img src="{{ public_path('assets/image/logo-invoice.png') }}" class="logo-nano" alt="Logo Nanolite">
                <div class="order-info">
                    <p><strong>No Order#</strong> {{ $order->no_order }}</p>
                    <p><strong>Date </strong> {{ $order->created_at->format('d/m/Y') }}</p>
                </div>
            </td>
        </tr>
    </table>

    {{-- BILL TO --}}
    <section class="bill-to">
    <h3>Bill To:</h3>
    <p><strong>{{ $order->customer->name }}</strong></p>
    <p>Kategori Customer: {{ $order->customerCategory->name ?? '-' }}</p>
    @if(is_array($order->address))
        @foreach($order->address as $addr)
            <p>
                {{ $addr['detail_alamat'] ?? '-' }},
                {{ $addr['kelurahan'] ?? '-' }},
                {{ $addr['kecamatan'] ?? '-' }},
                {{ $addr['kota_kab'] ?? '-' }},
                {{ $addr['provinsi'] ?? '-' }},
                {{ $addr['kode_pos'] ?? '-' }}
            </p>
        @endforeach
    @else
        <p>Alamat: {{ $order->address }}</p>
    @endif
    <p>Telp: {{ $order->phone }}</p>
</section>


    {{-- SALES & TOKO --}}
    <section class="sales">
        <h3>Informasi Sales & Toko</h3>
        <p>Karyawan: {{ $order->employee->name }}</p>
        <p>Telp Sales: {{ $order->employee->phone }}</p>
        
    </section>

    {{-- TABEL DAFTAR PRODUK --}}
    <table class="items">
        <thead>
            <tr>
                <th>Brand</th>
                <th>Kategori</th>
                <th>Produk</th>
                <th>Warna</th>
                <th>Pcs</th>
                <th>Harga</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->productsWithDetails() as $item)
                <tr>
                    <td>{{ $item['brand_name'] }}</td>
                    <td>{{ $item['category_name'] }}</td>
                    <td>{{ $item['product_name'] }}</td>
                    <td>{{ $item['color'] }}</td>
                    <td>{{ $item['quantity'] }}</td>
                    <td>Rp {{ number_format($item['price'], 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- BARIS INFORMASI PEMBAYARAN (KIRI) DAN TABEL TOTALS (KANAN) --}}
    <table class="info-totals-table">
        <tr>
            {{-- SEL KIRI: Informasi Pembayaran & Pesanan --}}
            <td class="info-cell">
                <section class="order-info">
                    <h3>Informasi Pembayaran & Pesanan</h3>
                    <p>Metode Pembayaran: {{ ucfirst($order->payment_method) }}</p>
                    <p>Status Pembayaran: {{ ucfirst($order->status_pembayaran) }}</p>
                    <p>Program: {{ $order->customerProgram?->name ?: '-' }}</p>
                    <p>Status Pesanan: {{ ucfirst($order->status) }}</p>
                </section>
            </td>

            {{-- SEL KANAN: Tabel Totals --}}
            <td class="totals-cell">
                <table class="totals">
                    <tr>
                        <th>Subtotal</th>
                        <td>Rp {{ number_format($order->total_harga, 0, ',', '.') }}</td>
                    </tr>
                    @if($order->diskons_enabled)
                        <tr>
                            <th>Diskon ({{ $order->diskon_1 }}%)</th>
                            <td>- Rp {{ number_format(($order->diskon_1/100) * $order->total_harga, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <th>Penjelasan Diskon ({{ $order->diskon_1 }}%)</th>
                            <td>{{ $order->penjelasan_diskon_1 }}</td>
                        </tr>
                        <tr>
                            <th>Diskon ({{ $order->diskon_2 }}%)</th>
                            <td>- Rp {{ number_format(($order->diskon_2/100) * $order->total_harga, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <th>Penjelasan Diskon ({{ $order->diskon_2 }}%)</th>
                            <td>{{ $order->penjelasan_diskon_2 }}</td>
                        </tr>
                    @endif

                    @if($order->reward_enabled)
                        <tr>
                            <th>Reward Point:</th>
                            <td>{{ $order->reward_point ?? '-' }}</td>
                        </tr>
                    @endif

                    @if($order->program_enabled)
                        <tr>
                            <th>Program Point:</th>
                            <td>{{ $order->jumlah_program ?? '-' }}</td>
                        </tr>
                    @endif
                    
                    <tr>
                        <th class="line-strong"><strong>Grand Total</strong></th>
                        <td class="line-strong">
                            <strong>Rp {{ number_format($order->total_harga_after_tax, 0, ',', '.') }}</strong>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- FOOTER --}}
    <footer>
        <p>• Terima kasih atas kepercayaan dan kerjasama Anda. •</p>
        <p>#untungpakainanolite #murahbergaransi</p>
    </footer>
</body>
</html>
 