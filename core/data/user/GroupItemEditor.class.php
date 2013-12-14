<?php

LoadIBC1Class('PropertyList', 'util');
LoadIBC1Class('DataService', 'data');

/**
 *
 * @version 0.7.20111214
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2013 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.data.user
 */
class GroupItemEditor extends PropertyList {

    private $ID = 0;
    private $IsNew = TRUE;
    private $dataservice;

    function __construct($service) {
        $this->dataservice = DataService::GetService($service, 'user');
    }

    public function GetDataService() {
        return $this->dataservice;
    }

    /**
     * to create a new user group.
     * 
     * initializes the editor to receive data,
     *  but does not save data before Save() is invoked.
     */
    public function Create() {
        $this->ID = 0;
        $this->IsNew = TRUE;
    }

    /**
     * to modify an existing user group.
     * 
     * initializes the editor to receive changes,
     *  but does not save changes before Save() is invoked.
     * @param int $id user group id
     */
    public function Open($id) {
        $this->IsNew = FALSE;
        $this->ID = intval($id);
    }

    /**
     * get id if not new.
     * 
     * @return int
     */
    public function GetID() {
        if ($this->IsNew) {
            throw new ServiceException('cannot get the id of an unsaved group');
        }
        return $this->ID;
    }

    /**
     * set user group name (required).
     * 
     * @param string $name  pure text with no html
     */
    public function SetName($name) {
        $this->SetValue('grpName', $name, IBC1_DATATYPE_PLAINTEXT);
    }

    /**
     * set user group owner.
     * 
     * @param string $uid   group owner
     */
    public function SetOwner($uid) {
        if ($uid == '') {
            $this->SetValue('grpOwner', '', IBC1_DATATYPE_PLAINTEXT);
        } else {
            $conn = $this->dataservice->GetDBConn();
            if ($this->IsNew) {
                $sql = $conn->CreateSelectSTMT($this->dataservice->GetDataTableName('user'));
                $sql->AddField('usrUID');
                $sql->AddEqual('usrUID', $uid, IBC1_DATATYPE_PLAINTEXT);
            } else {
                $sql = $conn->CreateSelectSTMT($this->dataservice->GetDataTableName('groupuser'));
                $sql->AddField('gpuUID');
                $sql->AddEqual('gpuUID', $uid, IBC1_DATATYPE_PLAINTEXT);
                $sql->AddEqual('gpuGID', $this->ID, IBC1_DATATYPE_INTEGER, IBC1_LOGICAL_AND);
            }
            $sql->Execute();
            $r = $sql->Fetch(1);
            $sql->CloseSTMT();
            if ($r) {
                $this->SetValue('grpOwner', $uid, IBC1_DATATYPE_PLAINTEXT);
            } else {
                throw new ServiceException('cannot find the user');
            }
        }
    }

    /**
     * set if this is a private user group
     * 
     * @param bool $p 
     */
    public function SetPrivate($p) {
        $this->SetValue('grpType', $p ? 0 : 1, IBC1_DATATYPE_INTEGER);
    }

    /**
     * save the user group into database.
     * 
     * add if Create() invoked
     * update if Open() invoked
     */
    public function Save() {

        if ($this->IsNew) {//create a new user group
            //check essential fields
            if ($this->GetValue('grpName') == NULL) {
                throw new ServiceException('group name has not been set');
            }
            if ($this->GetValue('grpOwner') == NULL) {
                $this->SetValue('grpType', 1); //public if no owner
            }

            //insert
            $conn = $this->dataservice->GetDBConn();
            $sql = $conn->CreateInsertSTMT($this->dataservice->GetDataTableName('group'));
            while (list($key, $item) = $this->GetEach()) {
                $sql->AddValue($key, $item[0], $item[1]);
            }
            $sql->Execute();
            $this->ID = $sql->GetLastInsertID();
            $sql->CloseSTMT();
            $this->IsNew = FALSE;

            //save owner
            if ($this->GetValue('grpOwner') != NULL) {
                $sql = $conn->CreateInsertSTMT($this->dataservice->GetDataTableName('groupuser'));
                $sql->AddValue('gpuUID', $this->GetValue('grpOwner'), IBC1_DATATYPE_PLAINTEXT);
                $sql->AddValue('gpuGID', $this->ID);
                $sql->Execute();
                $sql->CloseSTMT();
            }
        } else if ($this->ID > 0) {//modify the user group
            //check if changed
            if ($this->Count() == 0) {
                throw new ServiceException('no fields have not been changed');
            }

            //update
            $conn = $this->dataservice->GetDBConn();
            $sql = $conn->CreateUpdateSTMT($this->dataservice->GetDataTableName('group'));
            while (list($key, $item) = $this->GetEach()) {
                $sql->AddValue($key, $item[0], $item[1]);
            }
            $sql->AddEqual('grpID', $this->ID);
            $sql->Execute();
            $sql->CloseSTMT();
        } else {
            throw new ServiceException('no user group is open');
        }
    }

