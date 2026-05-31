import 'package:flutter/foundation.dart';

import '../core/storage_service.dart';
import 'auth_service.dart';

class AuthProvider extends ChangeNotifier {
  AuthProvider({
    required AuthService authService,
    required StorageService storageService,
  })  : _authService = authService,
        _storageService = storageService;

  AuthService _authService;
  StorageService _storageService;

  bool isLoading = false;
  String? errorMessage;
  Map<String, dynamic>? profile;

  void updateServices({
    required AuthService authService,
    required StorageService storageService,
  }) {
    _authService = authService;
    _storageService = storageService;
  }

  Future<bool> login(String email, String password) async {
    isLoading = true;
    errorMessage = null;
    notifyListeners();

    try {
      await _authService.login(email, password);
      await loadProfile(ignoreErrors: true);
      return true;
    } catch (error) {
      errorMessage = error.toString();
      return false;
    } finally {
      isLoading = false;
      notifyListeners();
    }
  }

  Future<void> loadProfile({bool ignoreErrors = false}) async {
    try {
      profile = await _authService.profile();
      notifyListeners();
    } catch (error) {
      if (!ignoreErrors) {
        errorMessage = error.toString();
        notifyListeners();
      }
    }
  }

  Future<void> logout() async {
    await _storageService.clearToken();
    profile = null;
    errorMessage = null;
    notifyListeners();
  }
}
