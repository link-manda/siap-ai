# Forensic Audit & Security Boundary Report (SIAP-AI)

**Auditor**: Forensic Security Auditor (`auditor_security`)  
**Timestamp**: 2026-09-05T04:30:00Z  
**Audit Type**: Forensic Integrity, RBAC Boundary, and Architectural Compliance  
**Target Repository**: `/Users/brahmanda/Sites/siap-ai`  
**Ground-Truth Constraints**: `ORIGINAL_REQUEST.md` (Integrity Mode: `development`), `AGENTS.md`  

---

## 1. Observation

Direct code observations, verbatim terminal outputs, exact file paths, line numbers, and database schema inspections:

### 1.1. Integrity & Authenticity Verification

1. **Test Suite Execution & Results**:
   - Running the test suite via `env -u AI_AGENT -u ANTIGRAVITY_AGENT ./vendor/bin/pest`:
     ```text
        PASS  Tests\Feature\ArchiveRbacTest
       ✓ it hides sangat rahasia documents from admin in datatable            0.14s  
       ✓ it shows sangat rahasia documents to pimpinan in datatable           0.01s  
       ✓ it forbids admin from downloading sangat rahasia documents           0.01s  
       ✓ it allows pimpinan to download sangat rahasia documents              0.01s  
       ✓ it forbids admin from deleting sangat rahasia documents              0.01s  

        PASS  Tests\Feature\ArchiveTest
       ✓ it processes uploaded document with AI and returns structured data   0.04s  
       ✓ it prevents guests from downloading archives                         0.01s  
       ✓ it allows authenticated users to download archives and logs the act… 0.01s  
       ✓ it soft deletes an archive                                           0.01s  

        PASS  Tests\Feature\ArchiveWatermarkTest
       ✓ it downloads pdf level 4 via watermark service                       0.01s  
       ✓ it flashes warning and downloads original for docx level 4           0.01s  

        PASS  Tests\Feature\ReportTest
       ✓ it allows pimpinan to export all archives to excel                   0.01s  
       ✓ it allows pimpinan to export all archives to pdf                     0.03s  
       ✓ it filters level 4 archives from admin in excel export               0.01s  

       Tests:    14 passed (33 assertions)
       Duration: 0.36s
     ```
   - All 14 tests run real assertions (`assertStatus`, `assertSee`, `assertDontSee`, `assertDatabaseHas`, `assertSoftDeleted`, `assertDownload`) against an in-memory SQLite database.
2. **Hardcoded Test Results Check**:
   - Project source files (`ArchiveController.php`, `ArchiveRepository.php`, `ArchivePolicy.php`, `ArchiveService.php`, `ReportExportService.php`, `WatermarkService.php`, `AiClassificationService.php`) were scrutinized for string literals matching test expectations or conditional branches that check test environments.
   - Result: **Zero hardcoded test result shortcuts found**. Methods perform real database queries, policy authorization logic, stream generation, and file operations.
3. **Facade & Dummy Implementation Check**:
   - `app/Ai/Agents/ArchiveClassifier.php:13-34` is a genuine `Laravel\Ai` agent implementing structured JSON output schema (`category_name`, `classification_name`, `summary`, `confidence_score`). In tests, `ArchiveClassifier::fake()` is used per Laravel official guidelines.
   - `app/Services/ArchiveService.php:30`:
     ```php
     $textToAnalyze = $extractedText ?: "Simulasi isi dokumen: " . $fileName;
     ```
     OCR extraction is simulated via a fallback string when no extracted text is provided. This is an explicit implementation placeholder noted in code comments rather than a deceptive facade to cheat test assertions.
   - `app/Services/WatermarkService.php:14-93` contains genuine implementation using `setasign\Fpdi\Fpdi` and `Intervention\Image\ImageManager`.
4. **Fabricated Output & Pre-Populated Artifact Check**:
   - A filesystem search for pre-existing log files, test results, or dummy caches returned only standard Laravel runtime logs (`storage/logs/laravel.log`, `storage/logs/browser.log`) and vendor assets.

---

### 1.2. Security & RBAC Boundary Forensic Checks

