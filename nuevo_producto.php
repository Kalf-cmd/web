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
    $description = $_POST['description'];
    $brand_id = $_POST['brand_id'];
    $category = $_POST['category'];
    $gender_id = $_POST['gender_id'];
    $price = $_POST['price'];
    $sizes = $_POST['sizes'];
    $selected_colors = $_POST['colors'];
    $color_images = $_FILES['color_images'];

    if (empty($name) || empty($brand_id) || empty($category) || empty($gender_id) || empty($price) || empty($sizes) || empty($selected_colors)) {
        $error = "Por favor, complete todos los campos obligatorios.";
    } else {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO shoes (name, description, brand_id, category, gender_id, price) VALUES (:name, :description, :brand_id, :category, :gender_id, :price)");
            $stmt->execute([
                'name' => $name,
                'description' => $description,
                'brand_id' => $brand_id,
                'category' => $category,
                'gender_id' => $gender_id,
                'price' => $price
            ]);
            $shoe_id = $pdo->lastInsertId();

            if (!is_dir('uploads')) {
                mkdir('uploads', 0777, true);
            }

            foreach ($selected_colors as $color_id) {
                $color_image_url = '';
                if (isset($color_images['name'][$color_id]) && $color_images['name'][$color_id] != '') {
                    $color_image_name = $color_images['name'][$color_id];
                    $color_image_tmp_name = $color_images['tmp_name'][$color_id];
                    $color_image_url = 'uploads/' . $color_image_name;
                    move_uploaded_file($color_image_tmp_name, $color_image_url);

                    $stmt = $pdo->prepare("INSERT INTO shoe_color_images (shoe_id, color_id, image_url) VALUES (:shoe_id, :color_id, :image_url)");
                    $stmt->execute([
                        'shoe_id' => $shoe_id,
                        'color_id' => $color_id,
                        'image_url' => $color_image_url
                    ]);
                }

                foreach ($sizes[$color_id] as $index => $size) {
                    $stmt = $pdo->prepare("INSERT INTO shoe_sizes (shoe_id, size, stock, color_id) VALUES (:shoe_id, :size, :stock, :color_id)");
                    $stmt->execute([
                        'shoe_id' => $shoe_id,
                        'size' => $size['size'],
                        'stock' => $size['stock'],
                        'color_id' => $color_id
                    ]);
                }
            }

            $pdo->commit();
            $success = "Producto agregado exitosamente.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error al agregar el producto: " . $e->getMessage();
        }
    }
}

