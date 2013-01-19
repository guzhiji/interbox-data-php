<?php

LoadIBC1Class('DataModel', 'datamodels');

/**
 *
 * @version 0.1
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2012 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.datamodels.keyvalue
 */
class BinaryWriter extends DataModel {

    function __construct($ServiceName, $BindingValue = NULL, $BindingType = IBC1_DATATYPE_INTEGER) {
        parent::__construct();
        $this->OpenService($ServiceName, $BindingValue, $BindingType);
    }

    public function OpenService($ServiceName, $BindingValue = NULL, $BindingType = IBC1_DATATYPE_INTEGER) {
        parent::OpenService($ServiceName, 'keyvalue');
        $this->bindingValue = $BindingValue;
        $this->bindingType = $BindingType;
    }
//TODO upload file | save binary data
    public function SaveFiles() {

        $this->totalsize = 0;
        if ($this->uid == "") {
            throw new Exception("|no user set");
        }

        foreach ($_FILES as $NewFile) {
            if ($NewFile["error"]) {
                // "|error occurred during uploading(" . $NewFile["error"] . ")");
                continue;
            }
            $this->totalsize+=$NewFile["size"];
            if ($this->totalsize > $this->_maxfilesize) {
                $this->totalsize-=$NewFile["size"];
                throw new Exception("|too large(" . $NewFile["size"] . ")");
                break;
            }
            $fNameArray = $this->FileExt($NewFile["name"]);
            if (!$this->_filetypelist->HasWord($fNameArray[1])) {
                // "|this type of file is not allowed");
                continue;
            }
            $conn = $this->GetDBConn();
            $sql = NULL;
            if ($this->isnew) {
                $sql = $conn->CreateInsertSTMT("ibc1_res" . $this->GetServiceName() . "_file");
            } else {
                $sql = $conn->CreateUpdateSTMT("ibc1_res" . $this->GetServiceName() . "_file");
                $sql->AddEqual("filID", $this->id, IBC1_DATATYPE_INTEGER);
            }

            $sql->AddValue("filName", $fNameArray[0], IBC1_DATATYPE_PLAINTEXT);
            $sql->AddValue("filType", $NewFile["type"], IBC1_DATATYPE_PLAINTEXT);
            $sql->AddValue("filSize", $NewFile["size"], IBC1_DATATYPE_INTEGER);
            $sql->AddValue("filExtName", $fNameArray[1], IBC1_DATATYPE_PLAINTEXT);
            $sql->AddValue("filTime", "CURRENT_TIMESTAMP()", IBC1_DATATYPE_EXPRESSION);
            $sql->AddValue("filUID", $this->uid, IBC1_DATATYPE_PLAINTEXT);
            $sql->SetDataFromFile("filData", $NewFile["tmp_name"]);
            $r = $sql->Execute();
            if ($r) {
                if ($this->isnew) {
                    $this->id = $sql->GetLastInsertID();
                }
                $sql->CloseSTMT();
                $this->idlist->AddItem($this->id);
            } else {
                $sql->CloseSTMT();
                // "|error occurred when operating database");
                continue;
            }
        }
        if ($this->totalsize == 0) {
            throw new Exception("|no file accepted");
        }
        return TRUE;
    }

}