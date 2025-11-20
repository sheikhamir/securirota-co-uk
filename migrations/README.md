# Database Migration System

This directory contains the database migration system for the Security Rota application.

## Migration Files

### Active Migrations
- `000_create_migrations_table.sql` - Creates the migration tracking table
- `001_core_schema.sql` - Complete database schema with all tables
- `002_default_data.sql` - Default roles and email templates

### Archived Migrations
The `archive/` directory contains the original incremental migration files that have been consolidated into the core schema. These are kept for reference but should not be run on new installations.

## Usage

### Setting up a new database
1. Create your database and update `config/database.php` with credentials
2. Run: `php setup_migrations.php` to initialize the migration system
3. The core schema and default data will be automatically applied

### Checking migration status
```bash
php migrate.php status
```

### Running pending migrations
```bash
php migrate.php migrate
```

### Fresh installation (DANGEROUS - drops all tables)
```bash
php migrate.php fresh
```

## Migration File Naming Convention

Migration files should be named using the format: `XXX_description.sql` where:
- `XXX` is a three-digit sequence number (000, 001, 002, etc.)
- `description` is a brief description of what the migration does

## Adding New Migrations

1. Create a new migration file with the next sequence number
2. Write your SQL changes using `CREATE TABLE IF NOT EXISTS`, `ALTER TABLE`, etc.
3. Test the migration on a copy of your database first
4. Run `php migrate.php migrate` to apply the new migration

## Database Schema Overview

The current schema includes:
- **users** - User authentication
- **officers** - Staff records and details
- **clients** - Client companies
- **sites** - Client sites requiring security
- **roles** - Job roles (Security, Controller, Supervisor, etc.)
- **shifts** - Shift assignments and scheduling
- **activity_log** - Audit trail of all system actions
- **email_templates** - Configurable email templates
- **email_images** - Images for email templates
- **documents** - Document uploads for officers
- **leave_requests** - Holiday/leave management
- **notifications** - System notifications
- **site_rotas** - Template-based shift patterns
- **subcontractors** - External contractor management
- **officer_holiday_pay** - Holiday pay accrual tracking
- **shift_activities** - Detailed shift activity logging

## Important Notes

1. Always backup your database before running migrations
2. Test migrations on a development copy first
3. The migration system tracks which migrations have been applied
4. Never modify migration files that have already been applied
5. Always use `IF NOT EXISTS` and similar safe SQL syntax in migrations
