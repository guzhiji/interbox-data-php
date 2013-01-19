<?php

LoadIBC1Class('DataServiceManager', 'datamodels');

/**
 *
 * @version 0.8.20121121
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2012 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.datamodels.user
 */
class UserServiceManager extends DataServiceManager {

    const E_INSTALLED = 1;
    const E_NOT_INSTALLED = 2;
    const E_INSTALL_FAILED = 3;
    const E_INVALID_VALUE = 4;
    const E_UNCONFIRMED = 5;

    private $_tables = array(
        'user',
        'level',
        'groupuser',
        'group'
    );

    private function GetTableSQL($ServiceName, DBConn $conn) {
        $sqlset = array();

        $sqlset[0][0] = $conn->CreateTableSTMT('create');
        $sqlset[0][1] = IBC1_PREFIX . '_' . $ServiceName . '_user';
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
        $sqlset[1][1] = IBC1_PREFIX . '_' . $ServiceName . '_level';
        $sql = &$sqlset[1][0];
        $sql->SetTable($sqlset[1][1]);
        $sql->AddField('levNumber', IBC1_DATATYPE_INTEGER, 2, FALSE, NULL, TRUE, '', FALSE);
        $sql->AddField('levName', IBC1_DATATYPE_PLAINTEXT, 256, FALSE);

        $sqlset[2][0] = $conn->CreateTableSTMT('create');
        $sqlset[2][1] = IBC1_PREFIX . '_' . $ServiceName . '_groupuser';
        $sql = &$sqlset[2][0];
        $sql->SetTable($sqlset[2][1]);
        $sql->AddField('gpuID', IBC1_DATATYPE_INTEGER, 10, FALSE, NULL, TRUE, '', TRUE);
        $sql->AddField('gpuUID', IBC1_DATATYPE_PLAINTEXT, 256, FALSE);
        $sql->AddField('gpuGID', IBC1_DATATYPE_INTEGER, 10, FALSE);

        $sqlset[3][0] = $conn->CreateTableSTMT('create');
        $sqlset[3][1] = IBC1_PREFIX . '_' . $ServiceName . '_group';
        $sql = &$sqlset[3][0];
        $sql->SetTable($sqlset[3][1]);
        $sql->AddField('grpID', IBC1_DATATYPE_INTEGER, 10, FALSE, NULL, TRUE, '', TRUE);
        $sql->AddField('grpName', IBC1_DATATYPE_PLAINTEXT, 256, FALSE);
        $sql->AddField('grpOwner', IBC1_DATATYPE_PLAINTEXT, 256, TRUE);
        $sql->AddField('grpType', IBC1_DATATYPE_INTEGER, 2, FALSE, 0);

        return $sqlset;
    }

    private function validateUserAdmin($uid, $pwd, $repeat) {
        if (!ValidateUID($uid)) {
            throw new ManagerException('invalid user id', UserServiceManager::E_INVALID_VALUE);
        }if (!ValidatePWD($pwd)) {
            throw new ManagerException('invalid password', UserServiceManager::E_INVALID_VALUE);
        }
        if ($pwd != $repeat) {
            throw new ManagerException('unconfirmed password', UserServiceManager::E_UNCONFIRMED);
        }
    }

    private function addUserAdmin($sql, $uid, $pwd, $level) {
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

    function __construct($ServiceName) {
        parent::__construct($ServiceName, 'user');
    }

    /**
     * create an user service.
     *
     * Demo:
     * <code>
     * LoadIBC1Class('UserServiceManager', 'datamodels.user');
     * $m=new UserServiceManager('usertest');
     * try{
     *     $m->Create(
     *         array(
     *             'level 1',
     *             'level 2',
     *             'level 3'
     *         ),
     *         array(
     *             'uid'=>'webmaster',
     *             'pwd'=>'mypwd',
     *             'repeat'=>'mypwd'
     *         )
     *     );
     *     echo "succeeded\n";
     * } catch(Exception $ex) {
     *     echo $ex->getMessage()."\n";
     * }
     * </code>
     * @param array $LevelList  sequenced user levels from lower to higher
     * structure:
     * <code>
     * array(
     *      'level 1',
     *      'level 2',
     *      'level 3',
     *      ...
     * )
     * </code>
     * @param array $UserAdmin  account of the top level user who administers users
     * structure:
     * <code>
     * array(
     *      'uid'=>'[user id]',
     *      'pwd'=>'[user password]',
     *      'repeat'=>'[repeat password]'
     * )
     * </code>
     * @throws ManagerException 
     */
    public function Install(array $LevelList, array $UserAdmin) {

        //validate user admin
        $uid = $UserAdmin['uid'];
        $pwd = $UserAdmin['pwd'];
        $repeat = $UserAdmin['repeat'];

        $this->validateUserAdmin($uid, $pwd, $repeat);

        //validate levels
        $c = count($LevelList);
        if ($c < 2) {
            throw new ManagerException('at least 2 user levels', UserServiceManager::E_INVALID_VALUE);
        }

        $ServiceName = $this->GetServiceName();
        if ($this->IsInstalled()) {
            throw new ManagerException("service '$ServiceName' has already been installed", UserServiceManager::E_INSTALLED);
        }

        //connect to database server
        $conn = $this->GetDBConn();

        //create tables
        $r = $this->CreateTables($this->GetTableSQL($ServiceName, $conn), $conn);
        if ($r == FALSE) {
            throw new ManagerException("fail to install user service '$ServiceName'", UserServiceManager::E_INSTALL_FAILED);
        }

        //create user levels
        $sql = $conn->CreateInsertSTMT();
        $sql->SetTable(IBC1_PREFIX . '_' . $ServiceName . '_level');
        for ($i = 0; $i < $c; $i++) {
            $sql->AddValue('levNumber', $i + 1); // start from 1
            $sql->AddValue('levName', $LevelList[$i], IBC1_DATATYPE_PLAINTEXT);

            $sql->Execute();
            $sql->ClearValues();
            $sql->CloseSTMT();
        }

        //add the user admin
        $sql->SetTable(IBC1_PREFIX . '_' . $ServiceName . '_user');
        $this->addUserAdmin($sql, $uid, $pwd, $c); //admin as the top level user
    }

    public function IsInstalled() {
        $s = $this->GetServiceName();
        $conn = $this->GetDBConn();
        foreach ($this->_tables as $table) {
            if (!$conn->TableExists(IBC1_PREFIX . '_' . $s . '_' . $table))
                return FALSE;
        }
        return TRUE;
    }

    public function Optimize() {
        $ServiceName = $this->GetServiceName();
        if (!$this->IsInstalled()) {
            throw new ManagerException("service '$ServiceName' is not installed", UserServiceManager::E_NOT_INSTALLED);
        }

        $conn = $this->GetDBConn();
        $sql = $conn->CreateTableSTMT('optimize');
        foreach ($this->_tables as $table) {
            $sql->SetTable(IBC1_PREFIX . '_' . $ServiceName . '_' . $table);
            $sql->Execute();
            $sql->CloseSTMT();
        }
    }

    public function Uninstall() {
        $s = $this->GetServiceName();
        $conn = $this->GetDBConn();
        $sql = $conn->CreateTableSTMT('drop');
        foreach ($this->_tables as $table) {
            $sql->SetTable(IBC1_PREFIX . '_' . $s . '_' . $table);
            $sql->Execute();
            $sql->CloseSTMT();
        }
    }

}