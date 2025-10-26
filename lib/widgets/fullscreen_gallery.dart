// lib/widgets/fullscreen_gallery.dart
import 'package:flutter/material.dart';

class FullscreenGallery extends StatefulWidget {
  final List<String> urls;
  final int initialIndex;
  const FullscreenGallery({super.key, required this.urls, this.initialIndex = 0});

  @override
  State<FullscreenGallery> createState() => _FullscreenGalleryState();
}

class _FullscreenGalleryState extends State<FullscreenGallery> {
  late final PageController _controller = PageController(initialPage: widget.initialIndex);

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: () => Navigator.pop(context),
      child: Scaffold(
        backgroundColor: Colors.black.withOpacity(0.98),
        body: SafeArea(
          child: PageView.builder(
            controller: _controller,
            itemCount: widget.urls.length,
            itemBuilder: (_, i) {
              final url = widget.urls[i];
              return Center(
                child: Hero(
                  tag: 'img-hero-$url',
                  child: InteractiveViewer(
                    maxScale: 5,
                    child: Image.network(url, fit: BoxFit.contain),
                  ),
                ),
              );
            },
          ),
        ),
      ),
    );
  }
}
