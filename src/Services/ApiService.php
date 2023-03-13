<?php
namespace Tophant\JmsSdk\Services;

use GuzzleHttp\Client;

class ApiService
{
    const SSO_URL = '/api/v1/settings/setting/?category=sso';  // 开启SSO登录
    const ASSET_PERMISSIONS = '/api/v1/perms/asset-permissions/'; // 配置权限
    const AUTH_LOGIN= '/api/v1/authentication/auth/'; // 登录用户获取token
    const SSO_LOGIN = '/api/v1/authentication/sso/login-url/'; // SSO 免登地址获取
    const USERS_USER = '/api/v1/users/users/'; // 用户查询
    const ASSETS_ASSETS = '/api/v1/assets/assets/'; // 资产查询
    const ASSETS_SYSTEM_USERS = '/api/v1/assets/system-users/'; // 系统用户查询
    const TERMINAL_SESSIONS =  '/api/v1/terminal/sessions/'; // 会话记录
    const ASSETS_NODE = '/api/v1/assets/nodes/children/tree/'; // 获取 node ID
    const USERS_GROUPS = '/api/v1/users/groups/'; // 用户组接口

    /**
     * Api post request to jms
     */
    public static function post(string $path, array $data, $isAuth = true): array
    {
        $client = new Client(['verify' => false]);
        $header = [ //设置头信息
            'Content-Type' => 'application/json'
        ];

        if ($isAuth) {
            $header['Authorization'] = "Bearer " . self::getToken();
        }

        $res = $client->request('POST', config('jms-sdk.base_url') . $path, [
            'headers' => $header,
            'json' => $data,
            'timeout' => 3, //超时时间（秒）
        ]);
        $body = $res->getBody();
        return json_decode($body->getContents(), true);
    }

    /**
     * Api put request to jms
     */
    public static function put(string $path, array $data, $isAuth = true): array
    {
        $client = new Client(['verify' => false]);
        $header = [ //设置头信息
            'Content-Type' => 'application/json'
        ];

        if ($isAuth) {
            $header['Authorization'] = "Bearer " . self::getToken();
        }

        $res = $client->request('PUT', config('jms-sdk.base_url') . $path, [
            'headers' => $header,
            'json' => $data,
            'timeout' => 3, //超时时间（秒）
        ]);
        $body = $res->getBody();
        return json_decode($body->getContents(), true);
    }


    /**
     * Api PATCH request to jms
     */
    public static function patch(string $path, array $data): array
    {
        $client = new Client(['verify' => false]);
        $header = [ //设置头信息
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer " . self::getToken()
        ];

        $res = $client->request('PATCH', config('jms-sdk.base_url') . $path, [
                'headers' => $header,
            'json' => $data,
            'timeout' => 3, //超时时间（秒）
        ]);
        $body = $res->getBody();
        return json_decode($body->getContents(), true);
    }

    /**
     * Api get request to jms
     */
    public static function get(string $path, array $data): array
    {
        $client = new Client(['verify' => false]);
        $res = $client->request('GET', config('jms-sdk.base_url') . $path, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer " . self::getToken(),
            ],
            'query' => $data,
            'timeout' => 3, //超时时间（秒）
        ]);
        $body = $res->getBody();
        return json_decode($body->getContents(), true);
    }

    /**
     * 获取管理员token
     */
    private static function getToken(): string
    {
        $data = ['username' => config('jms-sdk.username'), 'password' => config('jms-sdk.password')];
        $user = ApiService::post(ApiService::AUTH_LOGIN, $data, false);
        return $user['token'];
    }
}