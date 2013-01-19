<?php

/**
 *
 * @version 0.2
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2012 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.resource.filesystem
 */
LoadIBC1Class("DataServiceManager", "datamodels");

class ResourceManager extends DataServiceManager {

    public function Create($ServiceName, $UserService, $Root, $FileTypeList="txt htm html gif jpg png bmp rar zip", $MaxFileSize=1048576) {
        $conn = $this->GetDBConn();
        $this->GetError()->Clear();
        if (!$this->IsInstalled())
            return FALSE;
        if ($this->Exists($ServiceName)) {
            throw new ManagerException(4, "���� '$ServiceName' ���ѽ���|service '$ServiceName' has already been there");
            return FALSE;
        }
        if (!$this->Exists($UserService, "usr")) {
            throw new ManagerException(4, "�û����� '$UserService' ������|user service '$ServiceName' does not exist");
            return FALSE;
        }
        $Root = str_replace("\\", "/", $Root);
        if (substr($Root, -1) != "/")
            $Root.="/";
        if (!is_dir($Root)) {
            $ra = explode("/", $Root);
            $r = $ra[0] . "/";
            for ($i = 1; $i < count($ra); $i++) {
                if ($ra[$i] == "")
                    continue;
                $r.=$ra[$i] . "/";
                @mkdir($r, 0777);
            }

            //throw new ManagerException(4,"|the root does not exist");
            //return FALSE;
        }
        if (!$conn->TableExists("ibc1_dataservices_resource")) {
            $sqlset[0][0] = $conn->CreateTableSTMT("create", "ibc1_dataservices_resource");
            $sqlset[0][1] = "ibc1_dataservices_resource";
            $sql = &$sqlset[0][0];
            $sql->AddField("ServiceName", IBC1_DATATYPE_PLAINTEXT, 64, FALSE, NULL, TRUE, "", FALSE);
            $sql->AddField("Root", IBC1_DATATYPE_PLAINTEXT, 256, FALSE);
            $sql->AddField("FileTypeList", IBC1_DATATYPE_WORDLIST, 0, FALSE);
            $sql->AddField("MaxFileSize", IBC1_DATATYPE_INTEGER, 8, FALSE, 1048576);
            $sql->AddField("UserService", IBC1_DATATYPE_PLAINTEXT, 64, FALSE);

            /*
              $sql[0]="CREATE TABLE ibc1_dataservices_resource(";
              $sql[0].="ServiceName VARCHAR(64) NOT NULL,";
              $sql[0].="Root VARCHAR(255) NOT NULL,";
              $sql[0].="FileTypeList VARCHAR(255) NOT NULL,";
              $sql[0].="MaxFileSize INT(8) NOT NULL DEFAULT 1048576,";
              $sql[0].="UserService VARCHAR(64) NOT NULL,";
              $sql[0].="PRIMARY KEY (ServiceName)";
              $sql[0].=")TYPE=MyISAM DEFAULT CHARSET=utf8;";
             */
            if (!$this->CreateTables($sqlset, $conn)) {
                throw new ManagerException(3, "Resource������ʧ��|fail to create a Resource service");
                return FALSE;
            }
        }
        if ($this->GetError()->HasError())
            return FALSE;

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
        $sql->AddField("filDir", IBC1_DATATYPE_INTEGER, 10, FALSE, 0);


        /*
          $sql[0]="CREATE TABLE IBC1_res".$ServiceName."_File(";
          $sql[0].="filID INT(10) NOT NULL AUTO_INCREMENT,";
          $sql[0].="filName VARCHAR(64) NOT NULL,";
          $sql[0].="filExtName VARCHAR(8) NULL,";
          $sql[0].="filSize INT(8) NOT NULL DEFAULT 0,";
          $sql[0].="filTime TIMESTAMP(14) NOT NULL,";
          $sql[0].="filType VARCHAR(64) NOT NULL,";
          $sql[0].="filUID VARCHAR(64) NOT NULL,";
          $sql[0].="filDir INT(10) NOT NULL DEFAULT 0,";
          $sql[0].="PRIMARY KEY(filID)";
          $sql[0].=") TYPE=MyISAM DEFAULT CHARSET=utf8;";
         */

        $r = $this->CreateTables($sqlset, $conn);
        if ($r == FALSE) {
            throw new ManagerException(3, "Resource������ʧ��|fail to create Resource service");
            return FALSE;
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
        $sql->AddValue("Root", $Root, IBC1_DATATYPE_PLAINTEXT);
        $sql->AddValue("UserService", $UserService, IBC1_DATATYPE_PLAINTEXT);
        $sql->AddValue("FileTypeList", $FileTypeList, IBC1_DATATYPE_PLAINTEXT);
        $sql->AddValue("MaxFileSize", $MaxFileSize);
        $sql->Execute();
        $sql->ClearValues();
        $sql->CloseSTMT();
        if ($conn->GetError()->HasError()) {
            throw new ManagerException(7, "'" . $conn->GetError()->GetSource() . "' ����δ֪����|unknown error from '" . $conn->GetError()->GetSource() . "'");
            return FALSE;
        }
        return TRUE;
    }

    public function Delete($ServiceName) {

        $this->GetError()->Clear();
        if (!$this->Exists($ServiceName)) {
            throw new ManagerException(6, "����'$ServiceName'������|cannot find service '$ServiceName'");
            return FALSE;
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
        if ($conn->GetError()->HasError()) {
            throw new ManagerException(7, "'" . $conn->GetError()->GetSource() . "' ����δ֪����|unknown error from '" . $conn->GetError()->GetSource() . "'");
            return FALSE;
        }
        return TRUE;
    }

    public function Optimize($ServiceName) {
        if (!$this->Exists($ServiceName)) {
            throw new ManagerException(8, "does not exist");
            return FALSE;
        }
        $conn = $this->GetDBConn();
        $sql = $conn->CreateTableSTMT("optimize", "ibc1_res" . $ServiceName . "_file");
        $sql->Execute();
        $sql->CloseSTMT();
        return TRUE;
    }

    public function Modify($ServiceName, $FileTypeList="", $MaxFileSize=0) {
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
            if ($r)
                return TRUE;
            throw new ManagerException(8, "fail to modify");
            return FALSE;
        }
        throw new ManagerException(8, "no new information is set");
        return FALSE;
    }

}

?>