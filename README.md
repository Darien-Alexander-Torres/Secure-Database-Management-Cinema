🎬 Cinema E-Commerce & Management System

Este repositorio contiene un sistema integral para la gestión de un cine, que abarca desde la cartelera pública y la venta de boletos hasta un panel administrativo para el control de inventario y métricas de ventas.

Este es un proyecto desarrollado en colaboración, diseñado para implementar soluciones robustas de software que integren una interfaz de usuario dinámica con una arquitectura de base de datos segura.
🚀 Características Principales

    Cartelera Dinámica: Sistema de visualización de películas con filtrado por fecha y horarios en tiempo real.

    Gestión de Ventas: Proceso de selección de asientos interactivo y pasarela de pago simulada.

    Panel Administrativo: Módulo completo para la gestión de películas, edición de funciones y visualización de recaudación total.

    Autenticación y Roles: Sistema de registro y login con distinción de permisos entre usuarios finales y administradores.

    Impresión de Boletos: Formato de ticket optimizado mediante CSS para su impresión física tras la compra.

🛠️ Stack Tecnológico

    Lenguaje: PHP 8.x.

    Base de Datos: SQL Server (Transact-SQL).

    Conectividad: PDO (PHP Data Objects) con drivers SQLSRV.

    Frontend: Bootstrap 5 y JavaScript nativo.

🔒 Arquitectura de Seguridad y Datos

El sistema fue construido bajo principios de integridad y protección de datos:

    Integridad Transaccional: Uso de transacciones ACID para garantizar que las ventas y la asignación de boletos sean atómicas.

    Protección SQL: Implementación de sentencias preparadas y procedimientos almacenados para mitigar riesgos de inyección SQL.

    Seguridad de Datos: Enmascaramiento de información sensible de tarjetas bancarias y sanitización de entradas/salidas de datos.

📂 Estructura del Proyecto

   /admin: Panel de control para la gestión de películas, edición de horarios y visualización de métricas de recaudación.

   /assets: Recursos multimedia del sistema, incluyendo los pósters de las películas y el logotipo de la marca.

   /auth: Módulos de seguridad para el registro de usuarios, inicio de sesión y cierre de sesión seguro.

   /compras: Lógica transaccional para la selección de asientos en sala y el procesamiento seguro de pagos.

   /config: Gestión centralizada de la conexión a SQL Server mediante PDO y manejo de excepciones.

   /database: Scripts SQL con la definición de tablas, relaciones, cifrado AES-256 y procedimientos almacenados.

   /includes: Componentes globales reutilizables (Header y Footer) con lógica de navegación por roles y estilos de impresión.

   /peliculas: Vista detallada de información de filmes y despliegue dinámico de horarios disponibles.
