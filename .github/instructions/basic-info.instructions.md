---
appliesTo: "**"
---

# Workflow Basic Info
- The server shall be treated as a live server for testing purposes.
- The server shall not be considered to support Node.js functionality; it is a simple web server.
- The agent MUST NOT use Node.js commands or the Node.js runtime for any reason.
- The agent MUST NOT run `node`, `npm`, or any Node-based command under any circumstances.
- The agent MUST use alternative validation methods (such as static analysis, linting, or manual inspection) for JavaScript files.
- Any attempt to use Node.js for syntax checking, execution, or testing is strictly prohibited.

- Before executing any MySQL query:
  - The agent must check for the database file located at `/config/database.php`.
  - The agent must extract credentials from this file to connect.

- When testing database queries:
  - The agent must use the command line.
  - The agent shall not create new files for testing.

- Database integrity must always be maintained:
  - The agent shall not delete data unless explicitly confirmed by the user.

# Coding Standards
- All source code files must comply with **PSR-12** coding standards.
- All CSS code shall be consolidated into a single file.
- For JavaScript:
  - A **main JavaScript file** must always be created and used as the primary script.
  - Page-specific JavaScript files shall only be created if that page contains unique JavaScript functions.
  - The agent must prefer using the main JavaScript file whenever possible, instead of creating new ones.