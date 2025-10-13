import 'package:flutter/material.dart';

import 'form_garansi.dart';

class GaransiProfileScreen extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Garansi'),
        backgroundColor: Colors.blue,
        elevation: 0,
        leading: IconButton(
          icon: Icon(Icons.arrow_back),
          onPressed: () {
            Navigator.pop(context); // Navigate back to previous screen
          },
        ),
      ),
      body: Padding(
        padding: const EdgeInsets.all(20.0),
        child: Column(
          children: <Widget>[
            // Judul Garansi
            Row(
              children: [
                Icon(Icons.card_giftcard, color: Colors.white, size: 30),
                SizedBox(width: 10),
                Text(
                  'Garansi',
                  style: TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                  ),
                ),
              ],
            ),
            SizedBox(height: 20),

            // Daftar Garansi
            ListView.builder(
              shrinkWrap: true,
              itemCount: 2, // Jumlah item garansi
              itemBuilder: (context, index) {
                return Card(
                  color: Colors.blue[700],
                  margin: EdgeInsets.symmetric(vertical: 10),
                  child: ListTile(
                    contentPadding: EdgeInsets.all(12),
                    title: Text(
                      'Garansi',
                      style: TextStyle(fontSize: 18, color: Colors.white),
                    ),
                    subtitle: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: <Widget>[
                        Text(
                          'Customer: Toko Berkah',
                          style: TextStyle(color: Colors.white),
                        ),
                        Text(
                          'Tanggal Konsinyasi: 30/05/25',
                          style: TextStyle(color: Colors.white),
                        ),
                        Text(
                          'Produk: Nasmitec',
                          style: TextStyle(color: Colors.white),
                        ),
                      ],
                    ),
                    trailing: Icon(Icons.arrow_forward, color: Colors.white),
                    onTap: () {
                      // Navigasi ke form_garansi.dart
                      Navigator.push(
                        context,
                        MaterialPageRoute(builder: (context) => GaransiFormScreen()),
                      );
                    },
                  ),
                );
              },
            ),
            Spacer(),
          ],
        ),
      ),
      backgroundColor: Colors.blue[800],
    );
  }
}
