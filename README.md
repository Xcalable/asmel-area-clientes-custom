# Clínica Asmel — Área de clientes

Child theme de WordPress para el **área de clientes de Clínica Asmel**.

No es un tema “bonito” genérico: es el panel donde los clientes se registran, esperan aprobación, inician sesión y consultan informes / comprobantes. También hay carga de documentos, conversión DOC/DOCX → PDF y una automatización de importación (ZIP/DBF) pensada para el flujo operativo de la clínica.

> English summary: [*README.en.md*](./README.en.md)

---

## Qué resuelve

- Login / registro / primer acceso y política de caducidad de contraseña
- Aprobación de usuarios y control de accesos
- CPT **Informe** con archivos asociados
- Subida AJAX y conversión de documentos
- Dashboard de clientes (informes, comprobantes, exportación)
- Sync / importación automatizada desde carpetas del servidor

## Stack

- WordPress (tema hijo)
- PHP, JavaScript, CSS
- Librerías externas para conversión de documentos
- Lectura DBF para automatización

## Estructura

```
functions.php
includes/     # login, usuarios, CPT, dashboard, converter, automation…
assets/css/
assets/js/
style.css
```

## Privacidad en este repo público

Rutas de servidor, usuario de hosting y correos reales se reemplazaron por placeholders (`/path/to/...`, `clientes@example.com`).

El desarrollo privado (con paths reales) vive en otro repositorio.

## Nota

Código mostrado con permiso del cliente, como referencia de portfolio.

La marca Clínica Asmel y sus contenidos operativos pertenecen a su propietario.
