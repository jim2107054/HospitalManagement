# Hospital Management Dashboard

A comprehensive web-based hospital management system with a focus on filtering processes using SQL commands. This dashboard provides complete CRUD operations for managing patients, doctors, departments, appointments, and medical reports.

## Features

### 🏥 Core Modules
- **Overview Dashboard** - Statistics and charts for all hospital data
- **Patients Management** - Complete patient records with filtering
- **Departments Management** - Hospital department organization
- **Doctors Management** - Medical staff management
- **Appointments Management** - Appointment scheduling and tracking
- **Medical Reports** - Patient medical history and records

### 🔍 Advanced Filtering
- **SQL-based Filtering** - All filtering is done using SQL queries for optimal performance
- **Multiple Filter Options** - Date ranges, status, gender, blood group, departments, etc.
- **Real-time Search** - Search across multiple fields simultaneously
- **Dynamic Filtering** - Apply and clear filters instantly

### 📊 Data Visualization
- **Interactive Charts** - Pie charts, bar charts, and line graphs
- **Real-time Statistics** - Live counters and metrics
- **Responsive Dashboard** - Works on desktop, tablet, and mobile

### ⚡ Modern Features
- **CRUD Operations** - Create, Read, Update, Delete with modal popups
- **Responsive Design** - Mobile-friendly interface
- **Professional UI** - Clean and modern design
- **Error Handling** - Comprehensive error management
- **Data Validation** - Form validation and security

## Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Charts**: Chart.js
- **Icons**: Font Awesome
- **Design**: Custom CSS with Flexbox/Grid

## Project Structure

```
hospital-dashboard/
├── index.html                 # Main dashboard page
├── database.sql              # Database schema and sample data
├── css/
│   └── style.css             # Main stylesheet
├── js/
│   ├── main.js               # Core dashboard functionality
│   ├── crud.js               # CRUD operations
│   └── charts.js             # Chart management
├── php/
│   ├── overview.php          # Overview statistics
│   ├── patients.php          # Patient management
│   ├── departments.php       # Department management
│   ├── doctors.php           # Doctor management
│   ├── appointments.php      # Appointment management
│   └── medical-reports.php   # Medical reports management
└── includes/
    └── database.php          # Database connection and utilities
```

## Database Schema

### Tables
1. **departments** - Hospital departments (Cardiology, Neurology, etc.)
2. **doctors** - Medical staff with specializations
3. **patients** - Patient records and information
4. **appointments** - Appointment scheduling
5. **medical_records** - Patient medical history

### Key Relationships
- Doctors belong to departments
- Appointments link patients and doctors
- Medical records track patient visits
- Departments have head doctors

## Installation & Setup

### Prerequisites
- Web server (Apache/Nginx) with PHP support
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Modern web browser

### Installation Steps

1. **Clone/Download** the project to your web server directory
   ```bash
   # If using Git
   git clone <repository-url> hospital-dashboard
   
   # Or download and extract the ZIP file
   ```

2. **Database Setup**
   - Create a new MySQL database named `hospital_management`
   - Import the database schema:
   ```sql
   mysql -u your_username -p hospital_management < database.sql
   ```

3. **Configure Database Connection**
   - Open `includes/database.php`
   - Update the database connection settings:
   ```php
   private $host = 'localhost';
   private $db_name = 'hospital_management';
   private $username = 'your_db_username';
   private $password = 'your_db_password';
   ```

4. **Web Server Configuration**
   - Ensure your web server can execute PHP files
   - Place the project in your web server's document root
   - Access via: `http://localhost/hospital-dashboard/`

5. **Permissions** (Linux/Mac)
   ```bash
   chmod 755 hospital-dashboard/
   chmod 644 hospital-dashboard/php/*
   ```

## Usage Guide

### Navigation
- Use the left sidebar to navigate between modules
- Each module has its own page with statistics and data tables
- Click on navigation items to switch between sections

