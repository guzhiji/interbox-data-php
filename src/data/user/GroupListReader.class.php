<?php

LoadIBC1Class('DataList', 'datamodels');

/**
 *
 * @version 0.7.20111213
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2012 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.datamodels.user
 */
class GroupListReader extends DataList {

    protected $owner = '';
    protected $user = '';
    protected $type = 0;
    protected $groupname = '';
    protected $groupnameexact = FALSE;

    function __construct($ServiceName) {
        parent::__construct();
        $this->OpenService($ServiceName);
    }

    public function OpenService($ServiceName) {
        parent::OpenService($ServiceName, 'user');
    }

    public function GetGroup($gid) {
        $conn = $this->GetDBConn();
        $sql = $conn->CreateSelectSTMT($this->GetDataTableName('group'));
        $sql->AddField('*');
        $sql->AddEqual('grpID', $gid);
        $sql->Execute();
        $r = $sql->Fetch(1);
        $sql->CloseSTMT();
        if (!$r)
            throw new ServiceException('not found');
        return $r;
    }

    public function LoadGroup($gid) {

        //only support single-page list,it is a single-page list when PageSize=0
        if ($this->GetPageSize() != 0 || $this->GetPageNumber() > 1) {
            throw new ServiceException('only support single-page list');
        }

        $this->AddItem($this->GetGroup($gid));
    }

    /**
     * set the owner constraint on the list loading
     * @param string $uid
     * @param int $type 0=private,1=public,2=all
     */
    public function SetOwner($uid, $type = 2) {
        $this->owner = $uid;
        $this->type = intval($type);
    }

    /**
     * set group name constraint on the list loading
     * @param string $name
     * @param bool $exact 
     */
    public function SetName($name, $exact = FALSE) {
        $this->groupname = $name;
        $this->groupnameexact = $exact;
    }

    /**
     * set participant user constraint on the list loading
     * @param string $uid 
     */
    public function SetUser($uid) {
        $this->user = $uid;
    }

    public function LoadList() {
        $conn = $this->GetDBConn();
        $sql = $conn->CreateSelectSTMT();

        $sql->SetTable($this->GetDataTableName('group'));

        if ($this->groupname != '') {
            if ($this->groupnameexact)
                $sql->AddEqual('grpName', $this->groupname, IBC1_DATATYPE_PLAINTEXT, IBC1_LOGICAL_AND);
            else
                $sql->AddLike('grpName', $this->groupname, IBC1_DATATYPE_PLAINTEXT, IBC1_LOGICAL_AND);
            $sql->AddEqual('grpType', 1, IBC1_DATATYPE_INTEGER, IBC1_LOGICAL_AND); //only public
        }

        if ($this->user != '') {
            $sql->JoinTable($this->GetDataTableName('groupuser'), 'gpuGID=grpID');
            $sql->AddEqual('gpuUID', $this->user, IBC1_DATATYPE_PLAINTEXT, IBC1_LOGICAL_AND);
            $sql->AddEqual('grpType', 1, IBC1_DATATYPE_INTEGER, IBC1_LOGICAL_AND); //only public
        }

        if ($this->owner != '') {
            $sql->AddEqual('grpOwner', $this->owner, IBC1_DATATYPE_PLAINTEXT, IBC1_LOGICAL_AND);
            if ($this->type == 0 || $this->type == 1)
                $sql->AddEqual('grpType', $this->type, IBC1_DATATYPE_INTEGER, IBC1_LOGICAL_AND);
        }

        $sql->AddField('COUNT(grpID)', 'c');
        $this->GetCounts1($sql);

        $sql->ClearFields();
        $sql->AddField('*');
        $sql->SetLimit($this->GetPageSize(), $this->GetPageNumber());

        $sql->Execute();

        $this->Clear();
        while ($r = $sql->Fetch(1)) {
            $this->AddItem($r);
        }
        $this->GetCounts2();
        $sql->CloseSTMT();
    }

    /**
     * open groups that are owned by the user
     * @param string $uid
     * @param int $type 0=private,1=public,2=all
     */
    public function OpenByOwner($uid, $type = 2) {
        $this->SetOwner($uid, $type);
        $this->LoadList();
    }

    /**
     * open groups according to the given name criteria
     * @param string $name
     * @param bool $exact 
     */
    public function OpenByName($name, $exact = FALSE) {
        $this->SetName($name, $exact);
        $this->LoadList();
    }

    /**
     * open groups that the user takes part in
     * @param string $uid 
     */
    public function OpenByUser($uid) {
        $this->SetUser($uid);
        $this->LoadList();
    }

}
