<?php

LoadIBC1Class('DataModel', 'datamodels');

/**
 * An utility for managing user sessions
 * 
 * Demo:
 * <code>
 * LoadIBC1Class('UserPassport', 'datamodels.user');
 * $up=new UserPassport('usertest');
 * try{
 *     $up->Login('webmaster','mypwd');
 *     echo "login!\n";
 *     echo "welcome ".$up->GetUID()."\n";
 * } catch(Exception $ex) {
 *     echo $ex->getMessage()."\n";
 * }
 * $up->Logout();
 * if(!$up->IsOnline()) echo "logout!\n";
 * $up->CloseService();
 * </code>
 * @version 0.6
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2012 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.datamodels.user
 */
class UserPassport extends DataModel {

    const E_UNAUTHORIZED = 1;

    function __construct($ServiceName) {
        $this->OpenService($ServiceName);
        session_start();
        session_regenerate_id(TRUE);
    }

    public function OpenService($ServiceName) {
        parent::OpenService($ServiceName, 'user');
    }

    public function GetValue($name) {
        if (isset($_SESSION[$this->GetDataTableName($name)])) {
            return $_SESSION[$this->GetDataTableName($name)];
        }
        return NULL;
    }

    protected function SetValue($name, $value) {
        $_SESSION[$this->GetDataTableName($name)] = $value;
    }

    public function IsOnline() {
        return $this->GetValue('UID') != NULL;
//        if ($this->GetValue('UID') == NULL) {
//            @session_destroy();
//            return FALSE;
//        }
//        return TRUE;
    }

    public function IsUserAdmin() {
        return $this->GetValue('IsUserAdmin');
    }

    public function GetUID() {
        return $this->GetValue('UID');
    }

    public function GetFace() {
        return $this->GetValue('Face');
    }

    public function GetNickName() {
        return $this->GetValue('NickName');
    }

    public function GetLevel() {
        return $this->GetValue('Level');
    }

    public function GetPoints() {
        return $this->GetValue('Points');
    }

    public function GetLoginCount() {
        return $this->GetValue('LoginCount');
    }

    public function GetRegisterTime() {
        return $this->GetValue('RegisterTime');
    }

    public function GetLoginTime() {
        return $this->GetValue('LoginTime');
    }

    public function GetLoginIP() {
        return $this->GetValue('LoginIP');
    }

    public function Login($UID, $PWD) {
        $conn = $this->GetDBConn();
        $sql = $conn->CreateSelectSTMT($this->GetDataTableName('user'));
        $sql->AddEqual('usrUID', $UID, IBC1_DATATYPE_PLAINTEXT);
        $sql->Execute();
        $r = $sql->Fetch(1);
        $sql->CloseSTMT();
        if ($r) {
            $codedPWD = $r->usrPWD;
            $loginCount = $r->usrLoginCount;
            $this->SetValue('Face', $r->usrFace);
            $this->SetValue('NickName', $r->usrNickName);
            $this->SetValue('Level', $r->usrLevel);
            $this->SetValue('Points', $r->usrPoints);
            $this->SetValue('RegisterTime', $r->usrRegisterTime);
            $this->SetValue('IsUserAdmin', !!$r->usrIsUserAdmin);
        } else {
            throw new ServiceException('user not found', UserPassport::E_UNAUTHORIZED);
        }
        LoadIBC1Lib('PWDSecurity', 'util');
        if (IsPassed($PWD, $codedPWD)) {

            $t = date('Y-m-d H:i:s');
            $loginCount++;
            $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';

            $sql = $conn->CreateUpdateSTMT($this->GetDataTableName('user'));
            $sql->AddValue('usrLoginCount', 'usrLoginCount+1', IBC1_DATATYPE_EXPRESSION);
            $sql->AddValue('usrLoginTime', $t, IBC1_DATATYPE_PLAINTEXT);
            $sql->AddValue('usrLoginIP', $ip, IBC1_DATATYPE_PLAINTEXT);
            $sql->AddValue('usrIsOnline', 1, IBC1_DATATYPE_INTEGER);
            $sql->AddEqual('usrUID', $UID, IBC1_DATATYPE_PLAINTEXT);
            $sql->Execute();
            $sql->CloseSTMT();
            //refresh session properties...
            $this->SetValue('LoginTime', $t);
            $this->SetValue('LoginIP', $ip);
            $this->SetValue('LoginCount', $loginCount);
            $this->SetValue('UID', $UID);
        } else {
            $this->Logout();
            throw new ServiceException('wrong password', UserPassport::E_UNAUTHORIZED);
        }
    }

    public function Logout() {
        $uid = $this->GetUID();
        if (!empty($uid)) {
            $conn = $this->GetDBConn();
            $sql = $conn->CreateUpdateSTMT($this->GetDataTableName('user'));
            $sql->AddValue('usrIsOnline', 0, IBC1_DATATYPE_INTEGER);
            $sql->AddEqual('usrUID', $uid, IBC1_DATATYPE_PLAINTEXT);
            $sql->Execute();
            $sql->CloseSTMT();
        }
        unset($_SESSION[$this->GetDataTableName('UID')]);
        @session_destroy();
    }

}
