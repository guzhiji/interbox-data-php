<?php

LoadIBC1Class('PropertyList', 'util');
LoadIBC1Class('DataService', 'data');

/**
 * 
 * Demo
 * <code>
 * LoadIBC1Class('UserInfoReader', 'data.user');
 * $r=new UserInfoReader('usertest');
 * try{
 *     $r->Open('webmaster');
 *     echo $r->GetUID()."\n";
 * }catch(ServiceException $ex){
 *     echo $ex->getMessage()."\n";
 * }
 * if($r->CheckPWD('mypwd')) echo 'password: correct!';
 * </code>
 * @version 0.6
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2013 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.data.user
 */
class UserInfoReader extends PropertyList {

    const E_NOT_FOUND = 1;

    private $_service;
    private $_fields;

    function __construct($service) {
        $this->_service = DataService::GetService($service, 'user');
        $this->_fields = array(
            array('usrUID', 'UID', IBC1_DATATYPE_PLAINTEXT),
            array('usrPWD', 'PWD', IBC1_DATATYPE_PLAINTEXT),
            array('usrFace', 'Face', IBC1_DATATYPE_PLAINTEXT),
            array('usrNickName', 'NickName', IBC1_DATATYPE_PLAINTEXT),
            array('usrLevel', 'Level', IBC1_DATATYPE_INTEGER),
            array('usrPoints', 'Points', IBC1_DATATYPE_INTEGER),
            array('usrLoginCount', 'LoginCount', IBC1_DATATYPE_INTEGER),
            array('usrLoginIP', 'LoginIP', IBC1_DATATYPE_PLAINTEXT),
            array('usrLoginTime', 'LoginTime', IBC1_DATATYPE_PLAINTEXT),
            array('usrVisitTime', 'VisitTime', IBC1_DATATYPE_PLAINTEXT),
            array('usrRegisterTime', 'RegisterTime', IBC1_DATATYPE_PLAINTEXT),
            array('usrIsUserAdmin', 'IsUserAdmin', IBC1_DATATYPE_INTEGER)
        );
    }

    public function GetDataService() {
        return $this->_service;
    }

    public function Open($UID) {
        $conn = $this->_service->GetDBConn();
        $sql = $conn->CreateSelectSTMT($this->_service->GetDataTableName('user'));
        foreach ($this->_fields as $field)
            $sql->AddField($field[0], $field[1]);
        $sql->AddEqual('usrUID', $UID, IBC1_DATATYPE_PLAINTEXT);
        $sql->Execute();
        $r = $sql->Fetch(2);
        $sql->CloseSTMT();
        if ($r == FALSE) {
            throw new ServiceException("user '$UID' is not found", UserInfoReader::E_NOT_FOUND);
        }
        foreach ($this->_fields as $data)
            $this->SetValue($data[1], $r[$data[1]], $data[2]);
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
