import 'package:flutter/material.dart';

import '../widgets/placeholder_page.dart';

class ProductsPage extends StatelessWidget {
  const ProductsPage({super.key});

  @override
  Widget build(BuildContext context) {
    return const PlaceholderPage(
      title: 'Products',
      message: 'Products and services will be listed here.',
    );
  }
}
