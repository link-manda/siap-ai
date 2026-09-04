# Database & Models

- **Soft Deletes**: Core entity tables (especially `archives` and `categories`) MUST implement Soft Deletes.
- **Migrations**: Add `$table->softDeletes()` to the migration file.
- **Models**: Add `use Illuminate\Database\Eloquent\SoftDeletes;` and `use SoftDeletes;` to the model.

