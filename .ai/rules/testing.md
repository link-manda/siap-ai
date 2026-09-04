# Testing Standards

- **Pest PHP**: This project strictly uses Pest PHP for testing. Do not write classic PHPUnit class-based tests.
- **Test Types**: Prioritize Feature tests (`tests/Feature/`) to ensure end-to-end functionality works for the user.
- **Database Refresh**: Always use `RefreshDatabase` for feature tests.

