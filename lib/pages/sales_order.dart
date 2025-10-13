// lib/pages/sales_order.dart
import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../models/order_row.dart';
import '../services/api_service.dart';
import '../utils/downloader.dart'; // untuk auto-unduh di web

import 'create_sales_order.dart';
import 'home.dart';
import 'profile.dart';

class SalesOrderScreen extends StatefulWidget {
  final bool showCreatedSnack;
  const SalesOrderScreen({super.key, this.showCreatedSnack = false});

  @override
  State<SalesOrderScreen> createState() => _SalesOrderScreenState();
}

class _SalesOrderScreenState extends State<SalesOrderScreen> {
  final TextEditingController _searchCtrl = TextEditingController();

  List<OrderRow> _all = [];
  bool _loading = false;
  String? _error;

  String get _q => _searchCtrl.text.trim().toLowerCase();

  List<OrderRow> get _filtered {
    if (_q.isEmpty) return _all;
    return _all.where((o) {
      final blob = [
        o.orderNo,
        o.department,
        o.employee,
        o.customer,
        o.category,
        o.phone,
        o.address,
        o.productDetail,
        o.totalAwal,
        o.diskon,
        o.reasonDiskon,
        o.programName,
        o.programPoint,
        o.rewardPoint,
        o.totalAkhir,
        o.metodePembayaran,
        o.statusPembayaran,
        o.status,
        o.createdAt,
        o.updatedAt,
      ].join(' ').toLowerCase();
      return blob.contains(_q);
    }).toList();
  }

