<?php
/**
 * Payment Validator Class
 * 
 * Handles validation of payment-related data:
 * - Cart items validation
 * - Payment method verification
 * - Amount validation
 * - Email validation
 * - Card details validation
 * 
 * @author AquaWater Team
 * @version 1.0
 */

class PaymentValidator {
    
    /**
     * Validate cart items exist and are not empty
     * 
     * @param int $user_id User ID
     * @param mysqli $conn Database connection
     * @return array Cart validation result with item count and total quantity
     * @throws Exception If cart is empty or database error occurs
     */
    public static function validateCartItems($user_id, $conn) {
        if (!is_numeric($user_id) || $user_id <= 0) {
            throw new Exception("Invalid user ID.");
        }
        
        $stmt = $conn->prepare("
            SELECT SUM(c.quantity) as total_qty, COUNT(*) as item_count
            FROM cart c 
            WHERE c.user_id = ? AND c.quantity > 0
        ");
        
        if (!$stmt) {
            throw new Exception("Database prepare error: " . $conn->error);
        }
        
        $stmt->bind_param("i", $user_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Database execute error: " . $stmt->error);
        }
        
        $result = $stmt->get_result()->fetch_assoc();
        
        if (!$result || $result['item_count'] === 0 || $result['total_qty'] === null) {
            throw new Exception("Your cart is empty.");
        }
        
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Validate payment method is active and available
     * 
     * @param string $method Payment method name
     * @param mysqli $conn Database connection
     * @return array Payment method details
     * @throws Exception If payment method not found or inactive
     */
    public static function validatePaymentMethod($method, $conn) {
        // Sanitize input
        $method = trim($method);
        
        if (empty($method) || strlen($method) > 100) {
            throw new Exception("Invalid payment method format.");
        }
        
        // Only allow alphanumeric and spaces
        if (!preg_match('/^[a-zA-Z\s]+$/', $method)) {
            throw new Exception("Invalid payment method format.");
        }
        
        $stmt = $conn->prepare("
            SELECT id, method_name FROM payment_methods 
            WHERE LOWER(method_name) = LOWER(?) AND is_active = 1
        ");
        
        if (!$stmt) {
            throw new Exception("Database prepare error: " . $conn->error);
        }
        
        $stmt->bind_param("s", $method);
        
        if (!$stmt->execute()) {
            throw new Exception("Database execute error: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Payment method is not available.");
        }
        
        $method_data = $result->fetch_assoc();
        $stmt->close();
        
        return $method_data;
    }
    
    /**
     * Validate payment amount is within acceptable range
     * 
     * @param float $amount Amount to validate (in PKR)
     * @param float $min Minimum allowed amount (default: 1)
     * @param float $max Maximum allowed amount (default: 10,000,000)
     * @return float Validated amount as float
     * @throws Exception If amount is invalid
     */
    public static function validateAmount($amount, $min = 1, $max = 10000000) {
        // Convert to float
        $amount = floatval($amount);
        
        // Check if amount is numeric and within range
        if (!is_numeric($amount) || $amount < $min || $amount > $max) {
            throw new Exception(
                "Payment amount must be between Rs " . number_format($min, 2) . 
                " and Rs " . number_format($max, 2) . "."
            );
        }
        
        return $amount;
    }
    
    /**
     * Validate email address format
     * 
     * @param string $email Email address
     * @return string Validated email
     * @throws Exception If email is invalid
     */
    public static function validateEmail($email) {
        $email = trim($email);
        $validated_email = filter_var($email, FILTER_VALIDATE_EMAIL);
        
        if (!$validated_email) {
            throw new Exception("Invalid email address format.");
        }
        
        return $validated_email;
    }
    
    /**
     * Validate cardholder name
     * 
     * @param string $name Cardholder name
     * @return string Validated name
     * @throws Exception If name is invalid
     */
    public static function validateCardholderName($name) {
        $name = trim($name);
        
        if (empty($name)) {
            throw new Exception("Cardholder name is required.");
        }
        
        if (strlen($name) > 100) {
            throw new Exception("Cardholder name is too long.");
        }
        
        // Allow letters, spaces, hyphens, periods, and apostrophes
        if (!preg_match('/^[a-zA-Z\s\-\.\']{2,}$/', $name)) {
            throw new Exception("Cardholder name contains invalid characters.");
        }
        
        return $name;
    }
    
    /**
     * Validate postal code / ZIP code
     * 
     * @param string $zip Postal code
     * @return string Validated ZIP code
     * @throws Exception If ZIP code is invalid
     */
    public static function validatePostalCode($zip) {
        $zip = trim($zip);
        
        if (empty($zip)) {
            throw new Exception("Postal code is required.");
        }
        
        if (strlen($zip) > 20) {
            throw new Exception("Postal code is too long.");
        }
        
        // Allow digits, spaces, and hyphens (for various international formats)
        if (!preg_match('/^[\d\s\-]{2,}$/', $zip)) {
            throw new Exception("Invalid postal code format.");
        }
        
        return $zip;
    }
    
    /**
     * Validate Stripe payment method ID format
     * 
     * @param string $payment_method_id Stripe payment method ID
     * @return string Validated payment method ID
     * @throws Exception If format is invalid
     */
    public static function validateStripePaymentMethodId($payment_method_id) {
        $payment_method_id = trim($payment_method_id);
        
        if (empty($payment_method_id)) {
            throw new Exception("Stripe payment method ID is missing.");
        }
        
        // Stripe payment method IDs start with "pm_" followed by random alphanumeric characters
        if (!preg_match('/^pm_[a-zA-Z0-9]{24,}$/', $payment_method_id)) {
            throw new Exception("Invalid payment method format.");
        }
        
        return $payment_method_id;
    }
    
    /**
     * Validate Stripe payment intent ID format
     * 
     * @param string $intent_id Stripe payment intent ID
     * @return string Validated intent ID
     * @throws Exception If format is invalid
     */
    public static function validateStripeIntentId($intent_id) {
        $intent_id = trim($intent_id);
        
        if (empty($intent_id)) {
            throw new Exception("Stripe payment intent ID is missing.");
        }
        
        // Stripe payment intent IDs start with "pi_" followed by random alphanumeric characters
        if (!preg_match('/^pi_[a-zA-Z0-9]{24,}$/', $intent_id)) {
            throw new Exception("Invalid payment intent format.");
        }
        
        return $intent_id;
    }
    
    /**
     * Validate payment status is valid enum value
     * 
     * @param string $status Payment status
     * @return string Validated status
     * @throws Exception If status is invalid
     */
    public static function validatePaymentStatus($status) {
        $allowed_statuses = ['pending', 'processing', 'paid', 'failed', 'cancelled', 'refunded'];
        
        $status = strtolower(trim($status));
        
        if (!in_array($status, $allowed_statuses)) {
            throw new Exception("Invalid payment status: " . $status);
        }
        
        return $status;
    }
    
    /**
     * Validate order status is valid enum value
     * 
     * @param string $status Order status
     * @return string Validated status
     * @throws Exception If status is invalid
     */
    public static function validateOrderStatus($status) {
        $allowed_statuses = ['pending', 'payment_initiated', 'paid', 'confirmed', 'shipped', 'delivered', 'cancelled', 'refunded'];
        
        $status = strtolower(trim($status));
        
        if (!in_array($status, $allowed_statuses)) {
            throw new Exception("Invalid order status: " . $status);
        }
        
        return $status;
    }
}
?>
