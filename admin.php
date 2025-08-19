<?php
// Include config file which contains database connection
require_once 'session_config.php';
require_once 'config.php';
require_once 'notifications.php';

// Admin jogosultság ellenőrzése
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    Notification::add('Nincs jogosultsága az admin panel eléréséhez!', 'failure');
    header('Location: index.php');
    exit;
}

// Fetch statistics for cards
$monthlyOrdersQuery = "SELECT SUM(total_amount) as monthly_total FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
$yearlyOrdersQuery = "SELECT SUM(total_amount) as yearly_total FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
$totalOrdersQuery = "SELECT SUM(total_amount) as all_time_total FROM orders";
$activeOrdersQuery = "SELECT COUNT(*) as active_count FROM orders WHERE status IN ('pending', 'processing')";

// Previous period comparisons
$prevMonthOrdersQuery = "SELECT SUM(total_amount) as prev_month_total FROM orders 
                        WHERE created_at BETWEEN DATE_SUB(CURDATE(), INTERVAL 2 MONTH) AND DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
$prevYearOrdersQuery = "SELECT SUM(total_amount) as prev_year_total FROM orders 
                       WHERE created_at BETWEEN DATE_SUB(CURDATE(), INTERVAL 2 YEAR) AND DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
$prevWeekActiveOrdersQuery = "SELECT COUNT(*) as prev_week_active FROM orders 
                             WHERE status IN ('pending', 'processing') 
                             AND created_at BETWEEN DATE_SUB(CURDATE(), INTERVAL 2 WEEK) AND DATE_SUB(CURDATE(), INTERVAL 1 WEEK)";

// Execute queries
$monthlyResult = $db->query($monthlyOrdersQuery)->fetch();
$yearlyResult = $db->query($yearlyOrdersQuery)->fetch();
$totalResult = $db->query($totalOrdersQuery)->fetch();
$activeResult = $db->query($activeOrdersQuery)->fetch();

$prevMonthResult = $db->query($prevMonthOrdersQuery)->fetch();
$prevYearResult = $db->query($prevYearOrdersQuery)->fetch();
$prevWeekActiveResult = $db->query($prevWeekActiveOrdersQuery)->fetch();

// Calculate percentage changes
$monthlyChange = 0;
if ($prevMonthResult['prev_month_total'] > 0) {
    $monthlyChange = (($monthlyResult['monthly_total'] - $prevMonthResult['prev_month_total']) / $prevMonthResult['prev_month_total']) * 100;
}

$yearlyChange = 0;
if ($prevYearResult['prev_year_total'] > 0) {
    $yearlyChange = (($yearlyResult['yearly_total'] - $prevYearResult['prev_year_total']) / $prevYearResult['prev_year_total']) * 100;
}

// Calculate total average annual growth
$averageGrowth = 5.7; // You may want to calculate this based on historical data

// Calculate active orders change
$activeChange = $activeResult['active_count'] - $prevWeekActiveResult['prev_week_active'];

// Format numbers for display
$monthlyTotal = number_format($monthlyResult['monthly_total'] ?? 0, 0, '.', ',');
$yearlyTotal = number_format($yearlyResult['yearly_total'] ?? 0, 0, '.', ',');
$allTimeTotal = number_format($totalResult['all_time_total'] ?? 0, 0, '.', ',');
$activeCount = $activeResult['active_count'] ?? 0;

// Bevétel alakulása adatok lekérése
function getRevenueData($period = 'daily') {
    global $db;
    
    switch($period) {
        case 'daily':
            $query = $db->prepare("
                SELECT DATE(created_at) as date, SUM(total_amount) as revenue 
                FROM orders 
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY DATE(created_at)
                ORDER BY date
            ");
            break;
            
        case 'weekly':
            $query = $db->prepare("
                SELECT DATE_FORMAT(created_at, '%Y-%u') as date, SUM(total_amount) as revenue 
                FROM orders 
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 WEEK)
                GROUP BY DATE_FORMAT(created_at, '%Y-%u')
                ORDER BY date
            ");
            break;
            
        case 'monthly':
            $query = $db->prepare("
                SELECT DATE_FORMAT(created_at, '%Y-%m') as date, SUM(total_amount) as revenue 
                FROM orders 
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY date
            ");
            break;
    }
    
    $query->execute();
    return $query->fetchAll(PDO::FETCH_ASSOC);
}

// Order types chart
$orderTypesQuery = "SELECT category, COUNT(*) as category_count FROM orders GROUP BY category";
$orderTypesResult = $db->query($orderTypesQuery);

$categories = [];
$categoryCounts = [];

while ($row = $orderTypesResult->fetch()) {
    $categories[] = $row['category'];
    $categoryCounts[] = $row['category_count'];
}

// Recent orders
$recentOrdersQuery = "SELECT 
                        id, 
                        user_id, 
                        created_at, 
                        total_amount, 
                        status 
                      FROM orders 
                      ORDER BY created_at DESC 
                      LIMIT 5";
$recentOrdersResult = $db->query($recentOrdersQuery);

// Define status classes for UI
$statusClasses = [
    'pending' => 'bg-yellow-100 text-yellow-800',
    'processing' => 'bg-blue-100 text-blue-800',
    'completed' => 'bg-green-100 text-green-800',
    'cancelled' => 'bg-red-100 text-red-800'
];
?>
<!DOCTYPE html>
<html lang="hu">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  <!-- Fontos: A legfrissebb Chart.js könyvtár teljes elérési úttal -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.bundle.min.js"></script>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="new_styles.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="notifications/notifications.css">
  <script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    primary: {
                        lightest: '#ade4e5',
                        light: '#3ca7aa',
                        dark: '#00868a',
                        darkest: '#003f41',
                    },
                    lightblue: {
                        50: '#f0f7fa',
                        100: '#e1eef5',
                    }
                }
            }
        }
    }
  </script>
