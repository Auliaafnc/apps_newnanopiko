// lib/models/garansi_row.dart
class GaransiRow {
  final int? id; 
  final String garansiNo;      // no_garansi / warranty_no / no
  final String department;
  final String employee;
  final String category;       // customer category
  final String customer;
  final String phone;
  final String address;        // gabungan address_text / address_detail
  final String reason;         // alasan
  final String notes;          // note / '-'
  final String productDetail;  // join dari products[]
  // === Status terbaru ===
  final String statusPengajuan; // Disetujui/Ditolak/Pending/... (string dari API)
  final String statusProduk;    // status produk (mis. Diterima/Proses/...)
  final String statusGaransi;   // status garansi (mis. Aktif/Kadaluarsa/...)
  final String batasHold;       // batas hold (tanggal/ISO/teks)
  final String alasanHold;      // alasan di-hold (teks)
  // === Tanggal ===
  final String createdAt;       // "dd/mm/yyyy" / ISO
  final String updatedAt;       // "dd/mm/yyyy" / ISO
  final String purchaseDate;    // purchase_date / tanggal_pembelian
  final String claimDate;       // claim_date / tanggal_klaim

  // === Tambahan: semua gambar ===
  final List<String> imageUrls;         // kumpulan semua foto utama (image/images/photos/...)
  final List<String> deliveryImageUrls; // kumpulan semua foto pengiriman (delivery_images/...)

  // === Kompatibilitas dgn ReturnRow lama ===
  final String? imageUrl;      // tetap ada: isi dari imageUrls.first kalau ada
  final String? pdfUrl;        // pakai key "file_pdf_url" saja (seperti ReturnRow)

  GaransiRow({
    this.id,
    required this.garansiNo,
    required this.department,
    required this.employee,
    required this.category,
    required this.customer,
    required this.phone,
    required this.address,
    required this.reason,
    required this.notes,
    required this.productDetail,
    required this.statusPengajuan,
    required this.statusProduk,
    required this.statusGaransi,
    required this.batasHold,
    required this.alasanHold,
    required this.createdAt,
    required this.updatedAt,
    required this.purchaseDate,
    required this.claimDate,
    this.imageUrls = const [],
    this.deliveryImageUrls = const [],
    this.imageUrl,
    this.pdfUrl,
  });

  // ---- helpers (sama gaya ReturnRow) ----
  static String _s(dynamic v) {
    if (v == null) return '-';
    final s = '$v'.trim();
    return s.isEmpty ? '-' : s;
  }

    // Tambah helper ini (opsional, bisa bareng helpers lain)
  static int? _toInt(dynamic v) {
    if (v == null) return null;
    if (v is int) return v;
    return int.tryParse(v.toString());
  }

  // taruh di dalam class GaransiRow, dekat helper lain
static int? _firstInt(Map j, List keys) {
  for (final k in keys) {
    final v = (k is List && k.length == 2)
        ? (j[k[0]] is Map ? j[k[0]][k[1]] : null)
        : j[k];
    if (v == null) continue;
    if (v is int) return v;
    final p = int.tryParse(v.toString());
    if (p != null) return p;
  }
  return null;
}

static int? _digitsFrom(String s) {
  final only = RegExp(r'\d+').allMatches(s).map((m) => m.group(0)!).join();
  return only.isEmpty ? null : int.tryParse(only);
}



  // Perbarui _addr agar dukung lebih banyak bentuk
static String _addr(Map<String, dynamic> j) {
  final t = _s(j['address_text']);
  if (t != '-' && t.isNotEmpty) return t;

  // address (string langsung)
  final addrStr = _s(j['address']);
  if (addrStr != '-' && addrStr.isNotEmpty) return addrStr;

  // address_detail / alamat_detail (array)
  final detail = j['address_detail'] ?? j['alamat_detail'];
  if (detail is List && detail.isNotEmpty) {
    final parts = detail
        .map((e) {
          if (e is Map) {
            final d   = _s(e['detail_alamat']);
            final kel = _s((e['kelurahan'] is Map) ? e['kelurahan']['name'] : e['kelurahan_name']);
            final kec = _s((e['kecamatan'] is Map) ? e['kecamatan']['name'] : e['kecamatan_name']);
            final kab = _s((e['kota_kab']  is Map) ? e['kota_kab']['name']  : e['kota_kab_name']);
            final prv = _s((e['provinsi']  is Map) ? e['provinsi']['name']  : e['provinsi_name']);
            final kp  = _s(e['kode_pos']);
            return [d, kel, kec, kab, prv, kp]
                .where((x) => x != '-' && x.isNotEmpty)
                .join(', ');
          }
          return _s(e);
        })
        .where((x) => x != '-' && x.isNotEmpty)
        .toList();
    return parts.isEmpty ? '-' : parts.join(' | ');
  }
  return '-';
}


