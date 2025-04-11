<?php
include '../includes/header.php';
require_once '../includes/functions.php';

// Initialize variables
$date = '';
$time = '';
$party_size = '';
$special_request = '';
$success_message = '';
$error_message = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if user is logged in
    if (!isLoggedIn()) {
        $error_message = 'Please login to make a reservation.';
    } else {
        // Get form data
        $date = sanitize($_POST['date']);
        $time = sanitize($_POST['time']);
        $party_size = (int) sanitize($_POST['party_size']);
        $special_request = isset($_POST['special_request']) ? sanitize($_POST['special_request']) : '';
        
        // Validate inputs
        $errors = [];
        
        if (empty($date)) {
            $errors[] = 'Please select a date.';
        } elseif (strtotime($date) < strtotime(date('Y-m-d'))) {
            $errors[] = 'Please select a date in the future.';
        }
        
        if (empty($time)) {
            $errors[] = 'Please select a time.';
        }
        
        if (empty($party_size) || $party_size < 1) {
            $errors[] = 'Please select a valid party size.';
        }
        
        // If no errors, proceed with reservation
        if (empty($errors)) {
            $conn = getConnection();
            
            // Ensure user_id exists in session
            if (!isset($_SESSION['user_id'])) {
                $error_message = 'User session error. Please try logging out and logging back in.';
                $conn->close();
            } else {
                $user_id = $_SESSION['user_id'];
                
                // Check if reservation already exists
                $stmt = $conn->prepare("SELECT id FROM reservations WHERE user_id = ? AND reservation_date = ? AND reservation_time = ? AND status != 'cancelled'");
                $stmt->bind_param("iss", $user_id, $date, $time);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $error_message = 'You already have a reservation for this date and time.';
                } else {
                    // Insert new reservation
                    $stmt = $conn->prepare("INSERT INTO reservations (user_id, reservation_date, reservation_time, party_size, special_request) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("issis", $user_id, $date, $time, $party_size, $special_request);
                    
                    if ($stmt->execute()) {
                        $success_message = 'Your reservation has been successfully submitted! You will receive a confirmation soon.';
                        // Reset form fields
                        $date = '';
                        $time = '';
                        $party_size = '';
                        $special_request = '';
                    } else {
                        $error_message = 'Failed to make reservation. Please try again later. Error: ' . $conn->error;
                    }
                }
                
                $stmt->close();
                $conn->close();
            }
        } else {
            $error_message = implode(' ', $errors);
        }
    }
}
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8 text-center">Make a Reservation</h1>
    
    <?php if (!empty($success_message)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 max-w-2xl mx-auto">
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error_message)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6 max-w-2xl mx-auto">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>
    
    <div class="bg-white rounded-lg shadow-md p-6 mb-8 max-w-2xl mx-auto">
        <?php if (!isLoggedIn()): ?>
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-6">
                Please <a href="login.php" class="text-primary hover:underline">login</a> or <a href="register.php" class="text-primary hover:underline">register</a> to make a reservation.
            </div>
        <?php endif; ?>
        
        <form action="reservation.php" method="post">
            <div class="mb-4">
                <label for="date" class="block text-gray-700 font-medium mb-2">Date *</label>
                <input type="date" id="date" name="date" value="<?php echo $date; ?>" min="<?php echo date('Y-m-d'); ?>" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required <?php echo !isLoggedIn() ? 'disabled' : ''; ?>>
            </div>
            
            <div class="mb-4">
                <label for="time" class="block text-gray-700 font-medium mb-2">Time *</label>
                <select id="time" name="time" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required <?php echo !isLoggedIn() ? 'disabled' : ''; ?>>
                    <option value="" disabled <?php echo empty($time) ? 'selected' : ''; ?>>Select time</option>
                    <option value="11:00:00" <?php echo $time === '11:00:00' ? 'selected' : ''; ?>>11:00 AM</option>
                    <option value="11:30:00" <?php echo $time === '11:30:00' ? 'selected' : ''; ?>>11:30 AM</option>
                    <option value="12:00:00" <?php echo $time === '12:00:00' ? 'selected' : ''; ?>>12:00 PM</option>
                    <option value="12:30:00" <?php echo $time === '12:30:00' ? 'selected' : ''; ?>>12:30 PM</option>
                    <option value="13:00:00" <?php echo $time === '13:00:00' ? 'selected' : ''; ?>>1:00 PM</option>
                    <option value="13:30:00" <?php echo $time === '13:30:00' ? 'selected' : ''; ?>>1:30 PM</option>
                    <option value="14:00:00" <?php echo $time === '14:00:00' ? 'selected' : ''; ?>>2:00 PM</option>
                    <option value="18:00:00" <?php echo $time === '18:00:00' ? 'selected' : ''; ?>>6:00 PM</option>
                    <option value="18:30:00" <?php echo $time === '18:30:00' ? 'selected' : ''; ?>>6:30 PM</option>
                    <option value="19:00:00" <?php echo $time === '19:00:00' ? 'selected' : ''; ?>>7:00 PM</option>
                    <option value="19:30:00" <?php echo $time === '19:30:00' ? 'selected' : ''; ?>>7:30 PM</option>
                    <option value="20:00:00" <?php echo $time === '20:00:00' ? 'selected' : ''; ?>>8:00 PM</option>
                    <option value="20:30:00" <?php echo $time === '20:30:00' ? 'selected' : ''; ?>>8:30 PM</option>
                    <option value="21:00:00" <?php echo $time === '21:00:00' ? 'selected' : ''; ?>>9:00 PM</option>
                </select>
            </div>
            
            <div class="mb-4">
                <label for="party_size" class="block text-gray-700 font-medium mb-2">Number of Guests *</label>
                <select id="party_size" name="party_size" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required <?php echo !isLoggedIn() ? 'disabled' : ''; ?>>
                    <option value="" disabled <?php echo empty($party_size) ? 'selected' : ''; ?>>Select number of guests</option>
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo $party_size == $i ? 'selected' : ''; ?>><?php echo $i; ?></option>
                    <?php endfor; ?>
                    <option value="12" <?php echo $party_size == 12 ? 'selected' : ''; ?>>12</option>
                    <option value="15" <?php echo $party_size == 15 ? 'selected' : ''; ?>>15</option>
                    <option value="20" <?php echo $party_size == 20 ? 'selected' : ''; ?>>20 (Large Party)</option>
                </select>
            </div>
            
            <div class="mb-6">
                <label for="special_request" class="block text-gray-700 font-medium mb-2">Special Requests (Optional)</label>
                <textarea id="special_request" name="special_request" rows="4" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" <?php echo !isLoggedIn() ? 'disabled' : ''; ?>><?php echo $special_request; ?></textarea>
                <p class="text-sm text-gray-500 mt-1">Please mention any dietary requirements, special occasions, or seating preferences.</p>
            </div>
            
            <div class="flex justify-center">
                <button type="submit" class="bg-primary text-white px-6 py-3 rounded-md font-semibold hover:bg-opacity-90 transition" <?php echo !isLoggedIn() ? 'disabled' : ''; ?>>
                    Make Reservation
                </button>
            </div>
        </form>
    </div>
    
    <div class="bg-gray-100 rounded-lg p-6 max-w-2xl mx-auto">
        <h2 class="text-xl font-bold mb-4">Reservation Policy</h2>
        <ul class="list-disc pl-5 space-y-2 text-gray-700">
            <li>Reservations can be made up to 30 days in advance.</li>
            <li>Please arrive on time. Tables will only be held for 15 minutes past your reservation time.</li>
            <li>For parties of 8 or more, a credit card is required to secure your reservation.</li>
            <li>Cancellations should be made at least 24 hours in advance to avoid a cancellation fee.</li>
            <li>For large parties or special events, please contact us directly.</li>
        </ul>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 