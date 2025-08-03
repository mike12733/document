<?php
require_once '../config/database.php';

class NotificationSystem {
    private $db;
    
    public function __construct() {
        $this->db = getDBConnection();
    }
    
    // Create a new notification
    public function createNotification($user_id, $title, $message, $type = 'portal', $request_id = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO notifications (user_id, request_id, title, message, type, status) 
                VALUES (?, ?, ?, ?, ?, 'pending')
            ");
            $result = $stmt->execute([$user_id, $request_id, $title, $message, $type]);
            
            if ($result) {
                $notification_id = $this->db->lastInsertId();
                
                // Try to send notification based on type
                switch ($type) {
                    case 'email':
                        $this->sendEmail($user_id, $title, $message, $notification_id);
                        break;
                    case 'sms':
                        $this->sendSMS($user_id, $message, $notification_id);
                        break;
                    case 'portal':
                        // Portal notifications are stored in database only
                        $this->markNotificationSent($notification_id);
                        break;
                }
                
                return true;
            }
            return false;
        } catch (Exception $e) {
            error_log("Notification creation error: " . $e->getMessage());
            return false;
        }
    }
    
    // Send email notification
    private function sendEmail($user_id, $title, $message, $notification_id) {
        try {
            // Get user email
            $stmt = $this->db->prepare("SELECT email, first_name, last_name FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if (!$user) {
                throw new Exception("User not found");
            }
            
            // Email configuration (would need to be configured with actual SMTP settings)
            $to = $user['email'];
            $subject = "LNHS Portal - " . $title;
            
            // Create HTML email body
            $email_body = $this->createEmailTemplate($user['first_name'], $title, $message);
            
            // Headers for HTML email
            $headers = [
                'MIME-Version: 1.0',
                'Content-type: text/html; charset=utf-8',
                'From: LNHS Portal <noreply@lnhs.edu.ph>',
                'Reply-To: registrar@lnhs.edu.ph',
                'X-Mailer: PHP/' . phpversion()
            ];
            
            // Try to send email (this is a basic implementation)
            // In production, you would use a proper email service like PHPMailer, SendGrid, etc.
            $sent = mail($to, $subject, $email_body, implode("\r\n", $headers));
            
            if ($sent) {
                $this->markNotificationSent($notification_id);
            } else {
                $this->markNotificationFailed($notification_id, "Failed to send email");
            }
            
        } catch (Exception $e) {
            $this->markNotificationFailed($notification_id, $e->getMessage());
            error_log("Email sending error: " . $e->getMessage());
        }
    }
    
    // Send SMS notification
    private function sendSMS($user_id, $message, $notification_id) {
        try {
            // Get user phone number
            $stmt = $this->db->prepare("SELECT contact_number, first_name FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if (!$user || empty($user['contact_number'])) {
                throw new Exception("User phone number not found");
            }
            
            // SMS API configuration (would need to be configured with actual SMS service)
            // This is a placeholder for SMS integration
            // Popular SMS services in Philippines: Semaphore, Globe Labs, Smart DevNet
            
            $phone = $user['contact_number'];
            $sms_message = "LNHS Portal: " . substr($message, 0, 140); // Limit to 160 chars including prefix
            
            // Placeholder for SMS API call
            // $sms_sent = $this->sendSMSAPI($phone, $sms_message);
            $sms_sent = false; // Set to false since no actual SMS service is configured
            
            if ($sms_sent) {
                $this->markNotificationSent($notification_id);
            } else {
                $this->markNotificationFailed($notification_id, "SMS service not configured");
            }
            
        } catch (Exception $e) {
            $this->markNotificationFailed($notification_id, $e->getMessage());
            error_log("SMS sending error: " . $e->getMessage());
        }
    }
    
    // Create email template
    private function createEmailTemplate($first_name, $title, $message) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>{$title}</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #2563eb; color: white; padding: 20px; text-align: center; }
                .content { background-color: #f8fafc; padding: 20px; }
                .footer { background-color: #64748b; color: white; padding: 15px; text-align: center; font-size: 12px; }
                .btn { background-color: #2563eb; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎓 LNHS Documents Request Portal</h1>
                </div>
                <div class='content'>
                    <h2>Hello, {$first_name}!</h2>
                    <h3>{$title}</h3>
                    <p>" . nl2br(htmlspecialchars($message)) . "</p>
                    <a href='http://localhost/lnhs-portal/student/dashboard.php' class='btn'>View Dashboard</a>
                </div>
                <div class='footer'>
                    <p>LNHS Documents Request Portal</p>
                    <p>Laoag National High School, Laoag City, Ilocos Norte</p>
                    <p>For assistance, contact: registrar@lnhs.edu.ph | 077-123-4567</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    // Mark notification as sent
    private function markNotificationSent($notification_id) {
        try {
            $stmt = $this->db->prepare("UPDATE notifications SET status = 'sent' WHERE id = ?");
            $stmt->execute([$notification_id]);
        } catch (Exception $e) {
            error_log("Error marking notification as sent: " . $e->getMessage());
        }
    }
    
    // Mark notification as failed
    private function markNotificationFailed($notification_id, $error_message) {
        try {
            $stmt = $this->db->prepare("UPDATE notifications SET status = 'failed' WHERE id = ?");
            $stmt->execute([$notification_id]);
            error_log("Notification failed: " . $error_message);
        } catch (Exception $e) {
            error_log("Error marking notification as failed: " . $e->getMessage());
        }
    }
    
    // Get user notifications (for portal display)
    public function getUserNotifications($user_id, $limit = 10, $unread_only = false) {
        try {
            $where_clause = "user_id = ?";
            $params = [$user_id];
            
            if ($unread_only) {
                $where_clause .= " AND read_at IS NULL";
            }
            
            $stmt = $this->db->prepare("
                SELECT * FROM notifications 
                WHERE {$where_clause} 
                ORDER BY created_at DESC 
                LIMIT {$limit}
            ");
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error getting user notifications: " . $e->getMessage());
            return [];
        }
    }
    
    // Mark notification as read
    public function markAsRead($notification_id, $user_id) {
        try {
            $stmt = $this->db->prepare("
                UPDATE notifications 
                SET read_at = CURRENT_TIMESTAMP 
                WHERE id = ? AND user_id = ?
            ");
            return $stmt->execute([$notification_id, $user_id]);
        } catch (Exception $e) {
            error_log("Error marking notification as read: " . $e->getMessage());
            return false;
        }
    }
    
    // Get unread notification count
    public function getUnreadCount($user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM notifications 
                WHERE user_id = ? AND read_at IS NULL
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetch()['count'];
        } catch (Exception $e) {
            error_log("Error getting unread count: " . $e->getMessage());
            return 0;
        }
    }
    
    // Send status update notification
    public function sendStatusUpdateNotification($request_id, $new_status, $changed_by_id, $notes = '') {
        try {
            // Get request and user details
            $stmt = $this->db->prepare("
                SELECT dr.*, dt.name as document_name, u.id as user_id, u.first_name, u.email 
                FROM document_requests dr 
                JOIN document_types dt ON dr.document_type_id = dt.id 
                JOIN users u ON dr.user_id = u.id 
                WHERE dr.id = ?
            ");
            $stmt->execute([$request_id]);
            $request = $stmt->fetch();
            
            if (!$request) {
                throw new Exception("Request not found");
            }
            
            // Create notification message based on status
            $status_messages = [
                'pending' => 'Your request is being reviewed.',
                'processing' => 'Your request has been approved and is now being processed.',
                'approved' => 'Your request has been approved. Payment is required before processing.',
                'denied' => 'Your request has been denied. Please check the admin notes for details.',
                'ready_for_pickup' => 'Your document is ready for pickup! Please visit the registrar\'s office.',
                'completed' => 'Your request has been completed successfully. Thank you!'
            ];
            
            $title = "Request #{$request_id} - " . ucwords(str_replace('_', ' ', $new_status));
            $message = "Your request for {$request['document_name']} has been updated.\n\n";
            $message .= "Status: " . ucwords(str_replace('_', ' ', $new_status)) . "\n";
            $message .= $status_messages[$new_status] ?? '';
            
            if (!empty($notes)) {
                $message .= "\n\nAdmin Notes: " . $notes;
            }
            
            // Send portal notification
            $this->createNotification($request['user_id'], $title, $message, 'portal', $request_id);
            
            // Send email notification if enabled
            $this->createNotification($request['user_id'], $title, $message, 'email', $request_id);
            
            return true;
        } catch (Exception $e) {
            error_log("Error sending status update notification: " . $e->getMessage());
            return false;
        }
    }
    
    // Clean old notifications (run this periodically)
    public function cleanOldNotifications($days = 30) {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM notifications 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            return $stmt->execute([$days]);
        } catch (Exception $e) {
            error_log("Error cleaning old notifications: " . $e->getMessage());
            return false;
        }
    }
}

// Initialize notification system
$notificationSystem = new NotificationSystem();
?>