#### A. Audit Trail Information Leakage (Level 4 "Sangat Rahasia" Archive Titles)
- **Controller Observation**: `app/Http/Controllers/AuditTrailController.php:12`:
  ```php
  $logs = ArchiveLog::with(['user', 'archive'])->latest()->paginate(15);
  ```
  The query does **not** scope records by classification level or user role.
- **View Observation**: `resources/views/pages/tables/audit-trail.blade.php:38`:
  ```blade
  <td class="px-5 py-4 sm:px-6">
      <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $log->archive->title ?? 'Arsip Dihapus' }}</p>
  </td>
  ```
- **Route Observation**: `routes/web.php:34`:
  ```php
  Route::get('/audit-trail', [AuditTrailController::class, 'index'])->name('audit.index');
  ```
- **Finding**: While `ArchiveRepository::getBaseQuery()` successfully hides Level 4 ("Sangat Rahasia") archives from `Admin` on `/archives`, any document action (upload, download, deletion) logged in `archive_logs` displays the exact document title in plain text to `Admin` on `/audit-trail`.

#### B. Null Pointer Risk on Missing / Null Classification
- **Policy Observation**: `app/Policies/ArchivePolicy.php:29`:
  ```php
  public function view(User $user, Archive $archive): bool
  {
      if ($user->role === 'Pimpinan') {
          return true;
      }

      // Admin can only view if classification level is <= 3
      return $archive->classification->level <= 3;
  }
  ```
- **Controller Observation**: `app/Http/Controllers/ArchiveController.php:119`:
  ```php
  if ($archive->classification->level === 4) {
  ```
- **Database Schema Observation**: `database/migrations/2026_09_04_151607_create_archives_table.php:22`:
  ```php
  $table->foreignId('classification_id')->nullable()->constrained()->nullOnDelete();
  ```
- **Empirical Execution in Tinker**:
  ```bash
  php artisan tinker --execute '
  $archive = new \App\Models\Archive();
  $archive->classification_id = null;
  $user = new \App\Models\User(["role" => "Admin"]);
  (new \App\Policies\ArchivePolicy)->view($user, $archive);
  '
  ```
  *Result*: Emits warning/error:
  `Attempt to read property "level" on null in app/Policies/ArchivePolicy.php on line 29.`
- **Finding**: If an archive has `classification_id = null` (or if its classification record is deleted via `nullOnDelete`), `ArchivePolicy::view()`, `update()`, `delete()`, `restore()`, and `ArchiveController::download()` throw fatal null property access exceptions (HTTP 500 error).

#### C. File Path Tampering & Directory Traversal in `ArchiveController::store()`
- **Controller Observation**: `app/Http/Controllers/ArchiveController.php:75-89`:
  ```php
  public function store(Request $request)
  {
      $validated = $request->validate([
          'title' => ['required', 'string', 'max:255'],
          'document_number' => ['nullable', 'string', 'max:255'],
          'document_date' => ['nullable', 'date'],
          'file_path' => ['required', 'string'],
          'file_type' => ['required', 'string'],
          ...
      ]);

      $this->archiveService->storeArchive($validated, Auth::id());
      return redirect()->route('archives.index')->with('success', 'Arsip berhasil disimpan!');
  }
  ```
- **Frontend Form Observation**: `resources/views/pages/form/form-elements.blade.php:44`:
  ```blade
  <input type="hidden" name="file_path" id="file_path">
  ```
- **Finding**: The server blindly trusts a client-supplied hidden string parameter `file_path`. An attacker or malicious user can forge a `POST /archives` request with arbitrary paths (e.g. `../../.env`, `../../bootstrap/app.php`, or files uploaded by other users). When the archive is downloaded by Pimpinan via `Storage::download($archive->file_path, ...)`, storage resolves the tampered path, risking arbitrary file disclosure.

#### D. Permissive Base Query Default in `ArchiveRepository::getBaseQuery()`
- **Repository Observation**: `app/Repositories/ArchiveRepository.php:48-60`:
  ```php
  protected function getBaseQuery()
  {
      $query = Archive::query();
      $user = auth()->user();
      
      if ($user && $user->role === 'Admin') {
          $query->whereHas('classification', function ($q) {
              $q->where('level', '<=', 3);
          });
      }
      
      return $query;
  }
  ```
