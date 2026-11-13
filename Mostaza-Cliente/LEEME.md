# App Cliente Mostaza (Expo + TypeScript)

## 1. Backend (PHP)
Copiá `mostaza_web/api/cliente/index.php` a tu proyecto:
`C:\xampp\htdocs\Proyecto-Restaurante\mostaza_web\api\cliente\index.php`

- Cambiá `APP_SECRET` (línea ~19) por una clave tuya.
- `DEV_MODE = true` devuelve el código de login en pantalla si falla el envío del mail. Ponelo en `false` al publicar.
- No hace falta tocar la base: usa las tablas que ya existen (`producto`, `usuario_web`, `pedido_web`, `detalle_pedido_web`, `cupon`, `sucursal`, `mesa`).

Probalo en el navegador: `http://localhost/Proyecto-Restaurante/mostaza_web/api/cliente/index.php?action=categorias`

## 2. App
```
cd Mostaza-Cliente
npm install
npx expo install @react-native-async-storage/async-storage
npx expo start
```
- Celular y PC en la misma red wifi; Apache encendido en XAMPP.
- Si tu carpeta no se llama `Proyecto-Restaurante`, o la IP no se detecta sola, editá `src/constants/Config.ts`.

## Estructura
```
src/app/_layout.tsx      tabs (Inicio, Menú, Carrito, Pedidos, Perfil)
src/app/index.tsx        inicio: cupones, categorías, más pedidos
src/app/menu.tsx         menú completo con buscador y chips
src/app/carrito.tsx      carrito + barra de envío gratis
src/app/checkout.tsx     delivery / retiro / salón, cupón, puntos, propina, pago
src/app/pedidos.tsx      en curso / historial / pedir de nuevo
src/app/pedido/[id].tsx  seguimiento en vivo
src/app/perfil.tsx       datos, puntos, locales
src/components/          ProductCard, ProductModal, CartBar, StatusStepper, LoginScreen
src/context/             AuthContext, CartContext
src/services/api.ts      llamadas a la API
```
