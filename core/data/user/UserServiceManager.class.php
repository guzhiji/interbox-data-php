<?php

LoadIBC1Class('DataServiceManager', 'data');

/**
 *
 * @version 0.8.20130123
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2013 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.data.user
 */
class UserServiceManager extends DataServiceManager {

    private $_tables = array(
        'user',
        'level',
        'groupuser',
        'group'
    );

    private function GetTableSQL(DBConn $conn) {
        $sqlset = array();

        $sqlset[0][0] = $conn->CreateTableSTMT('create');
        $sqlset[0][1] = $this->GetDataTableName('user');
        $sql = &$sqlset[0][0];
        $sql->SetTable($sqlset[0][1]);
        $sql->AddField('usrUID', IBC1_DATATYPE_PLAINTEXT, 256, FALSE, NULL, TRUE, '', FALSE);
        $sql->AddField('usrPWD', IBC1_DATATYPE_PLAINTEXT, 256, FALSE);
        $sql->AddField('usrFace', IBC1_DATATYPE_PLAINTEXT, 256, TRUE);
        $sql->AddField('usrNickName', IBC1_DATATYPE_PLAINTEXT, 256, TRUE);
        $sql->AddField('usrLevel', IBC1_DATATYPE_INTEGER, 2, FALSE, 1);
        $sql->AddField('usrPoints', IBC1_DATATYPE_INTEGER, 10, FALSE, 0);
        $sql->AddField('usrLoginCount', IBC1_DATATYPE_INTEGER, 10, FALSE, 0);
        $sql->AddField('usrLoginIP', IBC1_DATATYPE_PLAINTEXT, 50, TRUE);
        $sql->AddField('usrLoginTime', IBC1_DATATYPE_DATETIME, 0, TRUE);
        $sql->AddField('usrVisitTime', IBC1_DATATYPE_DATETIME, 0, TRUE);
        $sql->AddField('usrRegisterTime', IBC1_DATATYPE_DATETIME, 0, FALSE);
        $sql->AddField('usrIsOnline', IBC1_DATATYPE_INTEGER, 1, FALSE, 0);
        $sql->AddField('usrIsUserAdmin', IBC1_DATATYPE_INTEGER, 1, FALSE, 0);

        $sqlset[1][0] = $conn->CreateTableSTMT('create');
        $sqlset[1][1] = $this->GetDataTableName('level');
        $sql = &$sqlset[1][0];
        $sql->SetTable($sqlset[1][1]);
        $sql->AddField('levNumber', IBC1_DATATYPE_INTEGER, 2, FALSE, NULL, TRUE, '', FALSE);
        $sql->AddField('levName', IBC1_DATATYPE_PLAINTEXT, 256, FALSE);

        $sqlset[2][0] = $conn->CreateTableSTMT('create');
        $sqlset[2][1] = $this->GetDataTableName('groupuser');
        $sql = &$sqlset[2][0];
        $sql->SetTable($sqlset[2][1]);
        $sql->AddField('gpuID', IBC1_DATATYPE_INTEGER, 10, FALSE, NULL, TRUE, '', TRUE);
        $sql->AddField('gpuUID', IBC1_DATATYPE_PLAINTEXT, 256, FALSE);
        $sql->AddField('gpuGID', IBC1_DATATYPE_INTEGER, 10, FALSE);

        $sqlset[3][0] = $conn->CreateTableSTMT('create');
        $sqlset[3][1] = $this->GetDataTableName('group');
        $sql = &$sqlset[3][0];
        $sql->SetTable($sqlset[3][1]);
        $sql->AddField('grpID', IBC1_DATATYPE_INTEGER, 10, FALSE, NULL, TRUE, '', TRUE);
        $sql->AddField('grpName', IBC1_DATATYPE_PLAINTEXT, 256, FALSE);
        $sql->AddField('grpOwner', IBC1_DATATYPE_PLAINTEXT, 256, TRUE);
        $sql->AddField('grpType', IBC1_DATATYPE_INTEGER, 2, FALSE, 0);

        return $sqlset;
    }

