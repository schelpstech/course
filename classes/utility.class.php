<?php

class Utility
{
    public function generateRandomString($length)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function generateRandomText($length)
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
    public function generateRandomDigits($length)
    {
        $characters = '1234567890';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function dayinterval($start, $end)
    {
        $interval = date_diff($start, $end);
        return $interval->format('%R%a days');
    }
    public function money($amount)
    {
        $regex = "/\B(?=(\d{3})+(?!\d))/i";
        return "&#8358;" . preg_replace($regex, ",", $amount);
    }

    public function number($amount)
    {
        $regex = "/\B(?=(\d{3})+(?!\d))/i";
        return preg_replace($regex, ",", $amount);
    }


    public function RemoveSpecialChar($str)
    {
        $result = str_replace(array('\'', '"', ',', ';', '<', '>', '/'), '', $str);
        return $result;
    }

    public function inputEncode($input)
    {
        try {
            // Step 1: Check if $input is empty or null
            if (empty($input)) {
                throw new Exception("Input data cannot be empty or null.");
            }

            // Step 2: Base64 encode the input
            $base64Encoded = base64_encode($input);

            // Check if base64_encode failed
            if ($base64Encoded === false) {
                throw new Exception("Failed to base64 encode the input.");
            }

            // Step 3: Hexadecimal encoding of the Base64-encoded data
            $hexEncoded = bin2hex($base64Encoded);

            // Check if bin2hex failed
            if ($hexEncoded === false) {
                throw new Exception("Failed to convert Base64-encoded data to hexadecimal.");
            }

            return $hexEncoded;
        } catch (Exception $e) {
            // Handle exceptions, log the error message, and return an error response or null
            error_log($e->getMessage()); // Log the error message for debugging
            return null; // or return a custom error message or handle as needed
        }
    }


    public function inputDecode($encodedData)
    {
        try {
            // Step 1: Check if $encodedData is not empty or null before decoding
            if (empty($encodedData)) {
                throw new Exception("Encoded data cannot be empty or null.");
            }

            // Step 2: Hexadecimal to binary
            $binary = hex2bin($encodedData);

            // Check if hex2bin failed and returned false
            if ($binary === false) {
                throw new Exception("Invalid hexadecimal input provided.");
            }

            // Step 3: Base64 decoding of the binary data
            $decodedBase64 = base64_decode($binary, true); // true to prevent non-base64 chars

            // Check if base64_decode failed
            if ($decodedBase64 === false) {
                throw new Exception("Failed to base64 decode the binary data.");
            }

            return $decodedBase64;
        } catch (Exception $e) {
            // Handle exceptions, log the error message, and return an error response or null
            error_log($e->getMessage()); // Log the error message for debugging
            return null; // or return a custom error message or handle as needed
        }
    }

    public function encodePassword($input)
    {
        // Step 1: Base64 encode
        $base64Encoded = base64_encode($input);

        // Step 2: Hexadecimal encoding of the Base64-encoded data
        $hexEncoded = bin2hex($base64Encoded);

        // Step 3: Hash the password with bcrypt
        $hashedPassword = password_hash($hexEncoded, PASSWORD_BCRYPT);

        return $hashedPassword;
    }


    public function verifyPassword($inputPassword, $storedHashedPassword)
    {
        // Verify the password using bcrypt
        return password_verify($inputPassword, $storedHashedPassword);
    }



    public function notifier($notification_alert, $notification_message)
    {
        $result =
            '<div class="alert alert-' . $notification_alert . ' alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <h5><i class="icon fas fa-check"></i> Response!</h5>
                            ' . $notification_message . '
                </div>';
        $_SESSION['msg'] = $result;
    }
    public function redirect($url)
    {
        // Perform the redirect
        header("Location: $url");
        exit();
    }

    public function redirectandencode($pageid)
    {
        // Build the URL dynamically using the inputEncode function
        $url = './router.php?pageid=' . $this->inputEncode($pageid);

        // Perform the redirect
        header("Location: $url");
        exit();
    }

    public function redirectWithNotification($notification_alert, $notification_message, $redirectUrl)
    {
        // Use the notifier to set the message in the session
        $this->notifier($notification_alert, $notification_message);

        // Perform the redirect
        $this->redirectandencode($redirectUrl);
        exit();
    }


    /**
     * Summary of handleUploadedFile
     * @param mixed $inputName
     * @param mixed $allowedTypes
     * @param mixed $maxFileSize
     * @param mixed $uploadPath
     * @return string
     */
    public function handleUploadedFile($inputName, $allowedTypes, $maxFileSize, $uploadPath)
    {
        $file = $_FILES[$inputName];
        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileError = $file['error'];
        $fileType = $file['type'];

        // Check if there was an error uploading the file
        if ($fileError !== UPLOAD_ERR_OK) {
            return "There was an error uploading the file.";
        }

        // Check if the file type is allowed
        if (!in_array($fileType, $allowedTypes)) {
            return "Invalid file type. Please upload an image file.";
        }

        // Check if the file size is within the limit
        if ($fileSize > $maxFileSize) {
            return "File size is too large. Please upload a file smaller than " . $maxFileSize . " bytes.";
        }


        $utility = new Utility();
        $saveFileName = ($utility->generateRandomString(8)) . ($utility->RemoveSpecialChar($fileName));
        // Move the uploaded file to the designated folder
        if (move_uploaded_file($fileTmpName, $uploadPath . '/' . $saveFileName)) {
            $_SESSION['fileName'] = $saveFileName;
            return "success";
        } else {
            return "There was an error uploading the file.";
        }
    }

    /**
     * Summary of calculateAge
     * @param mixed $birthdate
     * @return int
     */
    public function calculateAge($birthdate)
    {
        // Create DateTime objects for the birthdate and current date
        $birthDateObj = new DateTime($birthdate);
        $currentDateObj = new DateTime();

        // Calculate the difference between the two dates
        $ageInterval = $currentDateObj->diff($birthDateObj);

        // Return the calculated age
        return $ageInterval->y;
    }

    public function setNotification($alertClass, $iconClass, $message)
    {
        $_SESSION['msg'] = '
        <div class="alert ' . $alertClass . ' alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <h5><i class="' . $iconClass . '"></i> Response!</h5>
            ' . $message . '
        </div>';
    }
    public function isPasswordStrong($password)
    {
        // Define password strength criteria
        $minLength = 8;
        $hasUppercase = preg_match('/[A-Z]/', $password); // At least one uppercase letter
        $hasLowercase = preg_match('/[a-z]/', $password); // At least one lowercase letter
        $hasNumber = preg_match('/[0-9]/', $password);    // At least one number
        $hasSpecialChar = preg_match('/[\W_]/', $password); // At least one special character

        // Check if password meets all criteria
        if (strlen($password) < $minLength) {
            return [
                'status' => false,
                'message' => 'Password must be at least ' . $minLength . ' characters long.'
            ];
        }

        if (!$hasUppercase) {
            return [
                'status' => false,
                'message' => 'Password must include at least one uppercase letter.'
            ];
        }

        if (!$hasLowercase) {
            return [
                'status' => false,
                'message' => 'Password must include at least one lowercase letter.'
            ];
        }

        if (!$hasNumber) {
            return [
                'status' => false,
                'message' => 'Password must include at least one number.'
            ];
        }

        if (!$hasSpecialChar) {
            return [
                'status' => false,
                'message' => 'Password must include at least one special character.'
            ];
        }

        // If all conditions are met, password is strong
        return [
            'status' => true,
            'message' => 'Password is strong.'
        ];
    }

    /**
     * Sanitize user input
     * @param mixed $data
     * @return string
     */
    public function sanitizeInput($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeInput'], $data);
        }

        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');

        return $data;
    }


