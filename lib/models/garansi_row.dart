// lib/models/garansi_row.dart
class GaransiRow {
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
  final String status;         // Disetujui/Ditolak/Pending/... (string dari API)
  final String createdAt;      // "dd/mm/yyyy" / ISO
  final String updatedAt;      // "dd/mm/yyyy" / ISO
  final String purchaseDate;   // purchase_date / tanggal_pembelian
  final String claimDate;      // claim_date / tanggal_klaim

  // === Disamakan dengan ReturnRow ===
  final String? imageUrl;      // pakai key "image" saja (seperti ReturnRow)
  final String? pdfUrl;        // pakai key "file_pdf_url" saja (seperti ReturnRow)

  GaransiRow({
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
    required this.status,
    required this.createdAt,
    required this.updatedAt,
    required this.purchaseDate,
    required this.claimDate,
    this.imageUrl,
    this.pdfUrl,
  });

  // ---- helpers (sama gaya ReturnRow) ----
  static String _s(dynamic v) {
    if (v == null) return '-';
    final s = '$v'.trim();
    return s.isEmpty ? '-' : s;
  }

  static String _addr(Map<String, dynamic> j) {
    final t = _s(j['address_text']);
    if (t != '-' && t.isNotEmpty) return t;

    final detail = j['address_detail'];
    if (detail is List && detail.isNotEmpty) {
      final parts = detail
          .map((e) {
            if (e is Map) {
              final d   = _s(e['detail_alamat']);
              final kel = _s((e['kelurahan'] is Map) ? e['kelurahan']['name'] : null);
              final kec = _s((e['kecamatan'] is Map) ? e['kecamatan']['name'] : null);
              final kab = _s((e['kota_kab'] is Map) ? e['kota_kab']['name'] : null);
              final prv = _s((e['provinsi'] is Map) ? e['provinsi']['name'] : null);
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

  static String _joinProducts(List prods) {
    if (prods.isEmpty) return '-';
    return prods.map((p) {
      final brand = _s(p['brand']);
      final cat   = _s(p['category']);
      final prod  = _s(p['product']);
      final col   = _s(p['color']);
      final qty   = p['quantity'] ?? p['qty'] ?? 0;
      return '$brand-$cat-$prod-$col-Qty:$qty';
    }).join('\n');
  }

  // ---- mapper (diseragamkan dengan ReturnRow) ----
  factory GaransiRow.fromJson(Map<String, dynamic> j) {
    // nomor garansi fleksibel
    final garansiNo = () {
      final a = _s(j['no_garansi']);
      if (a != '-') return a;
      final b = _s(j['warranty_no']);
      if (b != '-') return b;
      final c = _s(j['no']);
      if (c != '-') return c;
      return '-';
    }();

    // address
    final address = _addr(j);

    // products -> join; fallback ke products_details (string dari server)
    String productDetail;
    if (j['products'] is List) {
      productDetail = _joinProducts(j['products'] as List);
    } else {
      productDetail = _s(j['products_details']);
    }

    return GaransiRow(
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
      status: _s(j['status']),
      createdAt: _s(j['created_at']),
      updatedAt: _s(j['updated_at']),
      purchaseDate: _s(j['purchase_date'] ?? j['tanggal_pembelian']),
      claimDate: _s(j['claim_date'] ?? j['tanggal_klaim']),

      // === persis ReturnRow ===
      imageUrl: j['image']?.toString(),
      pdfUrl: j['file_pdf_url']?.toString(),
    );
  }
}
