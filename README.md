# Proyecto de Tienda de Zapatos

## Requisitos
- Servidor web (Apache, Nginx, etc.)
- MariaDB
- PHP

## Configuración
1. Clona el repositorio o descarga los archivos del proyecto.
2. Coloca los archivos en el directorio raíz de tu servidor web (por ejemplo, `htdocs` para XAMPP).

## Base de Datos
1. Abre MariaDB y crea la base de datos `tienda_zapatos`.
2. Importa el archivo SQL proporcionado para crear las tablas y datos necesarios:
    ```sql
    -- Base de datos: `tienda_zapatos`
    -- Contenido del archivo SQL proporcionado
    ```

## Configuración de la Conexión a la Base de Datos
1. Abre el archivo `config.php` y asegúrate de que las credenciales de la base de datos sean correctas:
    ```php
    // ...existing code...
    $host = 'localhost';
    $port = 3306;
    $dbname = 'tienda_zapatos';
    $username = 'root';
    $password = '';
    // ...existing code...
    ```

## Ejecución del Proyecto
1. Inicia tu servidor web y MariaDB.
2. Abre tu navegador web y accede a `http://localhost/login.php`.
3. Ingresa las credenciales de administrador para acceder al panel de administración.

## Credenciales de Administrador
- Email: `admin@example.com`
- Contraseña: `adminpass`
