<?php
session_start();
require 'config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM administrators WHERE id = :id");
$stmt->execute(['id' => $_SESSION['admin_id']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    header("Location: login.php");
    exit();
}

// Obtener la cantidad de clientes
$stmt = $pdo->query("SELECT COUNT(*) AS total_customers FROM customers");
$total_customers = $stmt->fetch(PDO::FETCH_ASSOC)['total_customers'];

// Obtener la cantidad de productos (suma del stock de los zapatos con diferentes tallas y colores)
$stmt = $pdo->query("SELECT SUM(stock) AS total_products FROM shoe_sizes");
$total_products = $stmt->fetch(PDO::FETCH_ASSOC)['total_products'];

// Obtener la cantidad de productos vendidos
$stmt = $pdo->query("SELECT SUM(cart_items.quantity) AS total_sold FROM sales JOIN cart_items ON sales.cart_id = cart_items.cart_id");
$total_sold = $stmt->fetch(PDO::FETCH_ASSOC)['total_sold'];

// Obtener los productos vendidos por mes
$stmt = $pdo->query("SELECT MONTH(sale_date) AS month, SUM(cart_items.quantity) AS total_sold FROM sales JOIN cart_items ON sales.cart_id = cart_items.cart_id GROUP BY MONTH(sale_date)");
$sales_by_month = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener las ventas por fecha
$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-01');
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-t');

$stmt = $pdo->prepare("SELECT sales.sale_date, sales.id AS sale_id, sales.total_amount, administrators.name AS admin_name, GROUP_CONCAT(CONCAT(shoes.name, ' (', cart_items.quantity, ')') SEPARATOR ', ') AS products_sold
                       FROM sales
                       JOIN administrators ON sales.admin_id = administrators.id
                       JOIN cart_items ON sales.cart_id = cart_items.cart_id
                       JOIN shoe_sizes ON cart_items.shoe_sizes_id = shoe_sizes.id
                       JOIN shoes ON shoe_sizes.shoe_id = shoes.id
                       WHERE sales.sale_date BETWEEN :start_date AND :end_date
                       GROUP BY sales.id
                       ORDER BY sales.sale_date");
$stmt->execute(['start_date' => $start_date, 'end_date' => $end_date]);
$sales_by_date = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener los productos más vendidos
$stmt = $pdo->prepare("SELECT shoes.name, SUM(cart_items.quantity) AS total_quantity, SUM(cart_items.quantity * shoes.price) AS total_sales
                       FROM cart_items
                       JOIN shoe_sizes ON cart_items.shoe_sizes_id = shoe_sizes.id
                       JOIN shoes ON shoe_sizes.shoe_id = shoes.id
                       JOIN sales ON cart_items.cart_id = sales.cart_id
                       WHERE sales.sale_date BETWEEN :start_date AND :end_date
                       GROUP BY shoes.id
                       ORDER BY total_quantity DESC");
$stmt->execute(['start_date' => $start_date, 'end_date' => $end_date]);
$top_selling_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener los ingresos por marca
$stmt = $pdo->query("SELECT brands.name AS brand_name, SUM(cart_items.quantity * shoes.price) AS total_sales, SUM(cart_items.quantity) AS total_quantity
                     FROM cart_items
                     JOIN shoe_sizes ON cart_items.shoe_sizes_id = shoe_sizes.id
                     JOIN shoes ON shoe_sizes.shoe_id = shoes.id
                     JOIN brands ON shoes.brand_id = brands.id
                     JOIN sales ON cart_items.cart_id = sales.cart_id
                     GROUP BY brands.id
                     ORDER BY total_sales DESC");
$sales_by_brand = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener las ventas con descuento
$stmt = $pdo->prepare("SELECT shoes.name AS product_name, offers.discount_percentage, offers.start_date, offers.end_date, shoes.price AS original_price, 
                       (shoes.price * (1 - offers.discount_percentage / 100)) AS discounted_price
                       FROM sales
                       JOIN cart_items ON sales.cart_id = cart_items.cart_id
                       JOIN shoe_sizes ON cart_items.shoe_sizes_id = shoe_sizes.id
                       JOIN shoes ON shoe_sizes.shoe_id = shoes.id
                       JOIN offers ON shoes.id = offers.shoe_id
                       WHERE sales.sale_date BETWEEN :start_date AND :end_date");
$stmt->execute(['start_date' => $start_date, 'end_date' => $end_date]);
$discounted_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener los clientes bloqueados
$stmt = $pdo->query("SELECT name, email, blocked, created_at AS blocked_date FROM customers WHERE blocked = 1");
$blocked_customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Administración</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            margin: 0;
            height: 100vh;
            background-color: #f4f4f4;
        }
        .sidebar {
            width: 250px;
            background-color: #333;
            color: #fff;
            padding: 20px;
            box-sizing: border-box;
            transition: transform 0.3s ease;
        }
        .sidebar.hidden {
            transform: translateX(-100%);
        }
        .sidebar h2, .sidebar h3 {
            margin-top: 0;
        }
        .sidebar a, .menu-item {
            color: #fff;
            text-decoration: none;
            display: block;
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 4px;
        }
        .sidebar a:hover, .menu-item:hover {
            background-color: #444;
        }
        .content {
            flex: 1;
            padding: 20px;
            box-sizing: border-box;
        }
        .header {
            background-color: #007bff;
            color: #fff;
            padding: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 4px;
        }
        .header .logout {
            background-color: #ff0000;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            color: #fff;
            cursor: pointer;
        }
        .submenu {
            display: none;
            margin-left: 20px;
        }
        .submenu a {
            margin-bottom: 5px;
        }
        .menu-item {
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .menu-item .arrow {
            transition: transform 0.3s ease;
        }
        .menu-item.open .arrow {
            transform: rotate(90deg);
        }
        .menu-item i {
            margin-right: 10px;
        }
        .dashboard {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .dashboard .card {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            flex: 1;
            min-width: 200px;
            text-align: center;
        }
        .dashboard .card h3 {
            margin-top: 0;
        }
        .dashboard .card p {
            font-size: 24px;
            margin: 10px 0;
        }
        .chart-container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
        }
        .sales-report, .top-products-report, .brand-sales-report, .discounted-sales-report, .blocked-customers-report {
            margin-top: 20px;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .sales-report h3, .top-products-report h3, .brand-sales-report h3, .discounted-sales-report h3, .blocked-customers-report h3 {
            margin-top: 0;
        }
        .sales-report table, .top-products-report table, .brand-sales-report table, .discounted-sales-report table, .blocked-customers-report table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .sales-report table, .top-products-report table, .brand-sales-report table, .discounted-sales-report table, .blocked-customers-report table, th, td {
            border: 1px solid #ccc;
        }
        .sales-report th, .sales-report td, .top-products-report th, .top-products-report td, .brand-sales-report th, .brand-sales-report td, .discounted-sales-report th, .discounted-sales-report td, .blocked-customers-report th, .blocked-customers-report td {
            padding: 10px;
            text-align: left;
        }
        .sales-report th, .top-products-report th, .brand-sales-report th, .discounted-sales-report th, .blocked-customers-report th {
            background-color: #007bff;
            color: #fff;
        }
    </style>
    <script>
        function toggleSubmenu(id) {
            const submenu = document.getElementById(id);
            submenu.classList.toggle('submenu');
            submenu.previousElementSibling.classList.toggle('open');
        }
    </script>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <div class="content">
        <?php include 'includes/header.php'; ?>
        <div class="dashboard">
            <div class="card">
                <h3>Total Clientes</h3>
                <p><?php echo $total_customers; ?></p>
            </div>
            <div class="card">
                <h3>Total Productos</h3>
                <p><?php echo $total_products; ?></p>
            </div>
            <div class="card">
                <h3>Total Productos Vendidos</h3>
                <p><?php echo $total_sold; ?></p>
            </div>
        </div>
        <div class="chart-container">
            <canvas id="salesChart"></canvas>
        </div>
        <div class="sales-report">
            <h3>Reporte de Ventas por Fecha</h3>
            <form method="post" action="admin_dashboard.php">
                <label for="start_date">Fecha de inicio:</label>
                <input type="date" name="start_date" id="start_date" value="<?php echo $start_date; ?>">
                <label for="end_date">Fecha de fin:</label>
                <input type="date" name="end_date" id="end_date" value="<?php echo $end_date; ?>">
                <button type="submit">Filtrar</button>
            </form>
            <table>
                <thead>
                    <tr>
                        <th>Fecha de Venta</th>
                        <th>ID de Venta</th>
                        <th>Total de Venta</th>
                        <th>Productos Vendidos</th>
                        <th>Administrador</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sales_by_date as $sale): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($sale['sale_date']); ?></td>
                            <td><?php echo htmlspecialchars($sale['sale_id']); ?></td>
                            <td>S/ <?php echo htmlspecialchars(number_format($sale['total_amount'], 2)); ?></td>
                            <td><?php echo htmlspecialchars($sale['products_sold']); ?></td>
                            <td><?php echo htmlspecialchars($sale['admin_name']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="top-products-report">
            <h3>Reporte de Productos Más Vendidos</h3>
            <form method="post" action="admin_dashboard.php">
                <label for="start_date">Fecha de inicio:</label>
                <input type="date" name="start_date" id="start_date" value="<?php echo $start_date; ?>">
                <label for="end_date">Fecha de fin:</label>
                <input type="date" name="end_date" id="end_date" value="<?php echo $end_date; ?>">
                <button type="submit">Filtrar</button>
            </form>
            <table>
                <thead>
                    <tr>
                        <th>Nombre del Producto</th>
                        <th>Cantidad Vendida</th>
                        <th>Precio Total de Ventas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_selling_products as $product): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo htmlspecialchars($product['total_quantity']); ?></td>
                            <td>S/ <?php echo htmlspecialchars(number_format($product['total_sales'], 2)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="brand-sales-report">
            <h3>Reporte de Ingresos por Marca</h3>
            <table>
                <thead>
                    <tr>
                        <th>Nombre de la Marca</th>
                        <th>Total de Ventas</th>
                        <th>Cantidad de Productos Vendidos</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sales_by_brand as $brand): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($brand['brand_name']); ?></td>
                            <td>S/ <?php echo htmlspecialchars(number_format($brand['total_sales'], 2)); ?></td>
                            <td><?php echo htmlspecialchars($brand['total_quantity']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="discounted-sales-report">
            <h3>Reporte de Descuentos Aplicados</h3>
            <table>
                <thead>
                    <tr>
                        <th>Nombre del Producto</th>
                        <th>Porcentaje de Descuento</th>
                        <th>Fecha de la Oferta</th>
                        <th>Precio Original</th>
                        <th>Precio con Descuento</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($discounted_sales as $sale): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($sale['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($sale['discount_percentage']); ?>%</td>
                            <td><?php echo htmlspecialchars($sale['start_date']); ?> - <?php echo htmlspecialchars($sale['end_date']); ?></td>
                            <td>S/ <?php echo htmlspecialchars(number_format($sale['original_price'], 2)); ?></td>
                            <td>S/ <?php echo htmlspecialchars(number_format($sale['discounted_price'], 2)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="blocked-customers-report">
            <h3>Reporte de Clientes Bloqueados</h3>
            <table>
                <thead>
                    <tr>
                        <th>Nombre del Cliente</th>
                        <th>Correo Electrónico</th>
                        <th>Estado de Bloqueo</th>
                        <th>Fecha de Bloqueo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($blocked_customers as $customer): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($customer['name']); ?></td>
                            <td><?php echo htmlspecialchars($customer['email']); ?></td>
                            <td><?php echo $customer['blocked'] ? 'Bloqueado' : 'Activo'; ?></td>
                            <td><?php echo htmlspecialchars($customer['blocked_date']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="chart-container">
            <canvas id="brandSalesChart"></canvas>
        </div>
    </div>
    <script>
        const ctx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [<?php foreach ($sales_by_month as $sale) { echo '"' . date('F', mktime(0, 0, 0, $sale['month'], 10)) . '",'; } ?>],
                datasets: [{
                    label: 'Productos Vendidos',
                    data: [<?php foreach ($sales_by_month as $sale) { echo $sale['total_sold'] . ','; } ?>],
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        const brandCtx = document.getElementById('brandSalesChart').getContext('2d');
        const brandSalesChart = new Chart(brandCtx, {
            type: 'bar',
            data: {
                labels: [<?php foreach ($sales_by_brand as $brand) { echo '"' . $brand['brand_name'] . '",'; } ?>],
                datasets: [{
                    label: 'Total de Ventas',
                    data: [<?php foreach ($sales_by_brand as $brand) { echo $brand['total_sales'] . ','; } ?>],
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
