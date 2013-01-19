<?php

LoadIBC1Class('DataItem', 'datamodels');

/**
 * create, edit and delete any users of lower level than the admin's
 * 
 * @version 0.7.20111214
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2012 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.datamodels.user
 * @deprecated
 */
class UserAdmin extends DataItem {

    private $IsNew = TRUE;
    private $adminlevel = 0;
    private $UID = '';

    function __construct($ServiceName, $uid, $pwd) {
        parent::__construct();
        $this->OpenService($ServiceName, $uid, $pwd);
    }

    /**
     * open data service and grant admin power to a user admin
     * 
     * @param string $ServiceName
     * @param string $uid   user admin's user id
     * @param string $pwd   user admin's password
     */
    public function OpenService($ServiceName, $uid, $pwd) {
        parent::OpenService($ServiceName, 'user');

        //initialize the admin level
        $this->adminlevel = 0;
        //identify user admin
        LoadIBC1Lib('PWDSecurity', 'util');
        $conn = $this->GetDBConn();
        $sql = $conn->CreateSelectSTMT($this->GetDataTableName('user'));
        $sql->AddField('usrLevel');
        $sql->AddField('usrIsUserAdmin');
        $sql->AddEqual('usrUID', $uid, IBC1_DATATYPE_PLAINTEXT);
        $sql->AddEqual('usrPWD', PWDEncode($pwd), IBC1_DATATYPE_PWD, IBC1_LOGICAL_AND);
        $sql->Execute();
        $r = $sql->Fetch(1);
        $sql->CloseSTMT();
        if (!$r) {
            throw new ServiceException('wrong password');
        } else if ($r->usrLevel < 2) {
            throw new ServiceException('user level cannot be lower than 2');
        } else if (!$r->usrIsUserAdmin) {
            throw new ServiceException('the privilege to admin user is not granted to this user');
        } else {
            $this->adminlevel = $r->usrLevel;
        }
    }

    /**
     * prepares to modify existing user information
     * 
     * the user must have a lower level than the admin level
     * @param string $uid   existing user id
     */
    public function Open($uid) {

        $conn = $this->GetDBConn();
        $sql = $conn->CreateSelectSTMT($this->GetDataTableName('user'));
        $sql->AddField('usrIsUserAdmin');
        $sql->AddEqual('usrUID', $uid, IBC1_DATATYPE_PLAINTEXT);
        $sql->Execute();
        $r = $sql->Fetch(1);
        $sql->CloseSTMT();
        if ($r) {
            if ($r->usrIsUserAdmin < $this->adminlevel) {
                $this->UID = $uid;
                $this->IsNew = FALSE;
            } else {
                throw new ServiceException('the user has a higher user level');
            }
        } else {
            throw new ServiceException('not found');
        }
    }

    /**
     * prepares to create a new user
     * 
     * @param string $uid   new user id
     */
    public function Create($uid) {

        if (!ValidateUID($this->UID)) {
            throw new ServiceException('invalid uid');
        }

        $conn = $this->GetDBConn();
        $sql = $conn->CreateSelectSTMT($this->GetDataTableName('user'));
        $sql->AddField('usrUID');
        $sql->AddEqual('usrUID', $uid, IBC1_DATATYPE_PLAINTEXT);
        $sql->Execute();
        $r = $sql->Fetch();
        $sql->CloseSTMT();
        if ($r) {
            throw new ServiceException('UID exists');
        } else {
            $this->SetValue('usrUID', $uid);
            $this->IsNew = TRUE;
        }
    }

