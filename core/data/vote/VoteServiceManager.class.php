<?php

LoadIBC1Class('DataServiceManager', 'data');

/**
 *
 * @version 0.3.20130123
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2013 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.data.vote
 */
LoadIBC1Class('DataServiceManager', 'data');

class VoteServiceManager extends DataServiceManager {

    private $_tables = array(
        'vote',
        'entry'
    );

    private function GetTableSQL(DBConn $conn) {
        $sqlset = array();

        $sqlset[0][0] = $conn->CreateTableSTMT('create');
        $sqlset[0][1] = $this->GetDataTableName('vote');
        $sql = &$sqlset[0][0];
        $sql->SetTable($sqlset[0][1]);
        $sql->AddField('votID', IBC1_DATATYPE_INTEGER, 10, FALSE, NULL, TRUE, '', TRUE);
        $sql->AddField('votCaption', IBC1_DATATYPE_PLAINTEXT, 256, FALSE);
        $sql->AddField('votStartTime', IBC1_DATATYPE_DATETIME, 0, FALSE);
        $sql->AddField('votEndTime', IBC1_DATATYPE_DATETIME, 0, TRUE);
        $sql->AddField('votMaxNumber', IBC1_DATATYPE_INTEGER, 10, FALSE, -1);
        $sql->AddField('votMinNumber', IBC1_DATATYPE_INTEGER, 10, FALSE, 1);
        $sql->AddField('votContentID', IBC1_DATATYPE_INTEGER, 10, TRUE);
        $sql->AddField('votCatalogService', IBC1_DATATYPE_PLAINTEXT, 256, TRUE);

        $sqlset[1][0] = $conn->CreateTableSTMT('create');
        $sqlset[1][1] = $this->GetDataTableName('entry');
        $sql = &$sqlset[1][0];
        $sql->SetTable($sqlset[1][1]);
        $sql->AddField('entID', IBC1_DATATYPE_INTEGER, 10, FALSE, NULL, TRUE, '', TRUE);
        $sql->AddField('entVoteID', IBC1_DATATYPE_INTEGER, 10, FALSE);
        $sql->AddField('entText', IBC1_DATATYPE_PLAINTEXT, 256, FALSE);
        $sql->AddField('entValue', IBC1_DATATYPE_INTEGER, 10, FALSE, 0);

        return $sqlset;
    }

    function __construct($ServiceName) {
        parent::__construct($ServiceName, 'vote');
    }

    public function Install() {

        $ServiceName = $this->GetServiceName();
        if ($this->IsInstalled()) {
            throw new ManagerException("service '$ServiceName' has already been installed", self::E_INSTALLED);
        }

        //connect to database server
        $conn = $this->GetDBConn();

        //create tables
        $r = $this->CreateTables($this->GetTableSQL($conn), $conn);
        if ($r == FALSE) {
            throw new ManagerException('fail to create Vote service', self::E_INSTALL_FAILED);
        }
    }

    public function IsInstalled() {
        $conn = $this->GetDBConn();
        foreach ($this->_tables as $table) {
            if (!$conn->TableExists($this->GetDataTableName($table)))
                return FALSE;
        }
        return TRUE;
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

}
