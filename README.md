# Prueba Técnica – Backend PHP (Laravel)

Implementar una API REST para gestionar Pedidos (Orders) y Pagos (Payments), aplicando buenas
prácticas de desarrollo y tests de funcionalidad (feature test) que validen funcionalidades clave del
sistema (ej. el procesamiento exitoso de un pago).

## Requerimientos
- [X] Crear pedidos con nombre del cliente, monto total y estado inicial "pending".
- [X] Registrar pagos asociados a un pedido existente.
- [X] ~~Cada intento de pago será por el monto total del pedido~~ **Sistema de cuotas implementado**
- [X] Al registrar un pago, se debe conectar con una API externa simulada para confirmar la transacción.
- [X] Si el pago es exitoso, el pedido debe pasar a estado "paid".
- [X] Si el pago falla, el pedido debe pasar a estado "failed".
- [X] Un pedido en estado "failed" debe poder recibir nuevos intentos de pago.
- [X] Listar los pedidos mostrando su estado actual, intentos de pago realizados y los pagos asociados.

## Decisiones Técnicas

### Arquitectura
- **Repository Pattern**: Abstracción de acceso a datos, facilita testing y mantenimiento
- **Service Pattern**: Lógica de negocio separada de controladores
- **Modular por dominios**: Orders, Payments, Products, Users con sus respectivos Controllers, Services, Repositories y Models

### Sistema de Cuotas
En lugar de pagar el monto total de una vez, implementé un **sistema de pagos por cuotas** (installments):
- Más cercano a casos reales de e-commerce
- Permite pagos fraccionados
- Cada cuota se paga secuencialmente
- El pedido solo pasa a "paid" cuando todas las cuotas están pagadas
- Si una cuota falla, el pedido pasa a "failed" y puede reintentar el pago

### API Externa
- Configurada via `EXTERNAL_PAYMENT_API_URL` en `.env`
- Implementada con Laravel HTTP Client
- **Mock en Go**: API que devuelve estados aleatorios (success/failure) para simular escenarios reales
- Manejo de errores con `ExternalApiException`

### Base de Datos
- PostgreSQL con esquema normalizado
- Foreign keys con cascade para integridad referencial
- Tabla `payment_plan` para gestionar cuotas
- Tabla `payment` para registrar todos los intentos (exitosos y fallidos)

## API Endpoints

### Products
- `GET /api/products` - Listar productos (paginado)

### Orders
- `GET /api/orders/{orderId?}` - Listar órdenes del usuario (header: `x-user-id`)
- `POST /api/orders` - Crear nueva orden
  ```json
  {
    "products": [{"id": 1, "quantity": 2}],
    "installments": 3
  }
  ```

### Payments
- `GET /api/payments/plan/{orderId}` - Ver plan de pagos de una orden
- `POST /api/payments/pay` - Realizar pago de una cuota
  ```json
  {
    "order_id": 1,
    "total_payment": 100.00,
    "installment_number": 1
  }
  ```

**Autenticación**: Header `x-user-id` (simplificado para la prueba)

## Tests

Ejecutar tests:
```bash
php artisan test
```

**Cobertura:**
- [X] Orders: 12+ test cases (creación, validaciones, edge cases)
- [X] Payments: 8 test cases (pagos exitosos, fallidos, reintentos, estados)
- [X] Products: Tests básicos

## Instalación y Configuración

1. Clonar repositorio
2. Instalar dependencias:
   ```bash
   composer install
   ```

3. Configurar `.env`:
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=your_database
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   
   EXTERNAL_PAYMENT_API_URL=http://localhost:3000
   ```

4. Ejecutar migraciones:
   ```bash
   php artisan migrate
   ```

5. (Opcional) Seeders:
   ```bash
   php artisan db:seed
   ```

6. Crear super usuario:
   ```bash
   php artisan app:create-super-user
   ```

## Notas Adicionales

- **API externa en Go**: Implementé un servidor mock en Go que responde con estados aleatorios (success/failure), simulando comportamiento real de pasarelas de pago donde pueden ocurrir errores de red, rechazos bancarios, etc.
- **Testing con mocks**: Los tests usan `Http::fake()` para simular respuestas sin depender del servidor externo
- **Retry logic**: Pedidos fallidos pueden reintentar el pago de la cuota específica que falló


# Fue un verdadero reto 💪
```
         ,_---~~~~~----._         
  _,,_,*^____      _____``*g*\"*, 
 / __/ /'     ^.  /      \ ^@q   f 
[  @f | @))    |  | @))   l  0 _/  
 \`/   \~____ / __ \_____/    \   
  |           _l__l_           I   
  }          [______]           I  
  ]            | | |            |  
  ]             ~ ~             |  
  |                            |   
   |                           |   
```
