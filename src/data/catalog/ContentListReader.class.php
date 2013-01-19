<?php

LoadIBC1Class('DataList', 'datamodels');

/**
 *
 * @version 0.7.20111212
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2012 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.datamodels.catalog
 */
class ContentListReader extends DataList {

    protected $list_sql = NULL;

    function __construct($ServiceName) {
        parent::__construct();
        $this->OpenService($ServiceName);
    }

    public function OpenService($ServiceName) {
        parent::OpenService($ServiceName, 'catalog');
        $conn = $this->GetDBConn();
        $this->list_sql = $conn->CreateSelectSTMT($this->GetDataTableName('content'));
    }

    /**
     * add all necessary fields into the given SQL
     * @param IFieldExpList $sql 
     */
    private static function AddFields(IFieldExpList $sql) {

        $sql->AddField('cntID', 'ID');
        $sql->AddField('cntName', 'Name');
        $sql->AddField('cntCatalogID', 'CatalogID');
        //$sql->AddField('cntCatalogName', 'CatalogName');
        $sql->AddField('cntAuthor', 'Author');
        $sql->AddField('cntKeywords', 'Keywords');
        $sql->AddField('DATE_FORMAT(cntTimeCreated,"%Y-%m-%d %H:%i:%s")', 'TimeCreated');
        $sql->AddField('DATE_FORMAT(cntTimeUpdated,"%Y-%m-%d %H:%i:%s")', 'TimeUpdated');
        $sql->AddField('DATE_FORMAT(cntTimeVisited,"%Y-%m-%d %H:%i:%s")', 'TimeVisited');
        $sql->AddField('cntUID', 'UID');
        $sql->AddField('cntVisitCount', 'VisitCount');
        $sql->AddField('cntVisitLevel', 'VisitLevel');
        $sql->AddField('cntAdminLevel', 'AdminLevel');
        $sql->AddField('cntPointValue', 'PointValue');
    }

    /**
     * get a content object
     * @param int $id   content id
     * @return object
     */
    public function GetContent($id) {
        $conn = $this->GetDBConn();
        $sql = $conn->CreateSelectSTMT($this->GetDataTableName('content'));
        self::AddFields($sql);
        $sql->AddEqual('cntID', $id, IBC1_DATATYPE_INTEGER);
        $sql->Execute();
        $r = $sql->Fetch(1);
        $sql->CloseSTMT();
        if (!$r) {
            throw new ServiceException("not exist: $id");
        }
        return $r;
    }

    /**
     * get content using ID and add it into the list
     * 
     * only support single-page list
     * (it is a single-page list when PageSize=0)
     * @param int $id   content id
     */
    public function LoadContent($id) {
        if ($this->GetPageSize() != 0 || $this->GetPageNumber() > 1) {
            throw new ServiceException('only support single-page list,it is a single-page list when PageSize=0');
        }

        $r = $this->GetContent($id);
        if ($r) {
            $this->AddItem($r);
        }
    }

    /**
     * set a constraint of name on the list loading
     * @param string $name  content name, a proposed condition on the Name field
     * @param bool $exact optional, the default value is FALSE 
     * such that an exact match is NOT enforced
     */
    public function SetName($name, $exact = FALSE) {
        if ($this->list_sql != NULL) {
            $sql = $this->list_sql;
            if ($exact)
                $sql->AddEqual('cntName', $name, IBC1_DATATYPE_PLAINTEXT, IBC1_LOGICAL_AND);
            else
                $sql->AddLike('cntName', $name, IBC1_DATATYPE_PLAINTEXT, IBC1_LOGICAL_AND);
        }
    }

    /**
     * set a constraint of catalog id on the list loading
     * @param type $id  catalog id
     */
    public function SetCatalog($id) {
        if ($this->list_sql != NULL) {
            $sql = $this->list_sql;
            if ($id != 0)
                $sql->AddEqual('cntCatalogID', $id, IBC1_DATATYPE_INTEGER, IBC1_LOGICAL_AND); //or/and
        }
    }

