
<?php

class ApplicantScreeningController {

    private ApplicantScreeningModel $applicantScreeningModel;

    public function __construct(ApplicantScreeningModel $applicantScreeningModel) {
        $this->applicantScreeningModel = $applicantScreeningModel;
    }

    // Fetch applicants belonging to the company's job listings
    public function fetchCompanyApplicants() {

        header('Content-Type: application/json');

        $userId = $_POST['user_id'] ?? null;

        if (!$userId || !filter_var($userId, FILTER_VALIDATE_INT) || (int)$userId <= 0) {

            echo json_encode([
                "status" => "error",
                "message" => "Invalid company user ID."
            ]);

            return;
        }

        try {

            $applicants = $this->applicantScreeningModel
                ->fetchCompanyApplicants((int)$userId);

            echo json_encode([
                "status" => "success",
                "message" => "Company applicants retrieved successfully.",
                "applicants" => $applicants
            ]);

        } catch (PDOException $e) {

            error_log(
                "APPLICANT SCREENING ERROR: " . $e->getMessage()
            );

            http_response_code(500);

            echo json_encode([
                "status" => "error",
                "message" => "Failed to retrieve company applicants."
            ]);
        }
    }

    
    public function fetchApplicantScreeningDetails() {

        header('Content-Type: application/json');

        $applicationId = $_POST['application_id'] ?? null;
        $userId = $_POST['user_id'] ?? null;

        if (
            !filter_var($applicationId, FILTER_VALIDATE_INT) ||
            (int)$applicationId <= 0 ||
            !filter_var($userId, FILTER_VALIDATE_INT) ||
            (int)$userId <= 0
        ) {

            echo json_encode([
                "status" => "error",
                "message" => "Invalid application or company user ID."
            ]);

            return;
        }

        try {

            $details = $this->applicantScreeningModel
                ->fetchApplicantScreeningDetails(
                    (int)$applicationId,
                    (int)$userId
                );

            if (!$details) {

                http_response_code(404);

                echo json_encode([
                    "status" => "error",
                    "message" => "Applicant or application not found."
                ]);

                return;
            }

            echo json_encode([
                "status" => "success",
                "message" => "Applicant details retrieved successfully.",

                "application_status" => $details['application_status'],

                "applicant" => [
                    "full_name" => $details['applicant_name'],
                    "phone" => $details['applicant_phone'],
                    "address" => $details['applicant_address'],
                    "email" => $details['applicant_email'],
                    "headline" => $details['applicant_headline'],
                    "bio" => $details['applicant_bio'],
                    "accommodation_notes" => $details['accommodation_notes']
                ],

                "job" => [
                    "job_id" => $details['job_id'],
                    "title" => $details['job_title'],
                    "category" => $details['job_category'],
                    "location" => $details['job_location'],
                    "employment_type" => $details['employment_type'],
                    "salary" => $details['salary'],
                    "description" => $details['job_description'],
                    "requirements" => $details['job_requirements']
                ],

                "resume" => [
                    "resume_id" => $details['resume_id'],
                    "title" => $details['resume_title'],
                    "education" => $details['education'],
                    "experience" => $details['experience'],
                    "skills" => $details['skills'],
                    "certifications" => $details['certifications']
                ]
            ]);

        } catch (PDOException $e) {

            error_log(
                "FETCH APPLICANT DETAILS ERROR: " . $e->getMessage()
            );

            http_response_code(500);

            echo json_encode([
                "status" => "error",
                "message" => "Failed to retrieve applicant details."
            ]);
        }
    }


        
    public function updateApplicationStatus() {

        header('Content-Type: application/json');

        $applicationId = $_POST['application_id'] ?? null;
        $userId = $_POST['user_id'] ?? null;
        $status = $_POST['status'] ?? '';

        // Validate IDs
        if (
            !filter_var($applicationId, FILTER_VALIDATE_INT) ||
            (int)$applicationId <= 0 ||
            !filter_var($userId, FILTER_VALIDATE_INT) ||
            (int)$userId <= 0
        ) {

            echo json_encode([
                "status" => "error",
                "message" => "Invalid application or company user ID."
            ]);

            return;
        }

        // Validate application status
        if (!in_array($status, ['accepted', 'declined'], true)) {

            echo json_encode([
                "status" => "error",
                "message" => "Invalid application status."
            ]);

            return;
        }

        try {

            // Step 1: Retrieve applicant's phone number
            $phone = $this->applicantScreeningModel
                ->getApplicantPhone(
                    (int)$applicationId,
                    (int)$userId
                );

            // Step 2: Update application status
            $updated = $this->applicantScreeningModel
                ->updateApplicationStatus(
                    (int)$applicationId,
                    (int)$userId,
                    $status
                );

            // Stop if application was not updated
            if (!$updated) {

                echo json_encode([
                    "status" => "error",
                    "message" => "Application not found, already processed, or not owned by this company."
                ]);

                return;
            }

            // Step 3: Send SMS notification
            $smsSent = false;

            if ($phone !== null) {

                $smsSent = $this->sendApplicationStatusSMS(
                    $phone,
                    $status
                );

            } else {

                error_log(
                    "SMS NOT SENT: No phone number found for application ID "
                    . $applicationId
                );
            }

            // Step 4: Return response to Android
            echo json_encode([
                "status" => "success",

                "message" => $status === 'accepted'
                    ? "Applicant accepted successfully."
                    : "Applicant declined successfully.",

                "application_status" => $status,
                "notification_sent" => $smsSent
            ]);

        } catch (PDOException $e) {

            error_log(
                "UPDATE APPLICATION STATUS ERROR: " . $e->getMessage()
            );

            http_response_code(500);

            echo json_encode([
                "status" => "error",
                "message" => "Failed to update application status."
            ]);
        }
    }


        
    private function sendApplicationStatusSMS(string $phone, string $status): bool {

        $token = '1872|yqvWwnu2f3SS1rGAIXdhWBGmkWpN74bs2Hk0kbaa969cc45a';

        $phone = preg_replace('/[\s\-()]/', '', $phone);

        if (str_starts_with($phone, '+63')) {
            $phone = substr($phone, 1);
        } elseif (str_starts_with($phone, '09')) {
            $phone = '63' . substr($phone, 1);
        } elseif (str_starts_with($phone, '9')) {
            $phone = '63' . $phone;
        }

        // Validate Philippine mobile number
        if (!preg_match('/^639\d{9}$/', $phone)) {
            error_log("PHILSMS ERROR: Invalid applicant phone number.");
            return false;
        }

        // Prepare SMS message
        if ($status === 'accepted') {

            $message = "Good news! Your application has been accepted. "
                    . "The company will contact you regarding the next steps.";

        } else {

            $message = "Thank you for your interest. Unfortunately, "
                    . "your application has been declined. "
                    . "We appreciate your time and effort.";
        }

        $data = [
            'recipient' => $phone,
            'sender_id' => 'PhilSMS',
            'type' => 'plain',
            'message' => $message
        ];

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://app.philsms.com/api/v3/sms/send',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {

            error_log("PHILSMS CURL ERROR: " . curl_error($ch));

            curl_close($ch);
            return false;
        }

        curl_close($ch);

        // Log the API response for debugging
        error_log("PHILSMS HTTP CODE: " . $httpCode);
        error_log("PHILSMS RAW RESPONSE: " . $response);

        $result = json_decode($response, true);

        if (
            $httpCode >= 200 &&
            $httpCode < 300 &&
            isset($result['status']) &&
            $result['status'] === 'success'
        ) {
            return true;
        }

        error_log("PHILSMS API ERROR: Request was not successful.");

        return false;
    }



}
