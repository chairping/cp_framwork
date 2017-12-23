<?php

/**
 * @desc   环信群组集成类
 */
namespace App\User\Logic\HuanXin\Action;

trait GroupTrait
{
    /**
     * @desc  创建群组
     * @param string $groupName 群组名称
     * @param string $desc      群组描述
     * @param int   $public     是否是公开群
     * @param string $owner     群组的管理员
     * @param array $parmas     可选参数
     *          [
     *              'maxusers' => 200 群组成员最大数(包括群主), 值为数值类型,默认值200
     *              'members' => array() 如果加了此项,数组元素至少一个（注：群主jma1不需要写入到members里面）
     *              'approval' => bool 加入公开群是否需要批准, 默认值是true（加群需要群主批准）
     *          ]
     * @author chenping@273.cn 2015-10-12
     */
    public function createGroup($groupName, $desc, $owner, $public = false, $parmas = array()) {
        $this->_setCallableInfo(__FUNCTION__, ['owner' => $owner]);

        $url = $this->createUrl('chatgroups');
        $data = [
            'groupname' => $groupName,
            'desc' => $desc,
            'public' => $public,
            'owner' => (string)$owner,
            'approval' => isset($parmas['approval']) && !empty($parmas['approval']) ? (bool)$parmas['approval'] : false,
            'maxusers' => isset($parmas['maxusers']) && !empty($parmas['maxusers']) ? $parmas['maxusers'] : 2000,
        ];

        if (isset($parmas['members']) && !empty($parmas['members'])) {
            $data['members'] = $parmas['members'];
        }

        return $this->sendRequest($url, $data, 'POST');
    }

    /**
     * @desc  修改群组信息
     * @param $groupname    群组名称，修改时值不能包含斜杠("/")
     * @param $description  群组描述，修改时值不能包含斜杠("/")
     * @param $maxusers     群组成员最大数(包括群主), 值为数值类型
     * @author chenping@273.cn 2015-10-14
     * @return mixed
     */
    public function updateGroup($groupname, $description, $maxusers)
    {
        $maxusers = (int) $maxusers;
        $this->_setCallableInfo(__FUNCTION__);

        $url = $this->createUrl('chatgroups');
        return $this->sendRequest($url, compact('groupname', 'description', 'maxusers'), 'PUT');
    }

    /**
     * @desc  删除群组
     * @param $groupid  群组id
     * @author chenping@273.cn 2015-10-14
     * @return mixed
     */
    public function deleteGrop($groupid)
    {
        $this->_setCallableInfo(__FUNCTION__, compact('groupid'));

        $url = $this->createUrl("chatgroups/{$groupid}");
        return $this->sendRequest($url, array(), 'DELETE');
    }

    /**
     * @desc  添加用户至群组
     * @param string $groupId  群组id
     * @param int $username 用户id
     * @author chenping@273.cn 2015-10-12
     * @return mixed
     */
    public function addMember($groupId, $username)
    {
        $this->_setCallableInfo(__FUNCTION__, ['group_id' => $groupId, 'username' => $username]);

        $url = $this->createUrl("chatgroups/{$groupId}/users/{$username}");
        return $this->sendRequest($url, array(), 'POST');
    }


    public function addBatchMember() {

    }

    /**
     * @desc  删除成员
     * @param $groupid 群id
     * @param $userid  成员id
     * @author chenping@273.cn 2015-10-14
     * @return mixed
     */
    public function deleteMember($groupid, $userid)
    {
        $this->_setCallableInfo(__FUNCTION__, compact('groupid', 'userid'));
        $url = $this->createUrl("chatgroups/{$groupid}/users/{$userid}");
        return $this->sendRequest($url, array(), 'DELETE');
    }






}