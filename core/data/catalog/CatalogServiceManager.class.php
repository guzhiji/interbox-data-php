<?php

LoadIBC1Class('DataServiceManager', 'data');

/**
 *
 * @version 0.9.20130123
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2013 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.data.catalog
 */
class CatalogServiceManager extends DataServiceManager {

    private $_tables = array(
        'content',
        'catalog'
    );

    private function GetTableSQL(DBConn $conn) {
        $sqlset = array();

        $sqlset[0][0] = $conn->CreateTableSTMT('create');
        $sqlset[0][1] = $this->GetDataTableName('content');
        $sql = &$sqlset[0][0];
        $sql->SetTable($sqlset[0][1]);
        $sql->AddField('cntID', IBC1_DATATYPE_INTEGER, 10, FALSE, NULL, TRUE, '', TRUE);
        $sql->AddField('cntOrdinal', IBC1_DATATYPE_INTEGER, 10, TRUE, 0);
        $sql->AddField('cntName', IBC1_DATATYPE_PLAINTEXT, 256, FALSE);
        $sql->AddField('cntCatalogID', IBC1_DATATYPE_INTEGER, 10, FALSE, NULL, FALSE, 'parent');
        $sql->AddField('cntAuthor', IBC1_DATATYPE_PLAINTEXT, 256, TRUE);
        $sql->AddField('cntKeywords', IBC1_DATATYPE_WORDLIST, 255, TRUE);
        $sql->AddField('cntTimeCreated', IBC1_DATATYPE_DATETIME, 0, TRUE);
        $sql->AddField('cntTimeUpdated', IBC1_DATATYPE_DATETIME, 0, TRUE);
        $sql->AddField('cntTimeVisited', IBC1_DATATYPE_DATETIME, 0, TRUE);
        $sql->AddField('cntWorth', IBC1_DATATYPE_INTEGER, 10, FALSE, 0);
        $sql->AddField('cntUID', IBC1_DATATYPE_PLAINTEXT, 256, FALSE);
        $sql->AddField('cntVisitCount', IBC1_DATATYPE_INTEGER, 10, FALSE, 0);
        $sql->AddField('cntAdminLevel', IBC1_DATATYPE_INTEGER, 10, FALSE, -1);
        $sql->AddField('cntVisitLevel', IBC1_DATATYPE_INTEGER, 10, FALSE, 0);
        $sql->AddField('cntModule', IBC1_DATATYPE_PLAINTEXT, 256, FALSE);

        $sqlset[1][0] = $conn->CreateTableSTMT('create');
        $sqlset[1][1] = $this->GetDataTableName('catalog');
        $sql = &$sqlset[1][0];
        $sql->SetTable($sqlset[1][1]);
        $sql->AddField('clgID', IBC1_DATATYPE_INTEGER, 10, FALSE, NULL, TRUE, '', TRUE);
        $sql->AddField('clgName', IBC1_DATATYPE_PLAINTEXT, 256, FALSE);
        $sql->AddField('clgOrdinal', IBC1_DATATYPE_INTEGER, 10, TRUE);
        $sql->AddField('clgUID', IBC1_DATATYPE_PLAINTEXT, 256, TRUE);
        $sql->AddField('clgParentID', IBC1_DATATYPE_INTEGER, 10, FALSE);
        $sql->AddField('clgVisitLevel', IBC1_DATATYPE_INTEGER, 10, FALSE, 0);
        $sql->AddField('clgAdminLevel', IBC1_DATATYPE_INTEGER, 10, FALSE, -1);

        return $sqlset;
    }

    function __construct($ServiceName) {
        parent::__construct($ServiceName, 'catalog');
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
            throw new ManagerException('fail to create Catalog service', self::E_INSTALL_FAILED);
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