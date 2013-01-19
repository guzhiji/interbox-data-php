<?php

LoadIBC1Class('DataServiceManager', 'datamodels');

/**
 *
 * @version 0.8.20121121
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2012 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.datamodels.catalog
 */
class CatalogServiceManager extends DataServiceManager {

    const E_INSTALLED = 1;
    const E_NOT_INSTALLED = 2;
    const E_INSTALL_FAILED = 3;

    private $_tables = array(
        'content',
        'catalog',
        'admin'
    );

    private function GetTableSQL($middlename, DBConn $conn) {
        $sqlset = array();

        $sqlset[0][0] = $conn->CreateTableSTMT('create');
        $sqlset[0][1] = IBC1_PREFIX . '_' . $middlename . '_content';
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
        $sql->AddField('cntPointValue', IBC1_DATATYPE_INTEGER, 10, FALSE, 0);
        $sql->AddField('cntUID', IBC1_DATATYPE_PLAINTEXT, 256, FALSE);
        $sql->AddField('cntVisitCount', IBC1_DATATYPE_INTEGER, 10, FALSE, 0);
        $sql->AddField('cntAdminLevel', IBC1_DATATYPE_INTEGER, 10, TRUE);
        $sql->AddField('cntVisitLevel', IBC1_DATATYPE_INTEGER, 10, FALSE, 0);

        $sqlset[1][0] = $conn->CreateTableSTMT('create');
        $sqlset[1][1] = IBC1_PREFIX . '_' . $middlename . '_catalog';
        $sql = &$sqlset[1][0];
        $sql->SetTable($sqlset[1][1]);
        $sql->AddField('clgID', IBC1_DATATYPE_INTEGER, 10, FALSE, NULL, TRUE, '', TRUE);
        $sql->AddField('clgName', IBC1_DATATYPE_PLAINTEXT, 256, FALSE);
        $sql->AddField('clgOrdinal', IBC1_DATATYPE_INTEGER, 10, TRUE);
        $sql->AddField('clgUID', IBC1_DATATYPE_PLAINTEXT, 256, TRUE);
        $sql->AddField('clgParentID', IBC1_DATATYPE_INTEGER, 10, FALSE);
        $sql->AddField('clgGID', IBC1_DATATYPE_INTEGER, 10, FALSE, 0);
        $sql->AddField('clgAdminLevel', IBC1_DATATYPE_INTEGER, 10, FALSE);

        $sqlset[2][0] = $conn->CreateTableSTMT('create');
        $sqlset[2][1] = IBC1_PREFIX . '_' . $middlename . '_admin';
        $sql = &$sqlset[2][0];
        $sql->SetTable($sqlset[2][1]);
        $sql->AddField('admID', IBC1_DATATYPE_INTEGER, 10, FALSE, NULL, TRUE, '', TRUE);
        $sql->AddField('admCatalogID', IBC1_DATATYPE_INTEGER, 10, FALSE);
        $sql->AddField('admUID', IBC1_DATATYPE_PLAINTEXT, 256, FALSE);
        return $sqlset;
    }

    function __construct($ServiceName) {
        parent::__construct($ServiceName, 'catalog');
    }

    public function Install() {

        $ServiceName = $this->GetServiceName();
        if ($this->IsInstalled()) {
            throw new ManagerException("service '$ServiceName' has already been installed", CatalogServiceManager::E_INSTALLED);
        }

        //connect to database server
        $conn = $this->GetDBConn();

        //create tables
        $r = $this->CreateTables($this->GetTableSQL($ServiceName, $conn), $conn);
        if ($r == FALSE) {
            throw new ManagerException('fail to create Catalog service', CatalogServiceManager::E_INSTALL_FAILED);
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
            throw new ManagerException("service '$ServiceName' is not installed", CatalogServiceManager::E_NOT_INSTALLED);
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