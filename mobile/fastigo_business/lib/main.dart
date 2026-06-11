import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'dart:math' as math;

import 'package:flutter/material.dart';

void main() {
  runApp(const FastigoBusinessApp());
}

const Color brandPurple = Color(0xFF6A49F2);
const Color darkPurple = Color(0xFF32236F);
const Color lightPurple = Color(0xFFF6F4FE);
const Color bodyPurple = Color(0xFF3E3F66);

class FastigoBusinessApp extends StatelessWidget {
  const FastigoBusinessApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Fastigo Business',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(
          seedColor: brandPurple,
          primary: brandPurple,
          secondary: darkPurple,
          surface: Colors.white,
        ),
        scaffoldBackgroundColor: lightPurple,
        appBarTheme: const AppBarTheme(
          backgroundColor: Colors.white,
          foregroundColor: darkPurple,
          elevation: 0,
          centerTitle: false,
        ),
        cardTheme: CardThemeData(
          color: Colors.white,
          elevation: 0,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(14),
            side: const BorderSide(color: Color(0xFFE8E4F8)),
          ),
        ),
        inputDecorationTheme: InputDecorationTheme(
          filled: true,
          fillColor: Colors.white,
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: const BorderSide(color: Color(0xFFDFDAF3)),
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: const BorderSide(color: Color(0xFFDFDAF3)),
          ),
        ),
        filledButtonTheme: FilledButtonThemeData(
          style: FilledButton.styleFrom(
            backgroundColor: brandPurple,
            foregroundColor: Colors.white,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(12),
            ),
            padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 14),
          ),
        ),
        textTheme: const TextTheme(
          titleLarge: TextStyle(fontWeight: FontWeight.w800, color: darkPurple),
          titleMedium: TextStyle(
            fontWeight: FontWeight.w700,
            color: darkPurple,
          ),
          bodyMedium: TextStyle(color: bodyPurple),
        ),
      ),
      home: const AppRoot(),
    );
  }
}

class AppRoot extends StatefulWidget {
  const AppRoot({super.key});

  @override
  State<AppRoot> createState() => _AppRootState();
}

class _AppRootState extends State<AppRoot> {
  final ApiClient api = ApiClient();
  Session? session;

  void signedIn(Session nextSession) {
    setState(() => session = nextSession);
  }

  Future<void> signOut() async {
    try {
      await api.post('/shop/logout');
    } catch (_) {
      // Logging out locally is more important than surfacing network noise.
    }
    api.token = null;
    setState(() => session = null);
  }

  @override
  Widget build(BuildContext context) {
    if (session == null) {
      return LoginScreen(api: api, onSignedIn: signedIn);
    }

    return BusinessShell(api: api, session: session!, onSignOut: signOut);
  }
}

class ApiClient {
  String baseUrl = 'http://127.0.0.1:8000/api';
  String? token;

  Future<dynamic> get(String path) => _request('GET', path);
  Future<dynamic> post(String path, {Map<String, dynamic>? body}) =>
      _request('POST', path, body: body);
  Future<dynamic> put(String path, {Map<String, dynamic>? body}) =>
      _request('PUT', path, body: body);
  Future<dynamic> delete(String path) => _request('DELETE', path);

  Future<dynamic> _request(
    String method,
    String path, {
    Map<String, dynamic>? body,
  }) async {
    final uri = Uri.parse('$baseUrl$path');
    final client = HttpClient();
    client.connectionTimeout = const Duration(seconds: 12);

    try {
      final request = await client.openUrl(method, uri);
      request.headers.set(HttpHeaders.acceptHeader, 'application/json');
      request.headers.set(
        HttpHeaders.contentTypeHeader,
        'application/json; charset=utf-8',
      );
      if (token != null) {
        request.headers.set(HttpHeaders.authorizationHeader, 'Bearer $token');
      }
      if (body != null) {
        request.add(utf8.encode(jsonEncode(body)));
      }

      final response = await request.close();
      final payload = await response.transform(utf8.decoder).join();
      final decoded = payload.isEmpty
          ? <String, dynamic>{}
          : jsonDecode(payload);

      if (response.statusCode < 200 || response.statusCode >= 300) {
        throw ApiException(_messageFrom(decoded), response.statusCode);
      }

      return decoded;
    } on SocketException {
      throw ApiException(
        'Cannot connect to $baseUrl. Check that the Laravel server is running.',
        0,
      );
    } finally {
      client.close(force: true);
    }
  }

  String _messageFrom(dynamic decoded) {
    if (decoded is Map) {
      if (decoded['message'] != null) return decoded['message'].toString();
      if (decoded['errors'] is Map && (decoded['errors'] as Map).isNotEmpty) {
        final first = (decoded['errors'] as Map).values.first;
        if (first is List && first.isNotEmpty) return first.first.toString();
      }
    }
    return 'Request failed.';
  }
}

class ApiException implements Exception {
  ApiException(this.message, this.statusCode);
  final String message;
  final int statusCode;

  @override
  String toString() => message;
}

class Session {
  Session({
    required this.user,
    required this.company,
    required this.branch,
    required this.role,
  });

  final Map<String, dynamic> user;
  final Map<String, dynamic>? company;
  final Map<String, dynamic>? branch;
  final String role;

  bool get isManager => role == 'company_manager';

  String get userName => text(user['name'], fallback: 'User');
  String get companyName => text(company?['name'], fallback: 'Company');
  String get branchName => text(branch?['name'], fallback: 'All branches');
}

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key, required this.api, required this.onSignedIn});

  final ApiClient api;
  final ValueChanged<Session> onSignedIn;

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final email = TextEditingController(text: 'saleh9090@gmail.com');
  final password = TextEditingController(text: 'saleh9090@gmail.com');
  bool loading = false;
  String? error;

  Future<void> login() async {
    setState(() {
      loading = true;
      error = null;
    });

    try {
      final response =
          await widget.api.post(
                '/shop/login',
                body: {'email': email.text.trim(), 'password': password.text},
              )
              as Map<String, dynamic>;
      widget.api.token = response['token']?.toString();

      widget.onSignedIn(
        Session(
          user: asMap(response['user']),
          company: nullableMap(response['company']),
          branch: nullableMap(response['branch']),
          role: text(
            response['user']?['role'],
            fallback: text(response['role'], fallback: 'branch_employee'),
          ),
        ),
      );
    } on ApiException catch (exception) {
      setState(() => error = exception.message);
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 460),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Container(
                    width: 64,
                    height: 64,
                    decoration: BoxDecoration(
                      color: brandPurple,
                      borderRadius: BorderRadius.circular(18),
                    ),
                    child: const Icon(
                      Icons.storefront_rounded,
                      color: Colors.white,
                      size: 32,
                    ),
                  ),
                  const SizedBox(height: 22),
                  const Text(
                    'Fastigo Business',
                    style: TextStyle(
                      fontSize: 32,
                      fontWeight: FontWeight.w900,
                      color: darkPurple,
                    ),
                  ),
                  const SizedBox(height: 8),
                  const Text(
                    'Manage bills, branches, expenses, reports, items, and staff from one mobile workspace.',
                  ),
                  const SizedBox(height: 26),
                  TextField(
                    controller: email,
                    keyboardType: TextInputType.emailAddress,
                    decoration: const InputDecoration(labelText: 'Email'),
                  ),
                  const SizedBox(height: 12),
                  TextField(
                    controller: password,
                    obscureText: true,
                    decoration: const InputDecoration(labelText: 'Password'),
                  ),
                  if (error != null) ...[
                    const SizedBox(height: 12),
                    ErrorBox(message: error!),
                  ],
                  const SizedBox(height: 18),
                  FilledButton(
                    onPressed: loading ? null : login,
                    child: loading
                        ? const SizedBox(
                            width: 20,
                            height: 20,
                            child: CircularProgressIndicator(
                              strokeWidth: 2,
                              color: Colors.white,
                            ),
                          )
                        : const Text('Login'),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class BusinessShell extends StatefulWidget {
  const BusinessShell({
    super.key,
    required this.api,
    required this.session,
    required this.onSignOut,
  });

  final ApiClient api;
  final Session session;
  final VoidCallback onSignOut;

  @override
  State<BusinessShell> createState() => _BusinessShellState();
}

class _BusinessShellState extends State<BusinessShell> {
  int index = 0;

  late final List<AppSection> sections = [
    AppSection(
      'Dashboard',
      Icons.dashboard_rounded,
      DashboardScreen(api: widget.api, session: widget.session),
    ),
    AppSection(
      'Bills',
      Icons.receipt_long_rounded,
      BillsScreen(api: widget.api, session: widget.session),
    ),
    AppSection(
      'Expenses',
      Icons.payments_rounded,
      ExpensesScreen(api: widget.api, session: widget.session),
    ),
    AppSection(
      'Reports',
      Icons.bar_chart_rounded,
      ReportsScreen(api: widget.api, session: widget.session),
    ),
    AppSection(
      'Manage',
      Icons.settings_rounded,
      ManageScreen(api: widget.api, session: widget.session),
    ),
  ];

  @override
  Widget build(BuildContext context) {
    final selected = sections[index];

    return Scaffold(
      appBar: AppBar(
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(selected.title),
            Text(
              '${widget.session.companyName} • ${widget.session.role.replaceAll('_', ' ')}',
              style: const TextStyle(
                fontSize: 12,
                color: bodyPurple,
                fontWeight: FontWeight.w500,
              ),
            ),
          ],
        ),
        actions: [
          IconButton(
            onPressed: widget.onSignOut,
            icon: const Icon(Icons.logout_rounded),
            tooltip: 'Logout',
          ),
        ],
      ),
      body: selected.child,
      bottomNavigationBar: NavigationBar(
        selectedIndex: index,
        onDestinationSelected: (next) => setState(() => index = next),
        destinations: sections
            .map(
              (section) => NavigationDestination(
                icon: Icon(section.icon),
                label: section.title,
              ),
            )
            .toList(),
      ),
    );
  }
}

