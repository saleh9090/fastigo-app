import '../core/api_client.dart';
import '../core/storage_service.dart';

class AuthService {
  AuthService({
    required ApiClient apiClient,
    required StorageService storageService,
  })  : _apiClient = apiClient,
        _storageService = storageService;

  final ApiClient _apiClient;
  final StorageService _storageService;

  Future<void> login(String email, String password) async {
    final response = await _apiClient.dio.post(
      '/shop/login',
      data: {
        'email': email,
        'password': password,
      },
    );

    final data = response.data;
    final token = _extractToken(data);

    if (token == null || token.isEmpty) {
      throw Exception('Login response did not include an auth token.');
    }

    await _storageService.saveToken(token);
  }

  Future<Map<String, dynamic>> profile() async {
    final response = await _apiClient.dio.get('/shop/profile');
    final data = response.data;

    if (data is Map<String, dynamic>) {
      final profile = data['data'];
      if (profile is Map<String, dynamic>) {
        return profile;
      }
      return data;
    }

    return {};
  }

  String? _extractToken(dynamic data) {
    if (data is! Map<String, dynamic>) {
      return null;
    }

    final token = data['token'] ??
        data['access_token'] ??
        data['bearer_token'] ??
        data['plainTextToken'];

    if (token is String) {
      return token;
    }

    final nestedData = data['data'];
    if (nestedData is Map<String, dynamic>) {
      final nestedToken = nestedData['token'] ??
          nestedData['access_token'] ??
          nestedData['bearer_token'] ??
          nestedData['plainTextToken'];

      if (nestedToken is String) {
        return nestedToken;
      }
    }

    return null;
  }
}
