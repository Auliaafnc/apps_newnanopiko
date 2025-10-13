class OrderRow {
  final String orderNo;
  final String department;
  final String employee;
  final String category;
  final String customer;
  final String phone;
  final String address;        // gabungan address_text / address_detail
  final String totalAwal;      // Rp xxx
  final String diskon;         // 10% + 5%
  final String reasonDiskon;   // penjelasan diskon
  final String programName;
  final String programPoint;
  final String rewardPoint;
  final String totalAkhir;     // Rp xxx
  final String metodePembayaran;
  final String statusPembayaran;
  final String status;
  final String productDetail;  // join dari products[]
  final String createdAt;
  final String updatedAt;
  final String? invoicePdfUrl;

  OrderRow({
    required this.orderNo,
    required this.department,
    required this.employee,
    required this.category,
    required this.customer,
    required this.phone,
    required this.address,
    required this.totalAwal,
    required this.diskon,
    required this.reasonDiskon,
    required this.programName,
    required this.programPoint,
    required this.rewardPoint,
    required this.totalAkhir,
    required this.metodePembayaran,
    required this.statusPembayaran,
    required this.status,
    required this.productDetail,
    required this.createdAt,
    required this.updatedAt,
    this.invoicePdfUrl,
  });

  // --- helpers internal (sama gaya ReturnRow) ---
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
    final t = _s(j['address_text']);
    if (t != '-' && t.isNotEmpty) return t;

    final detail = j['address_detail'];
    if (detail is List && detail.isNotEmpty) {
      final parts = detail.map((e) {
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
      }).where((x) => x != '-').toList();
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
      final qty   = p['quantity'] ?? 0;
      final price = p['price'] ?? 0;
      return '$brand-$cat-$prod-$col-Rp$price-Qty:$qty';
    }).join('\n');
  }

  static String _combineDiskon(Map? d) {
    if (d == null) return '-';
    final enabled = d['enabled'] == true;
    final a = double.tryParse('${d['diskon_1'] ?? 0}') ?? 0;
    final b = double.tryParse('${d['diskon_2'] ?? 0}') ?? 0;
    if (!enabled || (a == 0 && b == 0)) return '-';

    final parts = <String>[];
    if (a > 0) parts.add('${a.toStringAsFixed(a.truncateToDouble() == a ? 0 : 1)}%');
    if (b > 0) parts.add('${b.toStringAsFixed(b.truncateToDouble() == b ? 0 : 1)}%');
    return parts.join(' + ');
  }

  static String _combineExplain(Map? d) {
    if (d == null || d['enabled'] != true) return '-';
    final p1 = _s(d['penjelasan_diskon_1']);
    final p2 = _s(d['penjelasan_diskon_2']);
    final parts = [if (p1 != '-') p1, if (p2 != '-') p2];
    return parts.isEmpty ? '-' : parts.join(' + ');
  }

  factory OrderRow.fromJson(Map<String, dynamic> j) {
    final products = (j['products'] is List) ? j['products'] as List : const [];

    final diskon = (j['diskon'] is Map)
        ? Map<String, dynamic>.from(j['diskon'])
        : {
            'enabled': j['diskons_enabled'],
            'diskon_1': j['diskon_1'],
            'diskon_2': j['diskon_2'],
            'penjelasan_diskon_1': j['penjelasan_diskon_1'],
            'penjelasan_diskon_2': j['penjelasan_diskon_2'],
          };

    final reward  = (j['reward'] is Map) ? Map<String, dynamic>.from(j['reward']) : {'points': j['reward_point']};
    final program = (j['program_point'] is Map) ? Map<String, dynamic>.from(j['program_point']) : {'points': j['jumlah_program']};

    final totalAwal  = j['total_harga'] is num ? _rp(j['total_harga']) : _s(j['total_harga']);
    final totalAkhir = j['total_harga_after_tax'] is num ? _rp(j['total_harga_after_tax']) : _s(j['total_harga_after_tax']);

    return OrderRow(
      orderNo: _s(j['no_order']),
      department: _s(j['department']),
      employee: _s(j['employee']),
      category: _s(j['customer_category']),
      customer: _s(j['customer']),
      phone: _s(j['phone']),
      address: _addr(j),
      totalAwal: totalAwal,
      diskon: _combineDiskon(diskon),
      reasonDiskon: _combineExplain(diskon),
      programName: _s(j['customer_program']),
      programPoint: _s(program['points']),
      rewardPoint: _s(reward['points']),
      totalAkhir: totalAkhir,
      metodePembayaran: _s(j['payment_method']),
      statusPembayaran: _s(j['status_pembayaran']),
      status: _s(j['status']),
      productDetail: _joinProducts(products),
      createdAt: _s(j['created_at']),
      updatedAt: _s(j['updated_at']),
      invoicePdfUrl: j['file_pdf_url']?.toString(),
    );
  }
}
