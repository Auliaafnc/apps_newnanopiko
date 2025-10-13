import 'package:flutter/material.dart';

import 'create_sales_order.dart';
import 'home.dart';
import 'profile.dart';

class CapsuleScreen extends StatelessWidget {
  const CapsuleScreen({super.key});

  @override
  Widget build(BuildContext context) {
    // Palet & panel seperti Bulb
    const Color bgPage = Color(0xFF0A1B2D);
    const Color headerLight = Color(0xFFE9ECEF);
    const Color blue6500 = Color(0xFF1EA7FF);
    const Color cardDark = Color(0xFF0F2741);

    final bool isTablet = MediaQuery.of(context).size.width >= 600;
    final double hPad = isTablet ? 24 : 16;
    final double vPad = isTablet ? 18 : 12;

    // Brand chip
    Widget brandChip() => Container(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
          decoration: BoxDecoration(
            color: Colors.grey[300],
            borderRadius: BorderRadius.circular(24),
          ),
          child: const Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(Icons.lightbulb, color: Colors.black),
              SizedBox(width: 8),
              Text(
                'Nanolite',
                style: TextStyle(color: Colors.black, fontWeight: FontWeight.w600),
              ),
            ],
          ),
        );

    // HERO image: panel gelap + radius (gaya Bulb)
    Widget productImage() => ClipRRect(
          borderRadius: BorderRadius.circular(isTablet ? 20 : 16),
          child: Container(
            color: cardDark,
            padding: const EdgeInsets.all(16),
            child: Center(
              child: Image.asset(
                'assets/images/capsule.jpg',
                width: isTablet ? 320 : 260,
                fit: BoxFit.contain,
              ),
            ),
          ),
        );

    // Kartu spesifikasi (header abu-abu)
    Widget specCard() {
      Widget header = Container(
        width: double.infinity,
        height: 42,
        decoration: const BoxDecoration(
          color: headerLight,
          borderRadius: BorderRadius.vertical(top: Radius.circular(12)),
        ),
        child: const Center(
          child: Text(
            'SPESIFIKASI',
            style: TextStyle(fontWeight: FontWeight.w700, color: Colors.black),
          ),
        ),
      );

      Widget row(String l, String r) => Padding(
            padding: const EdgeInsets.symmetric(vertical: 6),
            child: RichText(
              text: TextSpan(
                style: const TextStyle(color: Colors.black87, fontSize: 14, height: 1.4),
                children: [
                  TextSpan(text: '$l: ', style: const TextStyle(fontWeight: FontWeight.w800)),
                  TextSpan(text: r, style: const TextStyle(fontWeight: FontWeight.w600)),
                ],
              ),
            ),
          );

      return Container(
        decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12)),
        child: Padding(
          padding: const EdgeInsets.only(bottom: 14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              header,
              const SizedBox(height: 6),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    row('Tahan Sampai', '25.000 Jam'),
                    row('Fitting', 'E27'),
                    row('Hemat Energi', '90%'),
                    row('LED', 'Samsung'),
                    row('Tegangan', '165–250V'),
                  ],
                ),
              ),
            ],
          ),
        ),
      );
    }

    // ===== builder sel tabel (sama seperti Bulb) =====
    Widget th(String t) => ConstrainedBox(
          constraints: const BoxConstraints(minHeight: 44),
          child: Container(
            color: headerLight,
            alignment: Alignment.center,
            padding: const EdgeInsets.symmetric(horizontal: 8),
            child: Text(
              t,
              textAlign: TextAlign.center,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(color: Colors.black, fontWeight: FontWeight.w700, height: 1.1),
            ),
          ),
        );

    Widget td(String t) => ConstrainedBox(
          constraints: const BoxConstraints(minHeight: 42),
          child: Container(
            alignment: Alignment.center,
            padding: const EdgeInsets.symmetric(horizontal: 8),
            child: Text(
              t,
              textAlign: TextAlign.center,
              style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w600, height: 1.1),
            ),
          ),
        );

    Widget tdBlue(String t) => ConstrainedBox(
          constraints: const BoxConstraints(minHeight: 42),
          child: Container(
            alignment: Alignment.center,
            color: blue6500,
            padding: const EdgeInsets.symmetric(horizontal: 8),
            child: Text(
              t,
              style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, height: 1.1),
            ),
          ),
        );

    // ===== Tabel varian (8 kolom, panel gelap rounded + scroll HP) =====
    Widget capsuleTable() {
      // Kolom: Varian Watt | Lumen | Tinggi | Diameter | Harga | Warna | Keterangan | Isi/Dus
      const rows = [
        ['30 Watt', '3600 lm', '178 mm', '100 mm', 'Rp 117.900', '6500K', 'Cahaya Putih Kebiruan', '40'],
        ['50 Watt', '6000 lm', '211 mm', '120 mm', 'Rp 266.900', '6500K', 'Cahaya Putih Kebiruan', '24'],
      ];

      // Lebar kolom: fixed (HP) / flex (Tablet)
      const phoneWidths = <int, TableColumnWidth>{
        0: FixedColumnWidth(120), // Watt
        1: FixedColumnWidth(110), // Lumen
        2: FixedColumnWidth(110), // Tinggi
        3: FixedColumnWidth(110), // Diameter
        4: FixedColumnWidth(130), // Harga
        5: FixedColumnWidth(90),  // Warna
        6: FixedColumnWidth(180), // Keterangan
        7: FixedColumnWidth(90),  // Isi/Dus
      };
      final tabletWidths = <int, TableColumnWidth>{
        0: const FlexColumnWidth(1.1),
        1: const FlexColumnWidth(1.0),
        2: const FlexColumnWidth(1.0),
        3: const FlexColumnWidth(1.0),
        4: const FlexColumnWidth(1.2),
        5: const FlexColumnWidth(0.9),
        6: const FlexColumnWidth(1.6),
        7: const FlexColumnWidth(0.9),
      };

      final table = Table(
        columnWidths: isTablet ? tabletWidths : phoneWidths,
        defaultVerticalAlignment: TableCellVerticalAlignment.middle,
        border: const TableBorder.symmetric(
          inside: BorderSide(color: Colors.white24, width: 1),
          outside: BorderSide(color: Colors.white24, width: 1),
        ),
        children: [
          TableRow(children: [
            th('Watt'),
            th('Lumen'),
            th('Tinggi'),
            th('Diameter'),
            th('Harga'),
            th('Warna'),
            th('Keterangan'),
            th('Isi/Dus'),
          ]),
          for (final r in rows)
            TableRow(
              decoration: const BoxDecoration(color: bgPage),
              children: [
                td(r[0]),
                td(r[1]),
                td(r[2]),
                td(r[3]),
                td(r[4]),
                tdBlue(r[5]),
                td(r[6]),
                td(r[7]),
              ],
            ),
        ],
      );

      return ClipRRect(
        borderRadius: BorderRadius.circular(12),
        child: Container(
          color: cardDark,
          padding: const EdgeInsets.all(10),
          child: isTablet
              ? table
              : SingleChildScrollView(
                  scrollDirection: Axis.horizontal,
                  child: ConstrainedBox(
                    constraints: const BoxConstraints(minWidth: 1040),
                    child: table,
                  ),
                ),
        ),
      );
    }

    // Panel gambar perbandingan (rounded seperti Bulb)
    Widget roundedImage(String path, double h) => ClipRRect(
          borderRadius: BorderRadius.circular(16),
          child: Container(
            color: cardDark,
            padding: const EdgeInsets.all(12),
            child: Image.asset(path, height: h, fit: BoxFit.contain),
          ),
        );

    return Scaffold(
      backgroundColor: bgPage,
      appBar: AppBar(
        backgroundColor: Colors.grey[200],
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Colors.black),
          onPressed: () => Navigator.pop(context),
        ),
        title: const Text('nanopiko', style: TextStyle(color: Colors.black)),
      ),
      body: ListView(
        padding: EdgeInsets.fromLTRB(hPad, vPad, hPad, vPad + 16),
        children: [
          brandChip(),
          SizedBox(height: vPad),
          const Text(
            'Product Capsule',
            style: TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 18),
          ),
          SizedBox(height: vPad),

          // HERO (row di tablet, stack di HP)
          LayoutBuilder(builder: (context, c) {
            final row = isTablet && c.maxWidth >= 680;
            final img = productImage();
            final spec = specCard();
            if (row) {
              return Row(children: [Expanded(child: img), const SizedBox(width: 16), Expanded(child: spec)]);
            }
            return Column(children: [img, const SizedBox(height: 12), spec]);
          }),

          SizedBox(height: vPad),

          // TABLE
          capsuleTable(),

          SizedBox(height: vPad * 1.5),

          // Perbandingan (dua gambar, panel rounded)
          LayoutBuilder(builder: (context, c) {
            final twoCols = isTablet && c.maxWidth >= 680;
            final left = roundedImage('assets/images/nanocapspek.jpg', isTablet ? 220 : 170);
            final right = roundedImage('assets/images/kompcaps.jpg', isTablet ? 220 : 170);
            if (twoCols) {
              return Row(children: [Expanded(child: left), const SizedBox(width: 16), Expanded(child: right)]);
            }
            return Column(children: [left, const SizedBox(height: 12), right]);
          }),
        ],
      ),

      // Bottom nav (pill abu-abu seperti Bulb)
      bottomNavigationBar: Container(
        color: bgPage,
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
                Navigator.pushReplacement(context, MaterialPageRoute(builder: (_) => HomeScreen()));
              }),
              _navItem(context, Icons.shopping_cart, 'Create Order', onPressed: () {
                Navigator.push(context, MaterialPageRoute(builder: (_) => CreateSalesOrderScreen()));
              }),
              _navItem(context, Icons.person, 'Profile', onPressed: () {
                Navigator.push(context, MaterialPageRoute(builder: (_) => ProfileScreen()));
              }),
            ],
          ),
        ),
      ),
    );
  }

  // Bottom nav item (versi Bulb)
  Widget _navItem(BuildContext context, IconData icon, String label, {VoidCallback? onPressed}) {
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
          Text(label, style: TextStyle(color: const Color(0xFF0A1B2D), fontSize: fontSize)),
        ],
      ),
    );
  }
}
