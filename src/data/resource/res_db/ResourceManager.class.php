<?php

LoadIBC1Class('DataServiceManager', 'datamodels');

/**
 *
 * @version 0.2.20121121
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2012 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.datamodels.resource.database
 */
class ResourceServiceManager extends DataServiceManager {

    const E_INSTALLED = 1;
    const E_NOT_INSTALLED = 2;
    const E_INSTALL_FAILED = 3;

    private $_tables = array(
        'file'
    );

    private function GetTableSQL($middlename, DBConn $conn) {
        $sqlset = array();

        $sqlset[0][0] = $conn->CreateTableSTMT('create');
        $sqlset[0][1] = IBC1_PREFIX . '_' . $middlename . '_file';
        $sql = &$sqlset[0][0];
        $sql->SetTable($sqlset[0][1]);
        $sql->AddField('filID', IBC1_DATATYPE_INTEGER, 10, FALSE, NULL, TRUE, '', TRUE);
        $sql->AddField('filName', IBC1_DATATYPE_PLAINTEXT, 64, FALSE);
        $sql->AddField('filExtName', IBC1_DATATYPE_PLAINTEXT, 8, TRUE);
        $sql->AddField('filSize', IBC1_DATATYPE_INTEGER, 8, FALSE, 0);
        $sql->AddField('filTime', IBC1_DATATYPE_DATETIME, 0, FALSE);
        $sql->AddField('filType', IBC1_DATATYPE_PLAINTEXT, 64, TRUE);
        $sql->AddField('filUID', IBC1_DATATYPE_PLAINTEXT, 64, TRUE);
        $sql->AddField('filData', IBC1_DATATYPE_BINARY);

        return $sqlset;
    }

    function __construct($ServiceName) {
        parent::__construct($ServiceName, 'res_db');
    }

