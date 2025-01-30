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

$admin_name = '';
$customer_name = '';
$dni = '';
$min_amount = '';
$max_amount = '';

$query = "SELECT sales.sale_date, administrators.name AS admin_name, customers.name AS customer_name, customers.last_name AS customer_last_name, customers.email AS customer_email, customers.dni AS customer_dni, sales.total_amount
          FROM sales
          JOIN administrators ON sales.admin_id = administrators.id
          JOIN cart ON sales.cart_id = cart.id
          JOIN customers ON cart.customer_id = customers.id
          WHERE 1=1";

$params = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['filter_admin_name']) && !empty($_POST['admin_name'])) {
        $admin_name = $_POST['admin_name'];
        $query .= " AND administrators.name LIKE :admin_name";
        $params['admin_name'] = '%' . $admin_name . '%';
    }
    if (isset($_POST['filter_customer_name']) && !empty($_POST['customer_name'])) {
        $customer_name = $_POST['customer_name'];
        $query .= " AND (customers.name LIKE :customer_name OR customers.last_name LIKE :customer_name)";
        $params['customer_name'] = '%' . $customer_name . '%';
    }
    if (isset($_POST['filter_dni']) && !empty($_POST['dni'])) {
        $dni = $_POST['dni'];
        $query .= " AND customers.dni LIKE :dni";
        $params['dni'] = '%' . $dni . '%';
    }
    if (isset($_POST['filter_min_amount']) && !empty($_POST['min_amount'])) {
        $min_amount = $_POST['min_amount'];
        $query .= " AND sales.total_amount >= :min_amount";
        $params['min_amount'] = $min_amount;
    }
    if (isset($_POST['filter_max_amount']) && !empty($_POST['max_amount'])) {
        $max_amount = $_POST['max_amount'];
        $query .= " AND sales.total_amount <= :max_amount";
        $params['max_amount'] = $max_amount;
    }
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener la lista de administradores para el filtro
$stmt = $pdo->query("SELECT id, name FROM administrators");
$administrators = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_POST['export_excel'])) {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=ventas.xls");
    echo "Fecha\tVendedor\tCliente\tEmail del Cliente\tDNI del Cliente\tMonto\n";
    foreach ($sales as $sale) {
        echo $sale['sale_date'] . "\t" . $sale['admin_name'] . "\t" . $sale['customer_name'] . " " . $sale['customer_last_name'] . "\t" . $sale['customer_email'] . "\t" . $sale['customer_dni'] . "\t" . number_format($sale['total_amount'], 2) . "\n";
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ver Ventas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
        .search-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .search-container form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .search-container form div {
            flex: 1;
            min-width: 200px;
        }
        .search-container form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .search-container form input[type="text"],
        .search-container form input[type="number"],
        .search-container form select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .search-container form button {
            padding: 10px 20px;
            background-color: #007bff;
            border: none;
            border-radius: 4px;
            color: #fff;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .search-container form button:hover {
            background-color: #0056b3;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ccc;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #007bff;
            color: #fff;
        }
        .export-button {
            padding: 10px 20px;
            background-color: #28a745;
            border: none;
            border-radius: 4px;
            color: #fff;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 20px;
        }
        .export-button:hover {
            background-color: #218838;
        }
        .export-button i {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <div class="content">
        <?php include 'includes/header.php'; ?>
        <h2>Ventas</h2>
        <div class="search-container">
            <form method="post" action="ver_ventas.php">
                <div>
                    <input type="checkbox" name="filter_admin_name" id="filter_admin_name" <?php echo isset($_POST['filter_admin_name']) ? 'checked' : ''; ?>>
                    <label for="filter_admin_name">Nombre del administrador</label>
                    <select name="admin_name">
                        <option value="">Seleccione un administrador</option>
                        <?php foreach ($administrators as $administrator): ?>
                            <option value="<?php echo htmlspecialchars($administrator['name']); ?>" <?php echo $admin_name === $administrator['name'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($administrator['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <input type="checkbox" name="filter_customer_name" id="filter_customer_name" <?php echo isset($_POST['filter_customer_name']) ? 'checked' : ''; ?>>
                    <label for="filter_customer_name">Nombre del cliente</label>
                    <input type="text" name="customer_name" placeholder="Nombre del cliente" value="<?php echo htmlspecialchars($customer_name); ?>">
                </div>
                <div>
                    <input type="checkbox" name="filter_dni" id="filter_dni" <?php echo isset($_POST['filter_dni']) ? 'checked' : ''; ?>>
                    <label for="filter_dni">DNI del cliente</label>
                    <input type="text" name="dni" placeholder="DNI del cliente" value="<?php echo htmlspecialchars($dni); ?>">
                </div>
                <div>
                    <input type="checkbox" name="filter_min_amount" id="filter_min_amount" <?php echo isset($_POST['filter_min_amount']) ? 'checked' : ''; ?>>
                    <label for="filter_min_amount">Monto mínimo</label>
                    <input type="number" name="min_amount" placeholder="Monto mínimo" value="<?php echo htmlspecialchars($min_amount); ?>">
                </div>
                <div>
                    <input type="checkbox" name="filter_max_amount" id="filter_max_amount" <?php echo isset($_POST['filter_max_amount']) ? 'checked' : ''; ?>>
                    <label for="filter_max_amount">Monto máximo</label>
                    <input type="number" name="max_amount" placeholder="Monto máximo" value="<?php echo htmlspecialchars($max_amount); ?>">
                </div>
                <button type="submit"><i class="fas fa-filter"></i> Filtrar</button>
            </form>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Vendedor</th>
                    <th>Cliente</th>
                    <th>Email del Cliente</th>
                    <th>DNI del Cliente</th>
                    <th>Monto</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sales as $sale): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($sale['sale_date']); ?></td>
                        <td><?php echo htmlspecialchars($sale['admin_name']); ?></td>
                        <td><?php echo htmlspecialchars($sale['customer_name'] . ' ' . $sale['customer_last_name']); ?></td>
                        <td><?php echo htmlspecialchars($sale['customer_email']); ?></td>
                        <td><?php echo htmlspecialchars($sale['customer_dni']); ?></td>
                        <td>S/ <?php echo htmlspecialchars(number_format($sale['total_amount'], 2)); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <form method="post" action="ver_ventas.php">
            <button type="submit" name="export_excel" class="export-button"><i class="fas fa-file-excel"></i> Exportar a Excel</button>
        </form>
    </div>
</body>
</html>
