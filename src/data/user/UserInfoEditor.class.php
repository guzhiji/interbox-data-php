<?php

LoadIBC1Class('DataItem', 'datamodels');
LoadIBC1Class('UserPassport', 'datamodels.user');

/**
 * edit personal information either for modifying an existing user or creating a new user
 * 
 * @version 0.7.20111214
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2012 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.datamodels.user
 */
class UserInfoEditor extends DataItem {

    const E_UNAUTHORIZED = 1;
    const E_EXISTS = 2;
    const E_INCOMPLETE = 3;
    const E_INVALID = 4;
    const E_UNCONFIRMED = 5;
    const E_NOT_ALLOWED = 6;

    private $IsNew = TRUE;
    private $UID = '';
    private $level = 0;
    private $passport = NULL;

    function __construct($ServiceName) {
        parent::__construct();
        $this->OpenService($ServiceName);
    }

    public function OpenService($ServiceName) {
        parent::OpenService($ServiceName, 'user');
    }

    /**
     * open user account with credentials
     * 
     * Demo:
     * <code>
     * LoadIBC1Class('UserInfoEditor','datamodels.user');
     * $e=new UserInfoEditor('usertest');
     * try{
     *     $e->Open('webmaster', 'mypwd');
     *     $e->SetNickName('Tom');
     *     $e->Save();
     *     echo "nick name changed\n";
     * }catch(ServiceException $ex){
     *     switch($ex->getCode()){
     *         case UserInfoEditor::E_UNAUTHORIZED:
     *             echo 'open failed:'.$ex->getMessage()."\n";
     *             break;
     *         default:
     *             echo 'unexpected error:'.$ex->getMessage()."\n";
     *             break;
     *     }
     * }
     * $e->CloseService();
     * </code>
     * 
     * @param string $uid   existing user id
     * @param string $pwd   password
     */
    public function Open($uid, $pwd) {

        LoadIBC1Lib('PWDSecurity', 'util');
        $conn = $this->GetDBConn();
        $sql = $conn->CreateSelectSTMT($this->GetDataTableName('user'));
        $sql->AddField('usrPWD');
        $sql->AddEqual('usrUID', $uid, IBC1_DATATYPE_PLAINTEXT);
        $sql->Execute();
        $r = $sql->Fetch(1);
        $sql->CloseSTMT();
        if ($r && IsPassed($pwd, $r->usrPWD)) {
            $this->UID = $uid;
            $this->IsNew = FALSE;
        } else {
            $this->UID = '';
            throw new ServiceException('unauthorized', UserInfoEditor::E_UNAUTHORIZED);
        }
    }

