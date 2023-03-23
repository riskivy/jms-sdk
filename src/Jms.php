<?php
namespace Tophant\JmsSdk;

use Tophant\JmsSdk\Services\ApiService;

class Jms
{
    /**
     * 自动登录终端
     */
    public function autoLogin(string $userName): string
    {
        self::openSso();
        $userId = self::getUserId($userName);
        $targetId = self::getTargetId();
        $systemUserId = self::getSystemUserId();
        $params = ['target_id' => $targetId, 'type' => 'rdp', 'system_user_id' => $systemUserId, '_' => floor(microtime(true) * 1000)];
        $data = ['username' => $userName, 'next' => '/lion/?' . http_build_query($params)];
        return str_replace('http://localhost:8080', '', ApiService::post(ApiService::SSO_LOGIN, $data)['login_url']);
    }

    /**
     * 自动登录终端
     */
    public function replay(string $id): string
    {
        $data = ['username' => config('jms-sdk.username'), 'next' => '/luna/replay/' . $id];
        return str_replace('http://localhost:8080', '', ApiService::post(ApiService::SSO_LOGIN, $data)['login_url']);
    }


    /**
     * 会话审计
     */
    public function terminal($params): array
    {
        $data = [
            'is_finished' => 0, // 0 在线会话 1 历史会话
            'date_to' => '', // 查询截止时间
            'date_from' => '', // 查询开始时间
            'search' => '', // 搜索内容
            'offset' => '', // 数据分页下标
            'limit' => '', // 分页数数量
            'display' => 1,
            'draw' => 1
        ];
        $data = array_merge($data, $params);
        return ApiService::get(ApiService::TERMINAL_SESSIONS, $data);
    }

    /**
     * 创建资产
     */
    public function createAsset(string $userName, string $ip, string $username, string $password): string
    {
        $nodeId = self::getAssetNodeId();
        $data = [
            'platform' => 'Windows',
            'protocols' => ["rdp/3389"],
            'nodes' => [$nodeId],
            'is_active' => true,
            'hostname' => config('jms-sdk.target_name'),
            'ip' => $ip
        ];

        $assetData = ApiService::post(ApiService::ASSETS_ASSETS, $data);
        $targetId = $assetData['id'];

        // 创建系统用户，并关联权限
        if ($targetId) {
            $systemUserId = self::createSystemUser($userName, $username, $password);
            self::createPermission($targetId, $systemUserId, $nodeId);
        }

        return $targetId;
    }

    /**
     * 修改资产
     */
    public function updateAsset(string $id, string $userName, string $ip, string $username, string $password): string
    {
        $data = [
            'platform' => 'Windows',
            'protocols' => ["rdp/3389"],
            'nodes' => [self::getAssetNodeId()],
            'is_active' => true,
            'hostname' => config('jms-sdk.target_name'),
            'ip' => $ip
        ];

        ApiService::put(ApiService::ASSETS_ASSETS . $id . '/', $data);

        // 创建系统用户，并关联权限
        if ($id) {
            $systemUserId = self::getSystemUserId();
            self::updateSystemUser($systemUserId, $userName, $username, $password);
        }

        return $id;
    }

    /**
     * 开启SSO免登访问
     */
    private function openSso(): void
    {
        $data = [
            'AUTH_SSO' => true,
            'AUTH_SSO_AUTHKEY_TTL' => 900
        ];

        ApiService::patch(ApiService::SSO_URL, $data);
    }

    /**
     * 获取指定用户的ID
     */
    private function getSystemUserId(): string
    {
        $userData = ApiService::get(ApiService::ASSETS_SYSTEM_USERS, []);
        return $userData[0]['id'];
    }

    /**
     * 创建系统用户
     */
    public function createSystemUser(string $userName, string $username, string $password)
    {
        $data = [
            'protocol' => 'rdp',
            'username_same_with_user' => false,
            'login_mode' => "auto",
            'auto_generate_key' => false,
            'auto_push' => false,
            'name' => $userName,
            'username' => $username,
            'password' => $password
        ];


        $userData = ApiService::post(ApiService::ASSETS_SYSTEM_USERS, $data);
        return $userData['id'];
    }

    /**
     * 修改系统用户
     */
    public function updateSystemUser(string $id, string $userName, string $username, string $password)
    {
        $data = [
            'protocol' => 'rdp',
            'username_same_with_user' => false,
            'login_mode' => "auto",
            'auto_generate_key' => false,
            'auto_push' => false,
            'name' => $userName,
            'username' => $username,
            'password' => $password
        ];

        $userData = ApiService::put(ApiService::ASSETS_SYSTEM_USERS . $id . '/', $data);
        return $userData['id'];
    }

    /**
     * 获取指定用户的ID
     */
    private function getUserId(string $userName): string
    {
        $data = ['username' => $userName];
        $userData = ApiService::get(ApiService::USERS_USER, $data);
        if (empty($userData)) {
            $userId = self::createUser($userName);
        }else{
            $userId = $userData[0]['id'];
        }

        return $userId;
    }

    /**
     * 获取资产节点的ID
     */
    private function getAssetNodeId(): string
    {
        $nodeData = ApiService::get(ApiService::ASSETS_NODE, []);
        return $nodeData[0]['meta']['data']['id'];
    }

    /**
     * 创建用户
     */
    private function createUser(string $userName): string
    {
        $data = [
            'password_strategy' => 'custom',
            'need_update_password' => true,
            'mfa_level' => 0,
            'source' => 'local',
            'date_expired' => '2093-02-06T05:34:25.546714Z',
            'name' => $userName,
            'username' => $userName,
            'email' => time() . '@tophant.com',
            'groups' => [self::getUserGroupId()],
            'system_roles' => ["00000000-0000-0000-0000-000000000003"],
            'password' => 'kgPxtv2cWLUjhus++Ctuzhmp+xY5dV3QkVD2cZHuR8oFGzWkyosoqwoosKj1V1ozd/sZqUbzokH9jGK/7uKjdNoi513UAuBDLO7Ngsssss9Ie3l5uxHilwUig3b7QBKiacdybp3dMOiug5qAdVAa6PRyvMQ6z1P99Fzi9Lvv4iVfBAZg=:W5urJucGCnn/kPUrRszzxQ=='
        ];
        $userData = ApiService::post(ApiService::USERS_USER, $data);
        return $userData['id'];
    }

    /**
     * 获取实操机器的ID
     */
    public function getTargetId(): string
    {
        $data = ['hostname' => config('jms-sdk.target_name')];
        $targetData = ApiService::get(ApiService::ASSETS_ASSETS, $data);
        if (empty($targetData)) {
            return '';
        }

        return $targetData[0]['id'];
    }

    /**
     * 获取用户默认组的ID
     */
    private function getUserGroupId(): string
    {
        $nodeData = ApiService::get(ApiService::USERS_GROUPS, []);
        return $nodeData[0]['id'];
    }

    /**
     * 配置权限
     */
    private function createPermission(string $targetId, string $systemUser, string $nodeId): void
    {
        $data = [
            'assets' => [$targetId],
            'nodes' => [$nodeId],
            'actions' => ["all","connect","upload_file","download_file","updownload","clipboard_copy","clipboard_paste","clipboard_copy_paste"],
            'is_active' => true,
            'date_start' => date('Y').'-01-01T00:00:00.550Z',
            'date_expired' => '2223-02-04T05:59:30.550Z',
            'name' => "授权规则",
            'user_groups' => [self::getUserGroupId()],
            'system_users' => [$systemUser],
        ];
        ApiService::post(ApiService::ASSET_PERMISSIONS, $data);
    }
}
