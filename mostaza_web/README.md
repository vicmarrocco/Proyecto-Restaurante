# Mostaza Web — Sitio funcional (login + catálogo + carrito)

Sitio convertido a PHP a partir de `demo_restaurante`, con:
- Login por **email + código (token) enviado por Gmail**, sin contraseña.
- Catálogo de productos real, conectado a MySQL.
- Carrito de compras funcional (agregar, quitar, confirmar pedido).
- Armador de hamburguesa personalizada conectado al carrito.

## 1. Requisitos

- XAMPP, WAMP o Laragon (PHP 8+ y MySQL/MariaDB).
- Una cuenta de Gmail para enviar los códigos de acceso.

## 2. Instalar el proyecto

1. Copiá toda la carpeta `mostaza_web` dentro de tu carpeta de proyectos
   (en XAMPP: `C:\xampp\htdocs\mostaza_web`).
2. Abrí phpMyAdmin y creá (o reusá) la base `rest`.
3. Importá **en este orden**:
   1. `rest__2_.sql` (tu base original, si todavía no la importaste).
   2. `sql/schema_web.sql` (agrega las tablas del sitio de clientes y
      carga el catálogo de productos).
4. Revisá `conexion.php`: por defecto usa `root` sin contraseña
   (típico de XAMPP). Si tu MySQL usa otro usuario/clave, cambialo ahí.

## 3. Configurar el envío de correos (Gmail)

El login no usa contraseña: el cliente pone su email, y le llega un
código de 6 dígitos por correo que debe ingresar para entrar.

Para que el envío funcione necesitás una **contraseña de aplicación**
de Gmail (tu contraseña normal de Gmail NO va a funcionar):

1. Andá a https://myaccount.google.com/security
2. Activá la **Verificación en dos pasos** (obligatorio para el paso 3).
3. Andá a https://myaccount.google.com/apppasswords
4. Creá una nueva contraseña de aplicación (ej: "Mostaza Web") y copiá
   el código de 16 letras que te da Google.
5. Abrí `mail/config_mail.php` y completá:
   ```php
   define('MAIL_USER', 'tu_correo@gmail.com');
   define('MAIL_PASS', 'xxxxxxxxxxxxxxxx'); // las 16 letras, sin espacios
   ```

Con eso, tanto los códigos de acceso como los mensajes del formulario
de contacto van a salir desde esa cuenta de Gmail.

## 4. Probar

1. Iniciá Apache y MySQL desde el panel de XAMPP.
2. Abrí `http://localhost/mostaza_web/index.php`
3. Creá una cuenta en **Registro**, después iniciá sesión con ese email:
   te va a llegar un código de 6 dígitos a esa casilla.
4. Entrá a **Productos**, agregá cosas al carrito y confirmá el pedido.

## 5. Estructura del proyecto

```
mostaza_web/
├── conexion.php              → conexión a MySQL
├── index.php                 → home
├── productos.php             → grilla de categorías
├── categoria.php             → catálogo real por categoría (?cat=slug)
├── personalizado.php         → armador de hamburguesa
├── agregar_personalizado.php → agrega la hamburguesa armada al carrito
├── carrito.php                → ver / confirmar el carrito
├── agregar_carrito.php       → agrega un producto del catálogo
├── eliminar_carrito.php      → quita un producto del carrito
├── finalizar_pedido.php      → guarda el pedido en la base
├── pedido_confirmado.php     → resumen del pedido confirmado
├── login.php                 → login por email + token (2 pasos)
├── registro.php              → alta de cuenta nueva
├── logout.php                → cerrar sesión
├── contacto.php               → formulario de contacto (envía mail)
├── sobrenosotros.php
├── includes/
│   ├── header.php            → nav con sesión + contador de carrito
│   └── footer.php
├── mail/
│   ├── config_mail.php       → credenciales de Gmail (COMPLETAR)
│   ├── enviar_correo.php     → funciones de envío (token + contacto)
│   └── src/                  → PHPMailer (sin Composer)
├── sql/
│   └── schema_web.sql        → tablas nuevas + catálogo de productos
├── tienda.css                → estilos nuevos (precios, carrito, token)
└── img/, *.css, script.js    → assets originales del diseño
```

## 6. Notas

- El carrito vive en la **sesión de PHP** (como en el proyecto Honda),
  no en la base, hasta que el cliente confirma el pedido.
- Los pedidos confirmados quedan guardados en `pedido_web` y
  `detalle_pedido_web`, vinculados al cliente (`usuario_web`).
- El precio de la hamburguesa personalizada se recalcula **en el
  servidor** (`agregar_personalizado.php`) para que nadie pueda
  manipular el precio desde el navegador.
- Los códigos de acceso vencen a los 10 minutos y se bloquean después
  de 5 intentos fallidos; hay un botón para reenviar código.
