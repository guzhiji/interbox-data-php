<?php

LoadIBC1Class('DataItem', 'datamodels');

/**
 * 
 * Demo
 * <code>
 * LoadIBC1Class('UserInfoReader', 'datamodels.user');
 * $r=new UserInfoReader('usertest');
 * try{
 *     $r->Open('webmaster');
 *     echo $r->GetUID()."\n";
 * }catch(ServiceException $ex){
 *     echo $ex->getMessage()."\n";
 * }
 * if($r->CheckPWD('mypwd')) echo 'password: correct!';
 * $r->CloseService();
 * </code>
 * @version 0.6
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2012 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.datamodels.user
 */
class UserInfoReader extends DataItem {

    const E_NOT_FOUND = 1;

    function __construct($ServiceName) {
        parent::__construct();
        $this->OpenService($ServiceName);
    }

    public function OpenService($ServiceName) {
        parent::OpenService($ServiceName, 'user');
    }

    public function CloseService() {
        parent::CloseService();
        $this->Clear();
    }

    public function Open($UID) {

        $conn = $this->GetDBConn();
        $sql = $conn->CreateSelectSTMT($this->GetDataTableName('user'));
        $sql->AddField('usrUID');
        $sql->AddField('usrPWD');
        $sql->AddField('usrFace');
        $sql->AddField('usrNickName');
        $sql->AddField('usrLevel');
        $sql->AddField('usrPoints');
        $sql->AddField('usrLoginCount');
        $sql->AddField('usrLoginIP');
        $sql->AddField("DATE_FORMAT(usrLoginTime,\"%Y-%m-%d %H:%i:%s\")", 'LoginTime');
        $sql->AddField("DATE_FORMAT(usrVisitTime,\"%Y-%m-%d %H:%i:%s\")", 'VisitTime');
        $sql->AddField("DATE_FORMAT(usrRegisterTime,\"%Y-%m-%d %H:%i:%s\")", 'RegisterTime');
        $sql->AddField('usrIsUserAdmin');
        $sql->AddEqual('usrUID', $UID, IBC1_DATATYPE_PLAINTEXT);
        $sql->Execute();
        $r = $sql->Fetch(1);
        $sql->CloseSTMT();
        if ($r == FALSE) {
            throw new ServiceException("user '$UID' is not found", UserInfoReader::E_NOT_FOUND);
        }
        $this->SetValue('UID', $r->usrUID, IBC1_DATATYPE_PLAINTEXT);
        $this->SetValue('PWD', $r->usrPWD, IBC1_DATATYPE_PLAINTEXT);
        $this->SetValue('Face', $r->usrFace, IBC1_DATATYPE_PLAINTEXT);
        $this->SetValue('NickName', $r->usrNickName, IBC1_DATATYPE_PLAINTEXT);
        $this->SetValue('Level', $r->usrLevel, IBC1_DATATYPE_INTEGER);
        $this->SetValue('Points', $r->usrPoints, IBC1_DATATYPE_INTEGER);
        $this->SetValue('LoginCount', $r->usrLoginCount, IBC1_DATATYPE_INTEGER);
        $this->SetValue('LoginIP', $r->usrLoginIP, IBC1_DATATYPE_PLAINTEXT);
        $this->SetValue('LoginTime', $r->LoginTime, IBC1_DATATYPE_PLAINTEXT);
        $this->SetValue('VisitTime', $r->VisitTime, IBC1_DATATYPE_PLAINTEXT);
        $this->SetValue('RegisterTime', $r->RegisterTime, IBC1_DATATYPE_PLAINTEXT);
        $this->SetValue('IsUserAdmin', $r->usrIsUserAdmin, IBC1_DATATYPE_INTEGER);
    }

    public function CheckPWD($PWD) {
        LoadIBC1Lib('PWDSecurity', 'util');
        return IsPassed($PWD, $this->GetValue('PWD'));
    }

    public function GetUID() {
        return $this->GetValue('UID');
    }

    public function GetLevel() {
        return $this->GetValue('Level');
    }

    /*
      public function GetLevelName()
      {
      return $this->;
      }
     */

    public function GetPoints() {
        return $this->GetValue('Points');
    }

    public function GetFace() {
        return $this->GetValue('Face');
    }

    public function GetNickName() {
        return $this->GetValue('NickName');
    }

    public function GetLoginCount() {
        return $this->GetValue('LoginCount');
    }

    public function GetLoginIP() {
        return $this->GetValue('LoginIP');
    }

    public function GetLoginTime() {
        return $this->GetValue('LoginTime');
    }

    public function GetVisitTime() {
        return $this->GetValue('VisitTime');
    }

    public function GetRegisterTime() {
        return $this->GetValue('RegisterTime');
    }

    public function IsUserAdmin() {
        return !!$this->GetValue('IsUserAdmin');
    }

    public function IsOnline() {
        //TODO is online
    }

}