    /**
     * open user account with a passport of an user admin or the user itself
     * 
     * Demo:
     * <code>
     * define('SERVICENAME', 'usertest');
     * LoadIBC1Class('UserPassport', 'datamodels.user');
     * $up=new UserPassport(SERVICENAME);
     * try{
     *     $up->Login('webmaster','mypwd');
     *     echo "login!\n";
     *     echo "welcome ".$up->GetUID()."\n";
     * } catch(Exception $ex) {
     *     echo $ex->getMessage()."\n";
     * }
     * LoadIBC1Class('UserInfoEditor','datamodels.user');
     * $e=new UserInfoEditor(SERVICENAME);
     * try{
     *     $e->OpenWithPassport('webmaster', $up);
     *     $e->SetNickName('Tom');
     *     $e->Save();
     *     echo "nick name changed\n";
     * }catch(ServiceException $ex){
     *     switch($ex->getCode()){
     *         case UserInfoEditor::E_UNAUTHORIZED:
     *             echo 'open failed:'.$ex->getMessage()."\n";
     *             break;
     *         default:
     *             echo 'unexpected error:'.$ex->getMessage()."\n";
     *             break;
     *     }
     * }
     * $e->CloseService();
     * $up->Logout();
     * if(!$up->IsOnline()) echo "logout!\n";
     * $up->CloseService();
     * </code>
     * 
     * @param string $uid   user id;
     * Note: the user must have a level equal to or lower than the admin's level
     * @param UserPassport $passport    the user admin's passport or the user's own passport
     * @throws ServiceException 
     */
    public function OpenWithPassport($uid, UserPassport $passport) {
        if ($passport->IsOnline()) {
            if ($passport->GetUID() == $uid) {
                $this->UID = $uid;
                $this->IsNew = FALSE;
            } else if ($passport->IsUserAdmin()) {
                $conn = $this->GetDBConn();
                $sql = $conn->CreateSelectSTMT($this->GetDataTableName('user'));
                $sql->AddField('usrLevel');
                $sql->AddEqual('usrUID', $uid, IBC1_DATATYPE_PLAINTEXT);
                $sql->Execute();
                $r = $sql->Fetch(1);
                $sql->CloseSTMT();
                if ($r->usrLevel <= $passport->GetLevel()) {
                    $this->passport = $passport;
                    $this->UID = $uid;
                    $this->level = $r->usrLevel;
                    $this->IsNew = FALSE;
                } else {
                    $this->UID = '';
                }
            }
        }
        if (empty($this->UID)) {
            $this->passport = NULL;
            $this->level = 0;
            throw new ServiceException('unauthorized', UserInfoEditor::E_UNAUTHORIZED);
        }
    }

    /**
     * prepares to create a new user
     * 
     * Demo:
     * <code>
     * LoadIBC1Class('UserInfoEditor','datamodels.user');
     * $e=new UserInfoEditor('usertest');
     * try{
     *     $e->Create('Jim');
     *     $e->SetPWD('jimpwd', 'jimpwd');
     *     $e->Save();
     *     echo "user created\n";
     * }catch(ServiceException $ex){
     *     switch($ex->getCode()){
     *         case UserInfoEditor::E_EXISTS:
     *             echo 'username not available:'.$ex->getMessage()."\n";
     *             break;
     *         case UserInfoEditor::E_INVALID:
     *             echo 'invalid:'.$ex->getMessage()."\n";
     *             break;
     *         default:
     *             echo 'unexpected error:'.$ex->getMessage()."\n";
     *             break;
     *     }
     * }
     * $e->CloseService();
     * </code>
     * 
     * @param string $uid   new user id
     * @throws ServiceException 
     */
    public function Create($uid) {

        if (!ValidateUID($uid)) {
            throw new ServiceException('invalid uid', UserInfoEditor::E_INVALID);
        }

        $conn = $this->GetDBConn();
        $sql = $conn->CreateSelectSTMT($this->GetDataTableName('user'));
        $sql->AddField('usrUID');
        $sql->AddEqual('usrUID', $uid, IBC1_DATATYPE_PLAINTEXT);
        $sql->Execute();
        $r = $sql->Fetch();
        $sql->CloseSTMT();
        if ($r) {
            throw new ServiceException('UID exists', UserInfoEditor::E_EXISTS);
        } else {
            $this->SetValue('usrUID', $uid, IBC1_DATATYPE_PLAINTEXT);
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
            //create new
            //check essential fields
            if ($this->GetValue('usrUID') == NULL) {
                throw new ServiceException('no uid', UserInfoEditor::E_INCOMPLETE);
            }
            if ($this->GetValue('usrPWD') == NULL) {
                throw new ServiceException('no password', UserInfoEditor::E_INCOMPLETE);
            }
            //default level should be the lowest
            $this->SetValue('usrLevel', 1, IBC1_DATATYPE_INTEGER);
            //insert
            $sql = $conn->CreateInsertSTMT($this->GetDataTableName('user'));
            $sql->AddValue('usrRegisterTime', 'CURRENT_TIMESTAMP()', IBC1_DATATYPE_EXPRESSION);
        } else {
            //if changed
            if ($this->Count() == 0) {
                throw new ServiceException('no fields have been changed', UserInfoEditor::E_INCOMPLETE);
            } else if ($this->GetValue('usrUID') != NULL) {
                throw new ServiceException('uid cannot be changed', UserInfoEditor::E_NOT_ALLOWED);
            }
            //update
            $sql = $conn->CreateUpdateSTMT($this->GetDataTableName('user'));
        }
        $this->MoveFirst();
        while (list($key, $item) = $this->GetEach()) {
            $sql->AddValue($key, $item[0], $item[1]);
        }
        $sql->Execute();
        $sql->CloseSTMT();
        if ($this->IsNew) {
            $this->IsNew = FALSE;
            $this->UID = $this->GetValue('usrUID');
        }
    }

