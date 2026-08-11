# Clínica Asmel — Client area

WordPress child theme. I built **Clínica Asmel’s client area from scratch**:
registration, approval, login, dashboard, reports, receipts, document upload,
and PDF conversion.

I also migrated and integrated the clinic’s historical database (10+ years of legacy
records) with WordPress, with a registration flow so every user resets their
password and fills in data the old system didn’t have, plus ongoing ZIP/DBF sync.

Full version (Spanish): [*README.md*](./README.md)

---

## Summary

WordPress client-area platform for a clinic, focused on legacy integrations,
document processing, and resilient automation on shared hosting.

- 10K+ patient records with search and filtering
- Automatic document conversion (DOC → PDF) with integrity checks
- User lifecycle: register → approval → auth → password expiry
- Fault-tolerant import pipeline (legacy DBF + ZIP with time-slicing)

---

## Key architectural decisions

### 1. Legacy + WordPress integration

```
External MySQL DB ← (CONSULTA/PACIENTE.DBF) → ZIP Automation → PHP
```

**Problem:** historical dBase III data needed to stay in sync with WordPress.

**Solution:** custom DBF reader (no PHP extensions) + ZIP parser with type
detection (data vs. documents). Supports gradual migration without downtime.

### 2. Document conversion with fallback

```
Input: .doc (OLE), .docx (ZIP), .rtf (ASCII)
        ↓
    [PHPWord Parser]
        ↓
    [Dompdf Renderer + CSS]
        ↓
Output: validated PDF + embedded in post_meta
```

**Problem:** mixed input formats; some corrupted or poorly formatted.

**Decision:** fallback chain (Word2007 → OLE → RTF), PDF render via Dompdf + CSS.

**Performance:** batched with time-slicing to avoid shared-hosting timeouts.

### 3. Fault-tolerant async automation

```
Cron (45s timeout) → Time-slice detection → Global timer → Graceful resume
```

**Problem:** import document batches + DBF without breaking HTTP requests.

**Architecture:**
- Cron with global timeout (~45s on shared hosting)
- Time-slicing per iteration
- On timeout: pause, persist partial work, resume on next trigger
- Automatic cleanup (~7-day file retention)

### 4. Role-based access with approval

```
User Registration → Pending → Admin Approval → Client Access
```

- Nonce checks on sensitive actions
- 90-day password expiry policy
- Approval required before access
- Role-filtered menus
- Path-traversal prevention on downloads

### 5. Multipurpose export (ZIP + Excel)

```
Select patients/reports → Build XLSX in-memory → Pack into ZIP
```

- Styled Excel (headers, colors, autofit)
- ZIP folders per patient
- Client data isolation
- Real-time AJAX progress

---

## Technical capabilities

| Area | Technology | Implementation |
|------|------------|----------------|
| Backend | PHP 7.4+, WordPress hooks/filters | Informe CPT, modular includes |
| Database | MySQL + Legacy DBF (CP1252) | Custom DBF parser, no extensions |
| Documents | PHPWord, Dompdf, ZipArchive | Multi-format, PDF, bulk export |
| Frontend | jQuery AJAX, Vanilla JS, CSS | Progress uploads, drag-drop |
| Automation | WP Cron, time-slicing, recovery | Shared-hosting friendly |
| Security | Nonces, RBAC, path validation | Controlled access and user audit |

---

## Why this project matters

### Business problem
The clinic needed to **fully digitize its medical document workflow** without losing
access to 10+ years of legacy records. Previous setup: local files, no search,
no access control.

### Value delivered
- Multi-user controlled access with approval workflow
- Full-text search across 10K+ documents in <500ms
- Automated DOC→PDF conversion (removed ~4 hours/week of manual work)
- Historical database migration to WordPress + ongoing legacy DB sync
- No data loss on constrained shared hosting

### Transferable learnings
- Legacy system integration (common in 10+ year businesses)
- Controlled-access patterns (approval, user audit)
- Full-stack problem solving (client UX + backend automation)
- Production resilience (shared hosting limits, graceful degradation)
- Architectural tradeoffs (complexity vs. maintainability)

---

## Technical stack

**Backend**: PHP 7.4+ → Custom CPT (Informe) → WordPress hooks  
**Frontend**: jQuery + Vanilla JS → Real-time AJAX  
**Database**: WordPress (posts/postmeta) + External MySQL + Legacy DBF  
**Processing**: PHPWord (parse) → Dompdf (render) → ZipArchive (bundle)  
**Automation**: WordPress Cron + Custom time-slicing engine

---

## Note

Public repo is sanitized (no real server paths or emails).  
Shown with client permission; brand belongs to Clínica Asmel.
