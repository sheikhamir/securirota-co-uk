# Individual Migration Files - Dependency Order

The migration files have been split into individual table migrations with proper dependency ordering.

## Migration Execution Order

### Level 0 - No Dependencies
- `001_create_users_table.sql` - User authentication
- `002_create_clients_table.sql` - Client companies  
- `003_create_subcontractors_table.sql` - External contractors
- `004_create_roles_table.sql` - Job roles

### Level 1 - Depends on Level 0
- `005_create_officers_table.sql` - Depends on: users, subcontractors
- `006_create_sites_table.sql` - Depends on: clients

### Level 2 - Depends on Level 0 & 1
- `007_create_shifts_table.sql` - Depends on: sites, officers, roles
- `008_create_activity_log_table.sql` - Depends on: users
- `009_create_email_templates_table.sql` - Depends on: users
- `010_create_email_images_table.sql` - Depends on: users
- `011_create_documents_table.sql` - Depends on: officers
- `012_create_leave_requests_table.sql` - Depends on: officers, users
- `013_create_notifications_table.sql` - Depends on: users
- `014_create_site_rotas_table.sql` - Depends on: sites
- `015_create_officer_holiday_pay_table.sql` - Depends on: officers

### Level 3 - Depends on Level 2
- `016_create_shift_activities_table.sql` - Depends on: shifts

### Level 4 - Views and Data
- `017_create_activity_log_view.sql` - Depends on: activity_log, users, officers
- `018_insert_default_data.sql` - Default roles, admin user, email templates

## Dependency Graph

```
users ────┬─── officers ────┬─── documents
          │                 ├─── leave_requests
          │                 └─── officer_holiday_pay
          │
          ├─── activity_log
          ├─── email_templates  
          ├─── email_images
          └─── notifications

clients ──── sites ────┬─── shifts ──── shift_activities
                       └─── site_rotas

subcontractors ──── officers (reference)

roles ──── shifts (reference)

activity_log + users + officers ──── activity_log_view
```

## Key Features

1. **Proper Ordering**: Tables are created before their dependents
2. **Foreign Key Safety**: Parent tables exist before child tables reference them
3. **Rollback Safety**: Can be reversed in opposite order if needed
4. **Individual Control**: Each table can be managed separately
5. **Clear Dependencies**: Comments show what each table depends on

## Usage

Run migrations in order:
```bash
php migrate.php migrate
```

Or run individual migrations as needed. The migration system will track which ones have been applied.
