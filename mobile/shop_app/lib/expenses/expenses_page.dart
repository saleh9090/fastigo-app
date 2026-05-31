import 'package:flutter/material.dart';

import '../widgets/placeholder_page.dart';

class ExpensesPage extends StatelessWidget {
  const ExpensesPage({super.key});

  @override
  Widget build(BuildContext context) {
    return const PlaceholderPage(
      title: 'Expenses',
      message: 'Daily business expenses will be managed here.',
    );
  }
}
