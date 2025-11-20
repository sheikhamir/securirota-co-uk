#!/bin/bash
# Database Backup Script for Multi-Tenant Migration
# Run this BEFORE running migrations

echo "🔄 Creating database backup before migration..."

# Create backup directory
BACKUP_DIR="backups/$(date +%Y%m%d_%H%M%S)_pre_migration"
mkdir -p "$BACKUP_DIR"

# Database credentials
DB_USER="rohabae1_rota"
DB_PASS="-MtMr]i!hs?5Y1A*"
DB_NAME="rohabae1_rota"

# Create full database backup
echo "📦 Creating full database backup..."
mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_DIR/full_database_backup.sql"

# Create structure-only backup
echo "🏗️ Creating structure-only backup..."
mysqldump -u "$DB_USER" -p"$DB_PASS" --no-data "$DB_NAME" > "$BACKUP_DIR/structure_only.sql"

# Create data-only backup for critical tables
echo "📊 Creating data-only backup for critical tables..."
mysqldump -u "$DB_USER" -p"$DB_PASS" --no-create-info "$DB_NAME" users officers clients sites shifts > "$BACKUP_DIR/critical_data_only.sql"

# Show table counts before migration
echo "📈 Recording table counts before migration..."
mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "
SELECT 
  'users' as table_name, COUNT(*) as row_count FROM users
UNION ALL SELECT 'officers', COUNT(*) FROM officers  
UNION ALL SELECT 'clients', COUNT(*) FROM clients
UNION ALL SELECT 'sites', COUNT(*) FROM sites
UNION ALL SELECT 'shifts', COUNT(*) FROM shifts
UNION ALL SELECT 'documents', COUNT(*) FROM documents;" > "$BACKUP_DIR/table_counts_before.txt"

echo "✅ Backup completed in: $BACKUP_DIR"
echo ""
echo "📁 Backup files created:"
ls -la "$BACKUP_DIR"
echo ""
echo "🔄 To restore if needed:"
echo "mysql -u $DB_USER -p'$DB_PASS' $DB_NAME < $BACKUP_DIR/full_database_backup.sql"
