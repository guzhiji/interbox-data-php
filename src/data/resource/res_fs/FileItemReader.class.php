<?php

/**
 *
 * @version 0.1
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2012 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.resource.filesystem
 */
class FileItemReader extends DataItem {

	private $id = 0;
	private $_root = "";
	private $_filetypelist = "";
	private $_maxfilesize = 0;
	private $_userservice = "";

	function __construct(DBConnProvider $Conns, $ServiceName, ErrorList $EL=NULL) {
		parent::__construct($EL);
		$this->OpenService($Conns, $ServiceName);
		$this->GetError()->SetSource(__CLASS__);
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
			$this->_filetypelist = $r->FileTypeList;
			$this->_maxfilesize = $r->MaxFileSize;
			$this->_userservice = $r->UserService;
		}
		$sql->CloseSTMT();
	}

	public function CloseService() {
		parent::CloseService();
		$this->_root = "";
		$this->_filetypelist = "";
		$this->_maxfilesize = 0;
		$this->_userservice = "";
		$this->id = 0;
	}

	public function Open($id, $uid="") {
		if (!$this->IsServiceOpen()) {
			$this->GetError()->AddItem(1, "service is not open");
			return FALSE;
		}
		$conn = $this->GetDBConn();
		$sql = $conn->CreateSelectSTMT("ibc1_res" . $this->GetServiceName() . "_file");
		$sql->AddEqual("filID", $id, IBC1_DATATYPE_INTEGER);
		if ($uid != "")
		$sql->AddEqual("filUID", $uid, IBC1_DATATYPE_PLAINTEXT, IBC1_LOGICAL_AND);
		$sql->Execute();
		$r = $sql->Fetch(1);
		$sql->CloseSTMT();
		if ($r) {
			$this->id = $r->filID;
			$this->SetValue("filName", $r->filName, IBC1_DATATYPE_PLAINTEXT);
			$this->SetValue("filType", $r->filType, IBC1_DATATYPE_PLAINTEXT);
			$this->SetValue("filExtName", $r->filExtName, IBC1_DATATYPE_PLAINTEXT);
			$this->SetValue("filTime", $r->filTime, IBC1_DATATYPE_PLAINTEXT);
			$this->SetValue("filUID", $r->filUID, IBC1_DATATYPE_PLAINTEXT);
			$this->SetValue("filDir", $r->filDir, IBC1_DATATYPE_INTEGER);
			$this->SetValue("filSize", $r->filSize, IBC1_DATATYPE_INTEGER);

			return TRUE;
		}
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

	public function GetDirectory() {
		return intval($this->GetValue("filDir"));
	}

	public function GetSize($mode=0) {
		$s = intval($this->GetValue("filSize"));
		if ($mode == 0)
		return $this->SizeWithUnit($s);
		return $s;
	}

	public function GetData($bytefrom=0, $byteto=0) {
		$uid = $this->GetValue("filUID");
		$dir = intval($this->GetValue("filDir"));
		$ext = $this->GetValue("filExtName");

		if ($this->_root == "")
		return FALSE;
		if ($uid == "")
		return FALSE;

		$filename = str_replace("\\", "/", $this->_root);
		if (substr($filename, -1) != "/")
		$filename.="/";
		$filename.=$uid . "/" . $dir . "/" . $this->id;
		if ($ext != "")
		$filename.="." . $ext;

		if (!file_exists($filename))
		return FALSE;

		if ($byteto <= 0)
		$byteto = filesize($filename) - 1;

		$f = fopen($filename, "r");
		fseek($f, $bytefrom);
		$buffer = fread($f, $byteto - $bytefrom + 1);
		fclose($f);
		return $buffer;
	}

	public function GetRelativeURL() {
		$r = "/" . $this->GetValue("filUID") . "/" . $this->GetValue("filDir") . "/" . $this->id;
		$ext = $this->GetValue("filExtName");
		if ($ext != "")
		$r.="." . $ext;
		return $r;
	}

	public function ExportData($mode=0) {
		if (!$this->IsServiceOpen()) {
			$this->GetError()->AddItem(1, "service is not open");
			return FALSE;
		}
		if ($mode != 0) {
			header("Content-Disposition: attachment; filename=" . urlencode($this->GetName() . "." . $this->GetExtName()));
		}
		header("Content-Type: " . $this->GetType());
		echo($this->GetData());
		$this->CloseService();
		exit();
		// TODO @readfile("$fileurl") or die("File not found.");
	}
	//---------------------------------------------------------------
	//TODO test & reference
	function readfile_chunked ($filename) {
		$chunksize = 1*(1024*1024); // how many bytes per chunk
		$buffer = '';
		$handle = fopen($filename, 'rb');
		if ($handle === false) {
			return false;
		}
		while (!feof($handle)) {
			$buffer = fread($handle, $chunksize);
			print $buffer;
		}
		return fclose($handle);
	}
	function readfile_chunked2($filename,$retbytes=true) {
		$chunksize = 1*(1024*1024); // how many bytes per chunk
		$buffer = '';
		$cnt =0;
		// $handle = fopen($filename, 'rb');
		$handle = fopen($filename, 'rb');
		if ($handle === false) {
			return false;
		}
		while (!feof($handle)) {
			$buffer = fread($handle, $chunksize);
			echo $buffer;
			if ($retbytes) {
				$cnt += strlen($buffer);
			}
		}
		$status = fclose($handle);
		if ($retbytes && $status) {
			return $cnt; // return num. bytes delivered like readfile() does.
		}
		return $status;
	}
	//---------------------------------------------------------------
	private function SizeWithUnit($size) {
		if ($size <= 1000) {
			if ($size > 1)
			$size_unit = "Bytes";
			else
			$size_unit="Byte";
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
