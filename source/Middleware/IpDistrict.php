<?php

namespace Middleware;
use Exception\UserException;
use Lib\Ip;

/**
 * ip 区域 限制中间件
 * Class ipDistrict
 * @package Middleware
 */
class IpDistrict extends \Middleware {

    public static $APP = 1;
    public static $AGENCY_APP = 2;
    public static $ADMIN_APP = 3;

    public function call() {

    }

    public function check($type) {

        $loginUserInfo = $this->container->loginUser;

        $ipDistrict = new \Model\Config();

        $ipLimit = $ipDistrict->getIpLimit($type, $loginUserInfo['username']);

        $clientIp = $this->container->request->ip; // 当前访问者ip

        if ($ipLimit) { // 如果用户设置了 限制访问 则进行验证
            $ips = $ipLimit['val'];

            // ip优先于地域设置
            if ($ips) {
                if (!in_array($clientIp, explode(' ', $ips))) {
                    throw new UserException("您设置了ip限制，该ip:" . $clientIp . '禁止访问', 401);
                }
            } else {

                $districtLimit = $ipDistrict->getDistrictLimit($type, $loginUserInfo['username']);

                if ($districtLimit) {

                    $districts = $districtLimit['val'];

                    $districtInfo = Ip::find($clientIp);

                    if (!$districtInfo) {
                        throw new UserException("系统异常，请重试");
                    }

                    $districtInfo = array_filter($districtInfo);

                    $districtArr = [];

                    foreach ($districtInfo as $key => $val) {
                        $districtArr[$key] = $val;

                        if (isset($districtArr[$key -1])) {
                            $districtArr[$key] = $districtArr[$key-1] . $val;
                        }
                    }

                    $isPermit = false;

                    foreach (explode(' ', $districts) as $district) {
                        if (in_array($district, $districtArr, true)) {
                            $isPermit = true;
                            break;
                        }
                    }

                    if ($isPermit === false) {
                        throw new UserException('您设置了区域限制，当前区域无法登录系统', 401);
                    }
                }
            }
        }
    }
}