<?php

/**
 *
 * @version 0.1
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2012 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.resource.filesystem
 */
class FileListReader extends DataList {

    private $_name = "";
    private $_type = "";
    private $_extName = "";
    private $_nameExact = FALSE;
    private $_uid = "";
    private $_dir = 0;
    private $_sizeMin = 0;
    private $_sizeMax = 0;
    private $_timeFrom = "";
    private $_timeTo = "";

    function __construct(DBConnProvider $Conns, $ServiceName, ErrorList $EL=NULL) {
        parent::__construct($EL);
        parent::OpenService($Conns, $ServiceName, "res");
        $this->GetError()->SetSource(__CLASS__);
    }

    public function SetName($name, $extname="", $exact=FALSE) {
        $this->_name = $name;
        $this->_extName = $extname;
        $this->_nameExact = $exact;
    }

    public function SetType($t) {
        $this->_type = $t;
    }

    public function SetUser($uid) {
        $this->_uid = $uid;
    }

    public function SetDirectory($dir) {
        $this->_dir = intval($dir);
    }

    public function SetSize($min, $max) {
        $this->_sizeMin = intval($min);
        $this->_sizeMax = intval($max);
    }

    public function SetTime($from, $to) {
        $this->_timeFrom = date("Y-m-d H:i:s", $from);
        $this->_timeTo = date("Y-m-d H:i:s", $to);
    }

    public function LoadList() {
        if (!$this->IsServiceOpen()) {
            $this->GetError()->AddItem(1, "service is not open");
            return FALSE;
        }
        $conn = $this->GetDBConn();
        $sql = $conn->CreateSelectSTMT("ibc1_res" . $this->GetServiceName() . "_file");
        if ($this->_extName != "")
            $sql->AddEqual("filExtName", $this->_extName, IBC1_DATATYPE_PLAINTEXT, IBC1_LOGICAL_AND);
        if ($this->_name != "") {
            if ($this->_nameExact)
                $sql->AddEqual("filName", $this->_name, IBC1_DATATYPE_PLAINTEXT, IBC1_LOGICAL_AND);
            else
                $sql->AddLike("filName", $this->_name, IBC1_DATATYPE_PLAINTEXT, IBC1_LOGICAL_AND);
        }
        if ($this->_type != "")
            $sql->AddEqual("filType", $this->_type, IBC1_DATATYPE_PLAINTEXT, IBC1_LOGICAL_AND);
        if ($this->_uid != "")
            $sql->AddEqual("filUID", $this->_uid, IBC1_DATATYPE_PLAINTEXT, IBC1_LOGICAL_AND);
        if ($this->_dir > 0) {
            $sql->AddEqual("filDir", $this->_dir, IBC1_DATATYPE_INTEGER, IBC1_LOGICAL_AND);
        }
        if ($this->_sizeMax >= $this->_sizeMin && $this->_sizeMax > 0) {
            $sql->AddCondition("filSize<=" . $this->_sizeMax, IBC1_LOGICAL_AND);
        }
        if ($this->_sizeMin > 0) {
            $sql->AddCondition("filSize>=" . $this->_sizeMin, IBC1_LOGICAL_AND);
        }

        if ($this->_timeFrom != "") {
            $sql->AddCondition("TO_DAYS(filTime)>=TO_DAYS('" . $this->_timeFrom . "')", IBC1_LOGICAL_AND);
        }
        if ($this->_timeTo != "") {
            $sql->AddCondition("TO_DAYS(filTime)<=TO_DAYS('" . $this->_timeTo . "')", IBC1_LOGICAL_AND);
        }

        $sql->Execute();
        while ($r = $sql->Fetch(1)) {
            $this->AddItem($r);
        }
        $sql->CloseSTMT();
    }

}

?>