    /**
     * set password
     * 
     * @param string $newpwd    new password
     * @param string $repeat    repeat the password for confirmation
     * @throws ServiceException 
     */
    public function SetPWD($newpwd, $repeat) {
        if (!ValidatePWD($newpwd)) {
            throw new ServiceException('invalid password', UserInfoEditor::E_INVALID);
        }
        if ($newpwd != $repeat) {
            throw new ServiceException('unconfirmed password', UserInfoEditor::E_UNCONFIRMED);
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

    public function AddPoints($p) {
        if ($this->IsNew) {
            throw new ServiceException('cannot add points to a new user', UserInfoEditor::E_NOT_ALLOWED);
        }
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

    public function SetLevel($l) {
        if (empty($this->passport)) {
            throw new ServiceException('cannot change the level of this user', UserInfoEditor::E_NOT_ALLOWED);
        }
        if ($l > $this->passport->GetLevel()) {
            throw new ServiceException('the admin does not have privilege to grant the user a higher level than his/her self', UserInfoEditor::E_NOT_ALLOWED);
        }

        $conn = $this->GetDBConn();
        $sql = $conn->CreateSelectSTMT($this->GetDataTableName('level'));
        $sql->AddField('levNumber');
        $sql->AddEqual('levNumber', $l);
        $sql->Execute();
        $r = $sql->Fetch();
        $sql->CloseSTMT();
        if (!$r) {
            throw new ServiceException('invalid level', UserInfoEditor::E_INVALID);
        } else {
            $this->SetValue('usrLevel', $l, IBC1_DATATYPE_INTEGER);
        }
    }

    /**
     * grant the privilege to admin users of lower level
     * @param bool $ua 
     */
    public function SetUserAdmin($ua) {
        if (empty($this->passport)) {
            throw new ServiceException('not permitted to appoint user admins', UserInfoEditor::E_NOT_ALLOWED);
        }
        if ($this->level < 2) {
            throw new ServiceException('this user is not qualified to be an user admin', UserInfoEditor::E_NOT_ALLOWED);
        }
        $this->SetValue('usrIsUserAdmin', $ua ? 1 : 0, IBC1_DATATYPE_INTEGER);
    }

    public function Delete($uid = '') {
        if (empty($this->passport)) {
            throw new ServiceException('not permitted to delete users', UserInfoEditor::E_NOT_ALLOWED);
        }
        $pp = $this->passport;
        if (empty($uid)) {
            $uid = $this->UID;
            if ($this->level > $pp->GetLevel()) {
                throw new ServiceException('the admin does not have privilege to delete a user with a higher level than his/her self', UserInfoEditor::E_NOT_ALLOWED);
            }
        }
        $conn = $this->GetDBConn();
        $sql = $conn->CreateDeleteSTMT($this->GetDataTableName('user'));
        $sql->AddEqual('usrUID', $uid, IBC1_DATATYPE_PLAINTEXT);
        $sql->AddCondition('usrLevel<' . $pp->GetLevel(), IBC1_LOGICAL_AND);
        $sql->Execute();
        $sql->CloseSTMT();
        //TODO set foreign references
    }

}
