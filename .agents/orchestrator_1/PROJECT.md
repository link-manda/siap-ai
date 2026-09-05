# Project: SIAP-AI 6 Core Requirements Verification & Test Suite Audit

## Architecture & Code Review Boundaries
- Controllers: `app/Http/Controllers/` (e.g., `ArchiveController.php`, `DashboardController.php`, `ReportController.php`, `CategoryController.php`)
- Services: `app/Services/` (e.g., `AiClassificationService.php`, `ArchiveService.php`, `ReportService.php`)
- Repositories: `app/Repositories/` (e.g., `ArchiveRepository.php`, `CategoryRepository.php`)
- Models: `app/Models/` (e.g., `Archive.php`, `Category.php`, `User.php`)
- Views: `resources/views/` (e.g., dashboard, archives, reports, components)
- Routes & Middleware / Policies: `routes/`, `app/Policies/`, `app/Http/Middleware/`
- Tests: `tests/Feature/`, `tests/Unit/`

## Feature Inventory
| # | Feature | Description | Milestone | Source |
|---|---------|-------------|-----------|--------|
| 1 | Klasifikasi Arsip (termasuk filter level kerahasiaan) | Document classification, security levels (Biasa, Terbatas, Rahasia, Sangat Rahasia), filtering logic & RBAC checks | M1 | PRD / Req 1 |
| 2 | Total Arsip (statistik) | Total archives metrics and count statistics across dashboard & reports | M1 | PRD / Req 2 |
| 3 | Arsip berdasarkan waktu penyimpanan (tren bulanan) | Archive statistics based on retention/storage time and monthly trends | M1 | PRD / Req 3 |
| 4 | Arsip berdasarkan Kategori (statistik) | Category-based distribution and count statistics | M2 | PRD / Req 4 |
| 5 | Laporan (Dasbor Charts & Ekspor PDF/Excel) | Dashboard charts (e.g. Chart.js / ApexCharts) & Report exports (PDF via DomPDF/Snappy, Excel via Laravel-Excel) | M2 | PRD / Req 5 |
| 6 | Menu upload / download arsip (Watermark & validasi file) | File upload validation (mimes, size), dynamic watermark on download/preview, RBAC download permissions | M2 | PRD / Req 6 |
| 7 | Validasi Test Suite | Execute `php artisan test`, verify test scenarios, assertions, and coverage against PRD | M3 | PRD / R2 |
| 8 | Forensic RBAC & Security Audit | Verify RBAC boundaries (Admin, Petugas, Pimpinan/User), unauthorized access prevention, and code integrity | M4 | PRD / Acceptance Criteria |

## Milestones
| # | Name | Scope | Dependencies | Status |
|---|------|-------|-------------|--------|
| M1 | Verification of Core Requirements 1-3 | Klasifikasi & filter kerahasiaan, total arsip statistik, tren bulanan & waktu penyimpanan | none | IN_PROGRESS |
| M2 | Verification of Core Requirements 4-6 | Statistik kategori, dasbor charts & ekspor PDF/Excel, menu upload/download & watermark/validasi | none | IN_PROGRESS |
| M3 | Test Suite Execution & Coverage Audit | Run `php artisan test`, record pass/fail results, analyze test coverage of the 6 requirements | none | IN_PROGRESS |
| M4 | Forensic RBAC & Security Integrity Audit | Probe RBAC bypasses, policy enforcement, service-repository compliance, integrity checks | M1, M2, M3 | PLANNED |
| M5 | Synthesis & Final Evaluation Report | Consolidate findings into comprehensive Markdown evaluation artifact | M1, M2, M3, M4 | PLANNED |
