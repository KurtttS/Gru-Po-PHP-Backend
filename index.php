<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Require database config
require_once 'config/database.php';

// Determine type of action needed
$action = isset($_GET['action']) ? trim($_GET['action']) : '';

$database = new Database();
$conn = $database->getConnection();

switch ($action) {
    case 'register':
        // Register Company
        require_once 'companyAuth/companyAuthModel.php';
        require_once 'companyAuth/companyAuthController.php';
        
        $authModel = new companyAuthModel($conn);
        $controller = new companyAuthController($authModel);
        $controller->toRegisterCompany();
        break;

    case 'register_user':
        // Register Regular PWD User
        require_once 'userAuth/userAuthModel.php';       
        require_once 'userAuth/userAuthController.php';  
        
        $userModel = new UserAuthModel($conn);
        $userController = new userAuthController($userModel);
        $userController->toRegisterUser();
        break;

    case 'login':
        // Login user
        require_once 'loginAuth/loginAuthModel.php';
        require_once 'loginAuth/loginAuthController.php';

        $loginModel = new LoginAuthModel($conn);
        $loginController = new LoginAuthController($loginModel);

        $loginController->toLogin();

        break;

    case 'company_profile':
        // Fetch a company profile by its user ID
        require_once 'model/companyProfileModel.php';
        require_once 'controllers/companyProfileController.php';

        $companyProfileModel = new CompanyProfileModel($conn);
        $companyProfileController = new CompanyProfileController($companyProfileModel);
        $companyProfileController->toFetchCompanyProfile();
        break;
    
    case 'update_company_profile':
        // Update a company profile by its user ID
        require_once 'model/companyProfileModel.php';
        require_once 'controllers/companyProfileController.php';

        $companyProfileModel = new CompanyProfileModel($conn);
        $companyProfileController = new CompanyProfileController($companyProfileModel);
        $companyProfileController->toUpdateCompanyProfile();
        break;

    case 'post_job':
        // Post a new job
        require_once 'model/companyJobModel.php';
        require_once 'controllers/companyJobController.php';

        $companyJobModel = new CompanyJobModel($conn);
        $companyJobController = new CompanyJobController($companyJobModel);
        $companyJobController->toPostJob();
        break;

    case 'fetch_company_jobs':
        // Fetch all jobs posted by a company
        require_once 'model/companyJobModel.php';
        require_once 'controllers/companyJobController.php';

        $companyJobModel = new CompanyJobModel($conn);
        $companyJobController = new CompanyJobController($companyJobModel);
        $companyJobController->toFetchCompanyJobs();
        break;
    
    case 'fetch_company_stats':
        // Fetch company dashboard statistics
        require_once 'model/companyJobModel.php';
        require_once 'controllers/companyJobController.php';

        $companyJobModel = new CompanyJobModel($conn);
        $companyJobController = new CompanyJobController($companyJobModel);
        $companyJobController->toFetchCompanyStats();
        break;
    
    case 'fetch_job_details':
        // Fetch job details by job ID
        require_once 'model/companyJobModel.php';
        require_once 'controllers/companyJobController.php';

        $companyJobModel = new CompanyJobModel($conn);
        $companyJobController = new CompanyJobController($companyJobModel);
        $companyJobController->toFetchJobDetails();
        break;

    case 'fetch_home_jobs':
    case 'fetch_jobs':
        // Fetch the four newest jobs for the home page
        require_once 'model/homeModel.php';
        require_once 'controllers/homeController.php';

        $homeModel = new HomeModel($conn);
        $homeController = new HomeController($homeModel);
        $homeController->toFetchFourJobs();
        break;

    case 'fetch_all_jobs':
        // Fetch all jobs for Android search and category filtering
        require_once 'model/homeModel.php';
        require_once 'controllers/homeController.php';

        $homeModel = new HomeModel($conn);
        $homeController = new HomeController($homeModel);
        $homeController->toFetchAllJobs();
        break;

    case 'search_jobs':
    case 'search_job':
        // Search job listings for the home page
        require_once 'model/homeModel.php';
        require_once 'controllers/homeController.php';

        $homeModel = new HomeModel($conn);
        $homeController = new HomeController($homeModel);
        $homeController->toSearchJobs();
        break;
        
    case 'update_job':
        require_once 'model/companyJobModel.php';
        require_once 'controllers/companyJobController.php';

        $companyJobModel = new CompanyJobModel($conn);
        $companyJobController = new CompanyJobController($companyJobModel);
        $companyJobController->toUpdateJob();
        break;

    case 'pwd_profile':
        require_once 'model/pwdProfileModel.php';
        require_once 'controllers/pwdProfileController.php';

        $pwdProfileModel = new PwdProfileModel($conn);
        $pwdProfileController = new PwdProfileController($pwdProfileModel);
        $pwdProfileController->toFetchPwdProfile();
        break;

    case 'update_pwd_profile':
        require_once 'model/pwdProfileModel.php';
        require_once 'controllers/pwdProfileController.php';

        $pwdProfileModel = new PwdProfileModel($conn);
        $pwdProfileController = new PwdProfileController($pwdProfileModel);
        $pwdProfileController->toUpdatePwdProfile();
        break;

    case 'getJobDetails':
        require_once 'model/applicationModel.php';
        require_once 'controllers/applicationController.php';

        $applicationModel = new ApplicationModel($conn);
        $applicationController = new ApplicationController($applicationModel);
        $applicationController->toGetJobDetails();
        break;

    
    case 'fetch_resume':
        require_once 'model/applicationModel.php';
        require_once 'controllers/applicationController.php';

        $applicationModel = new ApplicationModel($conn);
        $applicationController = new ApplicationController($applicationModel);
        $applicationController->toGetResume();
        break;


    case 'save_resume':
        require_once 'model/applicationModel.php';
        require_once 'controllers/applicationController.php';

        $applicationModel = new ApplicationModel($conn);
        $applicationController = new ApplicationController($applicationModel);
        $applicationController->toSaveResume();
        break;

    case 'apply':
    case 'apply_job':
    case 'create_application':
        require_once 'model/applyModel.php';
        require_once 'controllers/applyController.php';

        $applyModel = new ApplyModel($conn);
        $applyController = new ApplyController($applyModel);
        $applyController->toCreateApplication();
        break;

    case 'check_application':
        require_once 'model/applyModel.php';
        require_once 'controllers/applyController.php';

        $applyModel = new ApplyModel($conn);
        $applyController = new ApplyController($applyModel);
        $applyController->toCheckApplicationStatus();
        break;

    case 'fetch_applicants':
        require_once 'model/applyModel.php';
        require_once 'controllers/applyController.php';

        $applyModel = new ApplyModel($conn);
        $applyController = new ApplyController($applyModel);
        $applyController->toFetchApplicants();
        break;

    case 'delete_application':
    case 'cancel_application':
        require_once 'model/applyModel.php';
        require_once 'controllers/applyController.php';

        $applyModel = new ApplyModel($conn);
        $applyController = new ApplyController($applyModel);
        $applyController->toDeleteApplication();
        break;


    default:
        // Endpoint not found
        http_response_code(404);
        echo json_encode([
            "status" => "error",
            "message" => "Invalid endpoint or action."
        ]);
        break;
}
?>