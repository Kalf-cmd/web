-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 27-01-2025 a las 04:38:13
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `tienda_zapatos`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `administrators`
--

CREATE TABLE `administrators` (
  `id` bigint(20) NOT NULL,
  `name` text NOT NULL,
  `last_name` text DEFAULT NULL,
  `email` text NOT NULL,
  `password` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `blocked` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `administrators`
--

INSERT INTO `administrators` (`id`, `name`, `last_name`, `email`, `password`, `created_at`, `blocked`) VALUES
(1, 'Admin', 'General', 'admin@example.com', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHeFupn1e1Z1Z1Z1Z1Z1Z1Z1Z1Z1Z1Z1', '2025-01-26 22:28:57', 0),
(2, 'Sofia', 'Rodriguez', 'sofia.admin@example.com', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHeFupn1e1Z1Z1Z1Z1Z1Z1Z1Z1Z1Z1Z1', '2025-01-26 22:28:57', 0),
(3, 'Admin1', 'Gómez', 'admin1@gmail.com', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHeFupn1e1Z1Z1Z1Z1Z1Z1Z1Z1Z1Z1Z1', '2025-01-26 22:31:19', 0),
(4, 'Admin2', 'Fernández', 'admin2@gmail.com', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHeFupn1e1Z1Z1Z1Z1Z1Z1Z1Z1Z1Z1Z1', '2025-01-26 22:31:19', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `brands`
--

CREATE TABLE `brands` (
  `id` bigint(20) NOT NULL,
  `name` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `brands`
--

INSERT INTO `brands` (`id`, `name`) VALUES
(1, 'Nike'),
(2, 'Adidas'),
(3, 'Puma');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cart`
--

CREATE TABLE `cart` (
  `id` bigint(20) NOT NULL,
  `customer_id` bigint(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cart`
--

INSERT INTO `cart` (`id`, `customer_id`, `created_at`) VALUES
(1, 1, '2025-01-26 22:32:48'),
(2, 2, '2025-01-26 22:32:48'),
(3, 3, '2025-01-26 22:32:48');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cart_items`
--

CREATE TABLE `cart_items` (
  `id` bigint(20) NOT NULL,
  `cart_id` bigint(20) DEFAULT NULL,
  `shoe_id` bigint(20) DEFAULT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cart_items`
--

INSERT INTO `cart_items` (`id`, `cart_id`, `shoe_id`, `quantity`) VALUES
(10, 1, 61590281, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `customers`
--

CREATE TABLE `customers` (
  `id` bigint(20) NOT NULL,
  `name` text NOT NULL,
  `last_name` text DEFAULT NULL,
  `email` text NOT NULL,
  `password` text NOT NULL,
  `address` text DEFAULT NULL,
  `dni` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `blocked` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `customers`
--

INSERT INTO `customers` (`id`, `name`, `last_name`, `email`, `password`, `address`, `dni`, `created_at`, `blocked`) VALUES
(1, 'Juan', 'Perez', 'juan.perez@example.com', 'password123', 'Av. Principal 123', '12345678', '2025-01-26 22:28:57', 0),
(2, 'Maria', 'Lopez', 'maria.lopez@example.com', 'password123', 'Calle Secundaria 456', '87654321', '2025-01-26 22:28:57', 0),
(3, 'Carlos', 'Garcia', 'carlos.garcia@example.com', 'password123', 'Av. Siempreviva 742', '12348765', '2025-01-26 22:28:57', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `offers`
--

CREATE TABLE `offers` (
  `id` bigint(20) NOT NULL,
  `shoe_id` bigint(20) DEFAULT NULL,
  `discount_percentage` decimal(5,2) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `offers`
--

INSERT INTO `offers` (`id`, `shoe_id`, `discount_percentage`, `start_date`, `end_date`) VALUES
(3, 61590281, 12.00, '2025-01-27 04:36:20', '2025-01-27 04:36:20');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` bigint(20) NOT NULL,
  `customer_id` bigint(20) DEFAULT NULL,
  `shoe_id` bigint(20) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review_text` text DEFAULT NULL,
  `review_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `product_reviews`
--

INSERT INTO `product_reviews` (`id`, `customer_id`, `shoe_id`, `rating`, `review_text`, `review_date`) VALUES
(4, 1, 61590281, 2, 'Estas Zapatillas son geniales', '2025-01-26 22:37:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sales`
--

CREATE TABLE `sales` (
  `id` bigint(20) NOT NULL,
  `customer_id` bigint(20) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `sale_date` datetime DEFAULT current_timestamp(),
  `admin_id` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sales`
--

INSERT INTO `sales` (`id`, `customer_id`, `total_amount`, `sale_date`, `admin_id`) VALUES
(1, 1, 240.99, '2025-01-26 22:33:00', 1),
(2, 2, 150.00, '2025-01-26 22:33:00', 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `shipping`
--

CREATE TABLE `shipping` (
  `id` bigint(20) NOT NULL,
  `sale_id` bigint(20) DEFAULT NULL,
  `address` text NOT NULL,
  `shipped_date` datetime DEFAULT NULL,
  `delivery_date` datetime DEFAULT NULL,
  `status_id` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `shipping`
--

INSERT INTO `shipping` (`id`, `sale_id`, `address`, `shipped_date`, `delivery_date`, `status_id`) VALUES
(1, 1, 'Av. Siempre Viva 123', '2025-01-10 10:00:00', '2025-01-12 14:00:00', 3),
(2, 2, 'Calle Falsa 456', '2025-01-15 12:00:00', '2025-01-17 16:00:00', 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `shipping_statuses`
--

CREATE TABLE `shipping_statuses` (
  `id` bigint(20) NOT NULL,
  `status` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `shipping_statuses`
--

INSERT INTO `shipping_statuses` (`id`, `status`) VALUES
(1, 'en proceso'),
(2, 'enviado'),
(3, 'entregado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `shoes`
--

CREATE TABLE `shoes` (
  `id` bigint(20) NOT NULL,
  `name` text NOT NULL,
  `description` text DEFAULT NULL,
  `brand_id` bigint(20) DEFAULT NULL,
  `category` text DEFAULT NULL,
  `gender_id` bigint(20) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `added_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `shoes`
--

INSERT INTO `shoes` (`id`, `name`, `description`, `brand_id`, `category`, `gender_id`, `price`, `added_at`) VALUES
(38149263, 'Air Max', 'Zapatilla deportiva de alta calidad', 1, 'Deportiva', 1, 120.00, '2025-01-26 22:28:57'),
(61590281, 'Running Speed', 'Zapatilla ligera para correr', 3, 'Deportiva', 1, 150.00, '2025-01-26 22:28:57'),
(73207613, 'Superstar', 'Zapatilla clásica de estilo urbano', 2, 'Casual', 3, 100.00, '2025-01-26 22:28:57');

--
-- Disparadores `shoes`
--
DELIMITER $$
CREATE TRIGGER `generate_shoe_id` BEFORE INSERT ON `shoes` FOR EACH ROW BEGIN
    DECLARE random_id BIGINT;

    -- Generar un número aleatorio de 8 dígitos (10000000 a 99999999)
    SET random_id = FLOOR(10000000 + (RAND() * 89999999));

    -- Verificar que el ID generado no exista en la tabla
    WHILE EXISTS (SELECT 1 FROM shoes WHERE id = random_id) DO
        SET random_id = FLOOR(10000000 + (RAND() * 89999999));
    END WHILE;

    -- Asignar el ID generado al campo id
    SET NEW.id = random_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `shoe_colors`
--

CREATE TABLE `shoe_colors` (
  `id` bigint(20) NOT NULL,
  `color` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `shoe_colors`
--

INSERT INTO `shoe_colors` (`id`, `color`) VALUES
(1, 'Rojo'),
(2, 'Azul'),
(3, 'Negro'),
(4, 'Blanco');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `shoe_genders`
--

CREATE TABLE `shoe_genders` (
  `id` bigint(20) NOT NULL,
  `gender` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `shoe_genders`
--

INSERT INTO `shoe_genders` (`id`, `gender`) VALUES
(1, 'Masculino'),
(2, 'Femenino'),
(3, 'Unisex');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `shoe_sizes`
--

CREATE TABLE `shoe_sizes` (
  `id` bigint(20) NOT NULL,
  `shoe_id` bigint(20) DEFAULT NULL,
  `size` int(11) NOT NULL,
  `stock` int(11) NOT NULL,
  `color_id` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `shoe_sizes`
--

INSERT INTO `shoe_sizes` (`id`, `shoe_id`, `size`, `stock`, `price`, `color_id`, `image_url`) VALUES
(9, 38149263, 12, 13, 32.00, 4, 'fff');

-- Table: shoe_color_images
CREATE TABLE shoe_color_images (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    shoe_id BIGINT,
    color_id BIGINT,
    image_url TEXT,
    FOREIGN KEY (shoe_id) REFERENCES shoes(id),
    FOREIGN KEY (color_id) REFERENCES shoe_colors(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `administrators`
--
ALTER TABLE `administrators`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`) USING HASH;

--
-- Indices de la tabla `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`) USING HASH;

--
-- Indices de la tabla `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indices de la tabla `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cart_id` (`cart_id`),
  ADD KEY `shoe_id` (`shoe_id`);

--
-- Indices de la tabla `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`) USING HASH,
  ADD UNIQUE KEY `dni` (`dni`) USING HASH;

--
-- Indices de la tabla `offers`
--
ALTER TABLE `offers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shoe_id` (`shoe_id`);

--
-- Indices de la tabla `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `shoe_id` (`shoe_id`);

--
-- Indices de la tabla `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indices de la tabla `shipping`
--
ALTER TABLE `shipping`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `status_id` (`status_id`);

--
-- Indices de la tabla `shipping_statuses`
--
ALTER TABLE `shipping_statuses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `status` (`status`) USING HASH;

--
-- Indices de la tabla `shoes`
--
ALTER TABLE `shoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `brand_id` (`brand_id`),
  ADD KEY `gender_id` (`gender_id`);

--
-- Indices de la tabla `shoe_colors`
--
ALTER TABLE `shoe_colors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `color` (`color`) USING HASH;

--
-- Indices de la tabla `shoe_genders`
--
ALTER TABLE `shoe_genders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `gender` (`gender`) USING HASH;

--
-- Indices de la tabla `shoe_sizes`
--
ALTER TABLE `shoe_sizes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shoe_id` (`shoe_id`),
  ADD KEY `color_id` (`color_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `administrators`
--
ALTER TABLE `administrators`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `brands`
--
ALTER TABLE `brands`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `cart`
--
ALTER TABLE `cart`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `customers`
--
ALTER TABLE `customers`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `offers`
--
ALTER TABLE `offers`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `sales`
--
ALTER TABLE `sales`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `shipping`
--
ALTER TABLE `shipping`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `shipping_statuses`
--
ALTER TABLE `shipping_statuses`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `shoes`
--
ALTER TABLE `shoes`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88740353;

--
-- AUTO_INCREMENT de la tabla `shoe_colors`
--
ALTER TABLE `shoe_colors`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `shoe_genders`
--
ALTER TABLE `shoe_genders`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `shoe_sizes`
--
ALTER TABLE `shoe_sizes`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`);

--
-- Filtros para la tabla `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `cart` (`id`),
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`shoe_id`) REFERENCES `shoes` (`id`);

--
-- Filtros para la tabla `offers`
--
ALTER TABLE `offers`
  ADD CONSTRAINT `offers_ibfk_1` FOREIGN KEY (`shoe_id`) REFERENCES `shoes` (`id`);

--
-- Filtros para la tabla `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `product_reviews_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `product_reviews_ibfk_2` FOREIGN KEY (`shoe_id`) REFERENCES `shoes` (`id`);

--
-- Filtros para la tabla `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `administrators` (`id`);

--
-- Filtros para la tabla `shipping`
--
ALTER TABLE `shipping`
  ADD CONSTRAINT `shipping_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`),
  ADD CONSTRAINT `shipping_ibfk_2` FOREIGN KEY (`status_id`) REFERENCES `shipping_statuses` (`id`);

--
-- Filtros para la tabla `shoes`
--
ALTER TABLE `shoes`
  ADD CONSTRAINT `shoes_ibfk_1` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`),
  ADD CONSTRAINT `shoes_ibfk_2` FOREIGN KEY (`gender_id`) REFERENCES `shoe_genders` (`id`);

--
-- Filtros para la tabla `shoe_sizes`
--
ALTER TABLE `shoe_sizes`
  ADD CONSTRAINT `shoe_sizes_ibfk_1` FOREIGN KEY (`shoe_id`) REFERENCES `shoes` (`id`),
  ADD CONSTRAINT `shoe_sizes_ibfk_2` FOREIGN KEY (`color_id`) REFERENCES `shoe_colors` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