    public function secureEncode($data, int $ttl = 300)
    {
        try {
            if ($data === null || $data === '') {
                throw new Exception("Data cannot be empty.");
            }

            // Normalize data (wrap scalar into array)
            if (!is_array($data)) {
                $data = ['value' => $data];
            }

            // Add expiry
            $data['exp'] = time() + $ttl;

            $plaintext = json_encode($data);
            if ($plaintext === false) {
                throw new Exception("JSON encoding failed.");
            }

            $key = hash('sha256', APP_KEY, true);
            $iv = random_bytes(12);
            $cipher = 'aes-256-gcm';

            $encrypted = openssl_encrypt(
                $plaintext,
                $cipher,
                $key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );

            if ($encrypted === false) {
                throw new Exception("Encryption failed.");
            }

            $payload = $iv . $tag . $encrypted;

            return rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
        } catch (Exception $e) {
            error_log($e->getMessage());
            return null;
        }
    }

    public function secureDecode(string $token)
    {
        try {
            if (empty($token)) {
                throw new Exception("Token cannot be empty.");
            }

            $key = hash('sha256', APP_KEY, true);
            $cipher = 'aes-256-gcm';

            $decoded = base64_decode(strtr($token, '-_', '+/'), true);

            if ($decoded === false) {
                throw new Exception("Invalid token.");
            }

            $iv = substr($decoded, 0, 12);
            $tag = substr($decoded, 12, 16);
            $ciphertext = substr($decoded, 28);

            $decrypted = openssl_decrypt(
                $ciphertext,
                $cipher,
                $key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );

            if ($decrypted === false) {
                throw new Exception("Decryption failed.");
            }

            $data = json_decode($decrypted, true);

            if (!is_array($data)) {
                throw new Exception("Invalid payload.");
            }

            // Expiry check
            if (isset($data['exp']) && time() > $data['exp']) {
                throw new Exception("Token expired.");
            }

            unset($data['exp']);

            // ✅ Return scalar if originally scalar
            if (count($data) === 1 && isset($data['value'])) {
                return $data['value'];
            }

            return $data;
        } catch (Exception $e) {
            error_log($e->getMessage());
            return null;
        }
    }
    public function validateRequest($token, $action)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return true;
        }

        $decoded = $this->secureDecode($token);

        if (
            !$decoded ||
            !isset($decoded['csrf'], $decoded['action']) ||
            $decoded['action'] !== $action ||
            !isset($_SESSION['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'], $decoded['csrf'])
        ) {
            return false;
        }

        unset($_SESSION['csrf_token']);
        return true;
    }

    public function generateCsrf($action)
    {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        return $this->secureEncode([
            'csrf' => $_SESSION['csrf_token'],
            'action' => $action,
            'ip' => $_SERVER['REMOTE_ADDR']
        ], 600);
    }


