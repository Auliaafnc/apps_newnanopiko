import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../models/customer.dart';
import '../services/api_service.dart';
import '../widgets/clickable_thumb.dart';
import 'package:apps_nanolite/services/api_service.dart';


import 'create_customer.dart';
import 'create_sales_order.dart';
import 'home.dart';
import 'profile.dart';

class CustomerScreen extends StatefulWidget {
  const CustomerScreen({super.key});

  @override
  State<CustomerScreen> createState() => _CustomerScreenState();
}

class _CustomerScreenState extends State<CustomerScreen> {
  final TextEditingController _searchCtrl = TextEditingController();

  List<Customer> _all = [];
  bool _loading = false;
  String? _error;

  String get _q => _searchCtrl.text.trim().toLowerCase();

  List<Customer> get _filtered {
    if (_q.isEmpty) return _all;
    return _all.where((c) {
      final blob =
          '${c.departmentName ?? ''} ${c.employeeName ?? ''} ${c.name} ${c.categoryName ?? ''} ${c.phone} ${c.email ?? ''} ${c.alamatDisplay} ${c.programName ?? ''} ${c.status}'
              .toLowerCase();
      return blob.contains(_q);
    }).toList();
  }

  @override
  void initState() {
    super.initState();
    _fetch();
  }

  Future<void> _fetch() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final items = await ApiService.fetchCustomers(perPage: 1000);
      setState(() => _all = items);
    } catch (e) {
      setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    super.dispose();
  }

  Future<void> _openUrl(String? url) async {
  if (url == null || url.isEmpty || url == '-') return;

  // coba ganti jadi Uri
  final uri = Uri.tryParse(url);
  if (uri == null) return;

  // kalau bisa, langsung buka aplikasi Google Maps
  final mapsUrl = Uri.parse("geo:0,0?q=${Uri.encodeComponent(url)}");

  if (await canLaunchUrl(mapsUrl)) {
    await launchUrl(mapsUrl, mode: LaunchMode.externalApplication);
  } else if (await canLaunchUrl(uri)) {
    await launchUrl(uri, mode: LaunchMode.externalApplication);
  }
}


  // Format tanggal sederhana (YYYY-MM-DD HH:mm)
  String? _fmtDate(DateTime? d) {
    if (d == null) return null;
    final y = d.year.toString().padLeft(4, '0');
    final m = d.month.toString().padLeft(2, '0');
    final day = d.day.toString().padLeft(2, '0');
    final hh = d.hour.toString().padLeft(2, '0');
    final mm = d.minute.toString().padLeft(2, '0');
    return '$y-$m-$day $hh:$mm';
  }

  // ===== Status chip (gaya sama Garansi/Return) =====
  Widget _statusChip(String raw) {
    final v = (raw.isEmpty ? '-' : raw).toLowerCase();
    String label;
    Color bg;

    switch (v) {
      // pola seperti garansi / approval
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
        label = 'Pending';
        bg = Colors.amber.withOpacity(0.18);
        break;

      // pola umum customer
      case 'active':
      case 'aktif':
        label = 'Aktif';
        bg = Colors.green.withOpacity(0.18);
        break;
      case 'inactive':
      case 'nonaktif':
      case 'non-aktif':
      case 'disabled':
        label = 'Nonaktif';
        bg = Colors.grey.withOpacity(0.22);
        break;
      case 'suspended':
      case 'blocked':
      case 'blokir':
        label = 'Diblokir';
        bg = Colors.red.withOpacity(0.18);
        break;

      case '-':
      default:
        label = raw.isEmpty ? '-' : raw;
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
                          'Customers List',
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

                  // --- Pull-to-refresh di sini ---
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

      // Bottom nav
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
              _navItem(context, Icons.shopping_cart, 'Create Order', onPressed: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(builder: (_) => const CreateSalesOrderScreen()),
                );
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
        final result = await Navigator.push<bool>(
          context,
          MaterialPageRoute(builder: (_) => const CreateCustomerScreen()),
        );
        if (!mounted) return;
        if (result == true) {
          await _fetch();
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Pembuatan customer berhasil'),
              backgroundColor: Colors.green,
            ),
          );
        }
      },
      icon: const Icon(Icons.person_add_alt_1),
      label: const Text('Create Customer'),
      style: ElevatedButton.styleFrom(
        backgroundColor: Colors.blue,
        foregroundColor: Colors.white,
        shape: const StadiumBorder(),
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
      ),
    );
  }

  Widget _buildTable() {
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: DataTable(
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
        ),
        dataTextStyle: const TextStyle(color: Colors.white),
        columns: const [
          DataColumn(label: Text('Department')),
          DataColumn(label: Text('Karyawan')),
          DataColumn(label: Text('Nama Customer')),
          DataColumn(label: Text('Kategori Customer')),
          DataColumn(label: Text('Telepon')),
          DataColumn(label: Text('Email')),
          DataColumn(label: Text('Alamat')),
          DataColumn(label: Text('Link Google Maps')),
          DataColumn(label: Text('Program Customer')),
          DataColumn(label: Text('Program Point')),
          DataColumn(label: Text('Reward Point')),
          DataColumn(label: Text('Gambar')),
          DataColumn(label: Text('Status')),
          DataColumn(label: Text('Tanggal Dibuat')),
          DataColumn(label: Text('Tanggal Diperbarui')),
        ],
        rows: _filtered.map((c) {
          DataCell t(String? v) => DataCell(Text(
                (v == null || v.isEmpty || v == 'null') ? '-' : v,
                overflow: TextOverflow.ellipsis,
              ));
          return DataRow(
            cells: [
              t(c.departmentName),
              t(c.employeeName),
              t(c.name),
              t(c.categoryName),
              t(c.phone),
              t(c.email),
              t(c.alamatDisplay),
              DataCell(
                (c.gmapsLink == null || c.gmapsLink!.isEmpty)
                    ? const Text('-')
                    : InkWell(
                        onTap: () => _openUrl(c.gmapsLink),
                        child: Text(
                          c.gmapsLink!,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            decoration: TextDecoration.underline,
                            color: Colors.blue,
                          ),
                        ),
                      ),
              ),
              t(c.programName),
              t(c.programPoint.toString()),
              t(c.rewardPoint.toString()),
              // ==== Gambar bulat & klik ====
             
              
              DataCell(
                (c.imageUrl == null || c.imageUrl!.isEmpty)
                    ? const Text('-')
                    : ClickableThumb(
                        url: c.imageUrl!,
                        heroTag: 'customer_${c.id}', // unik per baris
                        size: 40,
                      ),
              ),




              // ==== STATUS pakai chip ====
              DataCell(_statusChip(c.status)),
              t(_fmtDate(c.createdAt)),
              t(_fmtDate(c.updatedAt)),
            ],
          );
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
