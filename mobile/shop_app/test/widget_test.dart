import 'package:flutter_test/flutter_test.dart';
import 'package:shop_app/main.dart';

void main() {
  testWidgets('shows login page', (WidgetTester tester) async {
    await tester.pumpWidget(const FastigoShopApp());

    expect(find.text('Fastigo Shop'), findsOneWidget);
    expect(find.text('Email'), findsOneWidget);
    expect(find.text('Password'), findsOneWidget);
    expect(find.text('Login'), findsOneWidget);
  });
}