public function checkLoginAttempts($email)
{
    global $model;

    $ip = $_SERVER['REMOTE_ADDR'];

    $attempt = $model->getRows('login_attempts', [
        'where' => ['email' => $email, 'ip_address' => $ip],
        'return_type' => 'single'
    ]);

    // Default response
    $response = [
        'allowed' => true,
        'remaining_attempts' => 5,
        'lock_until' => null
    ];

    if ($attempt) {

        $maxAttempts = 5;
        $lockDuration = 300; // 5 minutes

        // 🔒 Already locked
        if (!empty($attempt['locked_until']) && strtotime($attempt['locked_until']) > time()) {

            return [
                'allowed' => false,
                'remaining_attempts' => 0,
                'lock_until' => $attempt['locked_until']
            ];
        }

        // ❌ Attempts exceeded → lock now
        if ($attempt['attempts'] >= $maxAttempts) {

            $lockUntil = date('Y-m-d H:i:s', time() + $lockDuration);

            $model->update('login_attempts', [
                'locked_until' => $lockUntil
            ], ['id' => $attempt['id']]);

            return [
                'allowed' => false,
                'remaining_attempts' => 0,
                'lock_until' => $lockUntil
            ];
        }

        // ✅ Still allowed
        $response['remaining_attempts'] = $maxAttempts - $attempt['attempts'];
    }

    return $response;
}

    public function recordFailedLogin($email)
    {
        global $model;

        $ip = $_SERVER['REMOTE_ADDR'];

        $attempt = $model->getRows('login_attempts', [
            'where' => ['email' => $email, 'ip_address' => $ip],
            'return_type' => 'single'
        ]);

        if ($attempt) {
            $model->update('login_attempts', [
                'attempts' => $attempt['attempts'] + 1,
                'last_attempt' => date('Y-m-d H:i:s')
            ], ['id' => $attempt['id']]);
        } else {
            $model->insert_data('login_attempts', [
                'email' => $email,
                'ip_address' => $ip,
                'attempts' => 1,
                'last_attempt' => date('Y-m-d H:i:s')
            ]);
        }
    }

    public function resetLoginAttempts($email)
    {
        global $model;

        $model->delete('login_attempts', [
            'email' => $email,
            'ip_address' => $_SERVER['REMOTE_ADDR']
        ]);
    }

    public function logActivity($action, $userId = null)
    {
        global $model;
        $userId = $userId ?? $_SESSION['admin_email'] ?? "Unknown";
        $model->insert_data('user_logs', [
            'user_id' => $userId,
            'action' => $action,
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT']
        ]);
    }
    public function logActivityUsers($action, $userId = null)
    {
        global $model;
        $userId = $userId ?? $_SESSION['user_email'] ?? "Unknown";
        $model->insert_data('user_logs_users', [
            'user_id' => $userId,
            'action' => $action,
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT']
        ]);
    }

    public function requireAdmin()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // 1. Check basic session
        if (empty($_SESSION['admin_id']) || empty($_SESSION['admin_email'])) {
            $this->denyAccess('Session not found');
        }

        // 2. Validate fingerprint (ANTI SESSION HIJACK)
        $currentFingerprint = hash(
            'sha256',
            $_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']
        );

        if (
            empty($_SESSION['admin_fingerprint']) ||
            $_SESSION['admin_fingerprint'] !== $currentFingerprint
        ) {
            session_destroy();
            $this->denyAccess('Session integrity failed');
        }

        // 3. (Optional but strong) Regenerate session periodically
        if (empty($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        }

        if (time() - $_SESSION['last_regeneration'] > 300) { // 5 mins
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
    private function denyAccess($message = 'Unauthorized access')
    {
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'message' => $message
        ]);
        exit;
    }
}
