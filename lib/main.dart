import 'package:flutter/material.dart';
import 'package:apps_nanolite/pages/welcome_screen.dart';

void main() => runApp(MyApp());

class MyApp extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Nanopiko App',
      theme: ThemeData(
        primarySwatch: Colors.blue,
      ),
      home: WelcomeScreen(),  // Set WelcomeScreen sebagai halaman pertama
    );
  }
}
