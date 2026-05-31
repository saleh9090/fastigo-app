import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'auth/auth_provider.dart';
import 'auth/auth_service.dart';
import 'auth/login_page.dart';
import 'core/api_client.dart';
import 'core/constants.dart';
import 'core/storage_service.dart';

void main() {
  runApp(const FastigoShopApp());
}

class FastigoShopApp extends StatelessWidget {
  const FastigoShopApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        Provider<StorageService>(
          create: (_) => StorageService(),
        ),
        ProxyProvider<StorageService, ApiClient>(
          update: (_, storageService, __) => ApiClient(storageService),
        ),
        ProxyProvider2<ApiClient, StorageService, AuthService>(
          update: (_, apiClient, storageService, __) => AuthService(
            apiClient: apiClient,
            storageService: storageService,
          ),
        ),
        ChangeNotifierProxyProvider2<AuthService, StorageService, AuthProvider>(
          create: (context) => AuthProvider(
            authService: context.read<AuthService>(),
            storageService: context.read<StorageService>(),
          ),
          update: (_, authService, storageService, authProvider) {
            return authProvider!
              ..updateServices(
                authService: authService,
                storageService: storageService,
              );
          },
        ),
      ],
      child: MaterialApp(
        title: AppConstants.appName,
        debugShowCheckedModeBanner: false,
        theme: ThemeData(
          colorScheme: ColorScheme.fromSeed(seedColor: Colors.teal),
          useMaterial3: true,
        ),
        home: const LoginPage(),
      ),
    );
  }
}
