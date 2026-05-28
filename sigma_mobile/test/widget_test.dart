import 'package:flutter_test/flutter_test.dart';
import 'package:sigma_mobile/main.dart';

void main() {
  testWidgets('Smoke test for SIGMA Login Screen', (WidgetTester tester) async {
    // Build our app and trigger a frame.
    await tester.pumpWidget(const SigmaApp());

    // Verify that our login title and button exist.
    expect(find.text('SIGMA'), findsOneWidget);
    expect(find.text('MASUK'), findsOneWidget);
  });
}
