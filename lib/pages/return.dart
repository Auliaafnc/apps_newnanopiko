// lib/pages/return.dart
import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../models/return_row.dart';
import '../services/api_service.dart';
import '../utils/downloader.dart'; // untuk auto-unduh di web
import '../widgets/clickable_thumb.dart'; // <-- tambah ini

import 'create_return.dart';
import 'create_sales_order.dart';
import 'home.dart';
import 'profile.dart';
import 'sales_order.dart';

class ReturnScreen extends StatefulWidget {
  const ReturnScreen({super.key});

  @override
  State<ReturnScreen> createState() => _ReturnScreenState();
}

class _ReturnScreenState extends State<ReturnScreen> {
  final TextEditingController _searchCtrl = TextEditingController();

  List<ReturnRow> _all = [];
  bool _loading = false;
  String? _error;

  String get _q => _searchCtrl.text.trim().toLowerCase();

  List<ReturnRow> get _filtered {
    if (_q.isEmpty) return _all;
    return _all.where((r) {
      final blob = [
        r.returnNo, r.department, r.employee, r.category, r.customer, r.phone,
        r.address, r.nominal, r.reason, r.notes, r.productDetail, r.status,
        r.createdAt, r.updatedAt,
      ].join(' ').toLowerCase();
      return blob.contains(_q);
    }).toList();
  }

  @override
  void initState() {
    super.initState();
    _fetch();
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    super.dispose();
  }

  Future<void> _fetch() async {
    setState(() { _loading = true; _error = null; });
    try {
      final items = await ApiService.fetchReturnRows(perPage: 1000);
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

  Future<void> _downloadPdf(String? url, String retNo) async {
    if (url == null || url.isEmpty) return;
    final fname = _safeFilename('Return_$retNo.pdf');
    await downloadFile(url, fileName: fname); // auto unduh di web
  }

  // ===== Status chip (sama gaya dengan Garansi) =====
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
                          'Return List',
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

      // Bottom nav – sama gaya dengan yang lain
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
              _navItem(context, Icons.shopping_cart, 'Create Order', onPressed: () async {
                final created = await Navigator.push<bool>(
                  context,
                  MaterialPageRoute(builder: (_) => const CreateSalesOrderScreen()),
                );
                if (created == true) {
                  if (!context.mounted) return;
                  Navigator.pushReplacement(
                    context,
                    MaterialPageRoute(
                      builder: (_) => const SalesOrderScreen(showCreatedSnack: true),
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
          MaterialPageRoute(builder: (_) => const CreateReturnScreen()),
        );
        if (created == true && mounted) {
          await _fetch();
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Return berhasil dibuat'),
              backgroundColor: Colors.green,
            ),
          );
        }
      },
      icon: const Icon(Icons.history),
      label: const Text('Create Return'),
      style: ElevatedButton.styleFrom(
        backgroundColor: Colors.blue,
        foregroundColor: Colors.white,
        shape: const StadiumBorder(),
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
      ),
    );
  }

  Widget _buildTable() {
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
        // rapatkan spasi
        columnSpacing: 10,
        horizontalMargin: 8,
        headingRowHeight: 38,
        dataRowHeight: 40,

        headingRowColor: MaterialStateProperty.all(const Color(0xFF22344C)),
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
          DataColumn(label: Text('Return Number')),
          DataColumn(label: Text('Department')),
          DataColumn(label: Text('Karyawan')),
          DataColumn(label: Text('Kategori Customer')),
          DataColumn(label: Text('Customer')),
          DataColumn(label: Text('Telepon')),
          DataColumn(label: Text('Alamat')),
          DataColumn(label: Text('Nominal')),
          DataColumn(label: Text('Alasan Return')),
          DataColumn(label: Text('Catatan')),
          DataColumn(label: Text('Detail Produk')),
          DataColumn(label: Text('Gambar')),
          DataColumn(label: Text('Dokumen')),
          DataColumn(label: Text('Status')), // <- DITAMBAH
          DataColumn(label: Text('Tanggal Dibuat')),
          DataColumn(label: Text('Tanggal Diperbarui')),
        ],
        rows: _filtered.map((r) {
          return DataRow(cells: [
            _textCell(r.returnNo, width: 130),
            _textCell(r.department, width: 120),
            _textCell(r.employee, width: 120),
            _textCell(r.category, width: 140),
            _textCell(r.customer, width: 140),
            _textCell(r.phone, width: 120),
            _textCell(r.address, width: 260),
            _textCell(r.nominal, width: 110),
            _textCell(r.reason, width: 160),
            _textCell(r.notes, width: 160),
          DataCell(
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.center,
              children: r.productDetail
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




            // ==== Gambar bulat & klik ====
            DataCell(
              (r.imageUrl == null || r.imageUrl!.isEmpty)
                  ? const Text('-')
                  : ClickableThumb(
                      url: r.imageUrl!,
                      heroTag: 'return_${r.returnNo}_${r.createdAt}',
                      size: 36,
                    ),
            ),

            DataCell(
              (r.pdfUrl != null && r.pdfUrl!.isNotEmpty)
                  ? IconButton(
                      tooltip: 'Unduh PDF',
                      icon: const Icon(Icons.picture_as_pdf, color: Colors.white),
                      onPressed: () => _downloadPdf(r.pdfUrl, r.returnNo),
                    )
                  : const Text('-'),
            ),

            // ==== STATUS CHIP (baru) ====
            DataCell(_statusChip(r.status)),

            _textCell(r.createdAt, width: 120),
            _textCell(r.updatedAt, width: 120),
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