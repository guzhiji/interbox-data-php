<?php

/**
 *
 * @version 0.1
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2012 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.resource.database
 */
class FileUploader extends DataModel {

    private $id = 0;
    private $uid = "";
    private $isnew = TRUE;
    private $totalsize = 0;
    private $idlist = NULL;
    private $_filetypelist = NULL;
    private $_maxfilesize = 0;
    private $_userservice = "";

    function __construct(DBConnProvider $Conns, $ServiceName) {
        $this->OpenService($Conns, $ServiceName);

        if (get_cfg_var("file_uploads") != "1")
            throw new Exception("|");
    }

    public function OpenService(DBConnProvider $Conns, $ServiceName) {
        parent::OpenService($Conns, $ServiceName, "res");
        $c = $this->GetDBConn();
        $sql = $c->CreateSelectSTMT("ibc1_dataservices_resource");
        $sql->AddField("*");
        $sql->AddEqual("ServiceName", $ServiceName, IBC1_DATATYPE_PLAINTEXT);
        $sql->Execute();
        if ($r = $sql->Fetch(1)) {
            $this->_filetypelist = new WordList($r->FileTypeList);
            $this->_maxfilesize = $r->MaxFileSize;
            $this->_userservice = $r->UserService;
            $this->idlist = new ItemList();
        }
        $sql->CloseSTMT();
    }

    public function CloseService() {
        parent::CloseService();

        $this->id = 0;
        $this->uid = "";
        $this->isnew = TRUE;
        $this->totalsize = 0;
        $this->idlist = NULL;
        $this->_filetypelist = NULL;
        $this->_maxfilesize = 0;
        $this->_userservice = "";
    }

    public function UpdateFile($id, $uid) {

        //better to check if the user is online
        $conn = $this->GetDBConn();
        $sql = $conn->CreateSelectSTMT("ibc1_res" . $this->GetServiceName() . "_file");
        $sql->AddField("filID");
        $sql->AddEqual("filID", $id, IBC1_DATATYPE_INTEGER, IBC1_LOGICAL_AND);
        $sql->AddEqual("filUID", $uid, IBC1_DATATYPE_PLAINTEXT, IBC1_LOGICAL_AND);
        $sql->Execute();
        $r = $sql->Fetch(1);
        $sql->CloseSTMT();
        if ($r) {
            $this->id = intval($id);
            $this->uid = $uid;
            $this->isnew = FALSE;
            $this->idlist->Clear();
            return TRUE;
        } else {
            $this->uid = "";
            $this->isnew = TRUE;
            return FALSE;
        }
    }

    public function UploadNew($uid) {

        //better to check if the user is online
        $conn = $this->GetDBConn();
        $sql = $conn->CreateSelectSTMT("ibc1_usr" . $this->_userservice . "_user");
        $sql->AddField("usrUID");
        $sql->AddEqual("usrUID", $uid, IBC1_DATATYPE_PLAINTEXT);
        $sql->Execute();
        $r = $sql->Fetch();
        $sql->CloseSTMT();
        if (!$r) {
            $this->uid = "";
            throw new Exception("|the user does not exist");
        }
        $this->uid = $uid;
        $this->isnew = TRUE;
        $this->id = 0;
        $this->idlist->Clear();
    }

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

    public function GetIDList() {
        return $this->idlist;
    }

    private function FileExt($filename) {
        $a = strrpos($filename, ".");
        if ($a > 0)
            return array(substr($filename, 0, $a), substr($filename, $a - strlen($filename) + 1));
        //substr($filename,$a+1,strlen($filename)-$a-1);
        return array();
    }

}

?>