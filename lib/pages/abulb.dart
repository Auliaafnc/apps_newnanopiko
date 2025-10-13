// lib/pages/a_bulb.dart
import 'package:flutter/material.dart';

import 'create_sales_order.dart';
import 'home.dart';
import 'profile.dart';
import 'sales_order.dart'; // flow setelah create

class ABulbScreen extends StatelessWidget {
  const ABulbScreen({super.key});

  @override
  Widget build(BuildContext context) {
    // Palette mengikuti gaya Bulb/TBulb
    const Color bgPage = Color(0xFF0A1B2D);
    const Color headerLight = Color(0xFFE9ECEF);
    const Color blue6500 = Color(0xFF1EA7FF);
    const Color yellow3000 = Color(0xFFFFC107);
    const Color cardDark = Color(0xFF0F2741); // panel sedikit beda agar radius terlihat

    final bool isTablet = MediaQuery.of(context).size.width >= 600;
    final double hPad = isTablet ? 24 : 16;
    final double vPad = isTablet ? 18 : 12;

    // Brand chip abu-abu
    Widget brandChip() => Container(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
          decoration: BoxDecoration(color: Colors.grey[300], borderRadius: BorderRadius.circular(24)),
          child: const Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(Icons.lightbulb_outline, color: Colors.black),
              SizedBox(width: 8),
              Text('Pikolite', style: TextStyle(color: Colors.black, fontWeight: FontWeight.w600)),
            ],
          ),
        );

    // Hero image dengan panel gelap ber-sudut melengkung
    Widget productImage() => ClipRRect(
          borderRadius: BorderRadius.circular(isTablet ? 20 : 16),
          child: Container(
            color: cardDark,
            padding: const EdgeInsets.all(16),
            child: Center(
              child: Image.asset('assets/images/spekabulb1.png',
                  height: isTablet ? 220 : 210, fit: BoxFit.contain),
            ),
          ),
        );

    // Kartu spesifikasi: header bar abu-abu
    Widget specCard() {
      Widget header = Container(
        width: double.infinity,
        height: 42,
        decoration: const BoxDecoration(
          color: headerLight,
          borderRadius: BorderRadius.vertical(top: Radius.circular(12)),
        ),
        child: const Center(
          child: Text('SPESIFIKASI', style: TextStyle(fontWeight: FontWeight.w700, color: Colors.black)),
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
                    row('Tahan Sampai', '15.000 Jam'),
                    row('Fitting', 'E27'),
                    row('Hemat Energi', '90%'),
                    row('Lumen', '100lm/Watt'),
                    row('Tegangan', '110–240V'),
                    row('CRI', '>80Ra'),
                  ],
                ),
              ),
            ],
          ),
        ),
      );
    }

    // ===== Header & cell builder (gaya TBulb) =====
    Widget th(String t) => ConstrainedBox(
          constraints: const BoxConstraints(minHeight: 44),
          child: Container(
            color: headerLight,
            alignment: Alignment.center,
            padding: const EdgeInsets.symmetric(horizontal: 8),
            child: Text(
              t,
              textAlign: TextAlign.center,
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

    Widget tdYellow(String t) => ConstrainedBox(
          constraints: const BoxConstraints(minHeight: 42),
          child: Container(
            alignment: Alignment.center,
            color: yellow3000,
            padding: const EdgeInsets.symmetric(horizontal: 8),
            child: Text(
              t,
              style: const TextStyle(color: Colors.black, fontWeight: FontWeight.w800, height: 1.1),
            ),
          ),
        );

    // ===== Tabel A-Bulb: 7 kolom (2 warna) =====
    Widget specTable() {
      const rows = [
        ['3 Watt','115mm x 60mm','Rp 12.900','3000K','Cahaya Putih Kekuningan','6500K','Cahaya Putih Kebiruan'],
        ['5 Watt','115mm x 60mm','Rp 14.900','3000K','Cahaya Putih Kekuningan','6500K','Cahaya Putih Kebiruan'],
        ['7 Watt','115mm x 55mm','Rp 16.900','3000K','Cahaya Putih Kekuningan','6500K','Cahaya Putih Kebiruan'],
        ['9 Watt','125.5mm x 65mm','Rp 19.900','3000K','Cahaya Putih Kekuningan','6500K','Cahaya Putih Kebiruan'],
        ['12 Watt','125.5mm x 65mm','Rp 22.900','3000K','Cahaya Putih Kekuningan','6500K','Cahaya Putih Kebiruan'],
        ['15 Watt','137.5mm x 69.8mm','Rp 25.900','3000K','Cahaya Putih Kekuningan','6500K','Cahaya Putih Kebiruan'],
      ];

      const phoneWidths = <int, TableColumnWidth>{
        0: FixedColumnWidth(120),
        1: FixedColumnWidth(200),
        2: FixedColumnWidth(140),
        3: FixedColumnWidth(120),
        4: FixedColumnWidth(220),
        5: FixedColumnWidth(120),
        6: FixedColumnWidth(220),
      };
      final tabletWidths = <int, TableColumnWidth>{
        0: const FlexColumnWidth(1.1),
        1: const FlexColumnWidth(1.6),
        2: const FlexColumnWidth(1.1),
        3: const FlexColumnWidth(1.0),
        4: const FlexColumnWidth(1.6),
        5: const FlexColumnWidth(1.0),
        6: const FlexColumnWidth(1.6),
      };

      assert(rows.every((r) => r.length == 7), 'Semua row harus 7 kolom');

      final table = Table(
        columnWidths: isTablet ? tabletWidths : phoneWidths,
        defaultVerticalAlignment: TableCellVerticalAlignment.middle,
        border: const TableBorder.symmetric(
          inside: BorderSide(color: Colors.white24, width: 1),
          outside: BorderSide(color: Colors.white24, width: 1),
        ),
        children: [
          TableRow(children: [
            th('Varian Watt'),
            th('Dimensi Produk'),
            th('Harga'),
            th('Warna'),
            th('Keterangan'),
            th('Warna'),
            th('Keterangan'),
          ]),
          for (final r in rows)
            TableRow(
              decoration: const BoxDecoration(color: bgPage),
              children: [
                td(r[0]),
                td(r[1]),
                td(r[2]),
                tdYellow(r[3]), // 3000K -> kuning
                td(r[4]),
                tdBlue(r[5]),   // 6500K -> biru
                td(r[6]),
              ],
            ),
        ],
      );

      // panel tabel dibungkus ClipRRect agar sudut terlihat
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
                    constraints: const BoxConstraints(minWidth: 1220),
                    child: table,
                  ),
                ),
        ),
      );
    }

    // Panel gambar perbandingan dengan sudut melengkung
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
          Text('Product A-Bulb',
              style: TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: isTablet ? 18 : 16)),
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
          specTable(),

          SizedBox(height: vPad * 1.5),

          // Perbandingan (gambar saja, konsisten dengan halaman lain) — pakai panel rounded
          LayoutBuilder(builder: (context, c) {
            final twoCols = isTablet && c.maxWidth >= 680;
            final left = roundedImage('assets/images/nanobulbspek.png', isTablet ? 220 : 170);
            final right = roundedImage('assets/images/productkomp.png', isTablet ? 220 : 170);
            if (twoCols) return Row(children: [Expanded(child: left), const SizedBox(width: 16), Expanded(child: right)]);
            return Column(children: [left, const SizedBox(height: 12), right]);
          }),
        ],
      ),

      // ===== BOTTOM NAVIGATION: gaya TBulb/Home (pill abu-abu) =====
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
              _navItem(context, Icons.shopping_cart, 'Create Order', onPressed: () async {
                final created = await Navigator.push<bool>(
                  context,
                  MaterialPageRoute(builder: (_) => CreateSalesOrderScreen()),
                );
                if (created == true) {
                  if (!context.mounted) return;
                  Navigator.pushReplacement(
                    context,
                    MaterialPageRoute(
                      builder: (_) => SalesOrderScreen(showCreatedSnack: true),
                    ),
                  );
                }
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

  // Nav item versi TBulb/Home
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