  // Perbarui _joinProducts agar qty aman
static String _joinProducts(List prods) {
  if (prods.isEmpty) return '-';
  String qStr(dynamic v) {
    if (v == null) return '0';
    if (v is num) return v.toString();
    final p = num.tryParse(v.toString());
    return p?.toString() ?? '0';
  }

  return prods.map((p) {
    final brand = _s(p['brand']);
    final cat   = _s(p['category']);
    final prod  = _s(p['product']);
    final col   = _s(p['color']);
    final qty   = qStr(p['quantity'] ?? p['qty']);
    return '$brand-$cat-$prod-$col-Qty:$qty';
  }).join('\n');
}


  // ---- helpers untuk kumpulkan SEMUA foto ----
  static List<String> _asStringList(dynamic v) {
    if (v == null) return const [];
    if (v is String) return v.trim().isEmpty ? const [] : [v.trim()];
    if (v is List) {
      return v
          .map((e) {
            if (e == null) return null;
            if (e is String) return e.trim();
            if (e is Map && e['url'] != null) return e['url'].toString();
            return e.toString();
          })
          .whereType<String>()
          .where((e) => e.isNotEmpty)
          .toList();
    }
    return [v.toString()];
  }

  static List<String> _collectStrings(Map j, List<String> keys) {
    final out = <String>[];
    for (final k in keys) {
      out.addAll(_asStringList(j[k]));
    }
    // de-duplicate sambil pertahankan urutan
    final seen = <String>{};
    final dedup = <String>[];
    for (final s in out) {
      if (s.isEmpty) continue;
      if (seen.add(s)) dedup.add(s);
    }
    return dedup;
  }

  // ---- mapper (diseragamkan dengan ReturnRow) ----
  factory GaransiRow.fromJson(Map<String, dynamic> j) {
  // ================= ID =================
  // jelajahi banyak kemungkinan key
  final id = _firstInt(j, [
    'id',
    'garansi_id', 'warranty_id', 'warranty_claim_id', 'claim_id',
    // nested:
    ['garansi','id'], ['warranty','id'], ['data','id'], ['pivot','garansi_id'],
  ]) ?? _digitsFrom(_s(j['no_garansi']) != '-' ? _s(j['no_garansi'])
                 : _s(j['warranty_no']) != '-' ? _s(j['warranty_no'])
                 : _s(j['no'])); // fallback terakhir: ambil angka dari nomor

  // ===== nomor garansi (punyamu sudah bagus) =====
  final garansiNo = (() {
    final a = _s(j['no_garansi']); if (a != '-') return a;
    final b = _s(j['warranty_no']); if (b != '-') return b;
    final c = _s(j['no']);          if (c != '-') return c;
    return '-';
  })();

  // ... (kode lain tetap) ...


  final address = _addr(j);

  String productDetail;
  if (j['products'] is List) {
    productDetail = _joinProducts(j['products'] as List);
  } else {
    productDetail = _s(j['products_details']);
  }

  final images = _collectStrings(j, ['image','images','image_urls','photos','garansi_images']);
  final deliveryImages = _collectStrings(j, ['delivery_images','delivery_image','delivery_photos','shipping_images','pengiriman_images']);
  final pdf = j['file_pdf_url']?.toString();

  final statusPengajuan = (() {
    final a = _s(j['status_pengajuan'] ?? j['statusPengajuan']);
    if (a != '-') return a;
    return _s(j['status']); // legacy
  })();

  final statusProduk  = _s(j['status_produk']  ?? j['statusProduk']  ?? j['product_status'] ?? j['status_product'] ?? j['productStatus']);
  final statusGaransi = _s(j['status_garansi'] ?? j['statusGaransi'] ?? j['warranty_status']);
  final batasHold     = _s(j['batas_hold']     ?? j['batasHold']     ?? j['hold_until'] ?? j['hold_limit'] ?? j['hold_deadline']);
  final alasanHold    = _s(j['alasan_hold']    ?? j['alasanHold']    ?? j['hold_reason']);

  return GaransiRow(
    id: id, // <<<<<<<<<<<<<<<<<<<<<< penting
    garansiNo: garansiNo,
    department: _s(j['department']),
    employee: _s(j['employee']),
    category: _s(j['customer_category'] ?? j['customerCategory']),
    customer: _s(j['customer']),
    phone: _s(j['phone']),
    address: address,
    reason: _s(j['reason']),
    notes: _s(j['note'] ?? j['notes']),
    productDetail: productDetail,
    statusPengajuan: statusPengajuan,
    statusProduk: statusProduk,
    statusGaransi: statusGaransi,
    batasHold: batasHold,
    alasanHold: alasanHold,
    createdAt: _s(j['created_at'] ?? j['createdAt']),
    updatedAt: _s(j['updated_at'] ?? j['updatedAt']),
    purchaseDate: _s(j['purchase_date'] ?? j['tanggal_pembelian']),
    claimDate: _s(j['claim_date'] ?? j['tanggal_klaim']),
    imageUrls: images,
    deliveryImageUrls: deliveryImages,
    imageUrl: images.isNotEmpty
    ? images.first
    : (j['image'] is String ? j['image'] as String : null),
    pdfUrl: pdf,
  );
}

}
