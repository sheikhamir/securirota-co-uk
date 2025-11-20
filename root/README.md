# Super Admin Root Directory

This directory contains all super admin functionality for the SecuriRota multi-tenant platform. Access is restricted to users with the `super_admin` role.

## Directory Structure

```
/root/
├── index.php              # Main super admin control panel
├── dashboard.php           # Comprehensive system dashboard with analytics
├── security.php            # Security monitoring and management
├── onboarding.php          # Company onboarding wizard
├── migration.php           # Data migration tools
├── branding.php            # Company branding management
├── settings.php            # Global system settings
├── .htaccess              # Security configuration
├── api/                   # Super admin API endpoints
│   ├── security/          # Security-related APIs
│   └── migration/         # Migration-related APIs
└── assets/                # Super admin specific assets
    ├── css/               # Custom stylesheets
    └── js/                # Custom JavaScript

```

## Features

### 🏠 Control Panel (`index.php`)
- Clean, modern interface for super admin access
- Real-time system statistics
- Quick navigation to all admin functions
- System status monitoring

### 📊 Dashboard (`dashboard.php`)
- Comprehensive system analytics with Chart.js
- Company management with detailed statistics
- Real-time monitoring of platform health
- Growth analytics and subscription distribution

### 🔐 Security Center (`security.php`)
- Security event monitoring and analysis
- Active session management
- Rate limiting configuration
- Security settings management
- Threat detection and response

### 🎯 Company Onboarding (`onboarding.php`)
- 4-step wizard for new company setup
- Automated admin user creation
- Subscription plan selection
- Default branding configuration

### 🔄 Migration Tools (`migration.php`)
- 5-step migration wizard for single-tenant conversions
- Compatibility checking
- Automated backup creation
- Data migration with verification

### 🎨 Branding Manager (`branding.php`)
- Company-specific theme customization
- Color scheme management
- Logo upload and management
- Feature toggle configuration

### ⚙️ System Settings (`settings.php`)
- Global platform configuration
- Email settings management
- Security policy configuration
- Maintenance mode controls

## Security Features

### Access Control
- Session-based authentication required
- Role verification on every page
- Company isolation enforcement
- CSRF protection on all forms

### Additional Protection
- Custom `.htaccess` security headers
- Rate limiting for admin endpoints
- Directory browsing disabled
- Sensitive file access blocked

### Monitoring
- All actions logged to activity log
- Security events tracked
- Failed access attempts monitored
- Session management with timeout

## API Endpoints

### Security APIs (`/api/security/`)
- `chart_data.php` - Security analytics data
- `events.php` - Security event management
- `active_sessions.php` - Session monitoring
- `terminate_session.php` - Remote session termination
- `update_settings.php` - Security configuration

### Migration APIs (`/api/migration/`)
- `compatibility_check.php` - System compatibility validation
- `create_backup.php` - Backup creation
- `create_default_company.php` - Company setup
- `migrate_step.php` - Incremental data migration
- `verify_migration.php` - Migration verification

## Usage Guidelines

### Access Requirements
1. Must be logged in with `super_admin` role
2. Session must be active and valid
3. Access logged for security audit

### Navigation
- Start from `index.php` for main control panel
- Use provided navigation links between sections
- Logout properly when finished

### Security Best Practices
- Always logout when finished
- Monitor security events regularly
- Review active sessions periodically
- Keep security settings updated

## Customization

### Styling
- Custom CSS can be added to `/assets/css/`
- Bootstrap 5 framework used throughout
- Font Awesome icons included

### Functionality
- Additional features can be added as new PHP files
- API endpoints should follow existing patterns
- Security middleware should be applied to all endpoints

## Maintenance

### Regular Tasks
- Review security logs weekly
- Check system health status
- Monitor company growth metrics
- Update security settings as needed

### Updates
- Test new features in staging environment
- Backup before major changes
- Update documentation as needed
- Monitor for security updates

## Support

For technical support or questions about super admin functionality:
1. Check system logs for error details
2. Review security events for issues
3. Contact platform administrator
4. Refer to main SecuriRota documentation

---

**Last Updated:** September 16, 2025  
**Version:** 2.0  
**Platform:** SecuriRota Multi-Tenant SAAS
