import 'package:flutter/material.dart';

import 'form_return.dart';

class ReturnProfileScreen extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Return'),
        backgroundColor: Colors.blue,
        elevation: 0,
        leading: IconButton(
          icon: Icon(Icons.arrow_back),
          onPressed: () {
            Navigator.pop(context); // Navigate back to the previous screen
          },
        ),
      ),
      body: Padding(
        padding: const EdgeInsets.all(20.0),
        child: Column(
          children: <Widget>[
            // Judul Return
            Row(
              children: [
                Icon(Icons.refresh, color: Colors.white, size: 30),
                SizedBox(width: 10),
                Text(
                  'Return',
                  style: TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                  ),
                ),
              ],
            ),
            SizedBox(height: 20),

            // Daftar Return
            ListView.builder(
              shrinkWrap: true,
              itemCount: 2, // Jumlah item return
              itemBuilder: (context, index) {
                return Card(
                  color: Colors.blue[700],
                  margin: EdgeInsets.symmetric(vertical: 10),
                  child: ListTile(
                    contentPadding: EdgeInsets.all(12),
                    title: Text(
                      'Return',
                      style: TextStyle(fontSize: 18, color: Colors.white),
                    ),
                    subtitle: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: <Widget>[
                        Text(
                          'No Order: RE12345',
                          style: TextStyle(color: Colors.white),
                        ),
                        Text(
                          'Customer: Toko Berkah',
                          style: TextStyle(color: Colors.white),
                        ),
                        Text(
                          'Produk: Nasmitec',
                          style: TextStyle(color: Colors.white),
                        ),
                        Text(
                          'Total: Rp 4000',
                          style: TextStyle(color: Colors.white),
                        ),
                      ],
                    ),
                    trailing: Icon(Icons.arrow_forward, color: Colors.white),
                    onTap: () {
                      // Navigate to the FormReturnScreen
                      Navigator.push(
                        context,
                        MaterialPageRoute(builder: (context) => ReturnFormScreen()),
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