    private function validateUserAdmin($uid, $pwd) {
        if (!ValidateUID($uid)) {
            throw new ManagerException('invalid user id', self::E_INSTALL_FAILED);
        }if (!ValidatePWD($pwd)) {
            throw new ManagerException('invalid password', self::E_INSTALL_FAILED);
        }
    }

    private function addUserAdmin($sql, $uid, $pwd, $level) {
        $sql->SetTable($this->GetDataTableName('user'));
        LoadIBC1Lib('PWDSecurity', 'util');
        $sql->ClearValues();
        $pwd = PWDEncode($pwd);
        $sql->AddValue('usrUID', $uid, IBC1_DATATYPE_PLAINTEXT);
        $sql->AddValue('usrPWD', $pwd, IBC1_DATATYPE_PWD);
        $sql->AddValue('usrLevel', $level);
        $sql->AddValue('usrRegisterTime', 'CURRENT_TIMESTAMP()', IBC1_DATATYPE_EXPRESSION);
        $sql->AddValue('usrIsUserAdmin', 1);
        $sql->Execute();
        $sql->CloseSTMT();
    }

    private function addUserLevels($sql, $userlevels, $c) {
        $sql->SetTable($this->GetDataTableName('level'));
        for ($i = 0; $i < $c; $i++) {
            $sql->AddValue('levNumber', $i + 1); // start from 1
            $sql->AddValue('levName', $userlevels[$i], IBC1_DATATYPE_PLAINTEXT);

            $sql->Execute();
            $sql->ClearValues();
            $sql->CloseSTMT();
        }
    }

    function __construct($ServiceName) {
        parent::__construct($ServiceName, 'user');
    }

    /**
     * create an user service.
     *
     * @throws ManagerException 
     */
    public function Install() {

        $userlevels = &$this->extra_args['user_levels'];
        if (!isset($userlevels)) {
            throw new ManagerException('user levels not predefined', self::E_INSTALL_FAILED);
        }

        $initialuser = &$this->extra_args['initial_user'];
        if (!isset($initialuser)) {
            throw new ManagerException('no initial user found', self::E_INSTALL_FAILED);
        }

        //validate the initial user
        $uid = $initialuser['uid'];
        $pwd = $initialuser['pwd'];

        $this->validateUserAdmin($uid, $pwd);

        //validate levels
        $c = count($userlevels);
        if ($c < 2) {
            throw new ManagerException('at least 2 user levels', self::E_INSTALL_FAILED);
        }

        $ServiceName = $this->GetServiceName();
        if ($this->IsInstalled()) {
            throw new ManagerException("service '$ServiceName' has already been installed", self::E_INSTALLED);
        }

        //connect to database server
        $conn = $this->GetDBConn();

        //create tables
        $r = $this->CreateTables($this->GetTableSQL($conn), $conn);
        if ($r == FALSE) {
            throw new ManagerException("fail to install user service '$ServiceName'", self::E_INSTALL_FAILED);
        }

        $sql = $conn->CreateInsertSTMT();

        //create user levels
        $this->addUserLevels($sql, $userlevels, $c);
        //add the initial user
        $this->addUserAdmin($sql, $uid, $pwd, $c); //admin as the top level user
    }

    public function IsInstalled() {
        $conn = $this->GetDBConn();
        foreach ($this->_tables as $table) {
            if (!$conn->TableExists($this->GetDataTableName($table)))
                return FALSE;
        }
        return TRUE;
    }

    public function Optimize() {
        $ServiceName = $this->GetServiceName();
        if (!$this->IsInstalled()) {
            throw new ManagerException("service '$ServiceName' is not installed", self::E_NOT_INSTALLED);
        }

        $conn = $this->GetDBConn();
        $sql = $conn->CreateTableSTMT('optimize');
        foreach ($this->_tables as $table) {
            $sql->SetTable($this->GetDataTableName($table));
            $sql->Execute();
            $sql->CloseSTMT();
        }
    }

    public function Uninstall() {
        $conn = $this->GetDBConn();
        $sql = $conn->CreateTableSTMT('drop');
        foreach ($this->_tables as $table) {
            $sql->SetTable($this->GetDataTableName($table));
            $sql->Execute();
            $sql->CloseSTMT();
        }
    }

}