# Fresh Harvest - Fruits and Harvesting Website

A comprehensive website for connecting fruit farmers with consumers, featuring fruit catalogs, harvest calendars, and e-commerce functionality.

## Features

- **Homepage with Seasonal Highlights**: Showcases current in-season fruits and harvesting tips
- **User Registration & Login**: Different roles for Admin, Seller (Farmer), and Buyer (Consumer)
- **Fruit Catalog**: Browseable listings with images, descriptions, pricing, and availability
- **Harvesting Schedule/Calendar**: Interactive calendar showing harvest periods for different fruits
- **Search & Filter Functionality**: Find fruits by name, harvest time, seller, etc.
- **Order & Delivery System**: Shopping cart, checkout, and delivery options
- **Role-Based Dashboards**: For Farmers, Consumers, and Website Administrators
- **Review and Rating System**: Rate and review fruits and sellers

## Technology Stack

- HTML5, CSS3
- JavaScript
- PHP
- MySQL (via XAMPP)

## Installation & Setup

### Prerequisites

- XAMPP (or similar local server environment with PHP 7.0+ and MySQL)
- Web browser
- Text editor

### Steps

1. **Install XAMPP**
   - Download and install XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)
   - Start the Apache and MySQL services from the XAMPP Control Panel

2. **Clone the repository**
   - Clone or download this repository to your XAMPP's htdocs folder (usually at `C:\xampp\htdocs\` on Windows or `/Applications/XAMPP/htdocs/` on macOS)
   - Rename the folder to "fresh_harvest" if necessary

3. **Set up the database**
   - Open phpMyAdmin by navigating to `http://localhost/phpmyadmin` in your browser
   - Create a new database named "fresh_harvest"
   - Import the database structure from `database/db_setup.sql`

4. **Configure the website**
   - Open `includes/config.php` and ensure the site URL is correct (default is `http://localhost/fresh_harvest`)
   - Check the database connection details in `includes/db_connect.php` (default is localhost, username "root", no password)

5. **Access the website**
   - Navigate to `http://localhost/fresh_harvest` in your web browser
   - Admin login (username: admin, password: admin123)

## Directory Structure

- `/admin` - Admin panel files
- `/buyer` - Buyer (consumer) account files
- `/css` - Stylesheets
- `/database` - Database setup and SQL files
- `/images` - Image assets
- `/includes` - Shared PHP includes, functions, and configuration
- `/js` - JavaScript files
- `/pages` - Main website pages
- `/seller` - Seller (farmer) dashboard files

## Usage

### Admin Account
- Manage all users (sellers and buyers)
- Approve seller registrations
- Monitor orders and payments
- Add/edit fruits and categories

### Seller Account
- Add or edit fruits/products
- Track stock and sales
- View consumer reviews and ratings

### Buyer Account
- Browse and order fruits
- Track orders and deliveries
- Save favorite items
- Leave reviews and ratings

## Contributing

1. Fork the repository
2. Create your feature branch: `git checkout -b feature/my-new-feature`
3. Commit your changes: `git commit -m 'Add some feature'`
4. Push to the branch: `git push origin feature/my-new-feature`
5. Submit a pull request

## License

This project is licensed under the MIT License - see the LICENSE file for details. 
