# Frontend (Blade & JavaScript)

- **Blade & JS Separation**: NEVER inject Blade variables directly into JavaScript logic blocks using `{{ $var }}`.
- **Data Handoff**: If you need to pass backend data to JavaScript, declare it using the `@json()` directive on a `const` or `let` variable at the very top of the `<script>` block.
- **Tailwind CSS v4**: Use utility classes directly in Blade templates. Use the provided TailAdmin components where applicable.
- **File Structure**: Blade files must be named using `snake_case` (e.g., `archive_form.blade.php`).

