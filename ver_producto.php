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

if (!isset($_GET['id'])) {
    header("Location: listar_productos.php");
    exit();
}

$product_id = $_GET['id'];
$color_id = isset($_GET['color_id']) ? $_GET['color_id'] : null;

$query = "SELECT shoes.*, brands.name AS brand_name, shoe_genders.gender AS gender_name FROM shoes 
          JOIN brands ON shoes.brand_id = brands.id 
          JOIN shoe_genders ON shoes.gender_id = shoe_genders.id
          WHERE shoes.id = :id";
$params = ['id' => $product_id];

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header("Location: listar_productos.php");
    exit();
}

$query = "SELECT shoe_sizes.*, shoe_colors.color, shoe_color_images.image_url, shoes.price FROM shoe_sizes 
          JOIN shoe_colors ON shoe_sizes.color_id = shoe_colors.id 
          LEFT JOIN shoe_color_images ON shoe_sizes.shoe_id = shoe_color_images.shoe_id AND shoe_sizes.color_id = shoe_color_images.color_id
          JOIN shoes ON shoe_sizes.shoe_id = shoes.id
          WHERE shoe_sizes.shoe_id = :shoe_id";
$params = ['shoe_id' => $product_id];

if ($color_id) {
    $query .= " AND shoe_sizes.color_id = :color_id";
    $params['color_id'] = $color_id;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$sizes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM offers WHERE shoe_id = :shoe_id");
$stmt->execute(['shoe_id' => $product_id]);
$offer = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT product_reviews.*, customers.name AS customer_name, customers.last_name AS customer_last_name FROM product_reviews 
                       JOIN customers ON product_reviews.customer_id = customers.id 
                       WHERE product_reviews.shoe_id = :shoe_id");
$stmt->execute(['shoe_id' => $product_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular el promedio de estrellas y el número de reseñas
$total_reviews = count($reviews);
$total_stars = array_sum(array_column($reviews, 'rating'));
$average_stars = $total_reviews ? $total_stars / $total_reviews : 0;

$star_counts = array_count_values(array_column($reviews, 'rating'));
for ($i = 1; $i <= 5; $i++) {
    if (!isset($star_counts[$i])) {
        $star_counts[$i] = 0;
    }
}

// Obtener todos los colores disponibles para el producto
$stmt = $pdo->prepare("SELECT DISTINCT shoe_colors.id, shoe_colors.color, shoe_color_images.image_url FROM shoe_sizes 
                       JOIN shoe_colors ON shoe_sizes.color_id = shoe_colors.id 
                       LEFT JOIN shoe_color_images ON shoe_sizes.shoe_id = shoe_color_images.shoe_id AND shoe_sizes.color_id = shoe_color_images.color_id
                       WHERE shoe_sizes.shoe_id = :shoe_id");
$stmt->execute(['shoe_id' => $product_id]);
$all_colors = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_color'])) {
    $new_color_id = $_POST['color_id'];
    $new_sizes = $_POST['sizes'];
    $new_stock = $_POST['stock'];
    $image_url = '';

    if (isset($_FILES['image_url']) && $_FILES['image_url']['error'] == 0) {
        $image_url = 'uploads/' . basename($_FILES['image_url']['name']);
        move_uploaded_file($_FILES['image_url']['tmp_name'], $image_url);
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO shoe_color_images (shoe_id, color_id, image_url) VALUES (:shoe_id, :color_id, :image_url)");
        $stmt->execute([
            'shoe_id' => $product_id,
            'color_id' => $new_color_id,
            'image_url' => $image_url
        ]);

        foreach ($new_sizes as $index => $size) {
            $stmt = $pdo->prepare("INSERT INTO shoe_sizes (shoe_id, size, stock, color_id) VALUES (:shoe_id, :size, :stock, :color_id)");
            $stmt->execute([
                'shoe_id' => $product_id,
                'size' => $size,
                'stock' => $new_stock[$index],
                'color_id' => $new_color_id
            ]);
        }

        $pdo->commit();
        header("Location: ver_producto.php?id=$product_id&color_id=$new_color_id");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error al agregar el color: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_size_id'])) {
    $edit_size_id = $_POST['edit_size_id'];
    $edit_size = $_POST['edit_size'];
    $edit_stock = $_POST['edit_stock'];

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("UPDATE shoe_sizes SET size = :size, stock = :stock WHERE id = :id");
        $stmt->execute([
            'size' => $edit_size,
            'stock' => $edit_stock,
            'id' => $edit_size_id
        ]);

        $pdo->commit();
        header("Location: ver_producto.php?id=$product_id&color_id=$color_id");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error al actualizar la talla: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_size'])) {
    $new_size = $_POST['new_size'];
    $new_stock = $_POST['new_stock'];
    $color_id = $_POST['color_id'];

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO shoe_sizes (shoe_id, size, stock, color_id) VALUES (:shoe_id, :size, :stock, :color_id)");
        $stmt->execute([
            'shoe_id' => $product_id,
            'size' => $new_size,
            'stock' => $new_stock,
            'color_id' => $color_id
        ]);

        $pdo->commit();
        header("Location: ver_producto.php?id=$product_id&color_id=$color_id");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error al agregar la talla: " . $e->getMessage();
    }
}

$stmt = $pdo->query("SELECT * FROM shoe_colors");
$colors = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ver Producto</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            margin: 0;
            height: 100vh;
            background-color: #f4f4f4;
            color: #000; /* Cambiar el color de la letra a negro */
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
        .product-details {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 800px;
            margin-top: 20px;
        }
        .product-details h2 {
            margin-top: 0;
            text-align: center;
        }
        .product-details img {
            display: block;
            margin: 0 auto 20px;
            width: 300px;
            height: 300px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
        }
        .product-details p {
            margin: 5px 0;
        }
        .product-details .info {
            display: flex;
            justify-content: space-around;
            margin-bottom: 10px;
        }
        .product-details .info div {
            flex: 1;
            padding: 10px;
            background-color: #f4f4f4;
            border-radius: 4px;
            margin-right: 10px;
        }
        .product-details .info div:last-child {
            margin-right: 0;
        }
        .sizes-table, .reviews-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .sizes-table th, .sizes-table td, .reviews-table th, .reviews-table td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }
        .sizes-table th, .reviews-table th {
            background-color: #007bff;
            color: #fff;
        }
        .review {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .review img {
            border-radius: 50%;
            margin-right: 10px;
            width: 30px;
            height: 30px;
        }
        .review .review-content {
            flex: 1;
        }
        .review .review-content .stars {
            color: #ffcc00;
        }
        .review-summary {
            margin-top: 20px;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .review-summary .bar {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .review-summary .bar .label {
            width: 80px;
            text-align: right;
            margin-right: 10px;
        }
        .review-summary .bar .bar-container {
            flex: 1;
            background-color: #f4f4f4;
            border-radius: 4px;
            overflow: hidden;
            margin-right: 10px;
        }
        .review-summary .bar .bar-fill {
            height: 20px;
            background-color: #ffcc00;
        }
        .review-summary .count {
            width: 40px;
            text-align: left;
        }
        .review-summary .average {
            font-size: 24px;
            font-weight: bold;
            margin-top: 20px;
            text-align: center;
        }
        .price {
            font-size: 20px;
        }
        .color-icon {
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 5px;
        }
        .color-options {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
        }
        .color-options img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            cursor: pointer;
            margin: 5px;
        }
        .actions .add-color {
            background-color: #28a745;
            color: #fff;
            margin-left: 10px;
        }
        .actions .add-color i {
            margin-right: 5px;
        }
        .actions .edit-size {
            background-color: #ffc107;
            color: #fff;
        }
        .actions .edit-size i {
            margin-right: 5px;
        }
        .actions .add-size {
            background-color: #28a745;
            color: #fff;
            margin-left: 10px;
        }
        .actions .add-size i {
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
        .modal-content select,
        .modal-content input[type="file"] {
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
        function changeColor(colorId) {
            window.location.href = 'ver_producto.php?id=<?php echo $product_id; ?>&color_id=' + colorId;
        }

        function openModal() {
            document.getElementById('addColorModal').style.display = 'flex';
            addRow(); // Agregar una fila inicial
        }

        function closeModal() {
            document.getElementById('addColorModal').style.display = 'none';
        }

        function openEditSizeModal(id, size, stock) {
            document.getElementById('edit_size_id').value = id;
            document.getElementById('edit_size').value = size;
            document.getElementById('edit_stock').value = stock;
            document.getElementById('editSizeModal').style.display = 'flex';
        }

        function closeEditSizeModal() {
            document.getElementById('editSizeModal').style.display = 'none';
        }

        function openAddSizeModal() {
            document.getElementById('addSizeModal').style.display = 'flex';
        }

        function closeAddSizeModal() {
            document.getElementById('addSizeModal').style.display = 'none';
        }

        function addRow() {
            const table = document.getElementById('sizesTable');
            const rowCount = table.rows.length;
            const row = table.insertRow(rowCount);
            const sizeCell = row.insertCell(0);
            const stockCell = row.insertCell(1);

            sizeCell.innerHTML = `<select name="sizes[]" required>
                <option value="">Seleccione una talla</option>
                ${generateSizeOptions()}
            </select>`;
            stockCell.innerHTML = `<input type="text" name="stock[]" required>`;
        }

        function generateSizeOptions() {
            const gender = <?php echo $product['gender_id']; ?>;
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
    </script>
</head>
<body>
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <div class="content">
        <?php include __DIR__ . '/includes/header.php'; ?>
        <div class="product-details">
            <h2><?php echo htmlspecialchars($product['name']); ?></h2>
            <img src="<?php echo htmlspecialchars($sizes[0]['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
            <?php if ($offer): ?>
                <p><i class="fas fa-percent"></i> <strong>Descuento del <?php echo intval($offer['discount_percentage']); ?>%</strong></p>
                <p class="price"><strong>Precio:</strong> <span class="price-now">S/ <?php echo htmlspecialchars(number_format($product['price'] * (1 - $offer['discount_percentage'] / 100), 2)); ?></span></p>
            <?php else: ?>
                <p class="price"><strong>Precio:</strong> S/ <?php echo htmlspecialchars($product['price']); ?></p>
            <?php endif; ?>
            <div class="info">
                <div>
                    <p><i class="fas fa-info-circle"></i> <strong>Descripción:</strong> <?php echo htmlspecialchars($product['description']); ?></p>
                </div>
                <div>
                    <p><i class="fas fa-tag"></i> <strong>Marca:</strong> <?php echo htmlspecialchars($product['brand_name']); ?></p>
                </div>
                <div>
                    <p><i class="fas fa-list"></i> <strong>Categoría:</strong> <?php echo htmlspecialchars($product['category']); ?></p>
                </div>
                <div>
                    <p><i class="fas fa-venus-mars"></i> <strong>Género:</strong> <?php echo htmlspecialchars($product['gender_name']); ?></p>
                </div>
                <div>
                    <p><i class="fas fa-palette"></i> <strong>Color:</strong> <?php echo htmlspecialchars($sizes[0]['color']); ?></p>
                </div>
            </div>
            <h3>Colores Disponibles</h3>
            <div class="color-options">
                <?php foreach ($all_colors as $color): ?>
                    <img src="<?php echo htmlspecialchars($color['image_url']); ?>" alt="<?php echo htmlspecialchars($color['color']); ?>" onclick="changeColor(<?php echo $color['id']; ?>)">
                <?php endforeach; ?>
                <button class="add-color" onclick="openModal()"><i class="fas fa-plus"></i> Agregar Color</button>
            </div>
            <h3>Tallas y Colores Disponibles</h3>
            <table class="sizes-table">
                <thead>
                    <tr>
                        <th>Talla</th>
                        <th>Color</th>
                        <th>Stock</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sizes as $size): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($size['size']); ?></td>
                            <td><?php echo htmlspecialchars($size['color']); ?> <span class="color-icon" style="background-color: <?php echo htmlspecialchars($size['color']); ?>;"></span></td>
                            <td><?php echo htmlspecialchars($size['stock']) . ' unidades'; ?></td>
                            <td>
                                <button class="edit-size" onclick="openEditSizeModal('<?php echo $size['id']; ?>', '<?php echo htmlspecialchars($size['size']); ?>', '<?php echo htmlspecialchars($size['stock']); ?>')"><i class="fas fa-edit"></i> Editar</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button class="add-size" onclick="openAddSizeModal()"><i class="fas fa-plus"></i> Agregar Talla</button>
            <h3>Resumen de Reseñas</h3>
            <div class="review-summary">
                <?php if ($total_reviews > 0): ?>
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <div class="bar">
                            <div class="label"><?php echo $i; ?> estrellas</div>
                            <div class="bar-container">
                                <div class="bar-fill" style="width: <?php echo ($star_counts[$i] / $total_reviews) * 100; ?>%;"></div>
                            </div>
                            <div class="count">(<?php echo $star_counts[$i]; ?>)</div>
                        </div>
                    <?php endfor; ?>
                    <div class="average"><?php echo number_format($average_stars, 1); ?> estrellas (<?php echo $total_reviews; ?> reseñas)</div>
                <?php else: ?>
                    <p>No hay reseñas</p>
                <?php endif; ?>
            </div>
            <h3>Reseñas de Clientes</h3>
            <?php if ($total_reviews > 0): ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review">
                        <img src="images/default_profile.png" alt="Profile Image">
                        <div class="review-content">
                            <p><strong><?php echo htmlspecialchars($review['customer_name'] . ' ' . htmlspecialchars($review['customer_last_name'])); ?></strong> - <?php echo htmlspecialchars($review['review_date']); ?></p>
                            <p class="stars">
                                <?php for ($i = 0; $i < 5; $i++): ?>
                                    <?php if ($i < $review['rating']): ?>
                                        <i class="fas fa-star"></i>
                                    <?php else: ?>
                                        <i class="far fa-star"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </p>
                            <p><?php echo htmlspecialchars($review['review_text']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No hay reseñas</p>
            <?php endif; ?>
        </div>
    </div>

    <div id="addColorModal" class="modal">
        <div class="modal-content">
            <h2>Agregar Color</h2>
            <form method="post" action="ver_producto.php?id=<?php echo $product_id; ?>" enctype="multipart/form-data">
                <label for="color_id">Color:</label>
                <select name="color_id" id="color_id" required>
                    <option value="">Seleccione un color</option>
                    <?php foreach ($colors as $color): ?>
                        <option value="<?php echo $color['id']; ?>"><?php echo htmlspecialchars($color['color']); ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="image_url">Imagen del color:</label>
                <input type="file" name="image_url" id="image_url" required>
                <h3>Tallas y Stock</h3>
                <table id="sizesTable" class="sizes-table">
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
                <button type="button" onclick="addRow()">Agregar Fila</button>
                <button type="submit" name="add_color">Agregar Color</button>
                <button type="button" onclick="closeModal()">Cancelar</button>
            </form>
        </div>
    </div>

    <div id="editSizeModal" class="modal">
        <div class="modal-content">
            <h2>Editar Talla</h2>
            <form method="post" action="ver_producto.php?id=<?php echo $product_id; ?>&color_id=<?php echo $color_id; ?>">
                <input type="hidden" name="edit_size_id" id="edit_size_id">
                <label for="edit_size">Talla:</label>
                <input type="text" name="edit_size" id="edit_size" required>
                <label for="edit_stock">Stock:</label>
                <input type="text" name="edit_stock" id="edit_stock" required>
                <button type="submit">Guardar Cambios</button>
                <button type="button" onclick="closeEditSizeModal()">Cancelar</button>
            </form>
        </div>
    </div>

    <div id="addSizeModal" class="modal">
        <div class="modal-content">
            <h2>Agregar Talla</h2>
            <form method="post" action="ver_producto.php?id=<?php echo $product_id; ?>&color_id=<?php echo $color_id; ?>">
                <label for="new_size">Talla:</label>
                <input type="text" name="new_size" id="new_size" required>
                <label for="new_stock">Stock:</label>
                <input type="text" name="new_stock" id="new_stock" required>
                <input type="hidden" name="color_id" value="<?php echo $color_id; ?>">
                <button type="submit" name="add_size">Agregar Talla</button>
                <button type="button" onclick="closeAddSizeModal()">Cancelar</button>
            </form>
        </div>
    </div>
</body>
</html>
