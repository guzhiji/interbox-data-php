<?php

LoadIBC1Class('ItemList', 'util');
LoadIBC1Class('DataService', 'data');
LoadIBC1Class('IPagination', 'data');
LoadIBC1Class('Paginator', 'data');

/**
 *
 * Demo:
 * <code>
 * //load user admins who are online
 * LoadIBC1Class('UserListReader', 'data.user');
 * $list=new UserListReader('usertest');
 * $list->SetUserAdmin(1);
 * $list->SetOnline(1);
 * $list->LoadList();
 * $list->MoveFirst();
 * while($user=$list->GetEach()){
 *     var_dump($user);
 *     echo "<hr />\n";
 * }
 * </code>
 * 
 * @version 0.7.20111213
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2013 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.data.user
 */
class UserListReader extends ItemList implements IPagination {

    protected $_sql = NULL;
    protected $_groupid = 0;
    protected $_service;
    protected $_paginator;
    protected $_fields;

    function __construct($service) {
        $this->_service = DataService::GetService($service, 'user');
        $conn = $this->_service->GetDBConn();
        $this->_sql = $conn->CreateSelectSTMT($this->_service->GetDataTableName('user'));
        $this->_paginator = new Paginator();
        $this->_groupid = 0;
        $this->_fields = array(
            'usrUID' => 'UID',
            'usrLevel' => 'Level',
            'usrPoints' => 'Points',
            'usrIsOnline' => 'IsOnline',
            'usrIsUserAdmin' => 'IsUserAdmin'
        );
    }

    public function GetDataService() {
        return $this->_service;
    }

    public function GetUser($uid) {
        $conn = $this->_service->GetDBConn();
        $sql = $conn->CreateSelectSTMT();
        $sql->SetTable($this->_service->GetDataTableName('user'));
        //$sql->JoinTable($this->_service->GetDataTableName('groupuser'), 'usrUID=gpuUID');
        foreach ($this->_fields as $f => $alias)
            $sql->AddField($f, $alias);
        $sql->AddEqual('usrUID', $uid, IBC1_DATATYPE_PLAINTEXT);
        $sql->Execute();
        $r = $sql->Fetch(1);
        $sql->CloseSTMT();
        if (!$r) {
            throw new ServiceException('not found');
        }
        return $r;
    }

    /**
     * get a user and add it into the list
     * 
     * only support single-page list
     * (it is a single-page list when PageSize=0)
     * @param string $uid 
     */
    public function LoadUser($uid) {

        if ($this->GetPageSize() != 0 || $this->GetPageNumber() > 1) {
            throw new ServiceException('only support single-page list');
        }

        $this->AddItem($this->GetUser($uid));
    }

    public function SetGroup($id) {
        $this->_groupid = intval($id);
    }

    /**
     *
     * @param int $statuscode
     * <ul>
     * <li>0 - [excluded] no online users</li>
     * <li>1 - [only included] only online users</li>
     * <li>2 - [all included] everyone</li>
     * </ul>
     * @param int $timeout default 1 minute
     */
    public function SetOnline($statuscode, $timeout = 60) {
        $timeout = abs(intval($timeout));
        // isonline && not timeout==true
        $sql = $this->_sql;
        switch ($statuscode) {
            case 0:
                $sql->AddCondition('usrIsOnline=0', IBC1_LOGICAL_AND);
                $sql->AddCondition("CURRENT_TIMESTAMP()-usrLoginTime>=$timeout", IBC1_LOGICAL_OR);
                break;
            case 1:
                $sql->AddCondition('usrIsOnline!=0', IBC1_LOGICAL_AND);
                $sql->AddCondition("CURRENT_TIMESTAMP()-usrLoginTime<$timeout", IBC1_LOGICAL_AND);
                break;
        }
    }

    /**
     *
     * @param int $statuscode 
     * <ul>
     * <li>0 - [excluded] no user admins</li>
     * <li>1 - [only included] only user admins</li>
     * <li>2 - [all included] everyone</li>
     * </ul>
     */
    public function SetUserAdmin($statuscode) {
        $sql = $this->_sql;
        switch ($statuscode) {
            case 0:
                $sql->AddCondition('usrIsUserAdmin=0', IBC1_LOGICAL_AND);
                break;
            case 1:
                $sql->AddCondition('usrIsUserAdmin!=0', IBC1_LOGICAL_AND);
                break;
        }
    }

    public function LoadList() {
        $sql = $this->_sql;
        if ($this->_groupid != 0) {
            $sql->JoinTable($this->_service->GetDataTableName('groupuser'), 'usrUID=gpuUID');
            $sql->AddEqual('gpuGID', $this->_groupid, IBC1_DATATYPE_INTEGER, IBC1_LOGICAL_AND);
        }
        $this->_paginator->GetCounts1($this->_sql);
        $this->_service->ListRecords($this, $this->_paginator, $this->_sql, $this->_fields);
        $this->_paginator->GetCounts2($this->Count());
    }

    public function Open($gid) {
        $this->SetGroup($gid);
        $this->LoadList();
    }

    public function OpenOnlineList() {
        $this->SetOnline(1);
        $this->SetUserAdmin(2);
        $this->LoadList();
    }

    public function OpenUserAdminList() {
        $this->SetOnline(2);
        $this->SetUserAdmin(1);
        $this->LoadList();
    }

    public function GetPageCount() {
        return $this->_paginator->PageCount;
    }

    public function GetPageNumber() {
        return $this->_paginator->PageNumber;
    }

    public function GetPageSize() {
        return $this->_paginator->PageSize;
    }

    public function GetTotalCount() {
        return $this->_paginator->TotalCount;
    }

    public function SetPageNumber($n) {
        $this->_paginator->SetPageNumber($n);
    }

    public function SetPageSize($s) {
        $this->_paginator->SetPageSize($s);
    }

}
