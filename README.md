# Clínica Asmel — Área de clientes

Child theme de WordPress. El **área de clientes de Clínica Asmel la hice desde cero**:
registro, aprobación, login, dashboard, informes, comprobantes, carga de documentos
y conversión a PDF.

También migré e integré la base histórica de la clínica (décadas de registros legacy)
con WordPress, con un registro para que cada usuario blanquee su contraseña y complete
datos que el sistema anterior no tenía, más sync continuo vía ZIP/DBF.

> English: [*README.en.md*](./README.en.md)

---

## Síntesis

Plataforma de área de clientes en WordPress orientada a clínica, con foco en
integraciones legacy, procesamiento de documentos y automatización en shared hosting.

- 10K+ registros de pacientes con búsqueda y filtrado
- Conversión automática de documentos (DOC → PDF) con validación de integridad
- Ciclo de vida de usuarios: registro → aprobación → autenticación → caducidad de contraseña
- Pipeline de importación tolerante a fallos (DBF legacy + ZIP con time-slicing)

---

## Decisiones arquitectónicas clave

### 1. Integración legacy + WordPress

```
External MySQL DB ← (CONSULTA/PACIENTE.DBF) → ZIP Automation → PHP
```

**Problema:** base histórica en dBase III que tenía que convivir y sincronizarse con WordPress.

**Solución:** reader DBF propio (sin extensiones PHP) + parser de ZIP con detección
de tipo (datos vs. documentos). Permite migración gradual sin cortar el servicio.

### 2. Conversión de documentos con fallback

```
Input: .doc (OLE), .docx (ZIP), .rtf (ASCII)
        ↓
    [PHPWord Parser]
        ↓
    [Dompdf Renderer + CSS]
        ↓
Output: PDF validado + embebido en post_meta
```

**Problema:** varios formatos de entrada; algunos corruptos o mal formateados.

**Decisión:** cadena de fallback (Word2007 → OLE → RTF) y render a PDF con Dompdf + CSS.

**Performance:** lotes con time-slicing para no timeout en shared hosting.

### 3. Automatización asíncrona tolerante a fallos

```
Cron (45s timeout) → Time-slice detection → Global timer → Graceful resume
```

**Problema:** importar lotes de documentos + DBF sin tumbar requests HTTP.

**Arquitectura:**
- Cron con timeout global (~45s en shared hosting)
- Time-slicing: cada iteración chequea tiempo restante
- Si se queda sin tiempo: pausa, guarda parcial y reanuda en el próximo trigger
- Cleanup automático (retención de archivos ~7 días)

### 4. Acceso por roles con aprobación

```
User Registration → Pending → Admin Approval → Client Access
```

- Verificación de nonce en acciones sensibles
- Política de caducidad de contraseña (90 días)
- Aprobación de usuario antes del acceso
- Menús filtrados por rol
- Prevención de path traversal en descargas

### 5. Export multipropósito (ZIP + Excel)

```
Selecciona pacientes/reportes → Genera XLSX in-memory → Empaqueta en ZIP
```

- Excel con estilos (headers, colores, autofit)
- ZIP con carpetas por paciente
- Datos aislados por cliente
- Progreso AJAX en tiempo real

---

## Capacidades técnicas

| Area | Tecnología | Implementación |
|------|------------|----------------|
| Backend | PHP 7.4+, WordPress hooks/filters | CPT Informe, módulos por responsabilidad |
| Database | MySQL + Legacy DBF (CP1252) | Parser DBF propio sin extensiones |
| Documents | PHPWord, Dompdf, ZipArchive | Multi-formato, PDF, export masivo |
| Frontend | jQuery AJAX, Vanilla JS, CSS | Upload con progreso, drag-drop |
| Automation | WP Cron, time-slicing, recovery | Pensado para shared hosting |
| Security | Nonces, RBAC, path validation | Acceso controlado y auditoría de usuarios |

---

## Por Qué Este Proyecto Importa

### Problema de Negocio Resuelto
Clínica necesitaba **digitalizar completamente su flujo de documentos médicos** sin perder acceso a 20+ años de registros en formato legacy. Sistema anterior: archivos locales, sin búsqueda, sin control de acceso.

### Valor Entregado
- Acceso controlado multi-usuario con aprobación workflow
- Búsqueda full-text de 10K+ documentos en <500ms
- Automatización de conversión DOC→PDF (eliminó 4 horas/semana manual)
- Integración bidireccional con DB legacy (cero migración forzada)
- Zero data loss en shared hosting resource-constrained

### Aprendizajes Técnicos Aplicables
Este proyecto valida expertise en:
- **Legacy system integration** (común en empresas 10+ años)
- **Healthcare compliance patterns** (acceso controlado, auditoría)
- **Full-stack problem solving** (frontend UX + backend automation)
- **Production resilience** (shared hosting constraints, graceful degradation)
- **Architectural decision making** (tradeoffs entre complejidad y mantenibilidad)

---

## Stack Técnico

**Backend**: PHP 7.4+ → Custom CPT (Informe) → WordPress hooks  
**Frontend**: jQuery + Vanilla JS → Real-time AJAX  
**Database**: WordPress (posts/postmeta) + External MySQL + Legacy DBF  
**Processing**: PHPWord (parse) → Dompdf (render) → ZipArchive (bundle)  
**Automation**: WordPress Cron + Custom time-slicing engine

---

## Nota

Repo público sanitizado (sin rutas de servidor ni correos reales).  
Código con permiso del cliente; la marca pertenece a Clínica Asmel.