  @override
  void initState() {
    super.initState();
    if (widget.showCreatedSnack) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Sales Order berhasil dibuat'),
            backgroundColor: Colors.green,
          ),
        );
      });
    }
    _fetch();
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    super.dispose();
  }

  Future<void> _fetch() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final items = await ApiService.fetchOrderRows(perPage: 1000);
      setState(() => _all = items);
    } catch (e) {
      setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _openUrl(String? url) async {
    if (url == null || url.isEmpty || url == '-') return;
    final uri = Uri.tryParse(url);
    if (uri == null) return;
    await launchUrl(uri, mode: LaunchMode.externalApplication);
  }

  String _safeFilename(String raw) =>
      raw.replaceAll(RegExp(r'[^A-Za-z0-9._-]'), '_');

  // ====== Tambahan: normalisasi URL supaya tidak "localhost" tanpa http ======

  // Ambil origin dari ApiService.baseUrl → "http(s)://host[:port]"
  String get _originFromBase {
    final u = Uri.tryParse(ApiService.baseUrl);
    if (u == null) return '';
    final port = u.hasPort ? ':${u.port}' : '';
    return '${u.scheme}://${u.host}$port';
  }

  // Selalu jadikan URL absolut & valid untuk file dokumen
  String _normalizeDocUrl(String raw) {
    if (raw.isEmpty) return raw;

    // sudah absolut
    if (raw.startsWith('http://') || raw.startsWith('https://')) return raw;

    final lower = raw.toLowerCase();

    // backend kadang kirim "localhost/..." atau "127.0.0.1/..."
    if (lower.startsWith('localhost/') ||
        lower.startsWith('127.0.0.1/') ||
        lower.startsWith('::1/')) {
      final scheme = Uri.tryParse(ApiService.baseUrl)?.scheme ?? 'http';
      return '$scheme://$raw';
    }

    // path Laravel umum
    if (raw.startsWith('/storage/')) return '$_originFromBase$raw';
    if (raw.startsWith('storage/'))  return '$_originFromBase/$raw';

    // path mentah yang kamu simpan: "orders/pdf/....pdf" atau "orders/excel/....xlsx"
    if (raw.startsWith('orders/pdf/') || raw.startsWith('orders/excel/')) {
      return '$_originFromBase/storage/$raw';
    }

    // fallback: sambung ke origin
    final withSlash = raw.startsWith('/') ? raw : '/$raw';
    return '$_originFromBase$withSlash';
  }

  Future<void> _downloadPdf(String? url, String orderNo) async {
    if (url == null || url.isEmpty) return;
    final fname = _safeFilename('SalesOrder_$orderNo.pdf');
    final normalized = _normalizeDocUrl(url);
    await downloadFile(normalized, fileName: fname); // auto-unduh (support web)
  }

  // ===== Status chip (sama gaya dengan Garansi/Return) =====
  Widget _statusChip(String raw) {
    final v = (raw.isEmpty ? '-' : raw).toLowerCase();
    String label;
    Color bg;
    switch (v) {
      case 'approved':
      case 'disetujui':
      case 'approve':
      case 'acc':
        label = 'Disetujui';
        bg = Colors.green.withOpacity(0.18);
        break;
      case 'rejected':
      case 'ditolak':
      case 'reject':
      case 'tolak':
        label = 'Ditolak';
        bg = Colors.red.withOpacity(0.18);
        break;
      case 'pending':
      case 'menunggu':
      case '-':
      default:
        label = 'Pending';
        bg = Colors.amber.withOpacity(0.18);
        break;
    }
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: Colors.white24),
      ),
      child: Text(label, style: const TextStyle(fontSize: 12, color: Colors.white)),
    );
  }

  @override
  Widget build(BuildContext context) {
    final bool isTablet = MediaQuery.of(context).size.width >= 600;

    return Scaffold(
      backgroundColor: const Color(0xFF0A1B2D),
      appBar: AppBar(
        backgroundColor: Colors.white,
        foregroundColor: Colors.black,
        elevation: 1,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Colors.black),
          onPressed: () => Navigator.pop(context),
        ),
        title: const Text('nanopiko', style: TextStyle(color: Colors.black)),
      ),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: LayoutBuilder(
            builder: (context, constraints) {
              final bool wide = constraints.maxWidth >= 900;

              return Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(
                        child: Text(
                          'Sales Order List',
                          style: TextStyle(
                            color: Colors.white,
                            fontSize: isTablet ? 20 : 18,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
                      if (wide) ...[
                        _buildSearchField(isTablet ? 320 : 260),
                        const SizedBox(width: 12),
                        _buildCreateButton(context),
                      ],
                    ],
                  ),
                  if (!wide) ...[
                    const SizedBox(height: 12),
                    _buildSearchField(double.infinity),
                    const SizedBox(height: 12),
                    Align(
                      alignment: Alignment.centerRight,
                      child: _buildCreateButton(context),
                    ),
                  ],
                  const SizedBox(height: 16),

                  Expanded(
                    child: RefreshIndicator(
                      onRefresh: _fetch,
                      child: SingleChildScrollView(
                        physics: const AlwaysScrollableScrollPhysics(),
                        child: Container(
                          decoration: BoxDecoration(
                            color: const Color(0xFF152236),
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(color: Colors.white24),
                          ),
                          padding: const EdgeInsets.all(12),
                          child: _loading
                              ? const Center(child: CircularProgressIndicator())
                              : _error != null
                                  ? Center(
                                      child: Column(
                                        mainAxisSize: MainAxisSize.min,
                                        children: [
                                          Text(
                                            _error!,
                                            style: const TextStyle(
                                                color: Colors.white70),
                                            textAlign: TextAlign.center,
                                          ),
                                          const SizedBox(height: 8),
                                          OutlinedButton(
                                            onPressed: _fetch,
                                            child: const Text('Coba lagi'),
                                          ),
                                        ],
                                      ),
                                    )
                                  : _buildTable(),
                        ),
                      ),
                    ),
                  ),
                ],
              );
            },
          ),
        ),
      ),

      // Bottom nav (gaya sama seperti layar lain)
      bottomNavigationBar: Container(
        color: const Color(0xFF0A1B2D),
        padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 20),
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 12),
          decoration: BoxDecoration(
            color: Colors.grey[300],
            borderRadius: BorderRadius.circular(40),
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceAround,
            children: [
              _navItem(context, Icons.home, 'Home', onPressed: () {
                Navigator.pushReplacement(
                  context,
                  MaterialPageRoute(builder: (_) => HomeScreen()),
                );
              }),
              _navItem(context, Icons.shopping_cart, 'Create Order',
                  onPressed: () async {
                final created = await Navigator.push<bool>(
                  context,
                  MaterialPageRoute(
                      builder: (_) => const CreateSalesOrderScreen()),
                );
                if (created == true && mounted) {
                  await _fetch();
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(
                      content: Text('Sales Order berhasil dibuat'),
                      backgroundColor: Colors.green,
                    ),
                  );
                }
              }),
              _navItem(context, Icons.person, 'Profile', onPressed: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(builder: (_) => ProfileScreen()),
                );
              }),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildSearchField(double width) {
    return SizedBox(
      width: width,
      height: 44,
      child: TextField(
        controller: _searchCtrl,
        onChanged: (_) => setState(() {}),
        style: const TextStyle(color: Colors.white),
        decoration: InputDecoration(
          hintText: 'Search...',
          hintStyle: const TextStyle(color: Colors.white60),
          prefixIcon: const Icon(Icons.search, color: Colors.white70),
          filled: true,
          fillColor: const Color(0xFF22344C),
          contentPadding:
              const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(22),
            borderSide: const BorderSide(color: Colors.white24),
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(22),
            borderSide: const BorderSide(color: Colors.white24),
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(22),
            borderSide: const BorderSide(color: Colors.white54),
          ),
        ),
      ),
    );
  }

  Widget _buildCreateButton(BuildContext context) {
    return ElevatedButton.icon(
      onPressed: () async {
        final created = await Navigator.push<bool>(
          context,
          MaterialPageRoute(
              builder: (_) => const CreateSalesOrderScreen()),
        );
        if (created == true && mounted) {
          await _fetch();
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Sales Order berhasil dibuat'),
              backgroundColor: Colors.green,
            ),
          );
        }
      },
      icon: const Icon(Icons.add),
      label: const Text('Create Order'),
      style: ElevatedButton.styleFrom(
        backgroundColor: Colors.blue,
        foregroundColor: Colors.white,
        shape: const StadiumBorder(),
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
      ),
    );
  }

  Widget _buildTable() {
    // text cell helper + default width diperkecil supaya rapat
    DataCell _textCell(String v, {double width = 180}) => DataCell(
          SizedBox(
            width: width,
            child: Text(
              (v.isEmpty || v == 'null') ? '-' : v,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(fontSize: 13),
            ),
          ),
        );

    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: DataTable(
        // ====== Rapatkan spasi tabel ======
        columnSpacing: 10,        // default 56
        horizontalMargin: 8,      // padding kiri/kanan sel
        headingRowHeight: 38,     // tinggi baris header
        dataRowHeight: 40,        // tinggi baris data

        headingRowColor:
            MaterialStateProperty.all(const Color(0xFF22344C)),
        dataRowColor: MaterialStateProperty.resolveWith(
          (s) => s.contains(MaterialState.hovered)
              ? const Color(0xFF1B2B42)
              : const Color(0xFF152236),
        ),
        headingTextStyle: const TextStyle(
          color: Colors.white,
          fontWeight: FontWeight.w600,
          fontSize: 13,
        ),
        dataTextStyle: const TextStyle(color: Colors.white, fontSize: 13),

        columns: const [
          DataColumn(label: Text('Order Number')),
          DataColumn(label: Text('Department')),
          DataColumn(label: Text('Karyawan')),
          DataColumn(label: Text('Customer')),
          DataColumn(label: Text('Kategori Customer')),
          DataColumn(label: Text('Telepon')),
          DataColumn(label: Text('Alamat')),
          DataColumn(label: Text('Detail Produk')),
          DataColumn(label: Text('Total Awal')),
          DataColumn(label: Text('Diskon')),
          DataColumn(label: Text('Penjelasan Diskon')),
          DataColumn(label: Text('Program Customer')),
          DataColumn(label: Text('Program Point')),
          DataColumn(label: Text('Reward Point')),
          DataColumn(label: Text('Total Akhir')),
          DataColumn(label: Text('Metode Pembayaran')),
          DataColumn(label: Text('Status Pembayaran')),
          DataColumn(label: Text('Status')),
          DataColumn(label: Text('Dokumen')),
          DataColumn(label: Text('Tanggal Dibuat')),
          DataColumn(label: Text('Tanggal Diperbarui')),
        ],
        rows: _filtered.map((o) {
          return DataRow(cells: [
            _textCell(o.orderNo, width: 130),
            _textCell(o.department, width: 120),
            _textCell(o.employee, width: 120),
            _textCell(o.customer, width: 140),
            _textCell(o.category, width: 140),
            _textCell(o.phone, width: 120),
            _textCell(o.address, width: 260),
           DataCell(
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.center,
              children: o.productDetail
                  .split('\n')
                  .map((line) => line.trim())
                  .where((line) => line.isNotEmpty)
                  .map((line) {
                return Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      "• ",
                      style: TextStyle(fontSize: 13, color: Colors.white),
                    ),
                    Expanded(
                      child: Text(
                        line,
                        style: const TextStyle(fontSize: 13, color: Colors.white),
                      ),
                    ),
                  ],
                );
              }).toList(),
            ),
          ),

            _textCell(o.totalAwal, width: 110),
            _textCell(o.diskon, width: 100),
            _textCell(o.reasonDiskon, width: 180),
            _textCell(o.programName, width: 140),
            _textCell(o.programPoint, width: 90),
            _textCell(o.rewardPoint, width: 90),
            _textCell(o.totalAkhir, width: 110),
            _textCell(o.metodePembayaran, width: 130),
            _textCell(o.statusPembayaran, width: 130),

            // ==== STATUS pakai chip ====
            DataCell(_statusChip(o.status)),

            DataCell(
              (o.invoicePdfUrl != null && o.invoicePdfUrl!.isNotEmpty)
                  ? IconButton(
                      tooltip: 'Unduh PDF',
                      icon: const Icon(Icons.picture_as_pdf, color: Colors.white),
                      onPressed: () {
                        final url = o.invoicePdfUrl!;
                        final fname = _safeFilename('SalesOrder_${o.orderNo}.pdf');
                        // panggil segera; biarkan async jalan di belakang
                        downloadFile(url, fileName: fname);
                      },
                    )
                  : const Text('-'),
            ),
 _textCell(o.createdAt, width: 120),
  _textCell(o.updatedAt, width: 120),
          ]);
        }).toList(),
      ),
    );
  }

  static Widget _navItem(BuildContext context, IconData icon, String label,
      {VoidCallback? onPressed}) {
    final bool isTablet = MediaQuery.of(context).size.shortestSide >= 600;
    final double iconSize = isTablet ? 32 : 28;
    final double fontSize = isTablet ? 14 : 12;

    return InkWell(
      onTap: onPressed,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: iconSize, color: const Color(0xFF0A1B2D)),
          const SizedBox(height: 4),
          Text(label,
              style:
                  TextStyle(color: const Color(0xFF0A1B2D), fontSize: fontSize)),
        ],
      ),
    );
  }
}
