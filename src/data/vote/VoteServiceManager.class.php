<?php

/**
 *
 * @version 0.2.20121121
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2012 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.datamodels.vote
 */
LoadIBC1Class('DataServiceManager', 'datamodels');

class VoteServiceManager extends DataServiceManager {

    const E_INSTALLED = 1;
    const E_NOT_INSTALLED = 2;
    const E_INSTALL_FAILED = 3;

    private $_tables = array(
        'vote',
        'entry'
    );

    private function GetTableSQL($middlename, DBConn $conn) {
        $sqlset = array();

        $sqlset[0][0] = $conn->CreateTableSTMT('create');
        $sqlset[0][1] = IBC1_PREFIX . '_' . $middlename . '_vote';
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
        $sqlset[1][1] = IBC1_PREFIX . '_' . $middlename . '_entry';
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
            throw new ManagerException("service '$ServiceName' has already been installed", VoteServiceManager::E_INSTALLED);
        }

        //connect to database server
        $conn = $this->GetDBConn();

        //create tables
        $r = $this->CreateTables($this->GetTableSQL($ServiceName, $conn), $conn);
        if ($r == FALSE) {
            throw new ManagerException('fail to create Vote service', VoteServiceManager::E_INSTALL_FAILED);
        }
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

    public function Optimize() {
        $ServiceName = $this->GetServiceName();
        if (!$this->IsInstalled()) {
            throw new ManagerException("service '$ServiceName' is not installed", VoteServiceManager::E_NOT_INSTALLED);
        }

        $conn = $this->GetDBConn();
        $sql = $conn->CreateTableSTMT('optimize');
        foreach ($this->_tables as $table) {
            $sql->SetTable(IBC1_PREFIX . '_' . $ServiceName . '_' . $table);
            $sql->Execute();
            $sql->CloseSTMT();
        }
    }

}
