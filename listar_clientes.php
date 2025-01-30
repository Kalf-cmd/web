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

$search_query = '';
$filter_blocked = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['search_query']) || isset($_POST['filter_blocked']))) {
    $search_query = $_POST['search_query'];
    $filter_blocked = $_POST['filter_blocked'];
    $query = "SELECT * FROM customers WHERE (name LIKE :search_query OR last_name LIKE :search_query OR dni LIKE :search_query OR email LIKE :search_query)";
    if ($filter_blocked !== '') {
        $query .= " AND blocked = :filter_blocked";
    }
    $stmt = $pdo->prepare($query);
    $params = ['search_query' => '%' . $search_query . '%'];
    if ($filter_blocked !== '') {
        $params['filter_blocked'] = $filter_blocked;
    }
    $stmt->execute($params);
} else {
    $stmt = $pdo->prepare("SELECT * FROM customers");
    $stmt->execute();
}
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM customers WHERE id = :id");
    $stmt->execute(['id' => $delete_id]);
    header("Location: listar_clientes.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_id'])) {
    $edit_id = $_POST['edit_id'];
    $name = $_POST['name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $address = $_POST['address'];
    $dni = $_POST['dni'];

    $stmt = $pdo->prepare("UPDATE customers SET name = :name, last_name = :last_name, email = :email, password = :password, address = :address, dni = :dni WHERE id = :id");
    $stmt->execute([
        'name' => $name,
        'last_name' => $last_name,
        'email' => $email,
        'password' => $password,
        'address' => $address,
        'dni' => $dni,
        'id' => $edit_id
    ]);
    header("Location: listar_clientes.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_block_id'])) {
    $toggle_block_id = $_POST['toggle_block_id'];
    $current_status = $_POST['current_status'];
    $new_status = $current_status ? 0 : 1;

    $stmt = $pdo->prepare("UPDATE customers SET blocked = :blocked WHERE id = :id");
    $stmt->execute(['blocked' => $new_status, 'id' => $toggle_block_id]);
    header("Location: listar_clientes.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listar Clientes</title>
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
        .search-container {
            margin-bottom: 20px;
        }
        .search-container input[type="text"] {
            padding: 8px;
            width: calc(100% - 220px);
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .search-container select {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-left: 10px;
        }
        .search-container button {
            padding: 8px;
            background-color: #007bff;
            border: none;
            border-radius: 4px;
            color: #fff;
            cursor: pointer;
            margin-left: 10px;
        }
        .search-container button:hover {
            background-color: #0056b3;
        }
        .search-container .reset-button {
            padding: 8px;
            background-color: #6c757d;
            border: none;
            border-radius: 4px;
            color: #fff;
            cursor: pointer;
            margin-left: 10px;
        }
        .search-container .reset-button:hover {
            background-color: #5a6268;
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
        .actions {
            display: flex;
            gap: 10px;
        }
        .actions button {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .actions .edit {
            background-color: #ffc107;
            color: #fff;
        }
        .actions .delete {
            background-color: #dc3545;
            color: #fff;
        }
        .actions .toggle-block {
            background-color: #17a2b8;
            color: #fff;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            width: 400px;
        }
        .modal-content h2 {
            margin-top: 0;
        }
        .modal-content label {
            display: block;
            margin-bottom: 8px;
        }
        .modal-content input[type="text"],
        .modal-content input[type="email"],
        .modal-content input[type="password"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .modal-content button {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            border: none;
            border-radius: 4px;
            color: #fff;
            font-size: 16px;
        }
        .modal-content button:hover {
            background-color: #0056b3;
        }
        .blocked {
            text-decoration: line-through;
        }
    </style>
    <script>
        function toggleSubmenu(id) {
            const submenu = document.getElementById(id);
            submenu.classList.toggle('submenu');
            submenu.previousElementSibling.classList.toggle('open');
        }

        function openModal(id, name, last_name, email, password, address, dni) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_last_name').value = last_name;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_password').value = password;
            document.getElementById('edit_address').value = address;
            document.getElementById('edit_dni').value = dni;
            document.getElementById('editModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }
    </script>
</head>
<body>
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <div class="content">
        <?php include __DIR__ . '/includes/header.php'; ?>
        <div class="search-container">
            <form method="post" action="listar_clientes.php">
                <input type="text" name="search_query" placeholder="Buscar por nombre, apellido, DNI o email" value="<?php echo htmlspecialchars($search_query); ?>">
                <select name="filter_blocked">
                    <option value="">Todos</option>
                    <option value="0" <?php echo $filter_blocked === '0' ? 'selected' : ''; ?>>Desbloqueados</option>
                    <option value="1" <?php echo $filter_blocked === '1' ? 'selected' : ''; ?>>Bloqueados</option>
                </select>
                <button type="submit">Buscar</button>
                <button type="button" class="reset-button" onclick="window.location.href='listar_clientes.php'">Volver</button>
            </form>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Apellido</th>
                    <th>DNI</th>
                    <th>Email</th>
                    <th>Contraseña</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $customer): ?>
                    <tr>
                        <td class="<?php echo $customer['blocked'] ? 'blocked' : ''; ?>"><?php echo htmlspecialchars($customer['name']); ?></td>
                        <td class="<?php echo $customer['blocked'] ? 'blocked' : ''; ?>"><?php echo htmlspecialchars($customer['last_name']); ?></td>
                        <td class="<?php echo $customer['blocked'] ? 'blocked' : ''; ?>"><?php echo htmlspecialchars($customer['dni']); ?></td>
                        <td class="<?php echo $customer['blocked'] ? 'blocked' : ''; ?>"><?php echo htmlspecialchars($customer['email']); ?></td>
                        <td class="<?php echo $customer['blocked'] ? 'blocked' : ''; ?>"><?php echo htmlspecialchars($customer['password']); ?></td>
                        <td class="actions">
                            <button class="edit" onclick="openModal('<?php echo $customer['id']; ?>', '<?php echo htmlspecialchars($customer['name']); ?>', '<?php echo htmlspecialchars($customer['last_name']); ?>', '<?php echo htmlspecialchars($customer['email']); ?>', '<?php echo htmlspecialchars($customer['password']); ?>', '<?php echo htmlspecialchars($customer['address']); ?>', '<?php echo htmlspecialchars($customer['dni']); ?>')">Editar</button>
                            <form method="post" action="listar_clientes.php" style="display:inline;">
                                <input type="hidden" name="delete_id" value="<?php echo $customer['id']; ?>">
                                <button type="submit" class="delete">Eliminar</button>
                            </form>
                            <form method="post" action="listar_clientes.php" style="display:inline;">
                                <input type="hidden" name="toggle_block_id" value="<?php echo $customer['id']; ?>">
                                <input type="hidden" name="current_status" value="<?php echo $customer['blocked']; ?>">
                                <button type="submit" class="toggle-block">
                                    <?php if ($customer['blocked']): ?>
                                        <i class="fas fa-unlock"></i> Desbloquear
                                    <?php else: ?>
                                        <i class="fas fa-lock"></i> Bloquear
                                    <?php endif; ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <h2>Editar Cliente</h2>
            <form method="post" action="listar_clientes.php">
                <input type="hidden" name="edit_id" id="edit_id">
                <label for="edit_name">Nombre:</label>
                <input type="text" name="name" id="edit_name" required>
                <label for="edit_last_name">Apellido:</label>
                <input type="text" name="last_name" id="edit_last_name">
                <label for="edit_email">Email:</label>
                <input type="email" name="email" id="edit_email" required>
                <label for="edit_password">Contraseña:</label>
                <input type="password" name="password" id="edit_password" required>
                <label for="edit_address">Dirección:</label>
                <input type="text" name="address" id="edit_address">
                <label for="edit_dni">DNI:</label>
                <input type="text" name="dni" id="edit_dni">
                <button type="submit">Guardar Cambios</button>
                <button type="button" onclick="closeModal()">Cancelar</button>
            </form>
        </div>
    </div>
</body>
</html>
