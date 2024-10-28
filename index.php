<?php

// Set the content type to application/json
header('Content-Type: application/json');

// Check if the 'number' parameter is provided
if (!isset($_GET['number']) || empty($_GET['number'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Please provide a valid 'number' parameter."
    ]);
    exit;
}

// Get the number parameter from the GET request
$number = $_GET['number'];

// Set the target URL with the provided number parameter
$url = "https://unknownx.top/bio_mainxxx.php?number=" . urlencode($number);

// Initialize a cURL session for the first request
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

// Execute the cURL request and store the response
$response = curl_exec($ch);

// Check for cURL errors or an empty response
if (curl_errno($ch) || empty($response)) {
    echo json_encode([
        "status" => "error",
        "message" => "No citizen data found"
    ]);
    curl_close($ch);
    exit;
}

// Close the first cURL session
curl_close($ch);

// Decode the JSON response
$data = json_decode($response, true);

// Check if the expected data is present in the response
if (isset($data['NAYEEM']['data']['nationalId']) && isset($data['NAYEEM']['data']['dob'])) {
    // Gather data from the initial response
    $output = [
        "msisdn" => $number,
        "api_owner" => "puffin",
        "nid" => $data['NAYEEM']['data']['nationalId'],
        "dob" => $data['NAYEEM']['data']['dob']
        
    ];

    // Verification attempt details
    $verification_method = "none";
    $successful_nid = $output['nid']; // Initially set to original NID

    // Set up the second request to the verification API
    $verificationUrl = "https://unknownx.top/verified.php?nid=" . urlencode($output['nid']) . "&num=" . urlencode("+88$number");
    $ch2 = curl_init($verificationUrl);
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);

    // Execute the verification request
    $verificationResponse = curl_exec($ch2);
    $verificationSuccess = false;

    if (!curl_errno($ch2) && !empty($verificationResponse)) {
        $verificationData = json_decode($verificationResponse, true);
        $verificationSuccess = ($verificationData['message'] === "NID VERIFY SUCCESS. AUTHOR @Owner_Of_DCS");
        if ($verificationSuccess) {
            $verification_method = "nid";
            $successful_nid = $output['nid'];
        }
    }
    curl_close($ch2);

    // If verification failed, make a third request to retrieve 'pin'
    if (!$verificationSuccess) {
        $backupUrl = "https://protigga.xyz/connection/api2scopy.php?nid=" . urlencode($output['nid']) . "&dob=" . urlencode($output['dob']);
        $ch3 = curl_init($backupUrl);
        curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch3, CURLOPT_SSL_VERIFYPEER, false);

        // Execute the third request and decode the response
        $backupResponse = curl_exec($ch3);
        curl_close($ch3);

        if (!curl_errno($ch3) && !empty($backupResponse)) {
            $backupData = json_decode($backupResponse, true);

            // Check if 'pin' is available
            if (isset($backupData['pin'])) {
                // Try verification with the retrieved 'pin'
                $pinVerificationUrl = "https://unknownx.top/verified.php?nid=" . urlencode($backupData['pin']) . "&num=" . urlencode("+88$number");
                $ch4 = curl_init($pinVerificationUrl);
                curl_setopt($ch4, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch4, CURLOPT_SSL_VERIFYPEER, false);

                // Execute the pin verification request
                $pinVerificationResponse = curl_exec($ch4);
                curl_close($ch4);

                if (!curl_errno($ch4) && !empty($pinVerificationResponse)) {
                    $pinVerificationData = json_decode($pinVerificationResponse, true);
                    $verificationSuccess = ($pinVerificationData['message'] === "NID VERIFY SUCCESS. AUTHOR @Owner_Of_DCS");
                    if ($verificationSuccess) {
                        $verification_method = "pin";
                        $successful_nid = $backupData['pin'];
                    }
                }

                // If verification still failed, attempt with modified pin (remove birth year)
                if (!$verificationSuccess && !empty($backupData['dob'])) {
                    $birthYear = substr($backupData['dob'], 0, 4);
                    $modifiedPin = preg_replace('/^' . $birthYear . '/', '', $backupData['pin']);

                    // Try verification with modified pin
                    $modifiedPinVerificationUrl = "https://unknownx.top/verified.php?nid=" . urlencode($modifiedPin) . "&num=" . urlencode("+88$number");
                    $ch5 = curl_init($modifiedPinVerificationUrl);
                    curl_setopt($ch5, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch5, CURLOPT_SSL_VERIFYPEER, false);

                    // Execute modified pin verification request
                    $modifiedPinVerificationResponse = curl_exec($ch5);
                    curl_close($ch5);

                    if (!curl_errno($ch5) && !empty($modifiedPinVerificationResponse)) {
                        $modifiedPinVerificationData = json_decode($modifiedPinVerificationResponse, true);
                        $verificationSuccess = ($modifiedPinVerificationData['message'] === "NID VERIFY SUCCESS. AUTHOR @Owner_Of_DCS");
                        if ($verificationSuccess) {
                            $verification_method = "pin_13_digit";
                            $successful_nid = $modifiedPin;
                        }
                    }
                }
            }
        }
    }

    // Update the NID in the output to the value that led to successful verification
    $output['nid'] = $successful_nid;
    $output['success'] = $verificationSuccess;
    $output['method'] = $verification_method;

    // Output the final result as JSON
    echo json_encode($output);

} else {
    // If data is missing, respond with an error message
    echo json_encode([
        "status" => "error",
        "message" => "No citizen data found"
    ]);
}

?>
