    </main>
    <footer class="bg-dark text-white py-8">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-xl font-bold mb-4">Restaurant Ordering and Reservation System</h3>
                    <p class="mb-4">Providing efficient and convenient service for our customers.</p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-white hover:text-secondary"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white hover:text-secondary"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white hover:text-secondary"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div>
                    <h3 class="text-xl font-bold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="index.php" class="text-white hover:text-secondary">Home</a></li>
                        <li><a href="pages/menu.php" class="text-white hover:text-secondary">Menu</a></li>
                        <li><a href="pages/reservation.php" class="text-white hover:text-secondary">Reservations</a></li>
                        <li><a href="pages/order.php" class="text-white hover:text-secondary">Order Online</a></li>
                        <li><a href="pages/contact.php" class="text-white hover:text-secondary">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xl font-bold mb-4">Contact Us</h3>
                    <address class="not-italic">
                        <p class="mb-2"><i class="fas fa-map-marker-alt mr-2"></i> 123 Restaurant Street, City, Country</p>
                        <p class="mb-2"><i class="fas fa-phone mr-2"></i> +1 123 456 7890</p>
                        <p class="mb-2"><i class="fas fa-envelope mr-2"></i> info@rors.com</p>
                    </address>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-6 text-center">
                <p>&copy; <?php echo date('Y'); ?> Restaurant Ordering and Reservation System. All rights reserved.</p>
            </div>
        </div>
    </footer>
    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-btn').addEventListener('click', function() {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        });
    </script>
</body>
</html>
<?php
// Flush the output buffer and send content to browser
if (ob_get_length()) {
    ob_end_flush();
}
?> 