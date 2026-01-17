<?php
/**
 * Payment Logger Class
 * 
 * Handles comprehensive logging of all payment-related activities:
 * - Payment transactions
 * - Payment failures
 * - Webhook events
 * - Payment status changes
 * - Error tracking
 * 
 * Logs are stored both in database and file system for redundancy.
 * 
 * @author AquaWater Team
 * @version 1.0
 */

class PaymentLogger {
    
    // Log levels
    const LEVEL_INFO = 'INFO';
    const LEVEL_WARNING = 'WARNING';
    const LEVEL_ERROR = 'ERROR';
    const LEVEL_DEBUG = 'DEBUG';
    
    /**
     * Log payment transaction
     * 
     * @param int $user_id User ID
     * @param int $order_id Order ID
     * @param float $amount Payment amount
     * @param string $method Payment method
     * @param string $status Payment status
     * @param string $transaction_id Transaction ID (optional)
     * @param array $additional_data Additional data to log (optional)
     * @param mysqli $conn Database connection
     * @return bool True if logged successfully
     */
    public static function logTransaction($user_id, $order_id, $amount, $method, $status, $transaction_id = null, $additional_data = [], $conn = null) {
        if ($conn === null) {
            $conn = $GLOBALS['conn'] ?? null;
        }
        
        try {
            $log_data = [
                'timestamp' => date('Y-m-d H:i:s'),
                'user_id' => intval($user_id),
                'order_id' => intval($order_id),
                'amount' => floatval($amount),
                'method' => $method,
                'status' => $status,
                'transaction_id' => $transaction_id,
                'ip_address' => self::getClientIp(),
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255),
                'additional_data' => $additional_data
            ];
            
            // Log to database if payment_logs table exists
            if (self::tableExists('payment_logs', $conn)) {
                $stmt = $conn->prepare("
                    INSERT INTO payment_logs 
                    (user_id, order_id, transaction_id, amount, method, status, data, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $json_data = json_encode($log_data);
                $stmt->bind_param(
                    "iisdsss",
                    $log_data['user_id'],
                    $log_data['order_id'],
                    $transaction_id,
                    $amount,
                    $method,
                    $status,
                    $json_data
                );
                
                $stmt->execute();
                $stmt->close();
            }
            
            // Log to file
            self::logToFile(json_encode($log_data), self::LEVEL_INFO);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Payment logging error: " . $e->getMessage());
            self::logToFile("Logging error: " . $e->getMessage(), self::LEVEL_ERROR);
            return false;
        }
    }
    
    /**
     * Log payment failure
     * 
     * @param int $user_id User ID
     * @param int $order_id Order ID
     * @param float $amount Payment amount
     * @param string $method Payment method
     * @param string $error_message Error message
     * @param string $error_code Error code (optional)
     * @param mysqli $conn Database connection
     * @return bool True if logged successfully
     */
    public static function logFailure($user_id, $order_id, $amount, $method, $error_message, $error_code = null, $conn = null) {
        if ($conn === null) {
            $conn = $GLOBALS['conn'] ?? null;
        }
        
        try {
            $log_data = [
                'timestamp' => date('Y-m-d H:i:s'),
                'user_id' => intval($user_id),
                'order_id' => intval($order_id),
                'amount' => floatval($amount),
                'method' => $method,
                'error_message' => $error_message,
                'error_code' => $error_code,
                'ip_address' => self::getClientIp()
            ];
            
            // Log to database
            if (self::tableExists('payment_logs', $conn)) {
                $stmt = $conn->prepare("
                    INSERT INTO payment_logs 
                    (user_id, order_id, amount, method, status, data, created_at) 
                    VALUES (?, ?, ?, ?, 'failed', ?, NOW())
                ");
                
                $json_data = json_encode($log_data);
                $status = 'failed';
                $stmt->bind_param(
                    "iidss",
                    $log_data['user_id'],
                    $log_data['order_id'],
                    $amount,
                    $method,
                    $json_data
                );
                
                $stmt->execute();
                $stmt->close();
            }
            
            // Log to file
            self::logToFile(json_encode($log_data), self::LEVEL_WARNING);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Payment failure logging error: " . $e->getMessage());
            self::logToFile("Failure logging error: " . $e->getMessage(), self::LEVEL_ERROR);
            return false;
        }
    }
    
    /**
     * Log webhook event
     * 
     * @param string $event_type Webhook event type (e.g., 'payment_intent.succeeded')
     * @param string $event_id Stripe event ID
     * @param array $event_data Event data
     * @param mysqli $conn Database connection
     * @return bool True if logged successfully
     */
    public static function logWebhookEvent($event_type, $event_id, $event_data, $conn = null) {
        if ($conn === null) {
            $conn = $GLOBALS['conn'] ?? null;
        }
        
        try {
            $log_data = [
                'timestamp' => date('Y-m-d H:i:s'),
                'event_type' => $event_type,
                'event_id' => $event_id,
                'event_data' => $event_data
            ];
            
            // Log to database
            if (self::tableExists('payment_logs', $conn)) {
                $stmt = $conn->prepare("
                    INSERT INTO payment_logs 
                    (transaction_id, status, data, created_at) 
                    VALUES (?, ?, ?, NOW())
                ");
                
                $json_data = json_encode($log_data);
                $status = 'webhook';
                $stmt->bind_param("iss", $event_id, $status, $json_data);
                
                $stmt->execute();
                $stmt->close();
            }
            
            // Log to file
            self::logToFile(json_encode($log_data), self::LEVEL_DEBUG);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Webhook logging error: " . $e->getMessage());
            self::logToFile("Webhook logging error: " . $e->getMessage(), self::LEVEL_ERROR);
            return false;
        }
    }
    
    /**
     * Log generic message
     * 
     * @param string $message Message to log
     * @param string $level Log level (INFO, WARNING, ERROR, DEBUG)
     * @param array $context Additional context data (optional)
     * @return bool True if logged successfully
     */
    public static function log($message, $level = self::LEVEL_INFO, $context = []) {
        try {
            $log_data = [
                'timestamp' => date('Y-m-d H:i:s'),
                'level' => $level,
                'message' => $message,
                'context' => $context,
                'ip_address' => self::getClientIp()
            ];
            
            // Log to PHP error log
            error_log($message . " | Context: " . json_encode($context));
            
            // Log to file
            self::logToFile(json_encode($log_data), $level);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Generic logging error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log to file system
     * 
     * @param string $message Message to log
     * @param string $level Log level
     * @return bool True if logged successfully
     */
    private static function logToFile($message, $level = self::LEVEL_INFO) {
        try {
            $log_dir = __DIR__ . '/../storage/logs';
            
            // Create logs directory if it doesn't exist
            if (!is_dir($log_dir)) {
                mkdir($log_dir, 0755, true);
            }
            
            $log_file = $log_dir . '/payments_' . date('Y-m-d') . '.log';
            
            $log_entry = '[' . date('Y-m-d H:i:s') . '] [' . $level . '] ' . $message . PHP_EOL;
            
            return file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX) !== false;
            
        } catch (Exception $e) {
            error_log("File logging error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get client IP address
     * 
     * @return string Client IP address
     */
    private static function getClientIp() {
        // Check for shared internet
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        // Check for IP passed from proxy
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        // Check for remote address
        else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        }
        
        return filter_var($ip, FILTER_VALIDATE_IP) ?: 'INVALID_IP';
    }
    
    /**
     * Check if database table exists
     * 
     * @param string $table_name Table name
     * @param mysqli $conn Database connection
     * @return bool True if table exists
     */
    private static function tableExists($table_name, $conn) {
        try {
            $stmt = $conn->prepare("SELECT 1 FROM " . $table_name . " LIMIT 1");
            return $stmt !== false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get payment logs for a specific order
     * 
     * @param int $order_id Order ID
     * @param mysqli $conn Database connection
     * @return array Array of payment logs
     */
    public static function getOrderLogs($order_id, $conn = null) {
        if ($conn === null) {
            $conn = $GLOBALS['conn'] ?? null;
        }
        
        try {
            if (!self::tableExists('payment_logs', $conn)) {
                return [];
            }
            
            $stmt = $conn->prepare("
                SELECT * FROM payment_logs 
                WHERE order_id = ? 
                ORDER BY created_at DESC
            ");
            
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $logs = [];
            
            while ($row = $result->fetch_assoc()) {
                $logs[] = $row;
            }
            
            $stmt->close();
            
            return $logs;
            
        } catch (Exception $e) {
            error_log("Error retrieving order logs: " . $e->getMessage());
            return [];
        }
    }
    /**
     * Get payment logs for a specific user
     * 
     * @param int $user_id User ID
     * @param int $limit Maximum number of logs to retrieve
     * @param mysqli $conn Database connection
     * @return array Array of payment logs
     */
    public static function getUserLogs($user_id, $limit = 50, $conn = null) {
        if ($conn === null) {
            $conn = $GLOBALS['conn'] ?? null;
        }
        
        try {
            if (!self::tableExists('payment_logs', $conn)) {
                return [];
            }
            
            $stmt = $conn->prepare("
                SELECT * FROM payment_logs 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            
            $stmt->bind_param("ii", $user_id, $limit);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $logs = [];
            
            while ($row = $result->fetch_assoc()) {
                $logs[] = $row;
            }
            
            $stmt->close();
            
            return $logs;
            
        } catch (Exception $e) {
            error_log("Error retrieving user logs: " . $e->getMessage());
            return [];
        }
    }
}
?>