- **Finding**: This access check inverts the Principle of Least Privilege:
  1. It restricts **only** the `Admin` role. If any new role is added (e.g. `'Staff'`, `'Operator'`, `'Guest'`), or if the query runs in an unauthenticated / queue / CLI context where `auth()->user()` is `null`, it returns all records including Level 4 ("Sangat Rahasia") by default.
  2. If an archive has `classification_id = null`, `whereHas` excludes it from `Admin` but permits it for any non-Admin user.

#### E. Watermark Silent Fallback Delivering Raw Unwatermarked Secret PDF
- **Service Observation**: `app/Services/WatermarkService.php:67-70`:
  ```php
  } catch (\Exception $e) {
      // Fallback to original if PDF is encrypted or unsupported version
      return $sourcePath;
  }
  ```
- **Controller Observation**: `app/Http/Controllers/ArchiveController.php:129-139`:
  ```php
  if ($watermarkedPath !== Storage::path($archive->file_path)) {
      return response()->download($watermarkedPath, $archive->title.'.'.$archive->file_type)
          ->deleteFileAfterSend(true);
  }
  ...
  return Storage::download($archive->file_path, $archive->title.'.'.$archive->file_type);
  ```
- **Finding**: When FPDI encounters modern PDF versions (1.5+) with compressed cross-reference streams or encryption, `WatermarkService::watermarkPdf` catches `\Exception` and returns `$sourcePath`. In `ArchiveController::download`, because `$watermarkedPath === Storage::path($archive->file_path)`, the `if` condition fails. Execution drops to line 139: the raw, unwatermarked Level 4 PDF is silently downloaded without session warnings, without error logging, and without security alerts.

#### F. Route and Guest Access Audit
- **Route Observation**: `routes/web.php:43-53`:
  ```php
  // Fallback for development (keeping tailadmin UI testing routes)
  Route::prefix('ui')->group(function () {
      Route::get('/calendar', function () { ... });
      Route::get('/alerts', function () { ... });
      Route::get('/buttons', function () { ... });
  });
  ```
- **Finding**:
  1. Routes under `/ui/*` are outside the `auth` middleware group and remain completely unauthenticated.
  2. In `tests/Feature/ArchiveTest.php:47`, guest redirection to `/login` is only tested for `archives.download`. No test assertions verify guest blocking on `dashboard`, `archives.index`, `archives.create`, `archives.store`, `archives.destroy`, `archives.export.excel`, `archives.export.pdf`, or `audit.index`.
  3. Route `audit.index` (`/audit-trail`) has no role middleware and is accessible to all authenticated users regardless of privilege.

#### G. Hardcoded API Secret in Root Testing Script
- **File Observation**: `test-ai.php:10`:
  ```php
  $key = config('ai.providers.openai-compatible.key') ?? env('OPENAI_COMPATIBLE_API_KEY', 'tp-_lYP75AYTC7l6GbWcSWJSR0-CSSBle6j8uoYC_NAAv0');
  ```
- **Finding**: An active API credential token (`tp-_lYP75AYTC7l6GbWcSWJSR0-CSSBle6j8uoYC_NAAv0`) is hardcoded as a fallback in a root script committed in the workspace.

---

### 1.3. Architecture & Standards Compliance

#### A. Service-Repository Pattern Violations (`AGENTS.md`)
`AGENTS.md` mandates:
> *Controller: Hanya bertugas menerima request, melakukan validasi ($request->validate()), meneruskan data ke Service, dan mengembalikan response. Dilarang keras menulis logika query database di Controller.*  
> *Repository: Menyediakan akses data (Eloquent/Query). Murni operasi CRUD ke database.*

Observed Violations:
1. `ArchiveController`:
   - Line 22: Directly injects `ArchiveRepositoryInterface` into Controller.
   - Line 36: Direct Eloquent query `Category::all()`.
   - Line 49-50: Direct Eloquent queries `Category::all()` and `Classification::all()`.
   - Line 98, 144, 165, 178: Calls repository methods directly from Controller.
   - Line 110-116: Direct Eloquent insertion `ArchiveLog::create([...])`.
   - Line 152: Direct Eloquent deletion `$archive->delete()`.
