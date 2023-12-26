<?php

namespace App\Services\Workerman;
declare(ticks=1);
use Illuminate\Support\Facades\Log;
use \GatewayWorker\Lib\Gateway;
use App\Helpers\AuthHelpers;
use App\Repositories\MemberMaterial\PermissionsRepository;
use App\Services\Tgbusiness\TeamReward\GetTeamRewardAssignService;

class Events
{
    public static function onWorkerStart($businessWorker)
    {
        \GatewayWorker\Lib\Gateway::$registerAddress = '127.0.0.1:' . env('WSREGISTERADDRESS', '1236');
    }

    public static function onWebSocketConnect($clientId, $data)
    {
        Log::channel('socket')->info($clientId . " 連線");

        // 確認是否有傳token
        if (!isset($data['get']['token'])) {
            Gateway::sendToClient($clientId, json_encode(['cmd' => 4444]));
            Gateway::closeClient($clientId);
            return;
        }

        //TODO 維護狀態
        // $token = $data['get']['token'];
        // $lang =  $data['get']['lang'] ?? 'zh-cn';

        // token 驗證
        /** @var AuthHelpers $AuthVerification */
        // $authVerification = app()->make(AuthHelpers::class);
        // $result = $authVerification->checkToken($token);

        // 驗證失敗
        // if (!$result['result']) {
        //     Log::channel('socket')->error($clientId . " 1040 token驗證失敗 token=>".$token);
        //     Log::channel('socket')->error(json_encode($result));
        //     Gateway::sendToClient($clientId, json_encode(['cmd' => 1040]));
        //     Gateway::closeClient($clientId);
        //     return;
        // }

        // if(empty($result['info'])){
        //     Log::channel('socket')->error($clientId . " 1040 token驗證成功 但info沒值 token=>".$token);
        //     Log::channel('socket')->error(json_encode($result));
        //     Gateway::sendToClient($clientId, json_encode(['cmd' => 1040]));
        //     Gateway::closeClient($clientId);
        //     return;
        // }
        // $userInfo = $result['info']->toArray() ?? [];

        if(empty($userInfo)){
            Log::channel('socket')->error($clientId . " 1040 token驗證成功 但info toarray沒值 token=>");
            Log::channel('socket')->error(json_encode([]));
            Gateway::sendToClient($clientId, json_encode(['cmd' => 1040]));
            Gateway::closeClient($clientId);
            return;
        }

        // $userName = $userInfo['username'];
        // $userId = (string)$userInfo['id'];


        $connectInfo = [
            'onConnectTime' => date('Y-m-d H:i:s'),
            'lastCheckTime' => time(),
        ];

        Gateway::setSession($clientId, $connectInfo);


        // /** @var PermissionsRepository $PermissionsRepository */
        // $permissionsRepository = app()->make(PermissionsRepository::class);
        // $userRoleId = $result['info']->toArray()['role_id'];
        // //使用者 擁有的 權限id
        // $viewPermissions = array_column($permissionsRepository->getRoleHavePermissions($userRoleId)->toArray(), 'permission_id', 'permission_id');
        // self::assignJoinGroup($clientId,$viewPermissions,$lang);

        // // 依照角色擁有公司線分群
        // $roleData = $permissionsRepository->getRolePermissionsByRoleId($userRoleId);

        // foreach ($roleData->roleCompanies as $item) {
        //     Gateway::joinGroup($clientId, $item->company_id);
        // }

        Log::channel('socket')->info('user連線: ', $connectInfo);
        Gateway::sendToClient($clientId, json_encode(['cmd' => 0]));
    }

    public static function onMessage($clientId, $message)
    {
        if ($message == 'ping') {
            Gateway::sendToClient($clientId, json_encode(['cmd' => 0]));
            return;
        }

        // 確認接收參數為json格式
        $checkJoson = is_string($message) && is_array(json_decode($message,
            true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
        if ($checkJoson) {
            $content = json_decode($message, true);
            $type = $content['type'];
            switch ($type) {
                // case 'subscribe':
                //     self::subscribe($clientId, $content);
                case 'teamRewardAssign':
                    self::teamRewardAssign($clientId, $content);
                    break;
            }
        }
    }

    public static function onClose($clientId)
    {
        Log::channel('socket')->warning("uid: " . $clientId . " 連線中斷");
    }

    private static function subscribe($clientId, $content)
    {
        // $channelType = $content['channelType']??"";

        // //通道類型
        // switch ($channelType) {
        //     case 'teamRewardAssign':
        //         $channel = 'teamRewardAssign-'.auth()->user()->id;
        //         break;
        //     default:
        //         $channel = '';
        //         break;
        // }

        // if ($channel == '') {
        //     $message = '訂閱通道加入失敗!';
        // }else {
        //     //把該client加入通道
        //     Gateway::joinGroup($clientId, $channel);
        //     $message = '訂閱通道加入成功!';
        // }

        // $send = [
        //     'type' => 'subscribe',
        //     'message' => $message,
        //     'channel' => $channel,
        // ];

        // Gateway::sendToClient($clientId, json_encode($send));
    }

    private function teamRewardAssign($clientId,$content)
    {
        // $service = app()->make(GetTeamRewardAssignService::class);
        // $service->process($content,$clientId);
    }
    private static $permissionsToGroupName = [
        10 => ['name' => 'Event', 'lang' => true],
    ];

    // 由權限 判斷加入的group
    private static function assignJoinGroup($clientId,$viewPermissions, $lang)
    {
        foreach (self::$permissionsToGroupName as $needPermission => $groupInfo) {
            if (array_key_exists($needPermission, $viewPermissions)) {
                $groupName = $groupInfo['lang'] ? $groupInfo['name'] . '_' . $lang : $groupInfo['name'];
                Gateway::joinGroup($clientId, $groupName);
                Log::channel('socket')->info('user join group ' . $groupName);
            }
        }
    }
}
