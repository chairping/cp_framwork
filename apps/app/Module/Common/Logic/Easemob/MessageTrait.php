<?php
/**
 * @desc   环信消息集成类
 */

namespace App\User\Logic\HuanXin\Action;


trait MessageTrait
{
    /**
     * @desc  发送给消息通用接口
     * @param array $params
     *       [
     *           "target_type" => "users",       // users 给用户发消息, chatgroups 给群发消息
     *           "target" => ["u1", "u2", "u3"], //注意这里需要用数组,数组长度建议不大于20, 即使只有一个用户,也要用数组 ['u1'], 给用户发送时数组元素是用户名,给群组发送时数组元素是groupid
     *            "msg" => [                     //文本消息事例
     *                 "type" => "txt",
     *                  "msg" => "hello from rest" //消息内容，参考[聊天记录](http://www.easemob.com/docs/rest/chatmessage/)里的bodies内容
     *            ],
     *           "from" => "jma2", //表示这个消息是谁发出来的, 可以没有这个属性, 那么就会显示是admin, 如果有的话, 则会显示是这个用户发出的
     *      ]
     * @author chenping@273.cn
     * @return mixed
     */
    public function sendMessage($params) {

        if (!is_array($params['target'])) {
            $params['target'] = [$params['target']];
        }

        if (!isset($params['ext']) || empty($params['ext'])) {
            unset($params['ext']);
        }

        $this->_setCallableInfo(__FUNCTION__, $params);

        $url = $this->createUrl('messages');
        return $this->sendRequest($url, $params, 'POST');
    }

    /**
     * @desc  发送文本消息
     * @param string $from  发送人
     * @param array  $target  要接收信息的用户username
     * @param string $msg  文本消息
     * @param string $target_type   接收消息的媒体类型 users 用户  chatgroups 群
     * @author chenping@273.cn 2015-10-13
     */
    public function sendTxtMessage($from, $target, $msg, $ext = array(), $target_type = 'users') {
        $msg = [
            'type' => 'txt',
            'msg' => $msg,
        ];
        return $this->sendMessage(compact('target_type', 'target', 'msg', 'from', 'ext'));
    }

    /**
     * @desc  发送文本透传消息
     * @param string $from    发送人
     * @param array  $target  要接收信息的用户username
     * @param string $msg     文本消息
     * @param string $target_type   接收消息的媒体类型 users 用户  chatgroups 群
     * @author chenping@273.cn 2015-10-13
     */
    public function sendCmdMessage($from, $target, $action, $ext = array(), $target_type = 'users') {
        $msg = [
            'type' => 'cmd',
            'action' => $action,
        ];
        return $this->sendMessage(compact('target_type', 'target', 'msg', 'from', 'ext'));
    }

    /**
     * @desc  发送欢迎消息到群组
     * @param string $target  接口人（多个传数组）
     * @param array $ext    扩展字段
     *            'group_photo' 群组图片
     *            'nickname'    用户昵称
     *            'user_photo'  用户头像
     *            'group_name'  群组名称
     *            'group_id'    环信群组id
     * @author chenping@273.cn 2015-10-19
     */
    public function welcomeToNewMember($target, $ext) {
        $ext['user_id'] = 4;
        $ext = [
            'group_info' => $ext
        ];

        return $this->sendTxtMessage('', $target, '', $ext, 'chatgroups');
    }

    /**
     * @desc 发送个人名片
     * @param array|string $target  接口人（多个传数组）
     * @param array $from
     *             uid
     *             content      内容
     *             cover_photo  封面图片
     *             title        标题
     *             showName     显示名称
     *             telephone    电话号码
     *             url
     * @param $ext
     * @author chenping@273.cn 2015-10-19
     */
    public function sendPersonalCard($target, $from, $msg, $ext, $target_type = 'users')
    {
        $ext = [
            'card' => $ext
        ];

        return $this->sendTxtMessage($from, $target, $msg, $ext, $target_type);
    }

    /**
     * @desc  发送车源分享
     * @param array|string $target  接口人（多个传数组）
     * @param string $msg    信息
     * @param string $from   发送者
     * @param array $ext
     *               card_time   上牌时间
     *               cover_photo 封面图片
     *               id          车源id
     *               kilometer   公里数 单位万
     *               price       价格
     *               title       标题
     * @param string $target_type
     * @author chenping@273.cn 2015-10-19
     */
    public function sendCarSource($target, $from, $msg, $ext, $target_type = 'users')
    {
        $ext = [
            'car_source' => $ext
        ];

        return $this->sendTxtMessage($from, $target, $msg, $ext, $target_type);
    }
}