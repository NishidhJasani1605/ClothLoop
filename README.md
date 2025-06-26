# ClothLoop - Clothing Rental Platform

ClothLoop is a modern clothing rental platform that connects people who want to rent clothing items with those who have items available for rent. This platform provides a sustainable and cost-effective alternative to buying clothing for one-time occasions.

![ClothLoop Logo](frontend/assets/images/logo_f.png)

## 📋 Table of Contents
- [Features](#features)
- [Technologies Used](#technologies-used)
- [Installation](#installation)
- [Usage](#usage)
- [Project Structure](#project-structure)
- [User Roles](#user-roles)
- [API Endpoints](#api-endpoints)
- [Future Enhancements](#future-enhancements)
- [Contributors](#contributors)
- [License](#license)

## ✨ Features

### Core Features
- **User Authentication**: Secure login and registration system for buyers and sellers
- **Product Listings**: Browse clothing items available for rent
- **Search & Filter**: Find products by category, occasion, and price
- **Location-Based Discovery**: Find rental items near your location
- **Wishlist**: Save items for later viewing
- **User Profiles**: Manage your account information and preferences

### Buyer Features
- View available clothing for rent
- Search and filter items by category, price, and occasion
- Add items to wishlist
- View nearby listings based on location
- Contact sellers directly

### Seller Features
- List clothing items for rent
- Manage product listings
- Update shop information
- Respond to buyer inquiries
- Track product interest

## 🔧 Technologies Used

### Frontend
- HTML5
- CSS3
- JavaScript (ES6+)
- Responsive design principles
- FontAwesome for icons
- Google Fonts

### Backend
- PHP
- MySQL
- RESTful API architecture

### Key Features Implementation
- Geolocation using the Browser Geolocation API
- Distance calculation (Haversine formula) for nearby listings
- Local storage for persistent wishlist functionality
- Animation and transitions for enhanced UX
- Responsive design for all device sizes

## 📥 Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- XAMPP/WAMP/MAMP (recommended for local development)

### Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/ClothLoop.git
   ```

2. **Set up the database**
   - Import the database schema from `database/clothloop.sql`
   - Configure the database connection in `backend/config/database.php`

3. **Configure the web server**
   - Set the document root to the project root directory
   - Ensure that PHP is properly configured

4. **Start the server**
   - If using XAMPP/WAMP/MAMP, start Apache and MySQL services
   - Navigate to `http://localhost/ClothLoop` in your browser

## 💻 Usage

### Buyer Account
1. Register as a buyer or use test credentials (Email: buyer@gmail.com, Password: buyer123)
2. Browse available clothing items on the dashboard
3. Use the search function or filter by category (Men, Women, Kids)
4. Sort items by price or find nearby items
5. Add items to your wishlist by clicking the heart icon
6. View an item's details by clicking "View Details"
7. Contact the seller through the provided information

### Seller Account
1. Register as a seller or use test credentials (Email: seller@gmail.com, Password: seller123)
2. Add products by providing details and uploading images
3. Manage your listings through the seller dashboard
4. Update your shop information including location
5. Respond to buyer inquiries

## 📁 Project Structure

```
ClothLoop/
├── backend/
│   ├── api/           # RESTful API endpoints
│   ├── config/        # Database and app configuration
│   ├── uploads/       # Product images and user uploads
│   └── utils/         # Helper functions and utilities
├── frontend/
│   ├── assets/        # Images, icons, and static resources
│   ├── js/            # JavaScript files
│   └── pages/         # HTML pages and templates
│       ├── about/     # About pages
│       ├── account/   # Account management pages
│       ├── auth/      # Authentication pages
│       ├── buyer/     # Buyer-specific pages
│       └── seller/    # Seller-specific pages
└── home.html          # Main entry point
```

## 👥 User Roles

### Buyer
- Browse and search products
- Add products to wishlist
- View nearby listings
- Contact sellers

### Seller
- Manage product listings
- Update shop information
- View interested buyers
- Respond to inquiries

## 🔄 API Endpoints

### Authentication
- `/backend/api/users/signup_process.php` - User registration
- `/backend/api/users/login_process.php` - User login

### Products
- `/backend/api/products/get_products.php` - Get all products or filter by category
- `/backend/api/products/direct_image.php` - Get product images
- `/backend/api/getproduct.php` - Get detailed product information

### User Profiles
- `/backend/api/buyers/get_buyer_profile.php` - Get buyer profile information
- `/backend/api/buyers/toggle_interest.php` - Toggle interest in a product

## 🚀 Future Enhancements

- **Messaging System**: In-app messaging between buyers and sellers
- **Reviews and Ratings**: Allow users to rate and review transactions
- **Advanced Filtering**: More detailed search and filter options

## 👨‍💻 Contributors

- Frontend Developer: Nishidh Jasani - [LinkedIn](http://www.linkedin.com/in/nishidh-jasani-n1605) | [GitHub](https://github.com/NishidhJasani1605)
- Backend Developers: 
  - Aaditya Desai - [LinkedIn](http://www.linkedin.com/in/aaditya-desai1) | [GitHub](https://github.com/aaditya-desai1)
  - Aaryan Joshi - [LinkedIn](https://www.linkedin.com/in/aaryan-joshi-36114a16b/) | [GitHub](https://github.com/Aaryan4144)
- Database Management: Yash Jariwala - [LinkedIn](http://www.linkedin.com/in/yashjariwala1303) | [GitHub](https://github.com/neel3103)

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

---

© 2024 ClothLoop. All rights reserved. 