    /**
     * set a constraint of keywords on the list loading
     * @param string $keywords
     */
    public function SetKeywords($keywords) {
        if ($this->list_sql != NULL) {
            $sql = $this->list_sql;
            LoadIBC1Class('WordList', 'util');
            $wl = new WordList($keywords);
            while ($item = $wl->GetEach()) {
                if ($item != '')
                    $sql->AddLike('cntKeywords', $item, IBC1_DATATYPE_PLAINTEXT, IBC1_LOGICAL_AND);
            }
        }
    }

    public function SetAdminLevel($g) {
        if ($this->list_sql != NULL) {
            $sql = $this->list_sql;
            if ($g > 0)
                $sql->AddEqual('cntAdminLevel', $g, IBC1_DATATYPE_INTEGER, IBC1_LOGICAL_AND);
        }
    }

    public function SetVisitLevel($g) {
        if ($this->list_sql != NULL) {
            $sql = $this->list_sql;
            if ($g > 0)
                $sql->AddEqual('cntVisitLevel', $g, IBC1_DATATYPE_INTEGER, IBC1_LOGICAL_AND);
        }
    }

    public function SetAdminUID($UID) {
        if ($this->list_sql != NULL) {
            $sql = $this->list_sql;
            if ($UID != '')
                $sql->AddEqual('cntUID', $UID, IBC1_DATATYPE_PLAINTEXT, IBC1_LOGICAL_AND);
        }
    }

    public function OrderBy($fieldname, $order = IBC1_ORDER_ASC) {
        if ($this->list_sql != NULL) {
            $sql = $this->list_sql;
            if ($order != IBC1_ORDER_ASC)
                $order = IBC1_ORDER_DESC;
            switch ($fieldname) {
                case 'name':
                    $sql->OrderBy('cntName', $order);
                    break;
                case 'ordinal':
                    $sql->OrderBy('cntOrdinal', $order);
                    break;
                case 'author':
                    $sql->OrderBy('cntAuthor', $order);
                    break;
                case 'ctime':
                    $sql->OrderBy('cntTimeCreated', $order);
                    break;
                case 'utime':
                    $sql->OrderBy('cntTimeUpdated', $order);
                    break;
                case 'vtime':
                    $sql->OrderBy('cntTimeVisited', $order);
                    break;
                case 'point':
                    $sql->OrderBy('cntPointValue', $order);
                    break;

                default:
                    throw new ServiceException('not supported');
            }
        }
    }

    /**
     * <code>
     * LoadIBC1Class('ContentListReader', 'datamodels.catalog');
     * $reader=new ContentListReader('catalogtest');
     * $reader->SetCatalog(1);
     * $reader->LoadList();
     * $reader->MoveFirst();
     * while($item=$reader->GetEach()){
     *     var_dump($item);
     *     echo "<hr />\n";
     * }
     * $reader->CloseService();
     * </code>
     * @throws Exception 
     */
    public function LoadList() {

        if ($this->list_sql === NULL) {
            throw new ServiceException('not properly initialized');
        }

        $sql = $this->list_sql;
        $sql->ClearFields();
        $sql->AddField('COUNT(cntID)', 'c');
        $this->GetCounts1($sql);
        $sql->ClearFields();
        self::AddFields($sql);
        $sql->SetLimit($this->GetPageSize(), $this->GetPageNumber());

        $sql->Execute();

        $this->Clear();
        while ($r = $sql->Fetch(1)) {
            $this->AddItem($r);
        }
        $this->GetCounts2();
        $sql->CloseSTMT();
    }

    public function OpenCatalog($ID) {
        $this->SetCatalog($ID);
        $this->LoadList();
    }

    public function OpenWithKey($KeyText) {
        $this->SetKeywords($KeyText);
        $this->LoadList();
    }

    public function OpenWithAdmin($UID) {
        $this->SetAdminUID($UID);
        $this->LoadList();
    }

}
