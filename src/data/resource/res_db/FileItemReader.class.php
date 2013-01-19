<?php

/**
 *
 * @version 0.1
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2012 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.resource.database
 */
class FileItemReader extends DataItem {

    private $id = 0;
    private $_filetypelist = "";
    private $_maxfilesize = 0;
    private $_userservice = "";

    function __construct(DBConnProvider $Conns, $ServiceName) {
        $this->OpenService($Conns, $ServiceName);
    }

    public function OpenService(DBConnProvider $Conns, $ServiceName) {
        parent::OpenService($Conns, $ServiceName, "res");
        $c = $this->GetDBConn();
        $sql = $c->CreateSelectSTMT("ibc1_dataservices_resource");
        $sql->AddField("*");
        $sql->AddEqual("ServiceName", $ServiceName, IBC1_DATATYPE_PLAINTEXT);
        $sql->Execute();
        if ($r = $sql->Fetch(1)) {
            $this->_filetypelist = $r->FileTypeList;
            $this->_maxfilesize = $r->MaxFileSize;
            $this->_userservice = $r->UserService;
        }
        $sql->CloseSTMT();
    }

    public function CloseService() {
        parent::CloseService();
        $this->_filetypelist = "";
        $this->_maxfilesize = 0;
        $this->_userservice = "";
        $this->id = 0;
    }

    public function Open($id, $uid="") {

        $conn = $this->GetDBConn();
        $sql = $conn->CreateSelectSTMT("ibc1_res" . $this->GetServiceName() . "_file");
        $sql->AddField("filName");
        $sql->AddField("filType");
        $sql->AddField("filExtName");
        $sql->AddField("filTime");
        $sql->AddField("filUID");
        $sql->AddField("filSize");
        $sql->AddEqual("filID", $id, IBC1_DATATYPE_INTEGER);
        if ($uid != "")
            $sql->AddEqual("filUID", $uid, IBC1_DATATYPE_PLAINTEXT, IBC1_LOGICAL_AND);
        $sql->Execute();
        $r = $sql->Fetch(1);
        if ($r) {
            $this->id = intval($id);
            $this->SetValue("filName", $r->filName, IBC1_DATATYPE_PLAINTEXT);
            $this->SetValue("filType", $r->filType, IBC1_DATATYPE_PLAINTEXT);
            $this->SetValue("filExtName", $r->filExtName, IBC1_DATATYPE_PLAINTEXT);
            $this->SetValue("filTime", $r->filTime, IBC1_DATATYPE_PLAINTEXT);
            $this->SetValue("filUID", $r->filUID, IBC1_DATATYPE_PLAINTEXT);
            $this->SetValue("filSize", $r->filSize, IBC1_DATATYPE_INTEGER);
            $sql->CloseSTMT();
            return TRUE;
        }
        $sql->CloseSTMT();
        return FALSE;
    }

    public function GetID() {
        return $this->id;
    }

    public function GetName() {
        return $this->GetValue("filName");
    }

    public function GetType() {
        return $this->GetValue("filType");
    }

    public function GetExtName() {
        return $this->GetValue("filExtName");
    }

    public function GetTime() {
        return $this->GetValue("filTime");
    }

    public function GetUser() {
        return $this->GetValue("filUID");
    }

    public function GetSize($mode=0) {
        $s = intval($this->GetValue("filSize"));
        if ($mode == 0)
            return $this->SizeWithUnit($s);
        return $s;
    }

    public function GetData() {

        $uid = $this->GetValue("filUID");
        $conn = $this->GetDBConn();
        $sql = $conn->CreateSelectSTMT("ibc1_res" . $this->GetServiceName() . "_file");
        $sql->AddField("filData");
        $sql->AddEqual("filID", $this->id, IBC1_DATATYPE_INTEGER);
        if ($uid != "")
            $sql->AddEqual("filUID", $uid, IBC1_DATATYPE_PLAINTEXT, IBC1_LOGICAL_AND);
        $sql->Execute();
        $r = $sql->Fetch(1);
        $buffer = "";
        if ($r) {
            $buffer = $r->filData;
        }
        $sql->CloseSTMT();

        return $buffer;
    }

    public function ExportData($mode=0) {

        if ($mode != 0) {
            header("Content-Disposition: attachment; filename=" . urlencode($this->GetName() . "." . $this->GetExtName()));
        }
        header("Content-Type: " . $this->GetType());
        echo($this->GetData());
        $this->CloseService();
        exit();
    }

    private function SizeWithUnit($size) {
        if ($size <= 1000) {
            if ($size > 1)
                $size_unit = "Bytes";
            else
                $size_unit = "Byte";
        }else if ($size <= 1000000) {
            $size = number_format($size / 1024, 3);
            $size_unit = "KB";
        } else if ($size <= 1000000000) {
            $size = number_format($size / 1024 / 1024, 3);
            $size_unit = "MB";
        } else {
            $size = number_format($size / 1024 / 1024 / 1024, 3);
            $size_unit = "GB";
        }
        return $size . " " . $size_unit;
    }

}

?>
