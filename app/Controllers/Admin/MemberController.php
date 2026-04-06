<?php

namespace App\Controllers\Admin;

use App\Core\Config;
use App\Models\MemberModel;
use App\Models\VipLevelModel;
use App\Services\VipLevelService;

class MemberController extends BaseController
{
    private MemberModel $memberModel;
    private VipLevelModel $vipLevelModel;
    private VipLevelService $vipLevelService;

    public function __construct()
    {
        parent::__construct();
        $this->memberModel = new MemberModel();
        $this->vipLevelModel = new VipLevelModel();
        $this->vipLevelService = new VipLevelService();
    }

    public function index(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = (int) Config::get('admin.list_per_page', 15);
        $search = trim((string) ($_GET['search'] ?? ''));
        $vipLevelId = (int) ($_GET['vip_level_id'] ?? 0);

        $result = $this->memberModel->getMembersForAdmin([
            'search' => $search,
            'vip_level_id' => $vipLevelId,
        ], $page, $limit);

        $this->render('members/index', [
            'title' => '會員管理',
            'members' => $result['rows'],
            'levels' => $this->vipLevelModel->getAllLevels(),
            'search' => $search,
            'vip_level_id' => $vipLevelId,
            'page' => $page,
            'limit' => $limit,
            'total' => $result['total'],
        ]);
    }

    public function detail(int $id): void
    {
        $id = (int) $id;
        $member = $this->memberModel->getMemberProfile($id);
        if (!$member) {
            $this->setError(Config::get('messages.admin.user_not_found'));
            $this->redirect('/admin/members');
            return;
        }

        $orders = $this->memberModel->getMemberOrderHistory($id, 20);

        $this->render('members/detail', [
            'title' => '會員詳情',
            'member' => $member,
            'orders' => $orders,
            'benefits' => $this->vipLevelService->parseBenefits($member['benefits_json'] ?? null),
        ]);
    }

    public function levelConfig(): void
    {
        $this->render('members/level-config', [
            'title' => '等級配置',
            'levels' => $this->vipLevelModel->getAllLevels(),
        ]);
    }

    public function updateLevelConfig(int $id): void
    {
        if (!$this->requireAdminCsrf()) {
            $this->setError(Config::get('messages.admin_login.csrf_invalid'));
            $this->redirect('/admin/members/levels');
            return;
        }

        $id = (int) $id;
        $discountRate = (float) ($_POST['discount_rate'] ?? 100);
        $freeShippingThreshold = (float) ($_POST['free_shipping_threshold'] ?? 99999999);
        $description = trim((string) ($_POST['description'] ?? ''));

        if ($discountRate <= 0 || $discountRate > 100) {
            $this->setError('折扣率必須在 0 到 100 之間');
            $this->redirect('/admin/members/levels');
            return;
        }

        $benefits = [
            'free_shipping_threshold' => $freeShippingThreshold,
            'description' => $description,
        ];

        $ok = $this->vipLevelModel->updateLevelConfig($id, $discountRate, $benefits);
        if (!$ok) {
            $this->setError('更新失敗，請稍後再試');
            $this->redirect('/admin/members/levels');
            return;
        }

        $this->setSuccess('會員等級配置已更新');
        $this->redirect('/admin/members/levels');
    }
}
