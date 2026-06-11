# fastigo_business

Fastigo Business mobile app for company managers and branch employees.

## iOS simulator from Desktop

If the project is stored under `~/Desktop`, iOS simulator builds can fail with
`resource fork, Finder information, or similar detritus not allowed`. This is a
macOS extended-attribute issue on generated iOS framework binaries, not a
Flutter code error.

Run the app through the clean temp-copy helper:

```bash
cd /Users/saleh9090/Desktop/fastigo-app/mobile/fastigo_business
./run_ios_simulator.sh
```

To choose a different simulator:

```bash
./run_ios_simulator.sh "iPhone 17 Pro Max"
```

## Getting Started

This project is a starting point for a Flutter application.

A few resources to get you started if this is your first Flutter project:

- [Learn Flutter](https://docs.flutter.dev/get-started/learn-flutter)
- [Write your first Flutter app](https://docs.flutter.dev/get-started/codelab)
- [Flutter learning resources](https://docs.flutter.dev/reference/learning-resources)

For help getting started with Flutter development, view the
[online documentation](https://docs.flutter.dev/), which offers tutorials,
samples, guidance on mobile development, and a full API reference.
