# Architecture & Service-Repository Pattern

- **Strict Separation of Concerns**: Controllers MUST NOT query the database or use Eloquent models directly for database operations.
- **Controllers**: Only handle request lifecycle, validation (`$request->validate()`), pass data to Services, and return HTTP responses/views.
- **Services**: Contain all core business logic (e.g., AI integration, complex calculations, orchestration). A service can call one or more repositories.
- **Repositories**: Purely data access layers. All Eloquent queries (create, update, delete, complex selects) belong here.
- **Interfaces**: Repositories MUST implement an interface (e.g., `ArchiveRepositoryInterface`) to allow for dependency injection and easy mocking.

