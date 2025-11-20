# SecuriRota - Security Company Rota & Management System

A comprehensive web-based management system designed specifically for security companies to handle officer scheduling, compliance tracking, client management, and operational reporting.

## Features

### 🔐 **Authentication & User Management**
- Secure login system with role-based access
- Admin and Officer user roles
- Session management and security

### 👥 **Officer Management**
- Complete officer profile management
- SIA license and compliance tracking
- Document management and expiry alerts
- Personal details, emergency contacts, and bank information
- Employment status and pay rate management

### 🏢 **Client & Site Management**
- Client company profiles with contacts
- Site location management with addresses
- Billing rates and contract information
- Service requirements tracking

### 📅 **Rota Planning & Scheduling**
- Interactive drag-and-drop rota calendar
- Weekly shift scheduling interface
- Officer allocation and shift management
- Multiple shift statuses (Allocated, Confirmed, Declined, Completed)
- Quick shift creation and editing

### 📊 **Reporting & Analytics**
- Comprehensive deployment reports
- Officer hours and performance tracking
- Client billing summaries
- Export to Excel and PDF formats
- Customizable date ranges and filters

### 💰 **Invoicing & Billing**
- Automated officer invoice generation
- Client billing calculations
- Period-based payment summaries
- Export options for accounting systems

### 🚀 **Operations Management**
- Real-time deployment tracking
- Shift status monitoring
- Officer availability management
- Operational dashboard with key metrics

### ⚙️ **System Administration**
- System settings and configuration
- User account management
- Data backup and maintenance tools
- Comprehensive support documentation

## Technical Specifications

### **Backend**
- **Language:** PHP 8.x
- **Database:** MySQL 8.x
- **Architecture:** MVC pattern with modular design
- **Security:** PDO prepared statements, session management, input validation

### **Frontend**
- **Styling:** Custom responsive CSS with CSS Grid and Flexbox
- **JavaScript:** Vanilla ES6+ with modern features
- **UI Components:** Modal dialogs, drag-and-drop, interactive forms
- **Charts:** Chart.js for analytics and reporting
- **Icons:** Font Awesome 6

### **Features**
- **Responsive Design:** Works on desktop, tablet, and mobile devices
- **Cross-browser Support:** Modern browsers (Chrome, Firefox, Safari, Edge)
- **Accessibility:** WCAG compliant interface elements
- **Performance:** Optimized queries and caching strategies

## Installation

### **Prerequisites**
- Web server (Apache/Nginx)
- PHP 8.0 or higher
- MySQL 8.0 or higher
- Modern web browser

### **Setup Steps**

1. **Download/Clone the system files**
   ```bash
   git clone <repository-url>
   # or download and extract the zip file
   ```

2. **Database Setup**
   ```sql
   CREATE DATABASE rota_system;
   USE rota_system;
   SOURCE database_schema.sql;
   ```

3. **Configuration**
   - Update database credentials in `config/database.php`
   - Set the correct `BASE_URL` in `config/config.php`
   - Ensure proper file permissions for uploads directory

4. **Web Server Configuration**
   - Point document root to the project directory
   - Ensure mod_rewrite is enabled (for Apache)
   - Configure SSL certificate for production use

5. **First Login**
   - Username: `admin`
   - Password: `password`
   - **Important:** Change default password immediately

## Usage Guide

### **Getting Started**
1. Log in with admin credentials
2. Add your first client company
3. Create site locations for the client
4. Add officer profiles with compliance details
5. Start scheduling shifts in the Rota planner

### **Daily Operations**
- Check dashboard for today's deployments
- Manage shift confirmations and changes
- Track officer attendance and completion
- Generate reports for client billing

### **Weekly Tasks**
- Plan next week's rota schedule
- Review officer availability
- Generate deployment reports
- Process officer invoices

### **Monthly Tasks**
- Generate client billing reports
- Review compliance document expiries
- Update officer pay rates if needed
- Backup system data

## File Structure

```
rota/
├── api/                    # REST API endpoints
│   ├── create_shift.php
│   ├── get_shift.php
│   ├── update_shift.php
│   └── delete_shift.php
├── assets/                 # Static assets
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── main.js
├── config/                 # Configuration files
│   ├── config.php
│   └── database.php
├── includes/              # Common includes
│   ├── header.php
│   └── footer.php
├── pages/                 # Application pages
│   ├── clients.php
│   ├── dashboard.php
│   ├── deployment.php
│   ├── invoices.php
│   ├── officers.php
│   ├── reports.php
│   ├── rota.php
│   ├── screening.php
│   ├── settings.php
│   ├── sites.php
│   └── support.php
├── login.php              # Authentication
├── logout.php
├── index.php              # Entry point
└── database_schema.sql    # Database structure
```

## Security Features

- **Password Hashing:** Secure password storage using PHP's password_hash()
- **SQL Injection Protection:** PDO prepared statements throughout
- **Session Security:** Secure session configuration and management
- **Input Validation:** Server-side validation on all user inputs
- **Role-based Access:** Different permissions for Admin and Officer roles
- **CSRF Protection:** Token-based form protection (recommended for production)

## Support & Maintenance

### **Regular Maintenance**
- Monitor disk space and database size
- Regular backups (daily recommended)
- Update officer compliance documents
- Review system logs for errors
- Keep software dependencies updated

### **Troubleshooting**
- Check PHP error logs for application issues
- Verify database connectivity
- Ensure proper file permissions
- Review browser console for JavaScript errors

### **Performance Optimization**
- Enable PHP OPcache for better performance
- Use database indexing for large datasets
- Implement caching for frequently accessed data
- Optimize images and static assets

## Support

For technical support or questions:
- **Documentation:** Built-in support guides available in the system
- **Email:** support@securirota.com
- **Phone:** +44 20 1234 5678
- **Hours:** Monday-Friday, 9AM-5PM GMT

## License

This software is proprietary and designed specifically for security company operations. 

## Version History

- **v1.0.0** - Initial release with core functionality
- **v1.1.0** - Added deployment tracking and advanced reporting
- **v1.2.0** - Enhanced UI/UX with drag-and-drop rota interface
- **v1.3.0** - Added comprehensive invoicing and billing features

---

**SecuriRota** - Streamlining security operations with intelligent workforce management.
