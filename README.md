# Prueba Técnica – Backend PHP (Laravel)

Implementar una API REST para gestionar Pedidos (Orders) y Pagos (Payments), aplicando buenas
prácticas de desarrollo y tests de funcionalidad (feature test) que validen funcionalidades clave del
sistema (ej. el procesamiento exitoso de un pago).

- Requerimientos
    * Crear pedidos con nombre del cliente, monto total y estado inicial “pending”.
    * Registrar pagos asociados a un pedido existente.
    * Cada intento de pago será por el monto total del pedido.
    * Al registrar un pago, se debe conectar con una API externa simulada para confirmar la transacción.
    * Si el pago es exitoso, el pedido debe pasar a estado “paid”.
    * Si el pago falla, el pedido debe pasar a estado "failed".
    * Un pedido en estado "failed" debe poder recibir nuevos intentos de pago.
    * Listar los pedidos mostrando su estado actual, intentos de pago realizados y los pagos asociados.

El postulante puede usar cualquier versión de Laravel y definir libremente la estructura del
proyecto.

- API Externa (simulada)
    Puedes usar cualquier servicio público o crear tu propio mock, por ejemplo:
    * https://reqres.in/
    * https://beeceptor.com/
    * https://mockoon.com/
    * O implementar tu propia simulación

Se evaluará:
- Funcionalidad completa y correcta
- Calidad del código
- Diseño de la solución
- Tests implementados
- Documentación

Notas Importantes:
- Tienes libertad total para organizar y estructurar el proyecto
- No es necesario implementar autenticación
- No es necesario crear frontend
- Documenta cualquier decisión técnica importante en el README