</head>
<body class="bg-pattern flex flex-col md:flex-row min-h-screen">
  <?php echo Notification::display(); ?>
  <!-- Sidebar -->
  <div class="w-full md:w-64 bg-primary-darkest text-white flex flex-col md:h-screen">
    <div class="p-4 bg-primary-dark">
      <h2 class="text-2xl font-bold">AdminPanel</h2>
    </div>
    <nav class="flex-grow p-4">
      <ul class="space-y-2">
        <li>
          <a href="admin.php" class="flex items-center p-2 <?php echo basename($_SERVER['PHP_SELF']) === 'admin.php' ? 'bg-primary-light' : 'hover:bg-primary-dark'; ?> rounded text-white">
            <span class="mr-2">🏠</span>
            <span>Vezérlőpult</span>
          </a>
        </li>
        <li>
          <a href="admin_products.php" class="flex items-center p-2 <?php echo basename($_SERVER['PHP_SELF']) === 'admin_products.php' ? 'bg-primary-light' : 'hover:bg-primary-dark'; ?> rounded text-white">
            <span class="mr-2">🛒</span>
            <span>Termékek</span>
          </a>
        </li>
        <li>
          <a href="admin_orders.php" class="flex items-center p-2 <?php echo basename($_SERVER['PHP_SELF']) === 'admin_orders.php' ? 'bg-primary-light' : 'hover:bg-primary-dark'; ?> rounded text-white">
            <span class="mr-2">📦</span>
            <span>Rendelések</span>
          </a>
        </li>
        <li>
          <a href="admin_customers.php" class="flex items-center p-2 <?php echo basename($_SERVER['PHP_SELF']) === 'admin_customers.php' ? 'bg-primary-light' : 'hover:bg-primary-dark'; ?> rounded text-white">
            <span class="mr-2">👥</span>
            <span>Ügyfelek</span>
          </a>
        </li>
        <li>
          <a href="admin_coupons.php" class="flex items-center p-2 <?php echo basename($_SERVER['PHP_SELF']) === 'admin_coupons.php' ? 'bg-primary-light' : 'hover:bg-primary-dark'; ?> rounded text-white">
            <span class="mr-2">🎟️</span>
            <span>Kuponok</span>
          </a>
        </li>
        <li>
          <a href="admin_reports.php" class="flex items-center p-2 <?php echo basename($_SERVER['PHP_SELF']) === 'admin_reports.php' ? 'bg-primary-light' : 'hover:bg-primary-dark'; ?> rounded text-white">
            <span class="mr-2">📊</span>
            <span>Jelentések</span>
          </a>
        </li>
        <li>
          <a href="admin_settings.php" class="flex items-center p-2 <?php echo basename($_SERVER['PHP_SELF']) === 'admin_settings.php' ? 'bg-primary-light' : 'hover:bg-primary-dark'; ?> rounded text-white">
            <span class="mr-2">⚙️</span>
            <span>Beállítások</span>
          </a>
        </li>
        <li>
          <a href="index.php" class="flex items-center p-2 hover:bg-red-700 text-white rounded">
              <span class="mr-2">🚪</span>
              <span>Kilépés</span>
          </a>
        </li>
      </ul>
    </nav>
  </div>

  <!-- Main Content -->
  <div class="flex-grow">
    <header class="bg-white shadow p-4">
      <div class="flex justify-between items-center">
        <h1 class="text-xl font-bold text-primary-darkest">Vezérlőpult</h1>
        <div class="flex items-center space-x-4">
          <a href="index.php" class="p-1 rounded-full text-primary-dark hover:text-primary-light transition duration-300">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7m-14 0l2 2m0 0l7 7-7-7m14 0l-2-2m0 0l-7-7-7 7"></path>
            </svg>
          </a>
        </div>
      </div>
    </header>

    <main class="p-4">
      <!-- Filter Section -->
      <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex flex-wrap gap-4">
          <div class="w-full md:w-auto">
            <label class="block text-sm font-medium text-primary-darkest mb-1">Dátum</label>
            <select class="form-control" id="dateFilter">
              <option value="7">Utolsó 7 nap</option>
              <option value="30">Utolsó 30 nap</option>
              <option value="month">Ez a hónap</option>
              <option value="year">Ez az év</option>
            </select>
          </div>
          <div class="w-full md:w-auto">
            <label class="block text-sm font-medium text-primary-darkest mb-1">Kategória</label>
            <select class="form-control" id="categoryFilter">
              <option value="all">Összes kategória</option>
              <?php foreach ($categories as $category): ?>
                <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="w-full md:w-auto md:ml-auto md:self-end">
            <button class="btn btn-primary" id="applyFilter">
              Szűrés alkalmazása
            </button>
          </div>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <!-- Monthly Orders -->
        <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition duration-150 ease-in-out">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-primary-darkest">Havi bevétel</h3>
            <span class="p-2 rounded-full bg-primary-lightest text-primary-darkest">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </span>
          </div>
          <div class="flex items-end">
            <p class="text-2xl font-bold text-primary-darkest"><?php echo $monthlyTotal; ?> Ft</p>
            <span class="ml-2 <?php echo $monthlyChange >= 0 ? 'text-green-500' : 'text-red-500'; ?> font-medium text-sm">
              <?php echo ($monthlyChange >= 0 ? '+' : '') . number_format($monthlyChange, 1); ?>%
            </span>
          </div>
          <p class="text-primary-dark text-sm mt-2">Az előző hónaphoz képest</p>
        </div>
        
        <!-- Yearly Orders -->
        <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition duration-150 ease-in-out">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-primary-darkest">Éves bevétel</h3>
            <span class="p-2 rounded-full bg-primary-lightest text-primary-darkest">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
              </svg>
            </span>
          </div>
          <div class="flex items-end">
            <p class="text-2xl font-bold text-primary-darkest"><?php echo $yearlyTotal; ?> Ft</p>
            <span class="ml-2 <?php echo $yearlyChange >= 0 ? 'text-green-500' : 'text-red-500'; ?> font-medium text-sm">
              <?php echo ($yearlyChange >= 0 ? '+' : '') . number_format($yearlyChange, 1); ?>%
            </span>
          </div>
          <p class="text-primary-dark text-sm mt-2">Az előző évhez képest</p>
        </div>
        
        <!-- Average Growth -->
        <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition duration-150 ease-in-out">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-primary-darkest">Átlagos növekedés</h3>
            <span class="p-2 rounded-full bg-primary-lightest text-primary-darkest">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
              </svg>
            </span>
          </div>
          <div class="flex items-end">
            <p class="text-2xl font-bold text-primary-darkest"><?php echo number_format($averageGrowth, 1); ?>%</p>
          </div>
          <p class="text-primary-dark text-sm mt-2">Éves átlagos növekedés</p>
        </div>
        
        <!-- Active Orders -->
        <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition duration-150 ease-in-out">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-primary-darkest">Aktív rendelések</h3>
            <span class="p-2 rounded-full bg-primary-lightest text-primary-darkest">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
              </svg>
            </span>
          </div>
          <div class="flex items-end">
            <p class="text-2xl font-bold text-primary-darkest"><?php echo $activeCount; ?></p>
            <span class="ml-2 <?php echo $activeChange >= 0 ? 'text-green-500' : 'text-red-500'; ?> font-medium text-sm">
              <?php echo ($activeChange >= 0 ? '+' : '') . $activeChange; ?>
            </span>
          </div>
          <p class="text-primary-dark text-sm mt-2">Az előző héthez képest</p>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Revenue Chart -->
        <div class="bg-white rounded-lg shadow-md p-6">
          <h3 class="text-lg font-semibold text-primary-darkest mb-4">Bevétel alakulása</h3>
          <div class="flex space-x-2 mb-4">
            <button class="px-3 py-1 rounded-md border border-primary-light text-primary-dark hover:bg-primary-lightest focus:outline-none period-btn active" data-period="daily">Napi</button>
            <button class="px-3 py-1 rounded-md border border-primary-light text-primary-dark hover:bg-primary-lightest focus:outline-none period-btn" data-period="weekly">Heti</button>
            <button class="px-3 py-1 rounded-md border border-primary-light text-primary-dark hover:bg-primary-lightest focus:outline-none period-btn" data-period="monthly">Havi</button>
          </div>
          <div class="w-full h-64">
            <canvas id="revenueChart"></canvas>
          </div>
        </div>
        
        <!-- Order Types Chart -->
        <div class="bg-white rounded-lg shadow-md p-6">
          <h3 class="text-lg font-semibold text-primary-darkest mb-4">Rendelések típusai</h3>
          <div class="w-full h-64">
            <canvas id="orderTypesChart"></canvas>
          </div>
        </div>
      </div>

      <!-- Recent Orders -->
      <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-lg font-semibold text-primary-darkest">Legutóbbi rendelések</h3>
          <a href="admin_orders.php" class="text-primary-light hover:text-primary-dark font-medium">Összes rendelés</a>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead>
              <tr>
                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rendelés ID</th>
                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ügyfél</th>
                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dátum</th>
                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Összeg</th>
                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Státusz</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <?php while ($order = $recentOrdersResult->fetch()): ?>
              <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-primary-darkest">#<?php echo $order['id']; ?></td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-primary-dark"><?php echo htmlspecialchars($order['user_name'] ?? 'N/A'); ?></td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-primary-dark"><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-primary-dark"><?php echo number_format($order['total_amount'], 0, '.', ' '); ?> Ft</td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="px-2 py-1 text-xs rounded-full <?php echo $statusClasses[$order['status'] ?? 'pending'] ?? 'bg-gray-100 text-gray-800'; ?>">
                    <?php echo htmlspecialchars($order['status'] ?? 'pending'); ?>
                  </span>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>

  <script>
  // Daily revenue data
  const revenueData = {
    daily: <?php echo json_encode(getRevenueData('daily')); ?>,
    weekly: <?php echo json_encode(getRevenueData('weekly')); ?>,
    monthly: <?php echo json_encode(getRevenueData('monthly')); ?>
  };

  // Chart configurations
  const revenueChartCtx = document.getElementById('revenueChart').getContext('2d');
  let revenueChart = new Chart(revenueChartCtx, {
    type: 'line',
    data: {
      datasets: [{
        label: 'Bevétel',
        backgroundColor: 'rgba(60, 167, 170, 0.1)',
        borderColor: '#3ca7aa',
        data: revenueData.daily.map(item => ({
          x: item.date,
          y: item.revenue
        })),
        fill: true,
        tension: 0.4
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        x: {
          type: 'time',
          time: {
            unit: 'day'
          }
        }
      }
    }
  });

  // Switch between time periods
  document.querySelectorAll('.period-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      const period = this.getAttribute('data-period');
      document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active', 'bg-primary-lightest', 'text-primary-darkest'));
      this.classList.add('active', 'bg-primary-lightest', 'text-primary-darkest');
      
      revenueChart.data.datasets[0].data = revenueData[period].map(item => ({
        x: item.date,
        y: item.revenue
      }));
      
      if (period === 'daily') {
        revenueChart.options.scales.x.time.unit = 'day';
      } else if (period === 'weekly') {
        revenueChart.options.scales.x.time.unit = 'week';
      } else if (period === 'monthly') {
        revenueChart.options.scales.x.time.unit = 'month';
      }
      
      revenueChart.update();
    });
  });

  // Order types chart
  const orderTypesChartCtx = document.getElementById('orderTypesChart').getContext('2d');
  new Chart(orderTypesChartCtx, {
    type: 'doughnut',
    data: {
      labels: <?php echo json_encode($categories); ?>,
      datasets: [{
        data: <?php echo json_encode($categoryCounts); ?>,
        backgroundColor: [
          '#ade4e5',
          '#3ca7aa',
          '#00868a',
          '#003f41',
          '#e1eef5'
        ],
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '70%',
      plugins: {
        legend: {
          position: 'bottom'
        }
      }
    }
  });

  // Apply filter functionality
  document.getElementById('applyFilter').addEventListener('click', function() {
    const dateFilter = document.getElementById('dateFilter').value;
    const categoryFilter = document.getElementById('categoryFilter').value;
    
    // In a real application, you would fetch filtered data via AJAX
    // For demo purposes, we'll just show an alert
    alert(`Szűrés alkalmazva: Dátum: ${dateFilter}, Kategória: ${categoryFilter}`);
  });
  </script>
</body>
</html>