<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Return – {{ $return->customer->name }}</title>
    <style>
        /* [style tetap seperti aslinya, tidak diubah] */
        body, h1, h2, h3, p, table, th, td, div, span { margin: 0; padding: 0; }
        body {
            font-family: Arial, sans-serif;
            color: #333;
            padding: 0 40px;
            font-size: 12px;
            line-height: 1.4;
        }
        .header-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .header-left, .header-right { vertical-align: middle; padding: 0; }
        .header-left { width: 60%; }
        .header-right { width: 40%; text-align: right; }
        .company-left h2 { font-size: 22px; font-weight: bold; margin-bottom: 4px; }
        .company-left p { font-size: 12px; color: #555; }
        .logo-nano { height: 120px; display: inline-block; margin-bottom: 8px; }
        .return-info p { margin: 2px 0; font-size: 12px; color: #333; }

        .bill-to { margin-top: 50px; }
        .bill-to h3 { font-size: 14px; margin-bottom: 6px; }
        .bill-to p { margin: 2px 0; font-size: 12px; }

        .sales { margin-top: 20px; }
        .sales h3 { font-size: 14px; margin-bottom: 6px; }
        .sales p { margin: 2px 0; font-size: 12px; }

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
        .text-right { text-align: right; }

        
         /* STYLE untuk BAGIAN INFORMASI PEMBAYARAN & PESANAN */
        .info-totals-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .info-cell {
            vertical-align: top;
            width: 60%;
            padding-top: 20px; /* dari 115px -> 20px */
            padding-right: 20px;
        }

         .return-info {
            margin: 0; /* margin diatur oleh sel tabel */
        }
        .return-info h3 {
            font-size: 14px;
            margin-bottom: 6px;
        }
        .return-info p {
            margin: 2px 0;
            font-size: 12px;
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

    {{-- HEADER --}}
    <table class="header-table">
        <tr>
            <td class="header-left">
                <div class="company-left">
                    <h2>PT. Berdikari Inti Gemilang</h2>
                    <p>Jl. Contoh No.123, Jakarta • Telp: (021) 1234 5678</p>
                </div>
            </td>
            <td class="header-right">
                <img src="{{ public_path('assets/image/logo-invoice.png') }}" class="logo-nano" alt="Logo Nanolite">
                <div class="return-info">
                     <p><strong>No Return#</strong> {{ $return->no_return }}</p>
                    <p><strong>Date:</strong> {{ $return->created_at->format('d/m/Y') }}</p>
                </div>
            </td>
        </tr>
    </table>

    {{-- BILL TO --}}
    <section class="bill-to">
        <h3>To:</h3>
        <p><strong>{{ $return->customer->name }}</strong></p>
        <p>Kategori Customer: {{ $return->category->name ?? '-' }}</p>
        @if(is_array($return->address))
            @foreach($return->address as $addr)
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
            <p>Alamat: {{ $return->address }}</p>
        @endif
            <p>Telp: {{ $return->phone ?? '-' }}</p>
    </section>

    


    {{-- SALES & CABANG --}}
    <section class="sales">
        <h3>Informasi Sales & Toko</h3>
        <p>Karyawan: {{ $return->employee->name ?? '-' }}</p>
        <p>Telp Sales: {{ $return->employee->phone ?? '-' }}</p>
    </section>

    {{-- PRODUK --}}
    <table class="items">
        <thead>
            <tr>
                <th>Brand</th>
                <th>Kategori</th>
                <th>Produk</th>
                <th>Warna</th>
                <th>Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @foreach($return->productsWithDetails() as $item)
                <tr>
                    <td>{{ $item['brand_name'] }}</td>
                    <td>{{ $item['category_name'] }}</td>
                    <td>{{ $item['product_name'] }}</td>
                    <td>{{ $item['color'] }}</td>
                    <td>{{ $item['quantity'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    
    {{-- BARIS INFORMASI PEMBAYARAN (KIRI) DAN TABEL TOTALS (KANAN) --}}
    <table class="info-totals-table">
        <tr>
            {{-- SEL KIRI: Informasi Pembayaran & Pesanan --}}
            <td class="info-cell">
                <section class="return-info">
                    <h3>Informasi Return</h3>
                    <p><strong>Alasan Return: {{ $return->reason }}</strong></p>
                    <p><strong>Nominal: Rp {{ number_format($return->amount, 0, ',', '.') }}</strong></p>
                    <p>Catatan Tambahan: {{ $return->note ?? '-' }}</p>
                    <p>Status Pengajuan: {{ ucfirst($return->status ?? 'pending') }}</p>
                </section>
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
