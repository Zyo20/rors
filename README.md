# Restaurant Ordering and Reservation System (RORS)

A comprehensive web-based system for restaurant management, including online ordering, reservations, kitchen management, and admin dashboard.

## Features

- **Customer Portal**
  - Menu browsing with detailed item information and allergen details
  - Online ordering system with cart functionality
  - Real-time order tracking and history
  - Table reservation system with confirmation emails
  - Customer profiles with order history and preferences
  - Secure checkout process with multiple payment options

- **Kitchen Dashboard**
  - Real-time order management interface
  - Order status updates (pending, confirmed, preparing, ready)
  - Order prioritization for efficient workflow
  - Special instructions visibility for each order
  - Auto-refreshing interface for latest orders

- **Admin Dashboard**
  - Comprehensive reservation management
  - Order tracking and detailed reporting
  - Menu item management (add, edit, delete)
  - Inventory control with low stock alerts
  - User management for staff and customers
  - Export functionality for reports
  - System notifications and alerts

- **User Dashboard**
  - Order history and details
  - Order cancellation options
  - Personal profile management
  - Ability to reorder previous orders
  - Reservation management

## Technologies Used

- **Frontend**: HTML, Tailwind CSS, JavaScript
- **Backend**: PHP
- **Database**: MySQL
- **Icons**: Font Awesome
- **Framework**: Pure PHP (no framework)

## Installation

1. Clone this repository to your local machine:
   ```
   git clone https://github.com/yourusername/rors.git
   ```

2. Import the database schema:
   - Create a MySQL database named `rors_db`
   - The schema will be automatically created when you first run the application

3. Configure your database connection:
   - Open `config/database.php`
   - Update the database credentials if necessary

4. Place the project in your web server directory (e.g., htdocs for XAMPP)

5. Visit the application in your browser:
   ```
   http://localhost/rors/
   ```

6. For testing purposes, you can install sample data using:
   ```
   http://localhost/rors/install/sample_data.php
   ```

## User Roles

1. **Customer**
   - Browse menu and view item details
   - Place and track orders
   - Make and manage reservations
   - View order history

2. **Kitchen Staff**
   - View and manage active orders
   - Update order statuses in real-time
   - View special instructions
   - Prioritize order preparation

3. **Admin/Manager**
   - Manage menu items and categories
   - Process and export reservations
   - View detailed reports and analytics
   - Manage user accounts and permissions
   - Monitor and update inventory

## Project Structure

- `admin/` - Admin dashboard and management interfaces
- `assets/` - CSS, JavaScript, and image files
- `components/` - Reusable UI components
- `config/` - Configuration files
- `dashboard/` - Customer dashboard interfaces
- `includes/` - Common PHP files and functions
- `install/` - Installation and sample data scripts
- `kitchen/` - Kitchen staff dashboard
- `pages/` - Customer-facing pages

## Screenshots

(Add screenshots of key pages when available)

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Contributors

- Your Name
- Other Contributors

## Acknowledgements

- Font Awesome for icons
- Tailwind CSS for styling
- All open-source contributors 