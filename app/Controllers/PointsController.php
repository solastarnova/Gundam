<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\UserModel;

/** 提供會員積分中心頁面與相關 API。 */
class PointsController extends Controller
{
    private UserModel $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new UserModel();
    }

    public function center(): void
    {
        $this->requireUser();
        $userId = (int) $_SESSION['user_id'];

        $membershipInfo = $this->userModel->getMembershipInfo($userId);
        $pointsLogs = $this->userModel->getPointsLogs($userId, 30);

        $this->render('account/points', [
            'title' => $this->titleWithSite('account_home'),
            'membershipInfo' => $membershipInfo,
            'pointsLogs' => $pointsLogs,
            'head_extra_css' => [],
        ]);
    }

    public function apiInfo(): void
    {
        $this->setupJsonApi();
        if (!$this->requireAuthForApi()) {
            return;
        }

        $userId = (int) $_SESSION['user_id'];
        $info = $this->userModel->getMembershipInfo($userId);
        $this->json([
            'success' => true,
            'data' => $info,
        ]);
    }

    public function apiLogs(): void
    {
        $this->setupJsonApi();
        if (!$this->requireAuthForApi()) {
            return;
        }

        $userId = (int) $_SESSION['user_id'];
        $limit = max(1, (int) ($_GET['limit'] ?? 30));
        $logs = $this->userModel->getPointsLogs($userId, $limit);

        $this->json([
            'success' => true,
            'logs' => $logs,
        ]);
    }
}