class AppSection {
  AppSection(this.title, this.icon, this.child);
  final String title;
  final IconData icon;
  final Widget child;
}

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key, required this.api, required this.session});
  final ApiClient api;
  final Session session;

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  late Future<Map<String, dynamic>> future;
  String period = 'day';
  int? branchId;
  DateTime frameDate = DateTime.now();
  DateTime customStartDate = DateTime.now();
  DateTime customEndDate = DateTime.now();

  @override
  void initState() {
    super.initState();
    future = load();
  }

  Future<Map<String, dynamic>> load() async {
    final branches = widget.session.isManager
        ? asList(asMap(await widget.api.get('/shop/branches'))['branches'])
        : <dynamic>[];
    final params = <String, String>{'period': period};

    if (branchId != null) {
      params['branch_id'] = branchId.toString();
    }

    if (period == 'custom') {
      params['start_date'] = formatDate(customStartDate);
      params['end_date'] = formatDate(customEndDate);
    } else {
      params['date'] = formatDate(frameDate);
    }

    final path = Uri(
      path: '/shop/dashboard',
      queryParameters: params,
    ).toString();
    final dashboard = asMap(await widget.api.get(path));

    return {'dashboard': dashboard, 'branches': branches};
  }

  void refresh() {
    setState(() {
      future = load();
    });
  }

  Future<void> pickCustomRange() async {
    final start = await showDatePicker(
      context: context,
      initialDate: customStartDate,
      firstDate: DateTime(2020),
      lastDate: DateTime.now().add(const Duration(days: 365)),
    );

    if (start == null || !mounted) return;

    final end = await showDatePicker(
      context: context,
      initialDate: customEndDate.isBefore(start) ? start : customEndDate,
      firstDate: start,
      lastDate: DateTime.now().add(const Duration(days: 365)),
    );

    if (end == null || !mounted) return;

    setState(() {
      period = 'custom';
      customStartDate = start;
      customEndDate = end;
      future = load();
    });
  }

  void shiftTimeline(int direction) {
    setState(() {
      switch (period) {
        case 'this_week':
          frameDate = frameDate.add(Duration(days: 7 * direction));
        case 'this_month':
        case 'last_month':
          frameDate = DateTime(frameDate.year, frameDate.month + direction);
        case 'this_year':
          frameDate = DateTime(frameDate.year + direction);
        case 'custom':
          final days =
              customEndDate.difference(customStartDate).inDays.abs() + 1;
          customStartDate = customStartDate.add(
            Duration(days: days * direction),
          );
          customEndDate = customEndDate.add(Duration(days: days * direction));
        default:
          frameDate = frameDate.add(Duration(days: direction));
      }
      future = load();
    });
  }

  Future<void> openTimelineFrame() async {
    var selectedPeriod = period == 'custom' ? 'custom' : period;

    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      showDragHandle: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (context) => StatefulBuilder(
        builder: (context, setSheetState) => SafeArea(
          child: ConstrainedBox(
            constraints: BoxConstraints(
              maxHeight: MediaQuery.sizeOf(context).height * 0.82,
            ),
            child: SingleChildScrollView(
              padding: const EdgeInsets.fromLTRB(24, 10, 24, 28),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Row(
                    children: [
                      const Expanded(
                        child: Text(
                          'Timeline frame',
                          style: TextStyle(
                            fontSize: 28,
                            fontWeight: FontWeight.w900,
                            color: Color(0xFF24213A),
                          ),
                        ),
                      ),
                      FilledButton.tonal(
                        onPressed: () {
                          if (selectedPeriod != 'custom') {
                            setState(() {
                              period = selectedPeriod;
                              final now = DateTime.now();
                              frameDate = selectedPeriod == 'last_month'
                                  ? DateTime(now.year, now.month - 1)
                                  : now;
                              future = load();
                            });
                          }
                          Navigator.pop(context);
                        },
                        child: const Text('Done'),
                      ),
                    ],
                  ),
                  const SizedBox(height: 26),
                  timelineOption(
                    label: 'Today',
                    selected: selectedPeriod == 'day',
                    onTap: () => setSheetState(() => selectedPeriod = 'day'),
                  ),
                  timelineOption(
                    label: 'This week',
                    selected: selectedPeriod == 'this_week',
                    onTap: () =>
                        setSheetState(() => selectedPeriod = 'this_week'),
                  ),
                  timelineOption(
                    label: 'This month',
                    selected: selectedPeriod == 'this_month',
                    onTap: () =>
                        setSheetState(() => selectedPeriod = 'this_month'),
                  ),
                  timelineOption(
                    label: 'Last month',
                    selected: selectedPeriod == 'last_month',
                    onTap: () =>
                        setSheetState(() => selectedPeriod = 'last_month'),
                  ),
                  timelineOption(
                    label: 'This year',
                    selected: selectedPeriod == 'this_year',
                    onTap: () =>
                        setSheetState(() => selectedPeriod = 'this_year'),
                  ),
                  ListTile(
                    contentPadding: EdgeInsets.zero,
                    title: const Text(
                      'Custom date',
                      style: TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    trailing: const Icon(Icons.chevron_right_rounded),
                    onTap: () {
                      Navigator.pop(context);
                      pickCustomRange();
                    },
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget timelineOption({
    required String label,
    required bool selected,
    required VoidCallback onTap,
  }) {
    return Column(
      children: [
        ListTile(
          contentPadding: EdgeInsets.zero,
          title: Text(
            label,
            style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800),
          ),
          trailing: Icon(
            selected ? Icons.radio_button_checked : Icons.radio_button_off,
            color: selected ? brandPurple : Colors.grey.shade300,
            size: 28,
          ),
          onTap: onTap,
        ),
        Divider(color: Colors.grey.shade100),
      ],
    );
  }

  String timelineTitle() {
    return switch (period) {
      'this_week' =>
        'Week . ${compactDate(frameDate.startOfWeek())} - ${compactDate(frameDate.endOfWeek())}',
      'this_month' => 'Month . ${monthName(frameDate.month)} ${frameDate.year}',
      'last_month' =>
        'Last month . ${monthName(frameDate.month)} ${frameDate.year}',
      'this_year' => 'Year . ${frameDate.year}',
      'custom' =>
        'Custom . ${compactDate(customStartDate)} - ${compactDate(customEndDate)}',
      _ => 'Day . ${compactDate(frameDate)}',
    };
  }

  String selectedLocationName(List<dynamic> branches) {
    if (!widget.session.isManager) {
      return widget.session.branchName;
    }

    if (branchId == null) {
      return 'All Locations';
    }

    final selected = branches
        .map(asMap)
        .where((branch) => number(branch['id'])?.toInt() == branchId)
        .firstOrNull;

    return text(selected?['name'], fallback: 'All Locations');
  }

  Future<void> openLocationPicker(List<dynamic> branches) async {
    if (!widget.session.isManager) return;

    await showModalBottomSheet<void>(
      context: context,
      showDragHandle: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (context) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(24, 10, 24, 28),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Align(
                alignment: Alignment.centerLeft,
                child: Text(
                  'Locations',
                  style: TextStyle(
                    fontSize: 28,
                    fontWeight: FontWeight.w900,
                    color: darkPurple,
                  ),
                ),
              ),
              const SizedBox(height: 18),
              locationOption(
                label: 'All Locations',
                selected: branchId == null,
                onTap: () {
                  setState(() {
                    branchId = null;
                    future = load();
                  });
                  Navigator.pop(context);
                },
              ),
              ...branches.map((branch) {
                final id = number(branch['id'])?.toInt();
                return locationOption(
                  label: text(branch['name']),
                  selected: id == branchId,
                  onTap: () {
                    setState(() {
                      branchId = id;
                      future = load();
                    });
                    Navigator.pop(context);
                  },
                );
              }),
            ],
          ),
        ),
      ),
    );
  }

  Widget locationOption({
    required String label,
    required bool selected,
    required VoidCallback onTap,
  }) {
    return Column(
      children: [
        ListTile(
          contentPadding: EdgeInsets.zero,
          title: Text(
            label,
            style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800),
          ),
          trailing: Icon(
            selected ? Icons.radio_button_checked : Icons.radio_button_off,
            color: selected ? brandPurple : Colors.grey.shade300,
            size: 28,
          ),
          onTap: onTap,
        ),
        Divider(color: Colors.grey.shade100),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    return RefreshableFuture(
      future: future,
      onRefresh: refresh,
      builder: (data) {
        final dashboard = asMap(data['dashboard']);
        final branches = asList(data['branches']);
        final latestBills = asList(dashboard['latest_bills']);
        final dashboardSummary = asMap(dashboard['dashboard_summary']);
        final grossSalesChart = asMap(dashboard['gross_sales_chart']);
        final periodLabel = text(
          dashboard['period_label'],
          fallback: dashboardPeriodLabel(period),
        );
        return ListView(
          padding: const EdgeInsets.all(16),
          children: [
            DashboardTopBar(
              title: selectedLocationName(branches),
              onLocationTap: widget.session.isManager
                  ? () => openLocationPicker(branches)
                  : null,
              onNotificationTap: () {},
            ),
            const SizedBox(height: 14),
            Card(
              child: Padding(
                padding: const EdgeInsets.all(14),
                child: Column(
                  children: [
                    Row(
                      children: [
                        IconButton(
                          onPressed: () => shiftTimeline(-1),
                          icon: const Icon(Icons.chevron_left_rounded),
                          color: Colors.grey,
                          iconSize: 32,
                        ),
                        Expanded(
                          child: InkWell(
                            borderRadius: BorderRadius.circular(12),
                            onTap: openTimelineFrame,
                            child: Padding(
                              padding: const EdgeInsets.symmetric(vertical: 12),
                              child: Text(
                                timelineTitle(),
                                textAlign: TextAlign.center,
                                style: const TextStyle(
                                  fontSize: 18,
                                  fontWeight: FontWeight.w900,
                                  color: Colors.black,
                                ),
                              ),
                            ),
                          ),
                        ),
                        IconButton(
                          onPressed: () => shiftTimeline(1),
                          icon: const Icon(Icons.chevron_right_rounded),
                          color: Colors.grey,
                          iconSize: 32,
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 14),
            DashboardGaugeSummary(summary: dashboardSummary),
            const SizedBox(height: 14),
            GrossSalesChartCard(chart: grossSalesChart),
            const SizedBox(height: 14),
            GridLayout(
              children: [
                MetricCard(
                  '$periodLabel sales',
                  money(dashboard['today_sales']),
                  Icons.trending_up_rounded,
                ),
                MetricCard(
                  '$periodLabel expenses',
                  money(dashboard['today_expenses']),
                  Icons.trending_down_rounded,
                ),
                MetricCard(
                  'Net profit',
                  money(dashboard['today_net_profit']),
                  Icons.account_balance_wallet_rounded,
                ),
                MetricCard(
                  'Bills',
                  text(dashboard['total_bills_today'], fallback: '0'),
                  Icons.receipt_rounded,
                ),
                MetricCard(
                  'In process',
                  text(dashboard['bills_in_process'], fallback: '0'),
                  Icons.sync_rounded,
                ),
                MetricCard(
                  'Ready',
                  text(dashboard['bills_ready'], fallback: '0'),
                  Icons.task_alt_rounded,
                ),
              ],
            ),
            SectionHeader(title: 'Latest bills', actionLabel: null),
            if (latestBills.isEmpty)
              const EmptyState(label: 'No bills yet')
            else
              ...latestBills.map(
                (bill) => InfoTile(
                  title: text(bill['bill_number'], fallback: 'Bill'),
                  subtitle:
                      '${text(bill['customer_phone'])} • ${statusLabel(text(bill['status']))}',
                  trailing: money(bill['total_amount']),
                ),
              ),
          ],
        );
      },
    );
  }
}

class BillsScreen extends StatefulWidget {
  const BillsScreen({super.key, required this.api, required this.session});
  final ApiClient api;
  final Session session;

  @override
  State<BillsScreen> createState() => _BillsScreenState();
}

class _BillsScreenState extends State<BillsScreen> {
  late Future<List<dynamic>> future;

  @override
  void initState() {
    super.initState();
    future = load();
  }

  Future<List<dynamic>> load() async =>
      paginatedList(await widget.api.get('/shop/bills'));

  void refresh() {
    setState(() {
      future = load();
    });
  }

  Future<void> createBill() async {
    final created = await showDialog<bool>(
      context: context,
      builder: (_) => BillFormDialog(api: widget.api, session: widget.session),
    );
    if (created == true) refresh();
  }

  Future<void> updateStatus(Map<String, dynamic> bill, String status) async {
    await runAction(
      context,
      () => widget.api.post(
        '/shop/bills/${bill['id']}/status',
        body: {'status': status},
      ),
    );
    refresh();
  }

  @override
  Widget build(BuildContext context) {
    return ScreenScaffold(
      action: FloatingActionButton.extended(
        onPressed: createBill,
        icon: const Icon(Icons.add_rounded),
        label: const Text('Bill'),
      ),
      child: RefreshableFuture(
        future: future,
        onRefresh: refresh,
        builder: (bills) => bills.isEmpty
            ? const EmptyState(label: 'No bills found')
            : ListView.builder(
                padding: const EdgeInsets.all(16),
                itemCount: bills.length,
                itemBuilder: (context, index) {
                  final bill = asMap(bills[index]);
                  return Card(
                    child: Padding(
                      padding: const EdgeInsets.all(14),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Expanded(
                                child: Text(
                                  text(bill['bill_number'], fallback: 'Bill'),
                                  style: const TextStyle(
                                    fontWeight: FontWeight.w800,
                                    color: darkPurple,
                                  ),
                                ),
                              ),
                              Text(
                                money(bill['total_amount']),
                                style: const TextStyle(
                                  fontWeight: FontWeight.w800,
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 6),
                          Text(
                            '${text(bill['customer_name'], fallback: 'Customer')} • ${text(bill['customer_phone'])}',
                          ),
                          const SizedBox(height: 10),
                          Wrap(
                            spacing: 8,
                            runSpacing: 8,
                            children: [
                              StatusChip(
                                label: statusLabel(text(bill['status'])),
                                color: statusColor(text(bill['status'])),
                              ),
                              StatusChip(
                                label: paymentLabel(
                                  text(bill['payment_status']),
                                ),
                                color: Colors.blueGrey,
                              ),
                              for (final status in [
                                'in_process',
                                'ready',
                                'delivered',
                              ])
                                OutlinedButton(
                                  onPressed: text(bill['status']) == status
                                      ? null
                                      : () => updateStatus(bill, status),
                                  child: Text(statusLabel(status)),
                                ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  );
                },
              ),
      ),
    );
  }
}

class BillFormDialog extends StatefulWidget {
  const BillFormDialog({super.key, required this.api, required this.session});
  final ApiClient api;
  final Session session;

  @override
  State<BillFormDialog> createState() => _BillFormDialogState();
}

class _BillFormDialogState extends State<BillFormDialog> {
  final phone = TextEditingController();
  final paidAmount = TextEditingController(text: '0');
  final itemName = TextEditingController();
  final quantity = TextEditingController(text: '1');
  final unitPrice = TextEditingController(text: '0');
  String paymentStatus = 'unpaid';
  String paymentMethod = 'cash';
  int? branchId;
  int? productId;
  List<dynamic> branches = [];
  List<dynamic> products = [];
  List<Map<String, dynamic>> items = [];
  bool loading = true;
  bool saving = false;
  String? error;

  @override
  void initState() {
    super.initState();
    loadOptions();
  }

  Future<void> loadOptions() async {
    try {
      final branchResponse = asMap(await widget.api.get('/shop/branches'));
      final productResponse = asMap(await widget.api.get('/shop/items'));
      setState(() {
        branches = asList(branchResponse['branches']);
        products = asList(productResponse['items']);
        if (branches.length == 1) {
          branchId = number(branches.first['id'])?.toInt();
        }
        loading = false;
      });
    } catch (exception) {
      setState(() {
        error = exception.toString();
        loading = false;
      });
    }
  }

  void addItem() {
    final selectedProduct = products
        .cast<dynamic>()
        .map(asMap)
        .where((item) => number(item['id'])?.toInt() == productId)
        .firstOrNull;
    final name = selectedProduct == null
        ? itemName.text.trim()
        : text(selectedProduct['name']);
    final price = selectedProduct == null
        ? double.tryParse(unitPrice.text) ?? 0
        : number(selectedProduct['price'])?.toDouble() ?? 0;
    final qty = double.tryParse(quantity.text) ?? 1;

    if (name.isEmpty || qty <= 0) return;

    setState(() {
      items.add({
        if (productId != null) 'product_id': productId,
        'item_name': name,
        'item_type': text(selectedProduct?['type'], fallback: 'service'),
        'quantity': qty,
        'unit_price': price,
      });
      productId = null;
      itemName.clear();
      quantity.text = '1';
      unitPrice.text = '0';
    });
  }

  Future<void> save() async {
    setState(() {
      saving = true;
      error = null;
    });

    try {
      await widget.api.post(
        '/shop/bills',
        body: {
          'customer_phone': phone.text.trim(),
          if (branchId != null) 'branch_id': branchId,
          'paid_amount': double.tryParse(paidAmount.text) ?? 0,
          'payment_status': paymentStatus,
          'payment_method': paymentMethod,
          'status': 'in_process',
          'items': items,
        },
      );
      if (mounted) Navigator.pop(context, true);
    } catch (exception) {
      setState(() => error = exception.toString());
    } finally {
      if (mounted) setState(() => saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: const Text('Create bill'),
      content: loading
          ? const SizedBox(
              height: 120,
              child: Center(child: CircularProgressIndicator()),
            )
          : SingleChildScrollView(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  if (error != null) ErrorBox(message: error!),
                  const SizedBox(height: 8),
                  TextField(
                    controller: phone,
                    keyboardType: TextInputType.phone,
                    decoration: const InputDecoration(
                      labelText: 'Customer phone',
                    ),
                  ),
                  const SizedBox(height: 10),
                  DropdownButtonFormField<int>(
                    key: ValueKey('bill_branch_$branchId'),
                    initialValue: branchId,
                    decoration: const InputDecoration(labelText: 'Branch'),
                    items: branches
                        .map(
                          (branch) => DropdownMenuItem<int>(
                            value: number(branch['id'])?.toInt(),
                            child: Text(text(branch['name'])),
                          ),
                        )
                        .toList(),
                    onChanged: (value) => setState(() => branchId = value),
                  ),
                  const SizedBox(height: 10),
                  DropdownButtonFormField<int?>(
                    key: ValueKey('bill_product_$productId'),
                    initialValue: productId,
                    decoration: const InputDecoration(
                      labelText: 'Product/service',
                    ),
                    items: [
                      const DropdownMenuItem<int?>(
                        value: null,
                        child: Text('Custom item'),
                      ),
                      ...products.map(
                        (item) => DropdownMenuItem<int?>(
                          value: number(item['id'])?.toInt(),
                          child: Text(
                            '${text(item['name'])} • ${money(item['price'])}',
                          ),
                        ),
                      ),
                    ],
                    onChanged: (value) => setState(() {
                      productId = value;
                      final selected = products
                          .map(asMap)
                          .where((item) => number(item['id'])?.toInt() == value)
                          .firstOrNull;
                      if (selected != null) {
                        itemName.text = text(selected['name']);
                        unitPrice.text = text(selected['price'], fallback: '0');
                      }
                    }),
                  ),
                  const SizedBox(height: 10),
                  Row(
                    children: [
                      Expanded(
                        child: TextField(
                          controller: itemName,
                          decoration: const InputDecoration(
                            labelText: 'Item name',
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      SizedBox(
                        width: 86,
                        child: TextField(
                          controller: quantity,
                          keyboardType: TextInputType.number,
                          decoration: const InputDecoration(labelText: 'Qty'),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  Row(
                    children: [
                      Expanded(
                        child: TextField(
                          controller: unitPrice,
                          keyboardType: TextInputType.number,
                          decoration: const InputDecoration(
                            labelText: 'Unit price',
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      FilledButton.tonal(
                        onPressed: addItem,
                        child: const Text('Add'),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  if (items.isNotEmpty)
                    ...items.asMap().entries.map(
                      (entry) => ListTile(
                        dense: true,
                        title: Text(text(entry.value['item_name'])),
                        subtitle: Text(
                          '${entry.value['quantity']} × ${money(entry.value['unit_price'])}',
                        ),
                        trailing: IconButton(
                          icon: const Icon(Icons.close_rounded),
                          onPressed: () =>
                              setState(() => items.removeAt(entry.key)),
                        ),
                      ),
                    ),
                  TextField(
                    controller: paidAmount,
                    keyboardType: TextInputType.number,
                    decoration: const InputDecoration(labelText: 'Paid amount'),
                  ),
                  const SizedBox(height: 10),
                  DropdownButtonFormField<String>(
                    key: ValueKey('payment_status_$paymentStatus'),
                    initialValue: paymentStatus,
                    decoration: const InputDecoration(
                      labelText: 'Payment status',
                    ),
                    items: const [
                      DropdownMenuItem(value: 'unpaid', child: Text('Unpaid')),
                      DropdownMenuItem(
                        value: 'partial',
                        child: Text('Partially paid'),
                      ),
                      DropdownMenuItem(value: 'paid', child: Text('Paid')),
                    ],
                    onChanged: (value) =>
                        setState(() => paymentStatus = value ?? 'unpaid'),
                  ),
                  const SizedBox(height: 10),
                  DropdownButtonFormField<String>(
                    key: ValueKey('payment_method_$paymentMethod'),
                    initialValue: paymentMethod,
                    decoration: const InputDecoration(
                      labelText: 'Payment method',
                    ),
                    items: const [
                      DropdownMenuItem(value: 'cash', child: Text('Cash')),
                      DropdownMenuItem(value: 'card', child: Text('Card')),
                      DropdownMenuItem(
                        value: 'bank_transfer',
                        child: Text('Bank transfer'),
                      ),
                      DropdownMenuItem(value: 'mixed', child: Text('Mixed')),
                    ],
                    onChanged: (value) =>
                        setState(() => paymentMethod = value ?? 'cash'),
                  ),
                ],
              ),
            ),
      actions: [
        TextButton(
          onPressed: saving ? null : () => Navigator.pop(context),
          child: const Text('Cancel'),
        ),
        FilledButton(
          onPressed: saving || phone.text.trim().isEmpty || items.isEmpty
              ? null
              : save,
          child: Text(saving ? 'Saving...' : 'Create'),
        ),
      ],
    );
  }
}

class CatalogScreen extends StatefulWidget {
  const CatalogScreen({super.key, required this.api, required this.session});
  final ApiClient api;
  final Session session;

  @override
  State<CatalogScreen> createState() => _CatalogScreenState();
}

class _CatalogScreenState extends State<CatalogScreen> {
  late Future<Map<String, List<dynamic>>> future;

  @override
  void initState() {
    super.initState();
    future = load();
  }

  Future<Map<String, List<dynamic>>> load() async {
    final items = asList(asMap(await widget.api.get('/shop/items'))['items']);
    final categories = asList(
      asMap(await widget.api.get('/shop/categories'))['categories'],
    );
    final units = asList(asMap(await widget.api.get('/shop/units'))['units']);
    return {'items': items, 'categories': categories, 'units': units};
  }

  void refresh() {
    setState(() {
      future = load();
    });
  }

  Future<void> addCategory() async {
    final name = await promptText(
      context,
      title: 'New category',
      label: 'Category name',
    );
    if (name == null || name.trim().isEmpty) return;
    if (!mounted) return;
    await runAction(
      context,
      () => widget.api.post(
        '/shop/categories',
        body: {'name': name.trim(), 'active': true},
      ),
    );
    refresh();
  }

  Future<void> addItem(List<dynamic> categories, List<dynamic> units) async {
    final created = await showDialog<bool>(
      context: context,
      builder: (_) =>
          ItemDialog(api: widget.api, categories: categories, units: units),
    );
    if (created == true) refresh();
  }

  @override
  Widget build(BuildContext context) {
    return RefreshableFuture(
      future: future,
      onRefresh: refresh,
      builder: (data) {
        final items = data['items']!;
        final categories = data['categories']!;
        final units = data['units']!;
        return ListView(
          padding: const EdgeInsets.all(16),
          children: [
            Row(
              children: [
                Expanded(
                  child: FilledButton.icon(
                    onPressed: widget.session.isManager
                        ? () => addItem(categories, units)
                        : null,
                    icon: const Icon(Icons.add_rounded),
                    label: const Text('Item'),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: widget.session.isManager ? addCategory : null,
                    icon: const Icon(Icons.category_rounded),
                    label: const Text('Category'),
                  ),
                ),
              ],
            ),
            SectionHeader(title: 'Categories', actionLabel: null),
            if (categories.isEmpty)
              const EmptyState(label: 'No categories')
            else
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: categories
                    .map(
                      (category) => Chip(label: Text(text(category['name']))),
                    )
                    .toList(),
              ),
            SectionHeader(title: 'Items and services', actionLabel: null),
            if (items.isEmpty)
              const EmptyState(label: 'No items')
            else
              ...items.map(
                (item) => InfoTile(
                  title: text(item['name']),
                  subtitle:
                      '${text(item['category_name'], fallback: 'No category')} • ${text(item['type'], fallback: 'item')}',
                  trailing: money(item['price']),
                ),
              ),
          ],
        );
      },
    );
  }
}

class ItemDialog extends StatefulWidget {
  const ItemDialog({
    super.key,
    required this.api,
    required this.categories,
    required this.units,
    this.item,
  });
  final ApiClient api;
  final List<dynamic> categories;
  final List<dynamic> units;
  final Map<String, dynamic>? item;

  @override
  State<ItemDialog> createState() => _ItemDialogState();
}

class _ItemDialogState extends State<ItemDialog> {
  final name = TextEditingController();
  final price = TextEditingController(text: '0');
  final description = TextEditingController();
  String type = 'service';
  int? categoryId;
  int? unitId;
  String? error;
  bool saving = false;

  bool get isEditing => widget.item != null;

  @override
  void initState() {
    super.initState();
    final item = widget.item;
    if (item != null) {
      name.text = text(item['name']);
      price.text = text(item['price'], fallback: '0');
      description.text = text(item['description']);
      type = text(item['type'], fallback: 'service');
      categoryId = number(item['category_id'])?.toInt();
      unitId = number(item['unit_id'])?.toInt();
    }
  }

  Future<void> save() async {
    setState(() {
      saving = true;
      error = null;
    });
    try {
      final body = {
        'name': name.text.trim(),
        'type': type,
        'price': double.tryParse(price.text) ?? 0,
        'description': description.text.trim(),
        if (categoryId != null) 'category_id': categoryId,
        if (unitId != null) 'unit_id': unitId,
        'active': true,
      };
      if (isEditing) {
        await widget.api.put('/shop/items/${widget.item!['id']}', body: body);
      } else {
        await widget.api.post('/shop/items', body: body);
      }
      if (mounted) Navigator.pop(context, true);
    } catch (exception) {
      setState(() => error = exception.toString());
    } finally {
      if (mounted) setState(() => saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: Text(isEditing ? 'Edit item' : 'New item'),
      content: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            if (error != null) ErrorBox(message: error!),
            TextField(
              controller: name,
              onChanged: (_) => setState(() {}),
              decoration: const InputDecoration(labelText: 'Name'),
            ),
            const SizedBox(height: 10),
            DropdownButtonFormField<String>(
              key: ValueKey('item_type_$type'),
              initialValue: type,
              decoration: const InputDecoration(labelText: 'Type'),
              items: const [
                DropdownMenuItem(value: 'service', child: Text('Service')),
                DropdownMenuItem(value: 'product', child: Text('Product')),
              ],
              onChanged: (value) => setState(() => type = value ?? 'service'),
            ),
            const SizedBox(height: 10),
            DropdownButtonFormField<int?>(
              key: ValueKey('item_category_$categoryId'),
              initialValue: categoryId,
              decoration: const InputDecoration(labelText: 'Category'),
              items: [
                const DropdownMenuItem<int?>(
                  value: null,
                  child: Text('No category'),
                ),
                ...widget.categories.map(
                  (category) => DropdownMenuItem<int?>(
                    value: number(category['id'])?.toInt(),
                    child: Text(text(category['name'])),
                  ),
                ),
              ],
              onChanged: (value) => setState(() => categoryId = value),
            ),
            const SizedBox(height: 10),
            DropdownButtonFormField<int?>(
              key: ValueKey('item_unit_$unitId'),
              initialValue: unitId,
              decoration: const InputDecoration(labelText: 'Unit'),
              items: [
                const DropdownMenuItem<int?>(
                  value: null,
                  child: Text('No unit'),
                ),
                ...widget.units.map(
                  (unit) => DropdownMenuItem<int?>(
                    value: number(unit['id'])?.toInt(),
                    child: Text(text(unit['name'])),
                  ),
                ),
              ],
              onChanged: (value) => setState(() => unitId = value),
            ),
            const SizedBox(height: 10),
            TextField(
              controller: price,
              onChanged: (_) => setState(() {}),
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(labelText: 'Price'),
            ),
            const SizedBox(height: 10),
            TextField(
              controller: description,
              onChanged: (_) => setState(() {}),
              decoration: const InputDecoration(labelText: 'Description'),
            ),
          ],
        ),
      ),
      actions: [
        TextButton(
          onPressed: saving ? null : () => Navigator.pop(context),
          child: const Text('Cancel'),
        ),
        FilledButton(
          onPressed: saving || name.text.trim().isEmpty ? null : save,
          child: Text(saving ? 'Saving...' : 'Save'),
        ),
      ],
    );
  }
}

class CategoryDialog extends StatefulWidget {
  const CategoryDialog({super.key, required this.api, this.category});

  final ApiClient api;
  final Map<String, dynamic>? category;

  @override
  State<CategoryDialog> createState() => _CategoryDialogState();
}

class _CategoryDialogState extends State<CategoryDialog> {
  final name = TextEditingController();
  final description = TextEditingController();
  String? error;
  bool saving = false;

  bool get isEditing => widget.category != null;

  @override
  void initState() {
    super.initState();
    final category = widget.category;
    if (category != null) {
      name.text = text(category['name']);
      description.text = text(category['description']);
    }
  }

  Future<void> save() async {
    setState(() {
      saving = true;
      error = null;
    });
    try {
      final body = {
        'name': name.text.trim(),
        'description': description.text.trim(),
        'active': true,
      };
      if (isEditing) {
        await widget.api.put(
          '/shop/categories/${widget.category!['id']}',
          body: body,
        );
      } else {
        await widget.api.post('/shop/categories', body: body);
      }
      if (mounted) Navigator.pop(context, true);
    } catch (exception) {
      setState(() => error = exception.toString());
    } finally {
      if (mounted) setState(() => saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: Text(isEditing ? 'Edit item category' : 'New item category'),
      content: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (error != null) ErrorBox(message: error!),
          TextField(
            controller: name,
            onChanged: (_) => setState(() {}),
            decoration: const InputDecoration(labelText: 'Name'),
          ),
          const SizedBox(height: 10),
          TextField(
            controller: description,
            onChanged: (_) => setState(() {}),
            decoration: const InputDecoration(labelText: 'Description'),
          ),
        ],
      ),
      actions: [
        TextButton(
          onPressed: saving ? null : () => Navigator.pop(context),
          child: const Text('Cancel'),
        ),
        FilledButton(
          onPressed: saving || name.text.trim().isEmpty ? null : save,
          child: Text(saving ? 'Saving...' : 'Save'),
        ),
      ],
    );
  }
}

class UnitDialog extends StatefulWidget {
  const UnitDialog({super.key, required this.api, this.unit});

  final ApiClient api;
  final Map<String, dynamic>? unit;

  @override
  State<UnitDialog> createState() => _UnitDialogState();
}

class _UnitDialogState extends State<UnitDialog> {
  final name = TextEditingController();
  final description = TextEditingController();
  String? error;
  bool saving = false;

  bool get isEditing => widget.unit != null;

  @override
  void initState() {
    super.initState();
    final unit = widget.unit;
    if (unit != null) {
      name.text = text(unit['name']);
      description.text = text(unit['description']);
    }
  }

  @override
  void dispose() {
    name.dispose();
    description.dispose();
    super.dispose();
  }

  Future<void> save() async {
    setState(() {
      saving = true;
      error = null;
    });
    try {
      final body = {
        'name': name.text.trim(),
        'description': description.text.trim(),
        'active': true,
      };
      if (isEditing) {
        await widget.api.put('/shop/units/${widget.unit!['id']}', body: body);
      } else {
        await widget.api.post('/shop/units', body: body);
      }
      if (mounted) Navigator.pop(context, true);
    } catch (exception) {
      setState(() => error = exception.toString());
    } finally {
      if (mounted) setState(() => saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: Text(isEditing ? 'Edit unit' : 'New unit'),
      content: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (error != null) ErrorBox(message: error!),
          TextField(
            controller: name,
            onChanged: (_) => setState(() {}),
            decoration: const InputDecoration(labelText: 'Name'),
          ),
          const SizedBox(height: 10),
          TextField(
            controller: description,
            onChanged: (_) => setState(() {}),
            decoration: const InputDecoration(labelText: 'Description'),
          ),
        ],
      ),
      actions: [
        TextButton(
          onPressed: saving ? null : () => Navigator.pop(context),
          child: const Text('Cancel'),
        ),
        FilledButton(
          onPressed: saving || name.text.trim().isEmpty ? null : save,
          child: Text(saving ? 'Saving...' : 'Save'),
        ),
      ],
    );
  }
}

class ExpensesScreen extends StatefulWidget {
  const ExpensesScreen({super.key, required this.api, required this.session});
  final ApiClient api;
  final Session session;

  @override
  State<ExpensesScreen> createState() => _ExpensesScreenState();
}

class _ExpensesScreenState extends State<ExpensesScreen> {
  late Future<Map<String, List<dynamic>>> future;

  @override
  void initState() {
    super.initState();
    future = load();
  }

  Future<Map<String, List<dynamic>>> load() async {
    final expenses = asList(
      asMap(await widget.api.get('/shop/expenses'))['expenses'],
    );
    final categories = asList(
      asMap(
        await widget.api.get('/shop/expense-categories'),
      )['expense_categories'],
    );
    final branches = asList(
      asMap(await widget.api.get('/shop/branches'))['branches'],
    );
    return {
      'expenses': expenses,
      'categories': categories,
      'branches': branches,
    };
  }

  void refresh() {
    setState(() {
      future = load();
    });
  }

  Future<void> addExpense(Map<String, List<dynamic>> data) async {
    final created = await showDialog<bool>(
      context: context,
      builder: (_) => ExpenseDialog(
        api: widget.api,
        branches: data['branches']!,
        categories: data['categories']!,
      ),
    );
    if (created == true) refresh();
  }

  Future<void> addCategory() async {
    final name = await promptText(
      context,
      title: 'New expense category',
      label: 'Category name',
    );
    if (name == null || name.trim().isEmpty) return;
    if (!mounted) return;
    await runAction(
      context,
      () => widget.api.post(
        '/shop/expense-categories',
        body: {'name': name.trim(), 'active': true},
      ),
    );
    refresh();
  }

  @override
  Widget build(BuildContext context) {
    return RefreshableFuture(
      future: future,
      onRefresh: refresh,
      builder: (data) {
        final expenses = data['expenses']!;
        return ScreenScaffold(
          action: FloatingActionButton.extended(
            onPressed: () => addExpense(data),
            icon: const Icon(Icons.add_rounded),
            label: const Text('Expense'),
          ),
          child: ListView(
            padding: const EdgeInsets.all(16),
            children: [
              OutlinedButton.icon(
                onPressed: widget.session.isManager ? addCategory : null,
                icon: const Icon(Icons.category_rounded),
                label: const Text('Add category'),
              ),
              const SizedBox(height: 12),
              if (expenses.isEmpty)
                const EmptyState(label: 'No expenses')
              else
                ...expenses.map(
                  (expense) => InfoTile(
                    title: text(expense['title']),
                    subtitle:
                        '${text(expense['category_name'], fallback: 'No category')} • ${text(expense['branch_name'], fallback: 'No branch')}',
                    trailing: money(expense['amount']),
                  ),
                ),
            ],
          ),
        );
      },
    );
  }
}

class ExpenseDialog extends StatefulWidget {
  const ExpenseDialog({
    super.key,
    required this.api,
    required this.branches,
    required this.categories,
  });
  final ApiClient api;
  final List<dynamic> branches;
  final List<dynamic> categories;

  @override
  State<ExpenseDialog> createState() => _ExpenseDialogState();
}

class _ExpenseDialogState extends State<ExpenseDialog> {
  final title = TextEditingController();
  final amount = TextEditingController(text: '0');
  final notes = TextEditingController();
  int? branchId;
  int? categoryId;
  bool saving = false;
  String? error;

  Future<void> save() async {
    setState(() {
      saving = true;
      error = null;
    });
    try {
      await widget.api.post(
        '/shop/expenses',
        body: {
          'title': title.text.trim(),
          'amount': double.tryParse(amount.text) ?? 0,
          'expense_date': DateTime.now().toIso8601String().substring(0, 10),
          'notes': notes.text.trim(),
          if (branchId != null) 'branch_id': branchId,
          if (categoryId != null) 'expense_category_id': categoryId,
        },
      );
      if (mounted) Navigator.pop(context, true);
    } catch (exception) {
      setState(() => error = exception.toString());
    } finally {
      if (mounted) setState(() => saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: const Text('New expense'),
      content: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            if (error != null) ErrorBox(message: error!),
            TextField(
              controller: title,
              decoration: const InputDecoration(labelText: 'Title'),
            ),
            const SizedBox(height: 10),
            TextField(
              controller: amount,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(labelText: 'Amount'),
            ),
            const SizedBox(height: 10),
            DropdownButtonFormField<int?>(
              key: ValueKey('expense_branch_$branchId'),
              initialValue: branchId,
              decoration: const InputDecoration(labelText: 'Branch'),
              items: [
                const DropdownMenuItem<int?>(
                  value: null,
                  child: Text('No branch'),
                ),
                ...widget.branches.map(
                  (branch) => DropdownMenuItem<int?>(
                    value: number(branch['id'])?.toInt(),
                    child: Text(text(branch['name'])),
                  ),
                ),
              ],
              onChanged: (value) => setState(() => branchId = value),
            ),
            const SizedBox(height: 10),
            DropdownButtonFormField<int?>(
              key: ValueKey('expense_category_$categoryId'),
              initialValue: categoryId,
              decoration: const InputDecoration(labelText: 'Category'),
              items: [
                const DropdownMenuItem<int?>(
                  value: null,
                  child: Text('No category'),
                ),
                ...widget.categories.map(
                  (category) => DropdownMenuItem<int?>(
                    value: number(category['id'])?.toInt(),
                    child: Text(text(category['name'])),
                  ),
                ),
              ],
              onChanged: (value) => setState(() => categoryId = value),
            ),
            const SizedBox(height: 10),
            TextField(
              controller: notes,
              decoration: const InputDecoration(labelText: 'Notes'),
            ),
          ],
        ),
      ),
      actions: [
        TextButton(
          onPressed: saving ? null : () => Navigator.pop(context),
          child: const Text('Cancel'),
        ),
        FilledButton(
          onPressed: saving || title.text.trim().isEmpty ? null : save,
          child: Text(saving ? 'Saving...' : 'Save'),
        ),
      ],
    );
  }
}

class ManageScreen extends StatefulWidget {
  const ManageScreen({super.key, required this.api, required this.session});
  final ApiClient api;
  final Session session;

  @override
  State<ManageScreen> createState() => _ManageScreenState();
}

class _ManageScreenState extends State<ManageScreen> {
  late Future<Map<String, dynamic>> future;
  String selectedLanguage = 'English';
  bool catalogExpanded = false;
  bool usersExpanded = false;
  bool notificationsEnabled = true;

  @override
  void initState() {
    super.initState();
    future = load();
  }

  Future<Map<String, dynamic>> load() async {
    final branches = asList(
      asMap(await widget.api.get('/shop/branches'))['branches'],
    );
    final subscription = asMap(await widget.api.get('/shop/subscription'));
    final items = asList(asMap(await widget.api.get('/shop/items'))['items']);
    final categories = asList(
      asMap(await widget.api.get('/shop/categories'))['categories'],
    );
    final units = asList(asMap(await widget.api.get('/shop/units'))['units']);
    List<dynamic> users = [];
    if (widget.session.isManager) {
      users = asList(asMap(await widget.api.get('/shop/users'))['users']);
    }
    return {
      'branches': branches,
      'users': users,
      'subscription': subscription,
      'items': items,
      'categories': categories,
      'units': units,
    };
  }

  void refresh() {
    setState(() {
      future = load();
    });
  }

  Future<void> addBranch() async {
    final name = await promptText(
      context,
      title: 'New branch',
      label: 'Branch name',
    );
    if (name == null || name.trim().isEmpty) return;
    if (!mounted) return;
    await runAction(
      context,
      () => widget.api.post('/shop/branches', body: {'name': name.trim()}),
    );
    refresh();
  }

  Future<void> addUser(List<dynamic> branches) async {
    final created = await showDialog<bool>(
      context: context,
      builder: (_) => UserDialog(api: widget.api, branches: branches),
    );
    if (created == true) refresh();
  }

  Future<void> editUser(
    Map<String, dynamic> user,
    List<dynamic> branches,
  ) async {
    final updated = await showDialog<bool>(
      context: context,
      builder: (_) =>
          UserDialog(api: widget.api, branches: branches, user: user),
    );
    if (updated == true) refresh();
  }

  Future<void> deleteUser(Map<String, dynamic> user) async {
    final confirmed = await confirmDelete(
      title: 'Delete user?',
      message: 'Delete ${text(user['name'], fallback: 'this user')}?',
    );
    if (confirmed != true) return;
    await widget.api.delete('/shop/users/${user['id']}');
    if (!mounted) return;
    refresh();
  }

  Future<void> openLanguageSheet() async {
    var nextLanguage = selectedLanguage;

    await showModalBottomSheet<void>(
      context: context,
      showDragHandle: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (context) => StatefulBuilder(
        builder: (context, setSheetState) => SafeArea(
          child: Padding(
            padding: const EdgeInsets.fromLTRB(24, 10, 24, 28),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Text(
                  'Language',
                  style: TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.w900,
                    color: darkPurple,
                  ),
                ),
                const SizedBox(height: 22),
                languageOption(
                  label: 'Arabic',
                  selected: nextLanguage == 'Arabic',
                  onTap: () => setSheetState(() => nextLanguage = 'Arabic'),
                ),
                languageOption(
                  label: 'English',
                  selected: nextLanguage == 'English',
                  onTap: () => setSheetState(() => nextLanguage = 'English'),
                ),
                const SizedBox(height: 6),
                FilledButton(
                  onPressed: () {
                    setState(() => selectedLanguage = nextLanguage);
                    Navigator.pop(context);
                  },
                  child: const Text('Done'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Future<void> openItemsSheet(
    List<dynamic> items,
    List<dynamic> categories,
    List<dynamic> units,
  ) async {
    await showModalBottomSheet<void>(
      context: context,
      showDragHandle: true,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (context) => SafeArea(
        child: ConstrainedBox(
          constraints: BoxConstraints(
            maxHeight: MediaQuery.sizeOf(context).height * 0.82,
          ),
          child: ListView(
            padding: const EdgeInsets.fromLTRB(24, 10, 24, 28),
            children: [
              SectionHeader(
                title: 'Items',
                actionLabel: widget.session.isManager ? 'Add' : null,
                onAction: widget.session.isManager
                    ? () async {
                        final created = await showDialog<bool>(
                          context: context,
                          builder: (_) => ItemDialog(
                            api: widget.api,
                            categories: categories,
                            units: units,
                          ),
                        );
                        if (created == true) {
                          if (!context.mounted) return;
                          Navigator.pop(context);
                          refresh();
                        }
                      }
                    : null,
              ),
              if (items.isEmpty)
                const EmptyState(label: 'No items')
              else
                ...items.map((item) {
                  final itemMap = asMap(item);
                  return InfoTile(
                    title: text(itemMap['name']),
                    subtitle:
                        '${text(itemMap['category_name'], fallback: 'No category')} • ${text(itemMap['unit_name'], fallback: 'No unit')} • ${text(itemMap['type'], fallback: 'item')}',
                    trailing: money(itemMap['price']),
                    onTap: widget.session.isManager
                        ? () => editItem(itemMap, categories, units)
                        : null,
                    onDelete: widget.session.isManager
                        ? () => deleteItem(itemMap)
                        : null,
                  );
                }),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> editItem(
    Map<String, dynamic> item,
    List<dynamic> categories,
    List<dynamic> units,
  ) async {
    final updated = await showDialog<bool>(
      context: context,
      builder: (_) => ItemDialog(
        api: widget.api,
        categories: categories,
        units: units,
        item: item,
      ),
    );
    if (updated == true) {
      if (!mounted) return;
      Navigator.pop(context);
      refresh();
    }
  }

  Future<void> deleteItem(Map<String, dynamic> item) async {
    final confirmed = await confirmDelete(
      title: 'Delete item?',
      message: 'Delete ${text(item['name'], fallback: 'this item')}?',
    );
    if (confirmed != true) return;
    await widget.api.delete('/shop/items/${item['id']}');
    if (!mounted) return;
    Navigator.pop(context);
    refresh();
  }

  Future<void> openCategoriesSheet(List<dynamic> categories) async {
    await showModalBottomSheet<void>(
      context: context,
      showDragHandle: true,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (context) => SafeArea(
        child: ConstrainedBox(
          constraints: BoxConstraints(
            maxHeight: MediaQuery.sizeOf(context).height * 0.82,
          ),
          child: ListView(
            padding: const EdgeInsets.fromLTRB(24, 10, 24, 28),
            children: [
              SectionHeader(
                title: 'Item Categories',
                actionLabel: widget.session.isManager ? 'Add' : null,
                onAction: widget.session.isManager
                    ? () async {
                        final created = await showDialog<bool>(
                          context: context,
                          builder: (_) => CategoryDialog(api: widget.api),
                        );
                        if (created == true) {
                          if (!context.mounted) return;
                          Navigator.pop(context);
                          refresh();
                        }
                      }
                    : null,
              ),
              if (categories.isEmpty)
                const EmptyState(label: 'No item categories')
              else
                ...categories.map((category) {
                  final categoryMap = asMap(category);
                  return InfoTile(
                    title: text(categoryMap['name']),
                    subtitle: text(categoryMap['description']),
                    trailing: '',
                    onTap: widget.session.isManager
                        ? () => editCategory(categoryMap)
                        : null,
                    onDelete: widget.session.isManager
                        ? () => deleteCategory(categoryMap)
                        : null,
                  );
                }),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> editCategory(Map<String, dynamic> category) async {
    final updated = await showDialog<bool>(
      context: context,
      builder: (_) => CategoryDialog(api: widget.api, category: category),
    );
    if (updated == true) {
      if (!mounted) return;
      Navigator.pop(context);
      refresh();
    }
  }

  Future<void> deleteCategory(Map<String, dynamic> category) async {
    final confirmed = await confirmDelete(
      title: 'Delete item category?',
      message: 'Delete ${text(category['name'], fallback: 'this category')}?',
    );
    if (confirmed != true) return;
    await widget.api.delete('/shop/categories/${category['id']}');
    if (!mounted) return;
    Navigator.pop(context);
    refresh();
  }

  Future<void> openUnitsSheet(List<dynamic> units) async {
    await showModalBottomSheet<void>(
      context: context,
      showDragHandle: true,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (context) => SafeArea(
        child: ConstrainedBox(
          constraints: BoxConstraints(
            maxHeight: MediaQuery.sizeOf(context).height * 0.82,
          ),
          child: ListView(
            padding: const EdgeInsets.fromLTRB(24, 10, 24, 28),
            children: [
              SectionHeader(
                title: 'Units',
                actionLabel: widget.session.isManager ? 'Add' : null,
                onAction: widget.session.isManager
                    ? () async {
                        final created = await showDialog<bool>(
                          context: context,
                          builder: (_) => UnitDialog(api: widget.api),
                        );
                        if (created == true) {
                          if (!context.mounted) return;
                          Navigator.pop(context);
                          refresh();
                        }
                      }
                    : null,
              ),
              if (units.isEmpty)
                const EmptyState(label: 'No units')
              else
                ...units.map((unit) {
                  final unitMap = asMap(unit);
                  return InfoTile(
                    title: text(unitMap['name']),
                    subtitle: text(unitMap['description']),
                    trailing: '',
                    onTap: widget.session.isManager
                        ? () => editUnit(unitMap)
                        : null,
                    onDelete: widget.session.isManager
                        ? () => deleteUnit(unitMap)
                        : null,
                  );
                }),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> editUnit(Map<String, dynamic> unit) async {
    final updated = await showDialog<bool>(
      context: context,
      builder: (_) => UnitDialog(api: widget.api, unit: unit),
    );
    if (updated == true) {
      if (!mounted) return;
      Navigator.pop(context);
      refresh();
    }
  }

  Future<void> deleteUnit(Map<String, dynamic> unit) async {
    final confirmed = await confirmDelete(
      title: 'Delete unit?',
      message: 'Delete ${text(unit['name'], fallback: 'this unit')}?',
    );
    if (confirmed != true) return;
    await widget.api.delete('/shop/units/${unit['id']}');
    if (!mounted) return;
    Navigator.pop(context);
    refresh();
  }

  Future<bool?> confirmDelete({
    required String title,
    required String message,
  }) {
    return showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(title),
        content: Text(message),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancel'),
          ),
          FilledButton(
            style: FilledButton.styleFrom(backgroundColor: Colors.red.shade600),
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Delete'),
          ),
        ],
      ),
    );
  }

  Widget languageOption({
    required String label,
    required bool selected,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 16),
        child: Row(
          children: [
            Container(
              width: 32,
              height: 32,
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(4),
                border: Border.all(color: brandPurple, width: 1.4),
              ),
              child: selected
                  ? Container(
                      margin: const EdgeInsets.all(5),
                      decoration: BoxDecoration(
                        color: brandPurple,
                        borderRadius: BorderRadius.circular(3),
                      ),
                    )
                  : null,
            ),
            const SizedBox(width: 10),
            Text(
              label,
              style: TextStyle(
                color: selected ? brandPurple : darkPurple,
                fontSize: 17,
                fontWeight: FontWeight.w800,
              ),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return RefreshableFuture(
      future: future,
      onRefresh: refresh,
      builder: (data) {
        final branches = asList(data['branches']);
        final users = asList(data['users']);
        final items = asList(data['items']);
        final categories = asList(data['categories']);
        final units = asList(data['units']);
        final subscription = asMap(data['subscription']);
        final package = asMap(subscription['package']);

        return ListView(
          padding: const EdgeInsets.all(16),
          children: [
            SubscriptionDetailsCard(
              packageName: text(package['name'], fallback: 'No package'),
              endDate: dateText(subscription['subscription_end']),
              companyStatus: text(
                subscription['company_status'],
                fallback: '-',
              ),
            ),
            const SizedBox(height: 14),
            SettingsTile(
              icon: Icons.language_rounded,
              title: 'Languages',
              trailing: selectedLanguage,
              onTap: openLanguageSheet,
            ),
            const SizedBox(height: 14),
            ExpandableSettingsHeader(
              title: 'Catalog',
              expanded: catalogExpanded,
              onTap: () => setState(() => catalogExpanded = !catalogExpanded),
            ),
            if (catalogExpanded) ...[
              const SizedBox(height: 10),
              SettingsTile(
                icon: Icons.inventory_2_rounded,
                title: 'Items',
                onTap: () => openItemsSheet(items, categories, units),
              ),
              const SizedBox(height: 10),
              SettingsTile(
                icon: Icons.category_rounded,
                title: 'Item Categories',
                onTap: () => openCategoriesSheet(categories),
              ),
              const SizedBox(height: 10),
              SettingsTile(
                icon: Icons.straighten_rounded,
                title: 'Units',
                onTap: () => openUnitsSheet(units),
              ),
            ],
            const SizedBox(height: 14),
            ExpandableSettingsHeader(
              title: 'Users',
              expanded: usersExpanded,
              onTap: () => setState(() => usersExpanded = !usersExpanded),
            ),
            if (usersExpanded) ...[
              const SizedBox(height: 10),
              if (!widget.session.isManager)
                const EmptyState(
                  label: 'Only company managers can manage users',
                )
              else ...[
                Align(
                  alignment: AlignmentDirectional.centerEnd,
                  child: TextButton.icon(
                    onPressed: () => addUser(branches),
                    icon: const Icon(Icons.add_rounded),
                    label: const Text('Add user'),
                  ),
                ),
                if (users.isEmpty)
                  const EmptyState(label: 'No users')
                else
                  ...users.map((user) {
                    final userMap = asMap(user);
                    return InfoTile(
                      title: text(userMap['name']),
                      subtitle:
                          '${text(userMap['role']).replaceAll('_', ' ')} • ${text(userMap['branch_name'], fallback: 'All branches')}',
                      trailing: text(userMap['active']) == 'true'
                          ? 'Active'
                          : 'Inactive',
                      onTap: () => editUser(userMap, branches),
                      onDelete: () => deleteUser(userMap),
                    );
                  }),
              ],
            ],
            const SizedBox(height: 14),
            NotificationSettingsTile(
              enabled: notificationsEnabled,
              onChanged: (value) =>
                  setState(() => notificationsEnabled = value),
            ),
            const SizedBox(height: 30),
            Center(
              child: Text(
                'managed by fastigo.app',
                textAlign: TextAlign.center,
                style: TextStyle(
                  color: brandPurple,
                  fontSize: 22,
                  fontWeight: FontWeight.w900,
                  decoration: TextDecoration.underline,
                  decorationColor: brandPurple,
                  decorationThickness: 1.5,
                ),
              ),
            ),
          ],
        );
      },
    );
  }
}

class UserDialog extends StatefulWidget {
  const UserDialog({
    super.key,
    required this.api,
    required this.branches,
    this.user,
  });

  final ApiClient api;
  final List<dynamic> branches;
  final Map<String, dynamic>? user;

  @override
  State<UserDialog> createState() => _UserDialogState();
}

class _UserDialogState extends State<UserDialog> {
  final name = TextEditingController();
  final email = TextEditingController();
  final password = TextEditingController();
  final phone = TextEditingController();
  String role = 'branch_employee';
  int? branchId;
  String? error;
  bool active = true;
  bool saving = false;

  bool get isEditing => widget.user != null;

  @override
  void initState() {
    super.initState();
    final user = widget.user;
    if (user == null) return;
    name.text = text(user['name']);
    email.text = text(user['email']);
    phone.text = text(user['phone']);
    role = text(user['role'], fallback: 'branch_employee');
    branchId = number(user['branch_id'])?.toInt();
    active = text(user['active'], fallback: 'true') == 'true';
  }

  @override
  void dispose() {
    name.dispose();
    email.dispose();
    password.dispose();
    phone.dispose();
    super.dispose();
  }

  Future<void> save() async {
    setState(() {
      saving = true;
      error = null;
    });
    try {
      final body = <String, dynamic>{
        'name': name.text.trim(),
        'email': email.text.trim(),
        'phone': phone.text.trim(),
        'role': role,
        if (branchId != null) 'branch_id': branchId,
        'active': active,
      };
      if (!isEditing || password.text.isNotEmpty) {
        body['password'] = password.text;
      }
      if (isEditing) {
        await widget.api.put('/shop/users/${widget.user!['id']}', body: body);
      } else {
        await widget.api.post('/shop/users', body: body);
      }
      if (mounted) Navigator.pop(context, true);
    } catch (exception) {
      setState(() => error = exception.toString());
    } finally {
      if (mounted) setState(() => saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: Text(isEditing ? 'Edit user' : 'New user'),
      content: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            if (error != null) ErrorBox(message: error!),
            TextField(
              controller: name,
              onChanged: (_) => setState(() {}),
              decoration: const InputDecoration(labelText: 'Name'),
            ),
            const SizedBox(height: 10),
            TextField(
              controller: email,
              onChanged: (_) => setState(() {}),
              keyboardType: TextInputType.emailAddress,
              decoration: const InputDecoration(labelText: 'Email'),
            ),
            const SizedBox(height: 10),
            TextField(
              controller: password,
              onChanged: (_) => setState(() {}),
              obscureText: true,
              decoration: InputDecoration(
                labelText: isEditing ? 'Password (optional)' : 'Password',
              ),
            ),
            const SizedBox(height: 10),
            TextField(
              controller: phone,
              keyboardType: TextInputType.phone,
              decoration: const InputDecoration(labelText: 'Phone'),
            ),
            const SizedBox(height: 10),
            DropdownButtonFormField<String>(
              key: ValueKey('user_role_$role'),
              initialValue: role,
              decoration: const InputDecoration(labelText: 'Role'),
              items: const [
                DropdownMenuItem(
                  value: 'company_manager',
                  child: Text('Company manager'),
                ),
                DropdownMenuItem(
                  value: 'branch_employee',
                  child: Text('Branch employee'),
                ),
              ],
              onChanged: (value) => setState(() {
                role = value ?? 'branch_employee';
                if (role == 'company_manager') branchId = null;
              }),
            ),
            const SizedBox(height: 10),
            DropdownButtonFormField<int?>(
              key: ValueKey('user_branch_$branchId'),
              initialValue: branchId,
              decoration: const InputDecoration(labelText: 'Branch'),
              items: [
                const DropdownMenuItem<int?>(
                  value: null,
                  child: Text('All branches'),
                ),
                ...widget.branches.map(
                  (branch) => DropdownMenuItem<int?>(
                    value: number(branch['id'])?.toInt(),
                    child: Text(text(branch['name'])),
                  ),
                ),
              ],
              onChanged: (value) => setState(() => branchId = value),
            ),
            const SizedBox(height: 10),
            SwitchListTile(
              contentPadding: EdgeInsets.zero,
              title: const Text('Active'),
              value: active,
              activeThumbColor: brandPurple,
              onChanged: (value) => setState(() => active = value),
            ),
          ],
        ),
      ),
      actions: [
        TextButton(
          onPressed: saving ? null : () => Navigator.pop(context),
          child: const Text('Cancel'),
        ),
        FilledButton(
          onPressed:
              saving ||
                  name.text.trim().isEmpty ||
                  email.text.trim().isEmpty ||
                  (!isEditing && password.text.length < 8) ||
                  (isEditing &&
                      password.text.isNotEmpty &&
                      password.text.length < 8)
              ? null
              : save,
          child: Text(saving ? 'Saving...' : 'Save'),
        ),
      ],
    );
  }
}

class ReportsScreen extends StatefulWidget {
  const ReportsScreen({super.key, required this.api, required this.session});
  final ApiClient api;
  final Session session;

  @override
  State<ReportsScreen> createState() => _ReportsScreenState();
}

class _ReportsScreenState extends State<ReportsScreen> {
  late Future<Map<String, dynamic>> future;

  @override
  void initState() {
    super.initState();
    future = load();
  }

  Future<Map<String, dynamic>> load() async {
    final sales = asMap(await widget.api.get('/shop/reports/sales'));
    final expenses = asMap(await widget.api.get('/shop/reports/expenses'));
    final profit = asMap(await widget.api.get('/shop/reports/profit'));
    final branches = asMap(await widget.api.get('/shop/reports/branches'));
    return {
      'sales': sales,
      'expenses': expenses,
      'profit': profit,
      'branches': branches,
    };
  }

  @override
  Widget build(BuildContext context) {
    return RefreshableFuture(
      future: future,
      onRefresh: () {
        setState(() {
          future = load();
        });
      },
      builder: (data) {
        final sales = asMap(data['sales']);
        final expenses = asMap(data['expenses']);
        final profit = asMap(data['profit']);
        final branches = asList(asMap(data['branches'])['branches']);
        return ListView(
          padding: const EdgeInsets.all(16),
          children: [
            GridLayout(
              children: [
                MetricCard(
                  'Total sales',
                  money(profit['total_sales']),
                  Icons.trending_up_rounded,
                ),
                MetricCard(
                  'Total expenses',
                  money(profit['total_expenses']),
                  Icons.trending_down_rounded,
                ),
                MetricCard(
                  'Net profit',
                  money(profit['net_profit']),
                  Icons.account_balance_wallet_rounded,
                ),
                MetricCard(
                  'Top products',
                  '${asList(sales['top_products']).length}',
                  Icons.star_rounded,
                ),
              ],
            ),
            SectionHeader(title: 'Bills by status', actionLabel: null),
            ...asMap(sales['bills_by_status']).entries.map(
              (entry) => InfoTile(
                title: statusLabel(entry.key),
                subtitle: 'Bills',
                trailing: text(entry.value),
              ),
            ),
            SectionHeader(title: 'Branch performance', actionLabel: null),
            if (branches.isEmpty)
              const EmptyState(label: 'No branch report data')
            else
              ...branches.map(
                (branch) => InfoTile(
                  title: text(branch['branch_name']),
                  subtitle:
                      'Sales ${money(branch['total_sales'])} • Expenses ${money(branch['total_expenses'])}',
                  trailing: money(branch['net_profit']),
                ),
              ),
            SectionHeader(title: 'Expense trend', actionLabel: null),
            ...asList(expenses['expenses_by_date'])
                .take(8)
                .map(
                  (row) => InfoTile(
                    title: dateText(row['date']),
                    subtitle: 'Expenses',
                    trailing: money(row['total']),
                  ),
                ),
          ],
        );
      },
    );
  }
}

class RefreshableFuture<T> extends StatelessWidget {
  const RefreshableFuture({
    super.key,
    required this.future,
    required this.builder,
    required this.onRefresh,
  });

  final Future<T> future;
  final Widget Function(T data) builder;
  final VoidCallback onRefresh;

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<T>(
      future: future,
      builder: (context, snapshot) {
        if (snapshot.connectionState == ConnectionState.waiting) {
          return const Center(child: CircularProgressIndicator());
        }
        if (snapshot.hasError) {
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              ErrorBox(message: snapshot.error.toString()),
              const SizedBox(height: 12),
              FilledButton.icon(
                onPressed: onRefresh,
                icon: const Icon(Icons.refresh_rounded),
                label: const Text('Retry'),
              ),
            ],
          );
        }
        return RefreshIndicator(
          onRefresh: () async => onRefresh(),
          child: builder(snapshot.data as T),
        );
      },
    );
  }
}

class ScreenScaffold extends StatelessWidget {
  const ScreenScaffold({super.key, required this.child, this.action});
  final Widget child;
  final Widget? action;

  @override
  Widget build(BuildContext context) {
    return Scaffold(body: child, floatingActionButton: action);
  }
}

class DashboardGaugeSummary extends StatelessWidget {
  const DashboardGaugeSummary({super.key, required this.summary});

  final Map<String, dynamic> summary;

  @override
  Widget build(BuildContext context) {
    final orders = asMap(summary['orders']);
    final netSales = asMap(summary['net_sales']);
    final averageOrder = asMap(summary['average_order']);

    return Card(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(14, 18, 14, 16),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            Expanded(
              child: GaugeMetric(
                label: 'Orders',
                value: compactMetric(orders['value'], decimals: 0),
                change: number(orders['change'])?.toDouble() ?? 0,
                progress: gaugeProgress(orders['value']),
                size: 86,
              ),
            ),
            Expanded(
              child: GaugeMetric(
                label: 'Net Sales',
                value: compactMetric(netSales['value']),
                change: number(netSales['change'])?.toDouble() ?? 0,
                progress: gaugeProgress(netSales['value']),
                size: 124,
                featured: true,
              ),
            ),
            Expanded(
              child: GaugeMetric(
                label: 'Avg. Order',
                value: compactMetric(averageOrder['value']),
                change: number(averageOrder['change'])?.toDouble() ?? 0,
                progress: gaugeProgress(averageOrder['value']),
                size: 86,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class GaugeMetric extends StatelessWidget {
  const GaugeMetric({
    super.key,
    required this.label,
    required this.value,
    required this.change,
    required this.progress,
    required this.size,
    this.featured = false,
  });

  final String label;
  final String value;
  final double change;
  final double progress;
  final double size;
  final bool featured;

  @override
  Widget build(BuildContext context) {
    final isNegative = change < 0;

    return Column(
      children: [
        SizedBox(
          width: size,
          height: size * 0.72,
          child: Stack(
            alignment: Alignment.center,
            children: [
              Positioned.fill(
                child: CustomPaint(
                  painter: GaugePainter(
                    progress: progress,
                    strokeWidth: featured ? 8 : 6,
                  ),
                ),
              ),
              Padding(
                padding: EdgeInsets.only(top: featured ? 12 : 10),
                child: Text(
                  value,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    color: Colors.black,
                    fontSize: featured ? 28 : 20,
                    fontWeight: FontWeight.w900,
                  ),
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 10),
        Text(
          label,
          textAlign: TextAlign.center,
          style: TextStyle(
            color: bodyPurple.withValues(alpha: 0.62),
            fontSize: featured ? 18 : 15,
            fontWeight: FontWeight.w900,
          ),
        ),
        const SizedBox(height: 4),
        Text(
          '${change >= 0 ? '+' : ''}${change.toStringAsFixed(1)}%',
          style: TextStyle(
            color: isNegative ? Colors.red.shade500 : brandPurple,
            fontSize: featured ? 21 : 17,
            fontWeight: FontWeight.w900,
          ),
        ),
      ],
    );
  }
}

class GaugePainter extends CustomPainter {
  GaugePainter({required this.progress, required this.strokeWidth});

  final double progress;
  final double strokeWidth;

  @override
  void paint(Canvas canvas, Size size) {
    final rect = Rect.fromLTWH(
      strokeWidth,
      strokeWidth,
      size.width - strokeWidth * 2,
      size.height * 1.35,
    );
    final backgroundPaint = Paint()
      ..color = lightPurple
      ..strokeWidth = strokeWidth
      ..strokeCap = StrokeCap.round
      ..style = PaintingStyle.stroke;
    final foregroundPaint = Paint()
      ..color = brandPurple
      ..strokeWidth = strokeWidth
      ..strokeCap = StrokeCap.round
      ..style = PaintingStyle.stroke;
    const startAngle = math.pi * 0.86;
    const sweepAngle = math.pi * 1.28;
    final clampedProgress = progress.clamp(0.08, 1.0);

    canvas.drawArc(rect, startAngle, sweepAngle, false, backgroundPaint);
    canvas.drawArc(
      rect,
      startAngle,
      sweepAngle * clampedProgress,
      false,
      foregroundPaint,
    );

    final markerAngle = startAngle + sweepAngle * clampedProgress;
    final markerCenter = Offset(
      rect.center.dx + math.cos(markerAngle) * rect.width / 2,
      rect.center.dy + math.sin(markerAngle) * rect.height / 2,
    );
    canvas.drawCircle(
      markerCenter,
      strokeWidth * 1.45,
      Paint()..color = brandPurple,
    );
  }

  @override
  bool shouldRepaint(GaugePainter oldDelegate) {
    return oldDelegate.progress != progress ||
        oldDelegate.strokeWidth != strokeWidth;
  }
}

class GrossSalesChartCard extends StatelessWidget {
  const GrossSalesChartCard({super.key, required this.chart});

  final Map<String, dynamic> chart;

  @override
  Widget build(BuildContext context) {
    final points = asList(chart['points']).map(asMap).toList();

    return Card(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(18, 16, 18, 18),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Gross Sales',
              style: TextStyle(
                color: bodyPurple.withValues(alpha: 0.62),
                fontSize: 20,
                fontWeight: FontWeight.w900,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              compactMetric(chart['total']),
              style: const TextStyle(
                color: darkPurple,
                fontSize: 36,
                fontWeight: FontWeight.w900,
              ),
            ),
            const SizedBox(height: 16),
            SizedBox(
              height: 260,
              child: CustomPaint(
                painter: GrossSalesBarPainter(points: points),
                size: Size.infinite,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class GrossSalesBarPainter extends CustomPainter {
  GrossSalesBarPainter({required this.points});

  final List<Map<String, dynamic>> points;

  @override
  void paint(Canvas canvas, Size size) {
    final values = points
        .map((point) => number(point['value'])?.toDouble() ?? 0)
        .toList();
    final maxValue = values.isEmpty ? 0.0 : values.reduce(math.max);
    final chartMax = maxValue <= 0 ? 1.0 : maxValue;
    final leftPadding = 38.0;
    final bottomPadding = 28.0;
    final topPadding = 8.0;
    final chartWidth = size.width - leftPadding;
    final chartHeight = size.height - topPadding - bottomPadding;
    final gridPaint = Paint()
      ..color = bodyPurple.withValues(alpha: 0.16)
      ..strokeWidth = 1.4
      ..style = PaintingStyle.stroke;
    final labelStyle = TextStyle(
      color: bodyPurple.withValues(alpha: 0.62),
      fontSize: 12,
      fontWeight: FontWeight.w800,
    );

    for (var i = 0; i <= 4; i++) {
      final y = topPadding + chartHeight * i / 4;
      canvas.drawLine(Offset(leftPadding, y), Offset(size.width, y), gridPaint);
      final value = chartMax * (4 - i) / 4;
      drawChartText(canvas, compactMetric(value), Offset(0, y - 8), labelStyle);
    }

    if (points.isEmpty) return;

    final slotWidth = chartWidth / points.length;
    final barWidth = math.min(22.0, slotWidth * 0.42);
    final barPaint = Paint()
      ..color = brandPurple
      ..strokeCap = StrokeCap.round
      ..strokeWidth = barWidth;

    for (var i = 0; i < points.length; i++) {
      final value = values[i];
      final x = leftPadding + slotWidth * i + slotWidth / 2;
      final barHeight = chartHeight * (value / chartMax);
      final yTop = topPadding + chartHeight - barHeight;
      final yBottom = topPadding + chartHeight;
      canvas.drawLine(Offset(x, yBottom), Offset(x, yTop), barPaint);
      drawChartText(
        canvas,
        text(points[i]['label']),
        Offset(x - 8, yBottom + 10),
        labelStyle,
      );
    }
  }

  @override
  bool shouldRepaint(GrossSalesBarPainter oldDelegate) {
    return oldDelegate.points != points;
  }
}

void drawChartText(
  Canvas canvas,
  String label,
  Offset offset,
  TextStyle style,
) {
  final painter = TextPainter(
    text: TextSpan(text: label, style: style),
    textDirection: TextDirection.ltr,
    maxLines: 1,
  )..layout(maxWidth: 60);
  painter.paint(canvas, offset);
}

class DashboardTopBar extends StatelessWidget {
  const DashboardTopBar({
    super.key,
    required this.title,
    required this.onLocationTap,
    required this.onNotificationTap,
  });

  final String title;
  final VoidCallback? onLocationTap;
  final VoidCallback onNotificationTap;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: Text(
            title,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(
              fontSize: 28,
              fontWeight: FontWeight.w900,
              color: darkPurple,
            ),
          ),
        ),
        const SizedBox(width: 12),
        DashboardIconButton(
          icon: Icons.location_on_outlined,
          onPressed: onLocationTap,
        ),
        const SizedBox(width: 10),
        DashboardIconButton(
          icon: Icons.notifications_none_rounded,
          onPressed: onNotificationTap,
        ),
      ],
    );
  }
}

class DashboardIconButton extends StatelessWidget {
  const DashboardIconButton({super.key, required this.icon, this.onPressed});

  final IconData icon;
  final VoidCallback? onPressed;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 56,
      height: 56,
      child: IconButton(
        onPressed: onPressed,
        icon: Icon(icon, size: 28),
        color: brandPurple,
        style: IconButton.styleFrom(
          backgroundColor: Colors.white,
          disabledBackgroundColor: Colors.white,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
            side: const BorderSide(color: Color(0xFFF0EEFA)),
          ),
        ),
      ),
    );
  }
}

class HeaderCard extends StatelessWidget {
  const HeaderCard({
    super.key,
    required this.title,
    required this.subtitle,
    required this.icon,
  });
  final String title;
  final String subtitle;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Row(
          children: [
            CircleAvatar(
              backgroundColor: brandPurple,
              foregroundColor: Colors.white,
              child: Icon(icon),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.w900,
                      color: darkPurple,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(subtitle),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class GridLayout extends StatelessWidget {
  const GridLayout({super.key, required this.children});
  final List<Widget> children;

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, constraints) {
        final columns = constraints.maxWidth > 700 ? 3 : 2;
        return GridView.count(
          crossAxisCount: columns,
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          mainAxisSpacing: 10,
          crossAxisSpacing: 10,
          childAspectRatio: 1.35,
          children: children,
        );
      },
    );
  }
}

class MetricCard extends StatelessWidget {
  const MetricCard(this.label, this.value, this.icon, {super.key});
  final String label;
  final String value;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(icon, color: brandPurple),
            const Spacer(),
            Text(
              value,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.w900,
                color: darkPurple,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              label,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(fontSize: 12),
            ),
          ],
        ),
      ),
    );
  }
}

class SectionHeader extends StatelessWidget {
  const SectionHeader({
    super.key,
    required this.title,
    this.actionLabel,
    this.onAction,
  });
  final String title;
  final String? actionLabel;
  final VoidCallback? onAction;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(2, 22, 2, 10),
      child: Row(
        children: [
          Expanded(
            child: Text(
              title,
              style: const TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.w900,
                color: darkPurple,
              ),
            ),
          ),
          if (actionLabel != null)
            TextButton(onPressed: onAction, child: Text(actionLabel!)),
        ],
      ),
    );
  }
}

class ExpandableSettingsHeader extends StatelessWidget {
  const ExpandableSettingsHeader({
    super.key,
    required this.title,
    required this.expanded,
    required this.onTap,
  });

  final String title;
  final bool expanded;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      borderRadius: BorderRadius.circular(16),
      onTap: onTap,
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 10),
        child: Row(
          children: [
            Expanded(
              child: Text(
                title,
                style: const TextStyle(
                  fontSize: 22,
                  fontWeight: FontWeight.w900,
                  color: darkPurple,
                ),
              ),
            ),
            Icon(
              expanded
                  ? Icons.keyboard_arrow_down_rounded
                  : Icons.chevron_right_rounded,
              color: brandPurple,
              size: 32,
            ),
          ],
        ),
      ),
    );
  }
}

class InfoTile extends StatelessWidget {
  const InfoTile({
    super.key,
    required this.title,
    required this.subtitle,
    required this.trailing,
    this.onTap,
    this.onDelete,
  });
  final String title;
  final String subtitle;
  final String trailing;
  final VoidCallback? onTap;
  final VoidCallback? onDelete;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: ListTile(
        title: Text(
          title,
          style: const TextStyle(
            fontWeight: FontWeight.w800,
            color: darkPurple,
          ),
        ),
        subtitle: subtitle.isEmpty ? null : Text(subtitle),
        trailing: onDelete != null
            ? Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  if (trailing.isNotEmpty) ...[
                    Text(
                      trailing,
                      style: const TextStyle(fontWeight: FontWeight.w800),
                    ),
                    const SizedBox(width: 4),
                  ],
                  IconButton(
                    onPressed: onDelete,
                    icon: const Icon(Icons.delete_outline_rounded),
                    color: Colors.red.shade600,
                  ),
                ],
              )
            : trailing.isEmpty
            ? null
            : Text(
                trailing,
                style: const TextStyle(fontWeight: FontWeight.w800),
              ),
        onTap: onTap,
      ),
    );
  }
}

class SettingsTile extends StatelessWidget {
  const SettingsTile({
    super.key,
    required this.icon,
    required this.title,
    this.trailing,
    required this.onTap,
  });

  final IconData icon;
  final String title;
  final String? trailing;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: ListTile(
        contentPadding: const EdgeInsets.symmetric(horizontal: 18, vertical: 8),
        leading: Icon(icon, color: darkPurple, size: 28),
        title: Text(
          title,
          style: const TextStyle(
            color: darkPurple,
            fontSize: 17,
            fontWeight: FontWeight.w800,
          ),
        ),
        trailing: trailing == null
            ? const Icon(Icons.chevron_right_rounded, color: bodyPurple)
            : Text(
                trailing!,
                style: const TextStyle(
                  color: brandPurple,
                  fontSize: 16,
                  fontWeight: FontWeight.w900,
                ),
              ),
        onTap: onTap,
      ),
    );
  }
}

class NotificationSettingsTile extends StatelessWidget {
  const NotificationSettingsTile({
    super.key,
    required this.enabled,
    required this.onChanged,
  });

  final bool enabled;
  final ValueChanged<bool> onChanged;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: ListTile(
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 18,
          vertical: 10,
        ),
        leading: const Icon(
          Icons.notifications_none_rounded,
          color: bodyPurple,
          size: 30,
        ),
        title: const Text(
          'Notifications',
          style: TextStyle(
            color: darkPurple,
            fontSize: 17,
            fontWeight: FontWeight.w800,
          ),
        ),
        trailing: Switch(
          value: enabled,
          activeThumbColor: Colors.white,
          activeTrackColor: brandPurple,
          inactiveThumbColor: Colors.white,
          inactiveTrackColor: const Color(0xFFE8E4F8),
          onChanged: onChanged,
        ),
      ),
    );
  }
}

class SubscriptionDetailsCard extends StatelessWidget {
  const SubscriptionDetailsCard({
    super.key,
    required this.packageName,
    required this.endDate,
    required this.companyStatus,
  });

  final String packageName;
  final String endDate;
  final String companyStatus;

  @override
  Widget build(BuildContext context) {
    final normalizedStatus = companyStatus.toLowerCase() == 'active'
        ? 'Active'
        : 'Inactive';
    final expiryDate = endDate == '-' ? '--------' : endDate;

    return Card(
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    'Subscription',
                    style: const TextStyle(
                      color: darkPurple,
                      fontSize: 16,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                ),
                Text(
                  normalizedStatus,
                  style: TextStyle(
                    color: normalizedStatus == 'Active'
                        ? brandPurple
                        : Colors.red.shade600,
                    fontSize: 14,
                    fontWeight: FontWeight.w900,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 4),
            Row(
              children: [
                Expanded(
                  child: Text(
                    packageName,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      color: bodyPurple,
                      fontSize: 13,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Text(
                  'Expiry Date : $expiryDate',
                  style: const TextStyle(
                    color: bodyPurple,
                    fontSize: 13,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class StatusChip extends StatelessWidget {
  const StatusChip({super.key, required this.label, required this.color});
  final String label;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: color,
          fontWeight: FontWeight.w800,
          fontSize: 12,
        ),
      ),
    );
  }
}

class ErrorBox extends StatelessWidget {
  const ErrorBox({super.key, required this.message});
  final String message;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.red.shade50,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.red.shade100),
      ),
      child: Text(message, style: TextStyle(color: Colors.red.shade800)),
    );
  }
}

class EmptyState extends StatelessWidget {
  const EmptyState({super.key, required this.label});
  final String label;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(22),
        child: Center(
          child: Text(label, style: const TextStyle(color: bodyPurple)),
        ),
      ),
    );
  }
}

Future<String?> promptText(
  BuildContext context, {
  required String title,
  required String label,
}) {
  final controller = TextEditingController();
  return showDialog<String>(
    context: context,
    builder: (_) => AlertDialog(
      title: Text(title),
      content: TextField(
        controller: controller,
        decoration: InputDecoration(labelText: label),
        autofocus: true,
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(context),
          child: const Text('Cancel'),
        ),
        FilledButton(
          onPressed: () => Navigator.pop(context, controller.text),
          child: const Text('Save'),
        ),
      ],
    ),
  );
}

Future<void> runAction(
  BuildContext context,
  Future<dynamic> Function() action,
) async {
  try {
    await action();
    if (context.mounted) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Saved')));
    }
  } catch (exception) {
    if (context.mounted) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(exception.toString())));
    }
  }
}

Map<String, dynamic> asMap(dynamic value) {
  if (value is Map<String, dynamic>) return value;
  if (value is Map) {
    return value.map((key, val) => MapEntry(key.toString(), val));
  }
  return <String, dynamic>{};
}

Map<String, dynamic>? nullableMap(dynamic value) {
  if (value == null) return null;
  return asMap(value);
}

List<dynamic> asList(dynamic value) {
  if (value is List) return value;
  return const [];
}

List<dynamic> paginatedList(dynamic value) {
  final map = asMap(value);
  if (map['data'] is List) return map['data'] as List;
  return asList(value);
}

String text(dynamic value, {String fallback = ''}) {
  if (value == null) return fallback;
  final result = value.toString();
  return result.isEmpty ? fallback : result;
}

num? number(dynamic value) {
  if (value is num) return value;
  return num.tryParse(text(value));
}

String money(dynamic value) {
  final amount = number(value)?.toDouble() ?? 0;
  return '${amount.toStringAsFixed(3)} OMR';
}

String compactMetric(dynamic value, {int decimals = 3}) {
  final amount = number(value)?.toDouble() ?? 0;
  if (decimals == 0) return amount.round().toString();
  if (amount.abs() >= 1000) return amount.toStringAsFixed(0);
  return amount.toStringAsFixed(decimals).replaceFirst(RegExp(r'\.?0+$'), '');
}

double gaugeProgress(dynamic value) {
  final amount = number(value)?.toDouble().abs() ?? 0;
  if (amount <= 0) return 0.08;
  return (amount / (amount + 50)).clamp(0.18, 0.92);
}

String dateText(dynamic value) {
  final raw = text(value, fallback: '-');
  return raw.length > 10 ? raw.substring(0, 10) : raw;
}

String formatDate(DateTime value) {
  final year = value.year.toString().padLeft(4, '0');
  final month = value.month.toString().padLeft(2, '0');
  final day = value.day.toString().padLeft(2, '0');

  return '$year-$month-$day';
}

String compactDate(DateTime value) => '${monthName(value.month)} ${value.day}';

String monthName(int month) {
  const names = [
    'Jan',
    'Feb',
    'Mar',
    'Apr',
    'May',
    'Jun',
    'Jul',
    'Aug',
    'Sep',
    'Oct',
    'Nov',
    'Dec',
  ];

  return names[(month - 1).clamp(0, 11)];
}

String dashboardPeriodLabel(String period) {
  return switch (period) {
    'day' => 'Day',
    'yesterday' => 'Yesterday',
    'this_week' => 'This week',
    'this_month' => 'This month',
    'this_year' => 'This year',
    'last_month' => 'Last month',
    'custom' => 'Custom dates',
    _ => 'Today',
  };
}

extension DateTimeFrame on DateTime {
  DateTime startOfWeek() {
    return subtract(Duration(days: weekday - DateTime.monday));
  }

  DateTime endOfWeek() {
    return startOfWeek().add(const Duration(days: 6));
  }
}

String boolText(dynamic value) {
  if (value == true || value == 1 || value == '1') return 'Yes';
  if (value == false || value == 0 || value == '0') return 'No';
  return text(value, fallback: '-');
}

String statusLabel(String status) {
  return switch (status) {
    'in_process' => 'In process',
    'ready' => 'Ready',
    'delivered' => 'Delivered',
    _ => status.replaceAll('_', ' '),
  };
}

String paymentLabel(String status) {
  return switch (status) {
    'partial' => 'Partially paid',
    'paid' => 'Paid',
    'unpaid' => 'Unpaid',
    _ => status.replaceAll('_', ' '),
  };
}

Color statusColor(String status) {
  return switch (status) {
    'ready' => Colors.green,
    'delivered' => brandPurple,
    'in_process' => Colors.orange,
    _ => Colors.blueGrey,
  };
}