2. `DashboardController`:
   - Line 11: Injects `ArchiveRepositoryInterface` directly into Controller.
   - Line 16-19: Directly invokes 4 repository methods without a `DashboardService`.
3. `AuditTrailController`:
   - Line 12: Direct Eloquent query `ArchiveLog::with(['user', 'archive'])->latest()->paginate(15)` without any Service or Repository layer.
4. `AiClassificationService`:
   - Lines 23-28: Direct Eloquent queries `Category::where(...)` and `Classification::where(...)` without Repository encapsulation.

#### B. SoftDeletes Compliance
- `Archive.php` & migration `create_archives_table.php`: **COMPLIANT** (`use SoftDeletes;` and `$table->softDeletes()`).
- `Category.php` & migration `create_categories_table.php`: **COMPLIANT** (`use SoftDeletes;` and `$table->softDeletes()`).
- `Classification.php` & migration `create_classifications_table.php`: **NON-COMPLIANT** (Missing `SoftDeletes`). A hard delete cascades `null` to `archives.classification_id`, triggering the null pointer vulnerability.
- `ArchiveRepository::getCategoryStats()` (line 70): Performs `join('categories', 'archives.category_id', '=', 'categories.id')` without `whereNull('categories.deleted_at')`, inadvertently including soft-deleted categories in aggregation.

#### C. Database Schema Discrepancy in Reports (`file_size`)
- `ReportExportService.php:22`:
  ```php
  'Ukuran File (KB)' => round($archive->file_size / 1024, 2),
  ```
- `database/migrations/2026_09_04_151607_create_archives_table.php`: Column `file_size` **does not exist**.
- `app/Models/Archive.php:14-27`: `file_size` is **not in `$fillable`**.
- Result: `$archive->file_size` always resolves to `null`, outputting `0` KB in all exported Excel/CSV reports.

#### D. Database Dialect Incompatibility in `ArchiveRepository::getMonthlyStats()`
- Location: `app/Repositories/ArchiveRepository.php:99`:
  ```php
  ->selectRaw('MONTH(archives.created_at) as month, COUNT(archives.id) as total')
  ```
- Verbatim error when executed in SQLite test environment:
  ```text
  Illuminate\Database\QueryException SQLSTATE[HY000]: General error: 1 no such function: MONTH (Connection: sqlite, Database: :memory:, SQL: select MONTH(archives.created_at) as month, COUNT(archives.id) as total from "archives" where strftime('%Y', "archives"."created_at") = cast(2026 as text) and "archives"."deleted_at" is null group by "month" order by "month" asc)
  ```
- This code succeeds on MySQL in production but fails immediately on SQLite. It escaped detection solely because the dashboard has 0% test coverage.

---

## 2. Logic Chain

1. **Integrity & Authenticity Assessment**:
   - *Premise 1*: Under `development` integrity mode, prohibited patterns include hardcoded test results, facade/dummy implementations, and fabricated verification outputs.
   - *Premise 2*: All 14 tests pass through authentic database execution, Eloquent queries, policy evaluations, and real framework dispatching. Faking AI responses in tests adheres to standard Laravel AI SDK testing methodology.
   - *Premise 3*: The simulated OCR string in `ArchiveService` is an acknowledged application fallback when no text is supplied, not an attempt to fool test assertions (no tests assert the simulation string).
   - *Conclusion*: There is **no evidence of cheating, hardcoded test results, or fraudulent verification artifacts**. The audit verdict on integrity is **CLEAN**.

2. **Audit Trail RBAC Leakage Assessment**:
   - *Premise 1*: PRD and `ArchiveRbacTest` require that Admin must not view Level 4 ("Sangat Rahasia") archives.
   - *Premise 2*: `AuditTrailController` queries all `ArchiveLog` entries and loads `archive` relations without filtering by classification level.
   - *Premise 3*: The view renders `{{ $log->archive->title }}`. Any authenticated Admin navigating to `/audit-trail` directly views the titles and download events of Level 4 archives.
   - *Conclusion*: **High severity information disclosure vulnerability**.

