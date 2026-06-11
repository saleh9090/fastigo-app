import 'package:flutter_test/flutter_test.dart';

import 'package:fastigo_business/main.dart';

void main() {
  testWidgets('shows the business login screen', (WidgetTester tester) async {
    await tester.pumpWidget(const FastigoBusinessApp());

    expect(find.text('Fastigo Business'), findsOneWidget);
    expect(find.text('Email'), findsOneWidget);
    expect(find.text('Login'), findsOneWidget);
  });
}