### Filtering Data
1. Navigate to any module (Patients, Doctors, etc.)
2. Use the filter section above the data table
3. Select filter criteria (dates, status, categories)
4. Enter search terms for text-based filtering
5. Click "Apply Filters" to execute SQL-based filtering
6. Use "Clear" to reset all filters

### CRUD Operations
- **Create**: Click the "Add [Item]" button in each module
- **Read**: Data is displayed in tables with pagination
- **Update**: Click the edit (pencil) icon in the Actions column
- **Delete**: Click the delete (trash) icon in the Actions column
- **View**: Click the view (eye) icon for detailed information

### Dashboard Features
- **Overview**: View statistics and charts
- **Real-time Data**: All data updates automatically
- **Responsive**: Works on all device sizes
- **Export**: Use browser print or save functions

## Sample Data

The database includes sample data for testing:
- 6 departments (Cardiology, Neurology, Emergency, etc.)
- 6 doctors with different specializations
- 7 patients with various information
- Sample appointments and medical records

## Advanced Features

### SQL Filtering Examples

The system uses dynamic SQL queries for filtering. Here are some examples:

**Patient Filtering by Gender and Blood Group:**
```sql
SELECT * FROM patients 
WHERE gender = 'Male' AND blood_group = 'A+'
ORDER BY registered_at DESC
```

**Appointment Filtering by Date Range:**
```sql
SELECT a.*, p.name as patient_name, d.name as doctor_name
FROM appointments a
LEFT JOIN patients p ON a.patient_id = p.id
LEFT JOIN doctors d ON a.doctor_id = d.id
WHERE a.appointment_date BETWEEN '2025-01-01' AND '2025-12-31'
AND a.status = 'Scheduled'
```

### Chart Data Queries

**Appointment Status Distribution:**
```sql
SELECT status, COUNT(*) as count 
FROM appointments 
GROUP BY status
```

**Blood Group Distribution:**
```sql
SELECT blood_group, COUNT(*) as count 
FROM patients 
WHERE blood_group IS NOT NULL 
GROUP BY blood_group
```

## Customization

### Adding New Filters
1. Add form elements to the HTML filter section
2. Update the `getFilters()` function in JavaScript
3. Modify the corresponding PHP file to handle new filter parameters

### Adding New Fields
1. Modify the database schema
2. Update the HTML forms in `crud.js`
3. Update the PHP CRUD handlers
4. Modify the table display functions

### Styling Changes
- Modify `css/style.css` for visual changes
- Update color variables at the top of the CSS file
- Use CSS custom properties for consistent theming

## Security Features

- **SQL Injection Prevention**: All queries use prepared statements
- **Input Validation**: Server-side and client-side validation
- **Error Handling**: Secure error messages without exposing system details
- **Data Sanitization**: All user inputs are properly sanitized

## Browser Support

- Chrome 80+
- Firefox 75+
- Safari 13+
- Edge 80+

## Performance Optimization

- **Database Indexing**: Key fields are indexed for fast queries
- **Efficient Queries**: Optimized SQL queries with proper JOINs
- **Pagination**: Large datasets are paginated
- **Caching**: Browser caching for static resources

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `includes/database.php`
   - Ensure MySQL service is running
   - Verify database exists and has proper permissions

2. **Charts Not Displaying**
   - Check browser console for JavaScript errors
   - Ensure Chart.js library is loaded
   - Verify PHP scripts are returning JSON data

3. **CRUD Operations Failing**
   - Check browser network tab for failed requests
   - Verify PHP error logs
   - Ensure proper permissions on PHP files

4. **Filters Not Working**
   - Check JavaScript console for errors
   - Verify PHP filter handlers are working
   - Check SQL query syntax in PHP files

### Debug Mode
Enable PHP error reporting for development:
```php
// Add to top of PHP files for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is open source and available under the [MIT License](LICENSE).

## Support

For support and questions:
- Check the troubleshooting section
- Review the code comments
- Create an issue in the repository

---

**Created with ❤️ for hospital management efficiency**