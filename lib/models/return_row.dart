class ReturnRow {
  final String returnNo;
  final String department;
  final String employee;
  final String category;       // customer category
  final String customer;
  final String phone;
  final String address;        // gabungan address_text / address_detail
  final String nominal;        // Rp x.xxx
  final String reason;
  final String notes;          // note / '-'
  final String productDetail;  // join dari products[]
  final String status;         // Disetujui/Ditolak/Pending/...
  final String createdAt;      // "dd/mm/yyyy"
  final String updatedAt;      // "dd/mm/yyyy"
  final String? imageUrl;      // optional
  final String? pdfUrl;        // ini yang dipakai tombol download

  ReturnRow({
    required this.returnNo,
    required this.department,
    required this.employee,
    required this.category,
    required this.customer,
    required this.phone,
    required this.address,
    required this.nominal,
    required this.reason,
    required this.notes,
    required this.productDetail,
    required this.status,
    required this.createdAt,
    required this.updatedAt,
    this.imageUrl,
    this.pdfUrl,
  });

  // --- helpers internal ---
  static String _s(dynamic v) {
    if (v == null) return '-';
    final s = '$v'.trim();
    return s.isEmpty ? '-' : s;
  }

  static String _rp(num? v) {
    final n = (v ?? 0).toInt();
    final s = n.toString().replaceAllMapped(
      RegExp(r'(\d)(?=(\d{3})+(?!\d))'),
      (m) => '${m[1]}.',
    );
    return 'Rp $s';
  }

  static String _addr(Map<String, dynamic> j) {
    // coba address_text dulu
    final t = _s(j['address_text']);
    if (t != '-' && t.isNotEmpty) return t;

    // fallback address_detail: [ .. ] => gabung
    final detail = j['address_detail'];
    if (detail is List && detail.isNotEmpty) {
      final parts = detail
          .map((e) {
            if (e is Map) {
              // detail_alamat, kelurahan/kecamatan/kota_kab/provinsi -> name
              final d = _s(e['detail_alamat']);
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
    // Format: Brand-Category-Product-Color-Qty:10
    if (prods.isEmpty) return '-';
    return prods.map((p) {
      final brand = _s(p['brand']);
      final cat   = _s(p['category']);
      final prod  = _s(p['product']);
      final col   = _s(p['color']);
      final qty   = p['quantity'] ?? 0;
      return '$brand-$cat-$prod-$col-Qty:$qty';
    }).join('\n');
  }

  factory ReturnRow.fromJson(Map<String, dynamic> j) {
    final products = (j['products'] is List) ? (j['products'] as List) : const [];

    // nominal (amount) bisa num/string
    String nominal;
    if (j['amount'] is num) {
      nominal = _rp(j['amount'] as num);
    } else {
      // kalau API sudah format Rp dari server
      nominal = _s(j['amount']);
      if (!nominal.startsWith('Rp ')) {
        // coba parse manual kalau string angka murni
        final numVal = num.tryParse(nominal.replaceAll('.', '').replaceAll(',', '.'));
        nominal = numVal != null ? _rp(numVal) : nominal;
      }
    }

    return ReturnRow(
      returnNo: _s(j['no_return']),
      department: _s(j['department']),
      employee: _s(j['employee']),
      category: _s(j['customer_category']),
      customer: _s(j['customer']),
      phone: _s(j['phone']),
      address: _addr(j),
      nominal: nominal,
      reason: _s(j['reason']),
      notes: _s(j['note']),
      productDetail: _joinProducts(products),
      status: _s(j['status']),
      createdAt: _s(j['created_at']),
      updatedAt: _s(j['updated_at']),
     imageUrl: j['image']?.toString(),  
      pdfUrl: j['file_pdf_url']?.toString(), // penting: mapping ke pdfUrl
    );
  }
}
