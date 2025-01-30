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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $address = $_POST['address'];
    $dni = $_POST['dni'];

    if (empty($name) || empty($email) || empty($password)) {
        $error = "Por favor, complete todos los campos obligatorios.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO customers (name, last_name, email, password, address, dni) VALUES (:name, :last_name, :email, :password, :address, :dni)");
        $stmt->execute([
            'name' => $name,
            'last_name' => $last_name,
            'email' => $email,
            'password' => $password,
            'address' => $address,
            'dni' => $dni
        ]);
        $success = "Cliente agregado exitosamente.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo Cliente</title>
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
        .form-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 800px;
            margin-top: 20px;
        }
        .form-container h2 {
            margin-top: 0;
            text-align: center;
        }
        .form-container label {
            display: block;
            margin-bottom: 8px;
        }
        .form-container input[type="text"],
        .form-container input[type="email"],
        .form-container input[type="password"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .form-container button {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            border: none;
            border-radius: 4px;
            color: #fff;
            font-size: 16px;
        }
        .form-container button:hover {
            background-color: #0056b3;
        }
        .error, .success {
            color: red;
            text-align: center;
        }
        .success {
            color: green;
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
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <div class="content">
        <?php include __DIR__ . '/includes/header.php'; ?>
        <div class="form-container">
            <h2>Nuevo Cliente</h2>
            <?php if (isset($error)): ?>
                <p class="error"><?php echo $error; ?></p>
            <?php endif; ?>
            <?php if (isset($success)): ?>
                <p class="success"><?php echo $success; ?></p>
            <?php endif; ?>
            <form method="post" action="nuevo_cliente.php">
                <label for="name">Nombre:</label>
                <input type="text" name="name" id="name" required>
                <label for="last_name">Apellido:</label>
                <input type="text" name="last_name" id="last_name">
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" required>
                <label for="password">Contraseña:</label>
                <input type="password" name="password" id="password" required>
                <label for="address">Dirección:</label>
                <input type="text" name="address" id="address">
                <label for="dni">DNI:</label>
                <input type="text" name="dni" id="dni">
                <button type="submit">Agregar Cliente</button>
            </form>
        </div>
    </div>
</body>
</html>
