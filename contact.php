<?php
include 'includes/db_connect.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject']);
    $message_type = trim($_POST['message_type'] ?? 'general_query');
    $body = trim($_POST['message']);

    if (empty($name) || empty($email) || empty($subject) || empty($body)) {
        $message = "<div class='bg-red-100 text-red-700 p-3 rounded-md mb-4'>⚠️ Please fill out all required fields.</div>";
    } else {
        // Insert into database
        $insert_query = "INSERT INTO contact_messages (name, email, phone, subject, message_type, message) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        
        if (!$stmt) {
            $message = "<div class='bg-red-100 text-red-700 p-3 rounded-md mb-4'>❌ Database error: " . $conn->error . "</div>";
        } else {
            $stmt->bind_param('ssssss', $name, $email, $phone, $subject, $message_type, $body);
            
            if ($stmt->execute()) {
                $message = "<div class='bg-green-100 text-green-700 p-3 rounded-md mb-4'>✅ Thank you! Your message has been received. We'll get back to you soon.</div>";
                // Clear form
                $_POST = [];
            } else {
                $message = "<div class='bg-red-100 text-red-700 p-3 rounded-md mb-4'>❌ Error: " . $stmt->error . "</div>";
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - AquaFlow</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include 'includes/header.php'; ?>

    <main class="min-h-screen py-12">
        <div class="max-w-6xl mx-auto px-4">
            <!-- Page Title -->
            <div class="text-center mb-12">
                <h1 class="text-4xl font-bold text-gray-800 mb-4">Get in Touch</h1>
                <p class="text-lg text-gray-600">Have a question or inquiry? We'd love to hear from you.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8 mb-12">
                <!-- Contact Info Cards -->
                <div class="bg-white rounded-lg shadow p-6 text-center hover:shadow-lg transition">
                    <div class="text-cyan-600 text-3xl mb-3">
                        <i class="fas fa-phone"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Call Us</h3>
                    <a href="tel:+923001234567" class="text-gray-600 hover:text-cyan-600">+92 300 123 4567</a>
                </div>

                <div class="bg-white rounded-lg shadow p-6 text-center hover:shadow-lg transition">
                    <div class="text-cyan-600 text-3xl mb-3">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Email Us</h3>
                    <a href="mailto:support@aquaflow.com" class="text-gray-600 hover:text-cyan-600">support@aquaflow.com</a>
                </div>

                <div class="bg-white rounded-lg shadow p-6 text-center hover:shadow-lg transition">
                    <div class="text-cyan-600 text-3xl mb-3">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Visit Us</h3>
                    <p class="text-gray-600">Karachi, Pakistan</p>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="grid md:grid-cols-2 gap-8">
                <!-- Form -->
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Send us a Message</h2>
                    
                    <?php if ($message): echo $message; endif; ?>

                    <form method="POST" class="space-y-4">
                        <!-- Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500" 
                                   required>
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500" 
                                   required>
                        </div>

                        <!-- Phone -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500">
                        </div>

                        <!-- Inquiry Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Inquiry Type *</label>
                            <select name="message_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500" required>
                                <option value="general_query">General Question</option>
                                <option value="bulk_order">Bulk Order Request</option>
                                <option value="complaint">Complaint</option>
                                <option value="partnership">Partnership Inquiry</option>
                            </select>
                        </div>

                        <!-- Subject -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Subject *</label>
                            <input type="text" name="subject" value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500" 
                                   required>
                        </div>

                        <!-- Message -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Message *</label>
                            <textarea name="message" rows="5" 
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500" 
                                      required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="w-full bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-3 rounded-lg transition">
                            Send Message
                        </button>
                    </form>
                </div>

                <!-- Right Side Info -->
                <div class="flex flex-col justify-center">
                    <div class="bg-cyan-50 rounded-lg p-8">
                        <h3 class="text-2xl font-bold text-gray-800 mb-6">Why Contact Us?</h3>
                        
                        <div class="space-y-4">
                            <div class="flex gap-4">
                                <div class="text-cyan-600 text-2xl flex-shrink-0">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-800">Quick Response</h4>
                                    <p class="text-gray-600 text-sm">We respond to all inquiries within 24 hours</p>
                                </div>
                            </div>

                            <div class="flex gap-4">
                                <div class="text-cyan-600 text-2xl flex-shrink-0">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-800">Expert Support</h4>
                                    <p class="text-gray-600 text-sm">Our team of water quality experts is here to help</p>
                                </div>
                            </div>

                            <div class="flex gap-4">
                                <div class="text-cyan-600 text-2xl flex-shrink-0">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-800">Multiple Options</h4>
                                    <p class="text-gray-600 text-sm">Call, email, or send a message - choose what works for you</p>
                                </div>
                            </div>

                            <div class="flex gap-4">
                                <div class="text-cyan-600 text-2xl flex-shrink-0">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-800">Bulk Order Support</h4>
                                    <p class="text-gray-600 text-sm">Special pricing for corporate and bulk orders</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
