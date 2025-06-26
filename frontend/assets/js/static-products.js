// Static products data for testing
const staticProducts = [
    {
        id: 1001,
        name: "Elegant Wedding Dress",
        description: "Beautiful white wedding dress with lace details and a long train. Perfect for your special day.",
        size: "M",
        category: "Women",
        price_per_day: 1499.99,
        contact: "9876543210",
        whatsapp: "9876543210",
        terms: "Security deposit required. Dry clean only. Return within 7 days.",
        status: "active",
        shop_name: "Bridal Elegance",
        image_url: "https://picsum.photos/id/64/800/1000",
        created_at: "2023-11-15 10:30:00"
    },
    {
        id: 1002,
        name: "Men's Formal Black Suit",
        description: "Classic black suit for formal occasions. Includes jacket and pants.",
        size: "L",
        category: "Men",
        price_per_day: 799.99,
        contact: "9876543211",
        whatsapp: "9876543211",
        terms: "Dry clean only. Return in original condition.",
        status: "active",
        shop_name: "Formal Attire Co.",
        image_url: "https://picsum.photos/id/1005/800/1000",
        created_at: "2023-12-05 14:20:00"
    },
    {
        id: 1003,
        name: "Kids Party Dress",
        description: "Colorful party dress for young girls. Comfortable and stylish.",
        size: "8",
        category: "Kids",
        price_per_day: 299.99,
        contact: "9876543212",
        whatsapp: "9876543212",
        terms: "Gentle wash. No stains.",
        status: "active",
        shop_name: "Little Fashions",
        image_url: "https://picsum.photos/id/177/800/1000",
        created_at: "2024-01-20 09:15:00"
    },
    {
        id: 1004,
        name: "Traditional Saree",
        description: "Elegant silk saree with golden border. Perfect for traditional occasions.",
        size: "Free",
        category: "Women",
        price_per_day: 999.99,
        contact: "9876543213",
        whatsapp: "9876543213",
        terms: "Dry clean only. Security deposit required.",
        status: "active",
        shop_name: "Heritage Attire",
        image_url: "https://picsum.photos/id/325/800/1000",
        created_at: "2024-02-10 16:45:00"
    },
    {
        id: 1005,
        name: "Men's Sherwani",
        description: "Royal looking sherwani for weddings and special occasions.",
        size: "XL",
        category: "Men",
        price_per_day: 1299.99,
        contact: "9876543214",
        whatsapp: "9876543214",
        terms: "Handle with care. Return within 5 days.",
        status: "active",
        shop_name: "Royal Garments",
        image_url: "https://picsum.photos/id/1059/800/1000",
        created_at: "2024-03-01 11:30:00"
    },
    {
        id: 1006,
        name: "Summer Beach Dress",
        description: "Light and airy beach dress perfect for summer vacations.",
        size: "S",
        category: "Women",
        price_per_day: 399.99,
        contact: "9876543215",
        whatsapp: "9876543215",
        terms: "Machine washable. Return clean.",
        status: "active",
        shop_name: "Summer Vibes",
        image_url: "https://picsum.photos/id/225/800/1000",
        created_at: "2024-03-15 13:20:00"
    },
    {
        id: 1007,
        name: "Designer Gown",
        description: "Stunning designer evening gown with sequin details.",
        size: "M",
        category: "Women",
        price_per_day: 2499.99,
        contact: "9876543216",
        whatsapp: "9876543216",
        terms: "Professional cleaning required. High security deposit.",
        status: "active",
        shop_name: "Glamour Collection",
        image_url: "https://picsum.photos/id/21/800/1000",
        created_at: "2024-03-20 17:10:00"
    },
    {
        id: 1008,
        name: "Casual Daytime Dress",
        description: "Comfortable casual dress for everyday wear.",
        size: "L",
        category: "Women",
        price_per_day: 249.99,
        contact: "9876543217",
        whatsapp: "9876543217",
        terms: "Simple wash. Return within 3 days.",
        status: "active",
        shop_name: "Daily Fashion",
        image_url: "https://picsum.photos/id/65/800/1000",
        created_at: "2024-03-22 10:05:00"
    }
];

// Function to get static products (simulates API call)
function getStaticProducts() {
    return {
        status: 'success',
        products: staticProducts
    };
}

// Function to get a product by ID
function getStaticProductById(id) {
    const product = staticProducts.find(product => product.id === parseInt(id));
    if (product) {
        return {
            status: 'success',
            product: product
        };
    } else {
        return {
            status: 'error',
            message: 'Product not found'
        };
    }
}

// Get the most recent products (for dashboard)
function getRecentStaticProducts(limit = 4) {
    // Sort by created_at date (most recent first)
    const sortedProducts = [...staticProducts].sort((a, b) => 
        new Date(b.created_at) - new Date(a.created_at)
    );
    
    // Return the specified number of products
    return {
        status: 'success',
        products: sortedProducts.slice(0, limit)
    };
} 