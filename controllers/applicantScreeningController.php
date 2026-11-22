
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

        if (!in_array($status, ['accepted', 'declined'], true)) {

            echo json_encode([
                "status" => "error",
                "message" => "Invalid application status."
            ]);

            return;
        }

        try {

            $updated = $this->applicantScreeningModel
                ->updateApplicationStatus(
                    (int)$applicationId,
                    (int)$userId,
                    $status
                );

            if (!$updated) {

                echo json_encode([
                    "status" => "error",
                    "message" => "Application not found, already processed, or not owned by this company."
                ]);

                return;
            }

            echo json_encode([
                "status" => "success",
                "message" => $status === 'accepted'
                    ? "Applicant accepted successfully."
                    : "Applicant declined successfully.",
                "application_status" => $status
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


}