    /**
     * add a user into the opened user group
     * @param string $uid   user's id
     */
    public function AddUser($uid) {
        //check if the user group opened
        if ($this->ID <= 0 || $this->IsNew) {
            throw new ServiceException('no user group is open');
        }

        //check if the user exists in the group
        //TODO set UID & GID a unique key
        $conn = $this->dataservice->GetDBConn();
        $sql = $conn->CreateSelectSTMT($this->dataservice->GetDataTableName('groupuser'));
        $sql->AddField('gpuUID');
        $sql->AddEqual('gpuUID', $uid, IBC1_DATATYPE_PLAINTEXT);
        $sql->AddEqual('gpuGID', $this->ID, IBC1_DATATYPE_INTEGER, IBC1_LOGICAL_AND);
        $sql->Execute();
        $r = $sql->Fetch(1);
        $sql->CloseSTMT();
        if ($r) {
            throw new ServiceException('the user is already a member of the group');
        }

        //check if the user exists
        $sql = $conn->CreateSelectSTMT($this->dataservice->GetDataTableName('user'));
        $sql->AddField('usrUID');
        $sql->AddEqual('usrUID', $uid, IBC1_DATATYPE_PLAINTEXT);
        $sql->Execute();
        $r = $sql->Fetch(1);
        $sql->CloseSTMT();
        if (!$r) {
            throw new ServiceException('user not exist');
        }
        //check if the group exists
        $sql = $conn->CreateSelectSTMT($this->dataservice->GetDataTableName('group'));
        $sql->AddField('grpID');
        $sql->AddEqual('grpID', $this->ID);
        $sql->Execute();
        $r = $sql->Fetch(1);
        $sql->CloseSTMT();
        if (!$r) {
            throw new ServiceException('user group not exist');
        }

        //add the user into the group
        $sql = $conn->CreateInsertSTMT($this->dataservice->GetDataTableName('groupuser'));
        $sql->AddValue('gpuUID', $uid, IBC1_DATATYPE_PLAINTEXT);
        $sql->AddValue('gpuGID', $this->ID);
        $sql->Execute();
        $sql->CloseSTMT();
    }

    /**
     * remove a user from the opened user group
     * @param string $uid user's id
     */
    public function RemoveUser($uid) {
        //check if the user group opened
        if ($this->ID <= 0 || $this->IsNew) {
            throw new ServiceException('no user group is open');
        }

        //validate the input parameter
        if ($uid == '') {
            throw new ServiceException('invalid user id');
        }

        //prevent removing the owner from a group
        $conn = $this->dataservice->GetDBConn();
        $sql = $conn->CreateSelectSTMT($this->dataservice->GetDataTableName('group'));
        $sql->AddField('grpOwner');
        $sql->AddEqual('grpID', $this->ID);
        $sql->Execute();
        $r = $sql->Fetch(1);
        $sql->CloseSTMT();
        if ($r) {
            if (strtolower($r->grpOwner) == strtolower($uid)) {
                throw new ServiceException('owner of a group cannot be removed');
            }
        }

        //remove the user from the opened group
        $sql = $conn->CreateDeleteSTMT($this->dataservice->GetDataTableName('groupuser'));
        $sql->AddEqual('gpuUID', $uid, IBC1_DATATYPE_PLAINTEXT);
        $sql->AddEqual('gpuGID', $this->ID, IBC1_DATATYPE_INTEGER, IBC1_LOGICAL_AND);
        $sql->Execute();
        $sql->CloseSTMT();
    }

}
