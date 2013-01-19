<?php

/**
 *
 * @version 0.1
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2012 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.resource.filesystem
 */
class FileUploader extends DataModel {

    private $id = 0;
    private $dir = 0;
    private $uid = "";
    private $isnew = TRUE;
    private $totalsize = 0;
    private $idlist = NULL;
    private $_root = "";
    private $_filetypelist = NULL;
    private $_maxfilesize = 0;
    private $_userservice = "";

    function __construct(DBConnProvider $Conns, $ServiceName, ErrorList $EL=NULL) {
        parent::__construct($EL);
        $this->OpenService($Conns, $ServiceName);
        $this->GetError()->SetSource(__CLASS__);

        if (get_cfg_var("file_uploads") != "1")
            $this->GetError()->AddItem(1, "|�������������ϴ�");
    }

    public function OpenService(DBConnProvider $Conns, $ServiceName) {
        parent::OpenService($Conns, $ServiceName, "res");
        $c = $this->GetDBConn();
        $sql = $c->CreateSelectSTMT("ibc1_dataservices_resource");
        $sql->AddField("*");
        $sql->AddEqual("ServiceName", $ServiceName, IBC1_DATATYPE_PLAINTEXT);
        $sql->Execute();
        if ($r = $sql->Fetch(1)) {
            $this->_root = $r->Root;
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
        $this->dir = 0;
        $this->uid = "";
        $this->isnew = TRUE;
        $this->totalsize = 0;
        $this->idlist = NULL;
        $this->_root = "";
        $this->_filetypelist = NULL;
        $this->_maxfilesize = 0;
        $this->_userservice = "";
    }

    public function UpdateFile($id, $uid) {
        if (!$this->IsServiceOpen()) {
            $this->GetError()->AddItem(1, "service is not open");
            return FALSE;
        }
        //better to check if the user is online
        $conn = $this->GetDBConn();
        $sql = $conn->CreateSelectSTMT("ibc1_res" . $this->GetServiceName() . "_file");
        $sql->AddField("filID");
        $sql->AddField("filDir");
        $sql->AddEqual("filID", $id, IBC1_DATATYPE_INTEGER, IBC1_LOGICAL_AND);
        $sql->AddEqual("filUID", $uid, IBC1_DATATYPE_PLAINTEXT, IBC1_LOGICAL_AND);
        $sql->Execute();
        $r = $sql->Fetch(1);
        $sql->CloseSTMT();
        if ($r) {
            $this->id = intval($id);
            $this->dir = $r->filDir;
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
        if (!$this->IsServiceOpen()) {
            $this->GetError()->AddItem(1, "service is not open");
            return FALSE;
        }
        //better to check if the user is online
        $conn = $this->GetDBConn();
        $sql = $conn->CreateSelectSTMT("ibc1_usr" . $this->_userservice . "_user");
        $sql->AddField("usrUID");
        $sql->AddEqual("usrUID", $uid, IBC1_DATATYPE_PLAINTEXT);
        $sql->Execute();
        $r = $sql->Fetch();
        $sql->CloseSTMT();
        if (!$r) {
            $this->GetError()->AddItem(1, "|the user does not exist");
            $this->uid = "";
            return FALSE;
        }
        $this->uid = $uid;
        $this->isnew = TRUE;
        $this->id = 0;
        $this->dir = 0;
        $this->idlist->Clear();
        return TRUE;
    }

    public function SaveFiles() {
        if (!$this->IsServiceOpen()) {
            $this->GetError()->AddItem(1, "service is not open");
            return FALSE;
        }
        $this->totalsize = 0;
        if ($this->_root == "") {
            $this->GetError()->AddItem(1, "|no root");
            return FALSE;
        }
        if ($this->uid == "") {
            $this->GetError()->AddItem(1, "|no user set");
            return FALSE;
        }

        $this->_root = str_replace("\\", "/", $this->_root);
        if (substr($this->_root, -1) != "/")
            $this->_root.="/";

        foreach ($_FILES as $NewFile) {
            if ($NewFile["error"]) {
                $this->GetError()->AddItem(1, "|error occurred during uploading(" . $NewFile["error"] . ")");
                continue;
            }
            $this->totalsize+=$NewFile["size"];
            if ($this->totalsize > $this->_maxfilesize) {
                $this->totalsize-=$NewFile["size"];
                $this->GetError()->AddItem(1, "|too large(" . $NewFile["size"] . ")");
                break;
            }
            $fNameArray = $this->FileExt($NewFile["name"]);
            if (!$this->_filetypelist->HasWord($fNameArray[1])) {
                $this->GetError()->AddItem(1, "|this type of file is not allowed");
                continue;
            }
            $conn = $this->GetDBConn();
            $filename = $this->_root . $this->uid . "/";
            if ($this->isnew) {
                //generate a path for the user
                if (!is_dir($filename))
                    @mkdir($filename, 0777);
                //generate a free dir
                $dir = $this->GetFreeDir($filename);
                if ($dir < 1) {
                    $this->GetError()->AddItem(1, "|a new directory cannot be created");
                    continue;
                }

                $sql = $conn->CreateInsertSTMT("ibc1_res" . $this->GetServiceName() . "_file");
            } else {
                $dir = $this->dir;
                $sql = $conn->CreateUpdateSTMT("ibc1_res" . $this->GetServiceName() . "_file");
                $sql->AddEqual("filID", $this->id, IBC1_DATATYPE_INTEGER);
            }

            $sql->AddValue("filName", $fNameArray[0], IBC1_DATATYPE_PLAINTEXT);
            $sql->AddValue("filType", $NewFile["type"], IBC1_DATATYPE_PLAINTEXT);
            $sql->AddValue("filSize", $NewFile["size"], IBC1_DATATYPE_INTEGER);
            $sql->AddValue("filExtName", $fNameArray[1], IBC1_DATATYPE_PLAINTEXT);
            $sql->AddValue("filTime", "CURRENT_TIMESTAMP()", IBC1_DATATYPE_EXPRESSION);
            $sql->AddValue("filUID", $this->uid, IBC1_DATATYPE_PLAINTEXT);
            $sql->AddValue("filDir", $dir, IBC1_DATATYPE_INTEGER);

            $r = $sql->Execute();
            if ($r) {
                if ($this->isnew) {
                    $this->id = $sql->GetLastInsertID();
                }
                $sql->CloseSTMT();
                $this->idlist->AddItem($this->id);
            } else {
                $sql->CloseSTMT();
                $this->GetError()->AddItem(1, "|error occurred when operating database");
                continue;
            }

            //get a full name for the file
            $filename.=$dir . "/" . $this->id;
            if ($fNameArray[1] != "")
                $filename.="." . $fNameArray[1];
            if (!$this->isnew) {
                @unlink($filename);
            }
            if (file_exists($filename)) {
                $this->GetError()->AddItem(1, "|the file already exists");
                continue;
            }

            if (!@move_uploaded_file($NewFile["tmp_name"], $filename)) {
                $this->GetError()->AddItem(1, "|fail to save the file");
                continue;
            }
        }
        if ($this->totalsize == 0) {
            $this->GetError()->AddItem(1, "|no file accepted");
            return FALSE;
        }
        return TRUE;
    }

    public function GetIDList() {
        return $this->idlist;
    }

    private function GetFreeDir($path) {
        $conn = $this->GetDBConn();
        $sql = $conn->CreateSelectSTMT("ibc1_res" . $this->GetServiceName() . "_file");
        $sql->AddField("MAX(filDir)", "m");
        $sql->AddEqual("filUID", $this->uid, IBC1_DATATYPE_PLAINTEXT);
        $sql->Execute();
        $m = $sql->Fetch(1)->m;
        $sql->CloseSTMT();
        $sql = $conn->CreateSelectSTMT("ibc1_res" . $this->GetServiceName() . "_file");
        $sql->AddField("COUNT(filDir)", "c");
        $sql->AddEqual("filDir", $m, IBC1_DATATYPE_INTEGER);
        $sql->Execute();
        $c = $sql->Fetch(1)->c;
        $sql->CloseSTMT();


        if ($c >= 100) {
            if (@mkdir($path . intval(1 + $m) . "/", 0777))
                return intval(1 + $m);
            else
                return intval($m);
        }
        if ($m > 0)
            return intval($m);
        @mkdir($path . "1/", 0777);
        return 1;
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