3. **File Path Tampering Assessment**:
   - *Premise 1*: `ArchiveController::processAi()` saves uploads to `private/archives/` and returns the file path to the client.
   - *Premise 2*: `ArchiveController::store()` accepts `file_path` as a raw string from `$request` without ensuring the path belongs to an active user session or is restricted within `private/archives/`.
   - *Premise 3*: `ArchiveController::download()` invokes `Storage::download($archive->file_path, ...)`.
   - *Conclusion*: **High severity path traversal / arbitrary file download vulnerability**.

4. **Watermark Fallback Assessment**:
   - *Premise 1*: Watermarking is mandated for Level 4 documents to prevent unauthorized distribution.
   - *Premise 2*: `WatermarkService::watermarkPdf` catches all exceptions and silently returns the original unwatermarked file path.
   - *Premise 3*: `ArchiveController::download` checks `if ($watermarkedPath !== Storage::path($archive->file_path))`. When equal, it skips watermark download and executes `Storage::download` without warning or security event logging.
   - *Conclusion*: **Medium severity security boundary failure**.

5. **Architecture & Standards Compliance Assessment**:
   - *Premise 1*: `AGENTS.md` explicitly forbids database queries in Controllers and mandates the Service-Repository pattern.
   - *Premise 2*: 3 out of 4 Controllers (`ArchiveController`, `DashboardController`, `AuditTrailController`) perform direct Eloquent queries, direct deletions, direct inserts, and direct repository method calls.
   - *Conclusion*: Architecture is **non-compliant** with `AGENTS.md`.

---

## 3. Caveats

1. **AI API Credential Expiration**: The faked AI agent in tests executes deterministically. Live testing of `POST /archives/process-ai` against the external endpoint depends on active API keys and upstream availability.
2. **Watermark Binary Dependencies**: In production environments, watermarking complex PDFs (version 1.5+ object streams) requires `FPDI PDF-Parser` commercial add-on or Ghostscript/Poppler CLI preprocessing to avoid triggering the fallback catch block.
3. **Dual Role Scope**: The application currently enforces only two roles: `'Admin'` and `'Pimpinan'`. If new roles are seeded without refactoring `getBaseQuery()`, permission boundaries will fail open.

---

## 4. Conclusion

### 4.1. Binary Forensic Audit Verdict

# **VERDICT: CLEAN**
*(No integrity violations, hardcoded test results, facade implementations, or cheating detected under Development Integrity Mode)*

---

### 4.2. Vulnerability Classification Matrix

| ID | Vulnerability / Defect | Severity | Affected Files | Impact & Blast Radius |
|---|---|---|---|---|
| **VULN-01** | **Audit Trail Information Leakage** | **HIGH** | `app/Http/Controllers/AuditTrailController.php:12`<br>`resources/views/pages/tables/audit-trail.blade.php:38` | Admin can view titles, uploaders, and download events of Level 4 ("Sangat Rahasia") archives. |
| **VULN-02** | **Arbitrary File Path Tampering** | **HIGH** | `app/Http/Controllers/ArchiveController.php:81`<br>`resources/views/pages/form/form-elements.blade.php:44` | Client can submit arbitrary `file_path` in `store()`, enabling path traversal and unauthorized file retrieval upon download. |
| **VULN-03** | **Hardcoded API Token Leaked in Root Script** | **HIGH** | `test-ai.php:10` | Third-party AI gateway token hardcoded in repository workspace. |
| **VULN-04** | **Silent Fallback to Unwatermarked Secret PDF** | **MEDIUM** | `app/Services/WatermarkService.php:67-70`<br>`app/Http/Controllers/ArchiveController.php:129-139` | Encrypted or v1.5+ PDFs download in raw unwatermarked state without user notification or security audit log. |
| **VULN-05** | **Inverted Permissive Base Query Default** | **MEDIUM** | `app/Repositories/ArchiveRepository.php:53-57` | Only restricts `'Admin'`. Future roles, unauthenticated contexts, CLI commands, and queue jobs default to seeing Level 4 records. |
| **VULN-06** | **Null Pointer Crash on Null Classification** | **MEDIUM** | `app/Policies/ArchivePolicy.php:29`<br>`app/Http/Controllers/ArchiveController.php:119` | HTTP 500 error when viewing, updating, deleting, or downloading archives with `classification_id = null`. |
| **VULN-07** | **Unauthenticated UI Template Routes** | **LOW** | `routes/web.php:43-53` | Routes under `/ui/*` are publicly exposed to unauthenticated guests. |
| **VULN-08** | **Missing Test Coverage for Guest Route Access** | **LOW** | `tests/Feature/ArchiveTest.php:47` | Only `archives.download` is tested against unauthenticated guests; remaining 8 protected routes lack guest assertions. |
| **VULN-09** | **Missing `file_size` Column in Database Schema** | **LOW** | `database/migrations/2026_09_04_151607_create_archives_table.php`<br>`app/Services/ReportExportService.php:22` | Excel/CSV report exports output `0` KB file size for all archives. |
| **VULN-10** | **Database Dialect Incompatibility (`MONTH()`)** | **LOW** | `app/Repositories/ArchiveRepository.php:99` | MySQL-specific `MONTH()` function causes fatal query error when executed against SQLite. |