$stmt = $pdo->query("SELECT * FROM brands");
$brands = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT * FROM shoe_genders");
$genders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT * FROM shoe_colors");
$colors = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo Producto</title>
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
        .form-container select,
        .form-container input[type="file"] {
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
        .sizes-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .sizes-table th, .sizes-table td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }
        .sizes-table th {
            background-color: #007bff;
            color: #fff;
        }
        .add-row-button {
            margin-top: 10px;
            background-color: #28a745;
            color: #fff;
            padding: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .add-row-button:hover {
            background-color: #218838;
        }
    </style>
    <script>
        function toggleSubmenu(id) {
            const submenu = document.getElementById(id);
            submenu.classList.toggle('submenu');
            submenu.previousElementSibling.classList.toggle('open');
        }

        function addRow(colorId) {
            const table = document.getElementById('sizesTable_' + colorId);
            const rowCount = table.rows.length;
            const row = table.insertRow(rowCount);
            const sizeCell = row.insertCell(0);
            const stockCell = row.insertCell(1);

            sizeCell.innerHTML = `<select name="sizes[${colorId}][${rowCount}][size]" required>
                <option value="">Seleccione una talla</option>
                ${generateSizeOptions()}
            </select>`;
            stockCell.innerHTML = `<input type="text" name="sizes[${colorId}][${rowCount}][stock]" required>`;
        }

        function generateSizeOptions() {
            const gender = document.getElementById('gender_id').value;
            let options = '';
            if (gender == 1) { // Hombre
                const sizes = [36, 37, 38, 39, 40, 41, 42, 43, 44];
                sizes.forEach(size => {
                    options += `<option value="${size}">${size}</option>`;
                });
            } else if (gender == 2) { // Mujer
                const sizes = [33, 34, 35, 36, 37, 38, 39, 40];
                sizes.forEach(size => {
                    options += `<option value="${size}">${size}</option>`;
                });
            } else if (gender == 3) { // Unisex
                const sizes = [36, 37, 38, 39, 40, 41, 42, 43, 44];
                sizes.forEach(size => {
                    options += `<option value="${size}">${size}</option>`;
                });
            } else if (gender == 6) { // Niños
                const sizes = [24, 25, 26, 27, 28, 29, 30, 31, 32, 33];
                sizes.forEach(size => {
                    options += `<option value="${size}">${size}</option>`;
                });
            }
            return options;
        }

        function toggleImageUpload(checkbox, colorId) {
            const imageInput = document.getElementById('color_image_' + colorId);
            const sizesTable = document.getElementById('sizesTable_' + colorId);
            const addRowButton = document.getElementById('addRowButton_' + colorId);
            if (checkbox.checked) {
                imageInput.style.display = 'block';
                sizesTable.style.display = 'table';
                addRowButton.style.display = 'block';
                if (sizesTable.rows.length === 1) {
                    addRow(colorId); // Agregar una fila inicial si no hay filas
                }
            } else {
                imageInput.style.display = 'none';
                sizesTable.style.display = 'none';
                addRowButton.style.display = 'none';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('gender_id').addEventListener('change', function() {
                const tables = document.querySelectorAll('.sizes-table');
                tables.forEach(table => {
                    while (table.rows.length > 1) {
                        table.deleteRow(1);
                    }
                });
            });
        });
    </script>
</head>
<body>
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <div class="content">
        <?php include __DIR__ . '/includes/header.php'; ?>
        <div class="form-container">
            <h2>Nuevo Producto</h2>
            <?php if (isset($error)): ?>
                <p class="error"><?php echo $error; ?></p>
            <?php endif; ?>
            <?php if (isset($success)): ?>
                <p class="success"><?php echo $success; ?></p>
            <?php endif; ?>
            <form method="post" action="nuevo_producto.php" enctype="multipart/form-data">
                <label for="name">Nombre:</label>
                <input type="text" name="name" id="name" required>
                <label for="description">Descripción:</label>
                <input type="text" name="description" id="description">
                <label for="brand_id">Marca:</label>
                <select name="brand_id" id="brand_id" required>
                    <option value="">Seleccione una marca</option>
                    <?php foreach ($brands as $brand): ?>
                        <option value="<?php echo $brand['id']; ?>"><?php echo htmlspecialchars($brand['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="category">Categoría:</label>
                <input type="text" name="category" id="category" required>
                <label for="gender_id">Género:</label>
                <select name="gender_id" id="gender_id" required>
                    <option value="">Seleccione un género</option>
                    <?php foreach ($genders as $gender): ?>
                        <option value="<?php echo $gender['id']; ?>"><?php echo htmlspecialchars($gender['gender']); ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="price">Precio:</label>
                <input type="text" name="price" id="price" required>
                <label>Seleccione los colores del producto:</label>
                <?php foreach ($colors as $color): ?>
                    <label>
                        <input type="checkbox" name="colors[]" value="<?php echo $color['id']; ?>" onchange="toggleImageUpload(this, <?php echo $color['id']; ?>)"> 
                        <?php echo htmlspecialchars($color['color']); ?>
                    </label><br>
                    <div id="color_image_<?php echo $color['id']; ?>" style="display: none;">
                        <label>Imagen del color:</label>
                        <input type="file" name="color_images[<?php echo $color['id']; ?>]"><br>
                    </div>
                    <table id="sizesTable_<?php echo $color['id']; ?>" class="sizes-table" style="display: none;">
                        <thead>
                            <tr>
                                <th>Talla</th>
                                <th>Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Las filas se agregarán automáticamente aquí -->
                        </tbody>
                    </table>
                    <button type="button" id="addRowButton_<?php echo $color['id']; ?>" onclick="addRow(<?php echo $color['id']; ?>)" class="add-row-button" style="display: none;">Agregar Fila</button>
                <?php endforeach; ?>
                <button type="submit">Agregar Producto</button>
            </form>
        </div>
    </div>
</body>
</html>
