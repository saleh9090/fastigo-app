import 'package:flutter/material.dart';

import 'bill_details_page.dart';
import 'create_bill_page.dart';

class BillsPage extends StatelessWidget {
  const BillsPage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Card(
            child: ListTile(
              leading: const Icon(Icons.receipt_long),
              title: const Text('No bills yet'),
              subtitle: const Text('Created bills will appear here.'),
              trailing: const Icon(Icons.chevron_right),
              onTap: () {
                Navigator.of(context).push(
                  MaterialPageRoute(
                    builder: (_) => const BillDetailsPage(),
                  ),
                );
              },
            ),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () {
          Navigator.of(context).push(
            MaterialPageRoute(
              builder: (_) => const CreateBillPage(),
            ),
          );
        },
        icon: const Icon(Icons.add),
        label: const Text('Create Bill'),
      ),
    );
  }
}
