// lib/widgets/foto_barang_pengiriman.dart
import 'package:flutter/material.dart';

import '../models/garansi_row.dart';
import 'fullscreen_gallery.dart';

class FotoBarangDanPengiriman extends StatelessWidget {
  final GaransiRow row;
  const FotoBarangDanPengiriman({super.key, required this.row});

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(child: _FotoColumn(title: 'Foto Barang', urls: row.imageUrls)),
            const SizedBox(width: 16),
            Expanded(child: _FotoColumn(title: 'Bukti Pengiriman', urls: row.deliveryImageUrls)),
          ],
        ),
        const SizedBox(height: 12),
        const Divider(),
      ],
    );
  }
}

class _FotoColumn extends StatelessWidget {
  final String title;
  final List<String> urls;
  const _FotoColumn({required this.title, required this.urls});

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(title, style: const TextStyle(fontWeight: FontWeight.w700)),
        const SizedBox(height: 8),
        if (urls.isEmpty)
          Text('-', style: TextStyle(color: Theme.of(context).hintColor))
        else
          Wrap(
            spacing: 10, runSpacing: 10,
            children: List.generate(urls.length, (i) {
              final u = urls[i];
              return GestureDetector(
                onTap: () => Navigator.push(
                  context,
                  PageRouteBuilder(
                    opaque: false,
                    pageBuilder: (_, __, ___) => FullscreenGallery(urls: urls, initialIndex: i),
                  ),
                ),
                child: Hero(
                  tag: 'img-hero-$u',
                  child: ClipOval(
                    child: Image.network(
                      u,
                      width: 50, height: 50, fit: BoxFit.cover,
                      errorBuilder: (_, __, ___) => Container(
                        width: 50, height: 50, color: Colors.grey.shade300,
                        alignment: Alignment.center,
                        child: const Icon(Icons.broken_image_outlined, size: 18),
                      ),
                    ),
                  ),
                ),
              );
            }),
          ),
      ],
    );
  }
}