---

### 4.3. Architectural Compliance Summary

- **Service-Repository Pattern**: **VIOLATED** in `ArchiveController`, `DashboardController`, `AuditTrailController`, and `AiClassificationService`.
- **SoftDeletes**: **COMPLIANT** on `archives` and `categories`; **MISSING** on `classifications`.

---

## 5. Verification Method

All observations and vulnerabilities can be independently verified using the following reproduction commands from `/Users/brahmanda/Sites/siap-ai`:

### 5.1. Run Integrity & Test Suite Check
```bash
env -u AI_AGENT -u ANTIGRAVITY_AGENT ./vendor/bin/pest
```
*Expected*: 14 tests pass (33 assertions).

### 5.2. Reproduce Null Pointer Crash (VULN-06)
```bash
php artisan tinker --execute '
$archive = new \App\Models\Archive();
$archive->classification_id = null;
$user = new \App\Models\User(["role" => "Admin"]);
(new \App\Policies\ArchivePolicy)->view($user, $archive);
'
```
*Expected*: Outputs `Attempt to read property "level" on null in app/Policies/ArchivePolicy.php on line 29`.

### 5.3. Reproduce SQLite Query Dialect Failure (VULN-10)
```bash
php artisan tinker --execute '
config(["database.default" => "sqlite", "database.connections.sqlite.database" => ":memory:"]);
\Illuminate\Support\Facades\Artisan::call("migrate");
\App\Models\Archive::create(["title"=>"t","file_path"=>"p","file_type"=>"pdf"]);
app(\App\Interfaces\ArchiveRepositoryInterface::class)->getMonthlyStats();
'
```
*Expected*: Throws `Illuminate\Database\QueryException: General error: 1 no such function: MONTH`.

### 5.4. Verify Audit Trail Information Leakage (VULN-01)
Inspect line 12 of `app/Http/Controllers/AuditTrailController.php` and line 38 of `resources/views/pages/tables/audit-trail.blade.php`. Notice unconditional pagination and title rendering.

### 5.5. Verify Missing `file_size` Column (VULN-09)
```bash
php artisan tinker --execute 'var_dump(\Illuminate\Support\Facades\Schema::hasColumn("archives", "file_size"));'
```
*Expected*: `bool(false)`.

### 5.6. Invalidation Conditions
This report's vulnerability findings would be invalidated if:
1. `AuditTrailController` is updated to join or filter logs where `archives.classification.level <= 3` for Admin users.
2. `ArchiveController::store()` validates that `file_path` matches an active server-side upload session token and restricts directory prefixes.
3. `WatermarkService` and `ArchiveController::download()` abort or flash an alert when watermarking fails rather than silently delivering raw files.
4. `ArchivePolicy` and `ArchiveController` implement null-coalescing (`$archive->classification?->level`).
5. Controllers are refactored to delegate data fetching to Services, eliminating direct Eloquent calls.
