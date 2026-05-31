import 'package:flutter/material.dart';

class BillDetailsPage extends StatelessWidget {
  const BillDetailsPage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Bill Details'),
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: const [
          Card(
            child: ListTile(
              title: Text('Bill Number'),
              subtitle: Text('Pending'),
            ),
          ),
          Card(
            child: ListTile(
              title: Text('Status'),
              subtitle: Text('In Process'),
            ),
          ),
          Card(
            child: ListTile(
              title: Text('Payment'),
              subtitle: Text('Unpaid'),
            ),
          ),
        ],
      ),
    );
  }
}
