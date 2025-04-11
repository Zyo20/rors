<?php
include 'includes/header.php';
require_once 'includes/functions.php';

// Get featured menu items
$conn = getConnection();
$sql = "SELECT * FROM menu_items WHERE is_available = 1 ORDER BY RAND() LIMIT 6";
$result = $conn->query($sql);
$featuredItems = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $featuredItems[] = $row;
    }
}
$conn->close();
?>

<!-- Hero Section -->
<section class="bg-dark text-white py-16 mb-12">
    <div class="container mx-auto px-4 flex flex-col md:flex-row items-center">
        <div class="md:w-1/2 mb-8 md:mb-0">
            <h1 class="text-4xl md:text-5xl font-bold mb-4">Welcome to Our Restaurant</h1>
            <p class="text-xl mb-6">Experience the best dining with our Restaurant Ordering and Reservation System.</p>
            <div class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
                <a href="pages/menu.php" class="bg-secondary text-white px-6 py-3 rounded-md font-semibold text-center hover:bg-opacity-90 transition">
                    View Our Menu
                </a>
                <a href="pages/reservation.php" class="bg-white text-primary px-6 py-3 rounded-md font-semibold text-center hover:bg-gray-100 transition">
                    Book a Table
                </a>
            </div>
        </div>
        <div class="md:w-1/2">
            <img src="assets/images/hero-image.jpg" alt="Restaurant Interior" class="rounded-lg shadow-lg w-full">
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="mb-12">
    <div class="container mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-8">Our Services</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-white p-6 rounded-lg shadow-md text-center">
                <div class="bg-primary text-white rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-utensils text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">Online Ordering</h3>
                <p class="text-gray-600">Order your favorite meals from our menu online for takeaway or delivery.</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md text-center">
                <div class="bg-secondary text-white rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-calendar-alt text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">Table Reservations</h3>
                <p class="text-gray-600">Book your table in advance to ensure availability for your preferred time.</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md text-center">
                <div class="bg-accent text-white rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-credit-card text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">Secure Payments</h3>
                <p class="text-gray-600">Pay securely online with multiple payment options including credit cards and digital wallets.</p>
            </div>
        </div>
    </div>
</section>

<!-- Featured Menu Items -->
<section class="mb-12">
    <div class="container mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-8">Featured Menu Items</h2>
        <?php if (count($featuredItems) > 0): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($featuredItems as $item): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <img src="<?php echo !empty($item['image_path']) ? $item['image_path'] : 'assets/images/default-food.jpg'; ?>" 
                             alt="<?php echo $item['name']; ?>" 
                             class="w-full h-48 object-cover">
                        <div class="p-4">
                            <h3 class="text-xl font-bold mb-2"><?php echo $item['name']; ?></h3>
                            <p class="text-gray-600 mb-4"><?php echo $item['description']; ?></p>
                            <div class="flex justify-between items-center">
                                <span class="text-primary font-bold"><?php echo formatPrice($item['price']); ?></span>
                                <a href="pages/menu_item.php?id=<?php echo $item['id']; ?>" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-opacity-90 transition">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-8">
                <a href="pages/menu.php" class="inline-block bg-primary text-white px-6 py-3 rounded-md font-semibold hover:bg-opacity-90 transition">
                    View Full Menu
                </a>
            </div>
        <?php else: ?>
            <div class="text-center p-8 bg-gray-100 rounded-lg">
                <p class="text-gray-600">No featured items available at the moment. Please check our full menu.</p>
                <a href="pages/menu.php" class="inline-block mt-4 bg-primary text-white px-6 py-3 rounded-md font-semibold hover:bg-opacity-90 transition">
                    View Full Menu
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Testimonials Section -->
<section class="bg-gray-100 py-12 mb-12">
    <div class="container mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-8">What Our Customers Say</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="flex items-center mb-4">
                    <div class="text-yellow-400 flex">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                </div>
                <p class="text-gray-600 mb-4">"The ordering system is so easy to use! I love being able to customize my order and schedule a pickup time. The food is always ready when promised."</p>
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-gray-300 rounded-full mr-3"></div>
                    <div>
                        <h4 class="font-semibold">John Smith</h4>
                        <p class="text-gray-500 text-sm">Regular Customer</p>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="flex items-center mb-4">
                    <div class="text-yellow-400 flex">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                </div>
                <p class="text-gray-600 mb-4">"Making a reservation was so simple. I appreciated receiving an email confirmation and reminder. Our table was ready exactly at our reserved time."</p>
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-gray-300 rounded-full mr-3"></div>
                    <div>
                        <h4 class="font-semibold">Sarah Johnson</h4>
                        <p class="text-gray-500 text-sm">First-time Visitor</p>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="flex items-center mb-4">
                    <div class="text-yellow-400 flex">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-alt"></i>
                    </div>
                </div>
                <p class="text-gray-600 mb-4">"The menu online shows all allergen information which is so helpful for someone with dietary restrictions like me. Staff is always accommodating!"</p>
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-gray-300 rounded-full mr-3"></div>
                    <div>
                        <h4 class="font-semibold">Michael Chen</h4>
                        <p class="text-gray-500 text-sm">Monthly Visitor</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="mb-12">
    <div class="container mx-auto px-4">
        <div class="bg-primary text-white rounded-lg shadow-lg p-8 md:p-12">
            <div class="text-center">
                <h2 class="text-3xl font-bold mb-4">Ready to Experience Our Delicious Food?</h2>
                <p class="text-xl mb-6">Place an order now or book a table for a memorable dining experience.</p>
                <div class="flex flex-col sm:flex-row justify-center space-y-4 sm:space-y-0 sm:space-x-4">
                    <a href="pages/order.php" class="bg-white text-primary px-6 py-3 rounded-md font-semibold hover:bg-gray-100 transition">
                        Order Online
                    </a>
                    <a href="pages/cart.php" class="bg-secondary text-white px-6 py-3 rounded-md font-semibold hover:bg-opacity-90 transition">
                        <i class="fas fa-shopping-cart mr-2"></i> View Cart
                    </a>
                    <a href="pages/reservation.php" class="bg-accent text-white px-6 py-3 rounded-md font-semibold hover:bg-opacity-90 transition">
                        Book a Table
                    </a>
                </div>
                
                <!-- Sample Data Installation Link -->
                <div class="mt-8 text-sm">
                    <a href="install/sample_data.php" class="bg-gray-700 text-white px-4 py-2 rounded-md hover:bg-gray-600 transition inline-flex items-center">
                        <i class="fas fa-database mr-2"></i> Install Sample Data
                    </a>
                    <p class="mt-2 text-gray-200">
                        <i class="fas fa-info-circle mr-1"></i> For testing: Creates sample users and menu categories
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?> 