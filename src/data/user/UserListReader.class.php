<?php

LoadIBC1Class('DataList', 'datamodels');

/**
 *
 * Demo:
 * <code>
 * //load user admins who are online
 * LoadIBC1Class('UserListReader', 'datamodels.user');
 * $list=new UserListReader('usertest');
 * $list->SetUserAdmin(1);
 * $list->SetOnline(1);
 * $list->LoadList();
 * $list->MoveFirst();
 * while($user=$list->GetEach()){
 *     var_dump($user);
 *     echo "<hr />\n";
 * }
 * $list->CloseService();
 * </code>
 * 
 * @version 0.7.20111213
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2012 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.datamodels.user
 */
class UserListReader extends DataList {

    protected $list_sql = NULL;
    protected $groupid = 0;

    function __construct($ServiceName) {
        parent::__construct();
        $this->OpenService($ServiceName);
    }

    public function OpenService($ServiceName) {
        parent::OpenService($ServiceName, 'user');
        $conn = $this->GetDBConn();
        $this->list_sql = $conn->CreateSelectSTMT($this->GetDataTableName('user'));
        $this->groupid = 0;
    }

    private static function AddFields(IFieldExpList $sql) {
        $sql->AddField('usrUID', 'UID');
        $sql->AddField('usrLevel', 'Level');
        $sql->AddField('usrPoints', 'Points');
        $sql->AddField('usrIsOnline', 'IsOnline');
        $sql->AddField('usrIsUserAdmin', 'IsUserAdmin');
    }

    public function GetUser($uid) {
        $conn = $this->GetDBConn();
        $sql = $conn->CreateSelectSTMT();
        $sql->SetTable($this->GetDataTableName('user'));
        //$sql->JoinTable($this->GetDataTableName('groupuser'), 'usrUID=gpuUID');
        self::AddFields($sql);
        $sql->AddEqual('usrUID', $uid);
        $sql->Execute();
        $r = $sql->Fetch(1);
        $sql->CloseSTMT();
        if (!$r) {
            throw new ServiceException('not found');
        }
        return $r;
    }

    /**
     * get a user and load it into the list
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
        $this->groupid = $id;
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
        if ($this->list_sql != NULL) {
            $sql = $this->list_sql;
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
        if ($this->list_sql != NULL) {
            $sql = $this->list_sql;
            switch ($statuscode) {
                case 0:
                    $sql->AddCondition('usrIsUserAdmin=0', IBC1_LOGICAL_AND);
                    break;
                case 1:
                    $sql->AddCondition('usrIsUserAdmin!=0', IBC1_LOGICAL_AND);
                    break;
            }
        }
    }

    public function LoadList() {
        if ($this->list_sql == NULL) {
            throw new ServiceException('not initialized properly');
        }

        $sql = $this->list_sql;
        if ($this->groupid != 0) {
            $sql->JoinTable($this->GetDataTableName('groupuser'), 'usrUID=gpuUID');
            $sql->AddEqual('gpuGID', $this->groupid, IBC1_DATATYPE_INTEGER, IBC1_LOGICAL_AND);
        }
        $sql->ClearFields();
        $sql->AddField('COUNT(usrUID)', 'c');
        $this->GetCounts1($sql);
        $sql->ClearFields();
        self::AddFields($sql);
        $sql->SetLimit($this->GetPageSize(), $this->GetPageNumber());
        $sql->Execute();
        $this->Clear();
        $this->MoveFirst();
        while ($r = $sql->Fetch(1)) {
            $this->AddItem($r);
        }
        $this->GetCounts2();
        $sql->CloseSTMT();
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

}
