import 'package:flutter/material.dart';

import 'form_sales_order.dart';

class SalesOrderProfileScreen extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Sales Order'),
        backgroundColor: Colors.blue,
        elevation: 0,
        leading: IconButton(
          icon: Icon(Icons.arrow_back),
          onPressed: () {
            // Kembali ke HomeScreen
            Navigator.pop(context);
          },
        ),
      ),
      body: Padding(
        padding: const EdgeInsets.all(20.0),
        child: Column(
          children: <Widget>[
            // Judul Sales Order
            Row(
              children: [
                Icon(Icons.store, color: Colors.white, size: 30),
                SizedBox(width: 10),
                Text(
                  'Sales Order',
                  style: TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                  ),
                ),
              ],
            ),
            SizedBox(height: 20),

            // Daftar Sales Order
            ListView.builder(
              shrinkWrap: true,
              itemCount: 2, // Bisa diganti sesuai jumlah data
              itemBuilder: (context, index) {
                return Card(
                  color: Colors.blue[700],
                  margin: EdgeInsets.symmetric(vertical: 10),
                  child: ListTile(
                    contentPadding: EdgeInsets.all(12),
                    title: Text(
                      'Sales Order',
                      style: TextStyle(fontSize: 18, color: Colors.white),
                    ),
                    subtitle: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: <Widget>[
                        Text(
                          'No Order: F12345$index',
                          style: TextStyle(color: Colors.white),
                        ),
                        Text(
                          'No. SKU: 12345678$index',
                          style: TextStyle(color: Colors.white),
                        ),
                        Text(
                          'Total: Rp 4000$index',
                          style: TextStyle(color: Colors.white),
                        ),
                      ],
                    ),
                    trailing: Icon(Icons.arrow_forward, color: Colors.white),
                    onTap: () {
                      // Navigasi ke form_sales_order.dart saat tap
                      Navigator.push(
                        context,
                        MaterialPageRoute(builder: (context) => SalesOrderFormScreen()),
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