    /**
     * save the user into database
     * 
     * add if Create() invoked
     * update if Open() invoked
     */
    public function Save() {

        $conn = $this->GetDBConn();

        if ($this->IsNew) {
            //check essential fields
            if ($this->GetValue('usrUID') == NULL) {
                throw new ServiceException('no uid');
            }
            if ($this->GetValue('usrPWD') == NULL) {
                throw new ServiceException('no password');
            }
            if ($this->adminlevel < 3) {
                $this->SetValue('usrLevel', 1, IBC1_DATATYPE_INTEGER);
            }
            //insert
            $sql = $conn->CreateInsertSTMT($this->GetDataTableName('user'));
            $sql->AddValue('usrRegisterTime', 'CURRENT_TIMESTAMP()', IBC1_DATATYPE_EXPRESSION);
        } else {
            //if changed
            if ($this->Count() == 0) {
                throw new ServiceException('no changes');
            } else if ($this->GetValue('usrUID') != NULL) {
                throw new ServiceException('uid cannot be changed');
            }
            //update
            $sql = $conn->CreateUpdateSTMT($this->GetDataTableName('user'));
            $sql->AddEqual('usrUID', $this->UID, IBC1_DATATYPE_PLAINTEXT);
        }

        $this->MoveFirst();
        while (list($key, $item) = $this->GetEach()) {
            $sql->AddValue($key, $item[0], $item[1]);
        }
        $sql->Execute();
        $sql->CloseSTMT();
        if ($this->IsNew)
            $this->IsNew = FALSE;
    }

    /**
     * set password
     * @param string $newpwd    new password
     * @param string $repeat    repeat the password for confirmation
     */
    public function SetPWD($newpwd, $repeat) {
        if (!ValidatePWD($newpwd)) {
            throw new ServiceException('invalid password');
        }
        if ($newpwd != $repeat) {
            throw new ServiceException('unconfirmed password');
        }
        LoadIBC1Lib('PWDSecurity', 'util');

        $this->SetValue('usrPWD', PWDEncode($newpwd), IBC1_DATATYPE_PWD);
    }

    public function SetFace($f) {
        $this->SetValue('usrFace', $f, IBC1_DATATYPE_PLAINTEXT);
    }

    public function SetNickName($nn) {
        $this->SetValue('usrNickName', $nn, IBC1_DATATYPE_PLAINTEXT);
    }

    public function SetLevel($l) {
        if ($l >= $this->adminlevel) {
            throw new ServiceException('the admin does not have power to grant a user of higher level');
        }

        $conn = $this->GetDBConn();
        $sql = $conn->CreateSelectSTMT($this->GetDataTableName('level'));
        $sql->AddField('levNumber');
        $sql->AddEqual('levNumber', $l);
        $sql->Execute();
        $r = $sql->Fetch();
        $sql->CloseSTMT();
        if (!$r) {
            throw new ServiceException('not exist');
        } else {
            $this->SetValue('usrLevel', $l, IBC1_DATATYPE_INTEGER);
        }
    }

    public function AddPoints($p) {
        $p = intval($p);
        if ($p != 0) {
            if ($p > 0)
                $e = 'usrPoints+' . abs($p);
            else
                $e = 'usrPoints-' . abs($p);
            $this->SetValue('usrPoints', $e, IBC1_DATATYPE_EXPRESSION);
        }
    }

    public function ClearPoints() {
        $this->SetValue('usrPoints', 0);
    }

    /**
     * grant the power to admin users of lower level
     * @param bool $ua 
     */
    public function SetUserAdmin($ua) {
        $this->SetValue('usrIsUserAdmin', $ua ? 1 : 0, IBC1_DATATYPE_INTEGER);
    }

    public function Delete($uid = '') {
        if ($uid == '')
            $uid = $this->UID;
        $conn = $this->GetDBConn();
        $sql = $conn->CreateDeleteSTMT($this->GetDataTableName('user'));
        $sql->AddEqual('usrUID', $uid, IBC1_DATATYPE_PLAINTEXT);
        $sql->AddCondition('usrLevel<' . $this->adminlevel, IBC1_LOGICAL_AND);
        $sql->Execute();
        $sql->CloseSTMT();
        //TODO set foreign references
    }

}
