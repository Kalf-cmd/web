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

$search_code = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search_code'])) {
    $search_code = $_POST['search_code'];
    $stmt = $pdo->prepare("SELECT shoes.*, brands.name AS brand_name, shoe_genders.gender AS gender_name, shoe_color_images.image_url, shoe_colors.color AS color_name, offers.discount_percentage, shoe_sizes.color_id, MAX(shoe_sizes.stock) AS max_stock FROM shoes 
                           JOIN brands ON shoes.brand_id = brands.id 
                           JOIN shoe_genders ON shoes.gender_id = shoe_genders.id
                           LEFT JOIN shoe_sizes ON shoes.id = shoe_sizes.shoe_id
                           LEFT JOIN shoe_colors ON shoe_sizes.color_id = shoe_colors.id
                           LEFT JOIN shoe_color_images ON shoes.id = shoe_color_images.shoe_id AND shoe_colors.id = shoe_color_images.color_id
                           LEFT JOIN offers ON shoes.id = offers.shoe_id
                           WHERE shoes.id = :search_code
                           GROUP BY shoes.id, shoe_colors.id");
    $stmt->execute(['search_code' => $search_code]);
} else {
    $stmt = $pdo->prepare("SELECT shoes.*, brands.name AS brand_name, shoe_genders.gender AS gender_name, shoe_color_images.image_url, shoe_colors.color AS color_name, offers.discount_percentage, shoe_sizes.color_id, MAX(shoe_sizes.stock) AS max_stock FROM shoes 
                           JOIN brands ON shoes.brand_id = brands.id 
                           JOIN shoe_genders ON shoes.gender_id = shoe_genders.id
                           LEFT JOIN shoe_sizes ON shoes.id = shoe_sizes.shoe_id
                           LEFT JOIN shoe_colors ON shoe_sizes.color_id = shoe_colors.id
                           LEFT JOIN shoe_color_images ON shoes.id = shoe_color_images.shoe_id AND shoe_colors.id = shoe_color_images.color_id
                           LEFT JOIN offers ON shoes.id = offers.shoe_id
                           GROUP BY shoes.id, shoe_colors.id");
    $stmt->execute();
}

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id']) && isset($_POST['delete_color_id'])) {
    $delete_id = $_POST['delete_id'];
    $delete_color_id = $_POST['delete_color_id'];

    $pdo->beginTransaction();
    try {
        // Eliminar registros asociados en shoe_sizes para el color específico
        $stmt = $pdo->prepare("DELETE FROM shoe_sizes WHERE shoe_id = :shoe_id AND color_id = :color_id");
        $stmt->execute(['shoe_id' => $delete_id, 'color_id' => $delete_color_id]);

        // Eliminar registros asociados en shoe_color_images para el color específico
        $stmt = $pdo->prepare("DELETE FROM shoe_color_images WHERE shoe_id = :shoe_id AND color_id = :color_id");
        $stmt->execute(['shoe_id' => $delete_id, 'color_id' => $delete_color_id]);

        $pdo->commit();
        header("Location: listar_productos.php");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error al eliminar el producto: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_id'])) {
    $edit_id = $_POST['edit_id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $brand_id = $_POST['brand_id'];
    $category = $_POST['category'];
    $gender_id = $_POST['gender_id'];
    $color_id = $_POST['color_id'];

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("UPDATE shoes SET name = :name, description = :description, price = :price, brand_id = :brand_id, category = :category, gender_id = :gender_id WHERE id = :id");
        $stmt->execute([
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'brand_id' => $brand_id,
            'category' => $category,
            'gender_id' => $gender_id,
            'id' => $edit_id
        ]);

        $stmt = $pdo->prepare("UPDATE shoe_sizes SET color_id = :color_id WHERE shoe_id = :shoe_id");
        $stmt->execute([
            'color_id' => $color_id,
            'shoe_id' => $edit_id
        ]);

        $pdo->commit();
        header("Location: listar_productos.php");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error al actualizar el producto: " . $e->getMessage();
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
    <title>Listar Productos</title>
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
        .submenu.open {
            display: block;
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
            width: calc(100% - 100px);
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .search-container button {
            padding: 8px;
            background-color: #007bff;
            border: none;
            border-radius: 4px;
            color: #fff;
            cursor: pointer;
        }
        .search-container button:hover {
            background-color: #0056b3;
        }
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .product-card {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            cursor: pointer;
        }
        .product-card img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
        }
        .product-card h3 {
            margin: 10px 0;
        }
        .product-card p {
            margin: 5px 0;
        }
        .product-card .price {
            font-size: 18px;
            font-weight: bold;
        }
        .product-card .price-discount {
            color: red;
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
        .actions .view {
            background-color: #28a745;
            color: #fff;
        }
        .actions .view i {
            margin-right: 5px;
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
        .modal-content select {
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
    </style>
    <script>
        function toggleSubmenu(id) {
            const submenu = document.getElementById(id);
            submenu.classList.toggle('open');
            submenu.previousElementSibling.classList.toggle('open');
        }

        function openModal(id, name, description, price, brand_id, category, gender_id, color_id) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_description').value = description;
            document.getElementById('edit_price').value = price;
            document.getElementById('edit_brand_id').value = brand_id;
            document.getElementById('edit_category').value = category;
            document.getElementById('edit_gender_id').value = gender_id;
            document.getElementById('edit_color_id').value = color_id;
            document.getElementById('editModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }
    </script>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <div class="content">
        <?php include 'includes/header.php'; ?>
        <div class="search-container">
            <form method="post" action="listar_productos.php">
                <input type="text" name="search_code" placeholder="Buscar por código de producto" value="<?php echo htmlspecialchars($search_code); ?>">
                <button type="submit">Buscar</button>
            </form>
        </div>
        <div class="product-grid">
            <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                    <p>ID: <?php echo htmlspecialchars($product['id']); ?></p>
                    <p><?php echo htmlspecialchars($product['description']); ?></p>
                    <p><strong>Marca:</strong> <?php echo htmlspecialchars($product['brand_name']); ?></p>
                    <p><strong>Categoría:</strong> <?php echo htmlspecialchars($product['category']); ?></p>
                    <p><strong>Género:</strong> <?php echo htmlspecialchars($product['gender_name']); ?></p>
                    <p><strong>Color:</strong> <?php echo htmlspecialchars($product['color_name']); ?></p>
                    <?php if ($product['discount_percentage']): ?>
                        <p class="price"><span class="price-discount">S/ <?php echo number_format($product['price'] * (1 - $product['discount_percentage'] / 100), 2); ?></span> <del>S/ <?php echo number_format($product['price'], 2); ?></del></p>
                    <?php else: ?>
                        <p class="price">S/ <?php echo number_format($product['price'], 2); ?></p>
                    <?php endif; ?>
                    <div class="actions">
                        <button class="edit" onclick="openModal('<?php echo $product['id']; ?>', '<?php echo htmlspecialchars($product['name']); ?>', '<?php echo htmlspecialchars($product['description']); ?>', '<?php echo htmlspecialchars($product['price']); ?>', '<?php echo htmlspecialchars($product['brand_id']); ?>', '<?php echo htmlspecialchars($product['category']); ?>', '<?php echo htmlspecialchars($product['gender_id']); ?>', '<?php echo htmlspecialchars($product['color_id']); ?>')">Editar</button>
                        <form method="post" action="listar_productos.php" style="display:inline;">
                            <input type="hidden" name="delete_id" value="<?php echo $product['id']; ?>">
                            <input type="hidden" name="delete_color_id" value="<?php echo $product['color_id']; ?>">
                            <button type="submit" class="delete">Eliminar</button>
                        </form>
                        <button class="view" onclick="window.location.href='ver_producto.php?id=<?php echo $product['id']; ?>&color_id=<?php echo $product['color_id']; ?>'"><i class="fas fa-eye"></i></button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <h2>Editar Producto</h2>
            <form method="post" action="listar_productos.php">
                <input type="hidden" name="edit_id" id="edit_id">
                <label for="edit_name">Nombre:</label>
                <input type="text" name="name" id="edit_name" required>
                <label for="edit_description">Descripción:</label>
                <input type="text" name="description" id="edit_description">
                <label for="edit_price">Precio:</label>
                <input type="text" name="price" id="edit_price" required>
                <label for="edit_brand_id">Marca:</label>
                <select name="brand_id" id="edit_brand_id" required>
                    <option value="">Seleccione una marca</option>
                    <?php foreach ($brands as $brand): ?>
                        <option value="<?php echo $brand['id']; ?>"><?php echo htmlspecialchars($brand['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="edit_category">Categoría:</label>
                <input type="text" name="category" id="edit_category" required>
                <label for="edit_gender_id">Género:</label>
                <select name="gender_id" id="edit_gender_id" required>
                    <option value="">Seleccione un género</option>
                    <?php foreach ($genders as $gender): ?>
                        <option value="<?php echo $gender['id']; ?>"><?php echo htmlspecialchars($gender['gender']); ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="edit_color_id">Color:</label>
                <select name="color_id" id="edit_color_id" required>
                    <option value="">Seleccione un color</option>
                    <?php foreach ($colors as $color): ?>
                        <option value="<?php echo $color['id']; ?>"><?php echo htmlspecialchars($color['color']); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Guardar Cambios</button>
                <button type="button" onclick="closeModal()">Cancelar</button>
            </form>
        </div>
    </div>
</body>
</html>