    public function Install() {

        $ServiceName = $this->GetServiceName();
        if ($this->IsInstalled()) {
            throw new ManagerException("service '$ServiceName' has already been installed", ResourceServiceManager::E_INSTALLED);
        }

        //connect to database server
        $conn = $this->GetDBConn();

        //create tables
        $r = $this->CreateTables($this->GetTableSQL($ServiceName, $conn), $conn);
        if ($r == FALSE) {
            throw new ManagerException('fail to create Resource service', ResourceServiceManager::E_INSTALL_FAILED);
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
            throw new ManagerException("service '$ServiceName' is not installed", ResourceServiceManager::E_NOT_INSTALLED);
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

/**
 *
 * @version 0.1
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2012 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.resource.database
 */
LoadIBC1Class("DataServiceManager", "datamodels");

class ResourceManager extends DataServiceManager {

    public function Create($ServiceName, $UserService, $FileTypeList = "txt htm html gif jpg png bmp rar zip", $MaxFileSize = 1048576) {
        $conn = $this->GetDBConn();

        if (!$this->IsInstalled())
            return FALSE;
        if ($this->Exists($ServiceName)) {
            throw new ManagerException("service '$ServiceName' has already been there");
        }
        if (!$this->Exists($UserService, "usr")) {
            throw new ManagerException("user service '$ServiceName' does not exist");
        }

        if (!$conn->TableExists("ibc1_dataservices_resource")) {
            $sqlset[0][0] = $conn->CreateTableSTMT("create", "ibc1_dataservices_resource");
            $sqlset[0][1] = "ibc1_dataservices_resource";
            $sql = &$sqlset[0][0];
            $sql->AddField("ServiceName", IBC1_DATATYPE_PLAINTEXT, 64, FALSE, NULL, TRUE, "", FALSE);
            $sql->AddField("FileTypeList", IBC1_DATATYPE_WORDLIST, 0, FALSE);
            $sql->AddField("MaxFileSize", IBC1_DATATYPE_INTEGER, 8, FALSE, 1048576);
            $sql->AddField("UserService", IBC1_DATATYPE_PLAINTEXT, 64, FALSE);

            /*
              $sql[0]="CREATE TABLE ibc1_dataservices_resource(";
              $sql[0].="ServiceName VARCHAR(64) NOT NULL,";
              $sql[0].="FileTypeList VARCHAR(255) NOT NULL,";
              $sql[0].="MaxFileSize INT(8) NOT NULL DEFAULT 1048576,";
              $sql[0].="UserService VARCHAR(64) NOT NULL,";
              $sql[0].="PRIMARY KEY (ServiceName)";
              $sql[0].=")TYPE=MyISAM DEFAULT CHARSET=utf8;";
             */
            if (!$this->CreateTables($sqlset, $conn)) {
                throw new ManagerException("fail to create a Resource service");
            }
        }

        $sqlset[0][0] = $conn->CreateTableSTMT("create", "ibc1_res" . $ServiceName . "_file");
        $sqlset[0][1] = "ibc1_res" . $ServiceName . "_file";
        $sql = &$sqlset[0][0];
        $sql->AddField("filID", IBC1_DATATYPE_INTEGER, 10, FALSE, NULL, TRUE, "", TRUE);
        $sql->AddField("filName", IBC1_DATATYPE_PLAINTEXT, 64, FALSE);
        $sql->AddField("filExtName", IBC1_DATATYPE_PLAINTEXT, 8, TRUE);
        $sql->AddField("filSize", IBC1_DATATYPE_INTEGER, 8, FALSE, 0);
        $sql->AddField("filTime", IBC1_DATATYPE_DATETIME, 0, FALSE);
        $sql->AddField("filType", IBC1_DATATYPE_PLAINTEXT, 64, TRUE);
        $sql->AddField("filUID", IBC1_DATATYPE_PLAINTEXT, 64, TRUE);
        $sql->AddField("filData", IBC1_DATATYPE_BINARY);


        /*
          $sql[0]="CREATE TABLE IBC1_res".$ServiceName."_File(";
          $sql[0].="filID INT(10) NOT NULL AUTO_INCREMENT,";
          $sql[0].="filName VARCHAR(64) NOT NULL,";
          $sql[0].="filExtName VARCHAR(8) NULL,";
          $sql[0].="filSize INT(8) NOT NULL DEFAULT 0,";
          $sql[0].="filTime TIMESTAMP(14) NOT NULL,";
          $sql[0].="filType VARCHAR(64) NOT NULL,";
          $sql[0].="filUID VARCHAR(64) NOT NULL,";
          $sql[0].="filData BLOB NULL,";
          $sql[0].="PRIMARY KEY(filID)";
          $sql[0].=") TYPE=MyISAM DEFAULT CHARSET=utf8;";
         */

        $r = $this->CreateTables($sqlset, $conn);
        if ($r == FALSE) {
            throw new ManagerException("fail to create Resource service");
        }
        $sql = $conn->CreateInsertSTMT();
        $sql->SetTable("ibc1_dataservices");
        $sql->AddValue("ServiceName", $ServiceName, IBC1_DATATYPE_PLAINTEXT);
        $sql->AddValue("ServiceType", "res", IBC1_DATATYPE_PLAINTEXT);
        $sql->Execute();
        $sql->CloseSTMT();
        $sql->ClearValues();
        $sql->SetTable("ibc1_dataservices_resource");
        $sql->AddValue("ServiceName", $ServiceName, IBC1_DATATYPE_PLAINTEXT);
        $sql->AddValue("UserService", $UserService, IBC1_DATATYPE_PLAINTEXT);
        $sql->AddValue("FileTypeList", $FileTypeList, IBC1_DATATYPE_PLAINTEXT);
        $sql->AddValue("MaxFileSize", $MaxFileSize);
        $sql->Execute();
        $sql->CloseSTMT();
        $sql->ClearValues();
    }

    public function Delete($ServiceName) {

        if (!$this->Exists($ServiceName)) {
            throw new ManagerException("cannot find service '$ServiceName'");
        }
        $conn = $this->GetDBConn();
        $sql = $conn->CreateTableSTMT("drop", "ibc1_res" . $ServiceName . "_file");
        $sql->Execute();
        $sql->CloseSTMT();
        $sql = $conn->CreateDeleteSTMT();
        $sql->AddEqual("ServiceName", $ServiceName, IBC1_DATATYPE_PLAINTEXT);
        $sql->SetTable("ibc1_dataservices");
        $sql->Execute();
        $sql->CloseSTMT();
        $sql->SetTable("ibc1_dataservices_resource");
        $sql->Execute();
        $sql->CloseSTMT();

        return TRUE;
    }

    public function Optimize($ServiceName) {
        if (!$this->Exists($ServiceName)) {
            throw new ManagerException("does not exist");
        }
        $conn = $this->GetDBConn();
        $sql = $conn->CreateTableSTMT("optimize", "ibc1_res" . $ServiceName . "_file");
        $sql->Execute();
        $sql->CloseSTMT();
        return TRUE;
    }

    public function Modify($ServiceName, $FileTypeList = "", $MaxFileSize = 0) {
        $conn = $this->GetDBConn();
        $sql = $conn->CreateUpdateSTMT("ibc1_dataservices_resource");
        $sql->AddEqual("ServiceName", $ServiceName, IBC1_DATATYPE_PLAINTEXT);
        if ($FileTypeList != "")
            $sql->AddValue("FileTypeList", $FileTypeList, IBC1_DATATYPE_PLAINTEXT);
        if ($MaxFileSize > 0)
            $sql->AddValue("MaxFileSize", $MaxFileSize);
        if ($sql->ValueCount() > 0) {
            $r = $sql->Execute();
            $sql->CloseSTMT();
            if (!$r)
                throw new ManagerException("fail to modify");
        }
        throw new ManagerException("no new information is set");
    }

}

?>