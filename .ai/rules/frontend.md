# Frontend (Blade & JavaScript)

- **Blade & JS Separation**: NEVER inject Blade variables directly into JavaScript logic blocks using `{{ $var }}`.
- **Data Handoff**: If you need to pass backend data to JavaScript, declare it using the `@json()` directive on a `const` or `let` variable at the very top of the `<script>` block.
- **Flux UI & Tailwind CSS v4**: Use Flux UI components (`<flux:card>`, `<flux:button>`, `<flux:badge>`, `<flux:input>`) and standard Tailwind utility classes. Do NOT use obsolete TailAdmin components or classes.
- **No Emoji Icons**: NEVER use UTF-8 emojis (e.g. 📄, 🔒, 🗑️, 👁️, ⚙️) as UI icons. Always use official Flux UI icon tags (`<flux:icon icon="..." />`) or Heroicons SVG.
- **Clean Views**: Do NOT leave unused or orphan Blade files in `resources/views/`. Keep views clean and strictly connected to routes or controllers.
- **File Structure**: Blade files must be named using `snake_case` (e.g., `archive_form.blade.php`).


