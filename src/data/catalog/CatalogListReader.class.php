<?php

LoadIBC1Class('DataList', 'datamodels');

/**
 *
 * @version 0.7.20111207
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2012 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.datamodels.catalog
 */
class CatalogListReader extends DataList {

    protected $list_sql = NULL;

    function __construct($ServiceName) {
        parent::__construct();
        $this->OpenService($ServiceName);
    }

    public function OpenService($ServiceName) {
        parent::OpenService($ServiceName, 'catalog');
        $conn = $this->GetDBConn();
        $this->list_sql = $conn->CreateSelectSTMT($this->GetDataTableName('catalog'));
    }

    /**
     * get basic attributes of a catalog
     * 
     * <code>
     * LoadIBC1Class('CatalogListReader','datamodels.catalog');
     * $reader=new CatalogListReader('catalogtest');
     * try{
     *      var_dump($reader->GetCatalog(1));
     * } catch (Exception $ex) {
     *      echo 'not found';
     * }
     * $reader->CloseService();
     * </code>
     * 
     * @param int $id catalog id
     * @return object
     */
    public function GetCatalog($id) {

        $conn = $this->GetDBConn();
        $sql = $conn->CreateSelectSTMT($this->GetDataTableName('catalog'));
        self::AddFields($sql);
        $sql->AddEqual('clgID', $id, IBC1_DATATYPE_INTEGER);
        $sql->Execute();
        $r = $sql->Fetch(1);
        $sql->CloseSTMT();
        if (!$r) {
            throw new ServiceException("not exist:$id");
        }
        return $r;
    }

    /**
     * get a catalog and add it into the list as an item
     * 
     * <code>
     * LoadIBC1Class('CatalogListReader','datamodels.catalog');
     * $reader=new CatalogListReader('catalogtest');
     * $reader->LoadCatalog(2);
     * $reader->MoveFirst();
     * while($item=$reader->GetEach()){
     *      var_dump($item);
     *      echo "<hr />\n";
     * }
     * $reader->CloseService();
     * </code>
     * only support single-page list,it is a single-page list when PageSize=0
     * @param int $id  catalog id
     */
    public function LoadCatalog($id) {

        if ($this->GetPageSize() != 0 || $this->GetPageNumber() > 1) {
            throw new ServiceException('only support single-page list');
        }

        $this->AddItem($this->GetCatalog($id));
    }

    /**
     * add all necessary fields into the given SQL
     * @param IFieldExpList $sql 
     */
    private static function AddFields(IFieldExpList $sql) {
        $sql->AddField('clgID', 'ID');
        $sql->AddField('clgName', 'Name');
        $sql->AddField('clgOrdinal', 'Ordinal');
        $sql->AddField('clgUID', 'UID');
        $sql->AddField('clgParentID', 'ParentID');
        $sql->AddField('clgGID', 'GID');
        $sql->AddField('clgAdminLevel', 'AdminGrade');
    }

    /**
     * load a path of catalogs from the given catalog to 
     * the leaf node of the hierarchy
     * @param int $id  catalog id of the root
     */
    public function LoadPath($id) {

        if ($this->GetPageSize() != 0 || $this->GetPageNumber() > 1) {
            throw new ServiceException('only support single-page list');
        }

        $conn = $this->GetDBConn();
        $sql = $conn->CreateSelectSTMT($this->GetDataTableName('catalog'));
        self::AddFields($sql);

        $sql->AddEqual('clgID', $id);
        $sql->Execute();
        $r = $sql->Fetch(1);
        $sql->CloseSTMT();
        if (!$r)
            throw new ServiceException("not exist:{$id}");
        $this->AddItem($r);
        while ($r->ParentID > 0) {
            $sql->ClearConditions();
            $sql->AddEqual('clgID', $r->ParentID);
            $sql->Execute();
            $r = $sql->Fetch(1);
            $sql->CloseSTMT();
            if (!$r) {
                throw new ServiceException("not exist:{$r->ParentID}");
            }
            $this->AddItem($r);
        }
    }

    /**
     * set a constraint of name on the list loading
     * @param string $name  catalog name, a proposed condition on the Name field
     * @param bool $exact optional, the default value is FALSE 
     * such that an exact match is NOT enforced
     */
    public function SetName($name, $exact = FALSE) {
        if ($this->list_sql != NULL) {
            $sql = $this->list_sql;
            if ($name != '') {
                if ($exact)
                    $sql->AddEqual('clgName', $name, IBC1_DATATYPE_PLAINTEXT, IBC1_LOGICAL_AND);
                else
                    $sql->AddLike('clgName', $name, IBC1_DATATYPE_PLAINTEXT, IBC1_LOGICAL_AND);
            }
        }
    }

    /**
     * set a constraint of parent catalog on the list loading
     * @param int $id catalog id, a proposed condition on parent catalog
     */
    public function SetParentCatalog($id) {
        if ($this->list_sql != NULL) {
            $sql = $this->list_sql;
            if ($id >= 0) {
                $sql->AddEqual('clgParentID', $id, IBC1_DATATYPE_INTEGER, IBC1_LOGICAL_AND);
            }
        }
    }

    /**
     * set a constraint of admin group on the list loading
     * @param int $gid user group id, a proposed condition on admin group
     */
    public function SetAdminGroup($gid) {
        if ($this->list_sql != NULL) {
            $sql = $this->list_sql;
            if ($gid > 0) {
                $sql->AddEqual('clgGID', $gid, IBC1_DATATYPE_INTEGER, IBC1_LOGICAL_AND);
            }
        }
    }

    /**
     * set a constraint of visitor group on the list loading
     * @param int $gid user group id, a proposed condition on visitor group
     */
    public function SetVisitGroup($gid) {
        if ($this->list_sql != NULL) {
            $sql = $this->list_sql;
            if ($gid > 0) {
                $sql->AddEqual('clgVisitGID', $gid, IBC1_DATATYPE_INTEGER, IBC1_LOGICAL_AND);
            }
        }
    }

    public function OrderBy($fieldname, $order) {
        if ($this->list_sql != NULL) {
            $sql = $this->list_sql;
            if ($order != IBC1_ORDER_ASC)
                $order = IBC1_ORDER_DESC;
            switch ($fieldname) {
                case 'name':
                    $sql->OrderBy('clgName', $order);
                    break;
                case 'ordinal':
                    $sql->OrderBy('clgOrdinal', $order);
                    break;

                default:
                    throw new ServiceException('not supported');
            }
        }
    }

    /**
     * load the list with the constraints set in this class
     * 
     * <code>
     * LoadIBC1Class('CatalogListReader','datamodels.catalog');
     * $reader=new CatalogListReader('catalogtest');
     * $reader->LoadList();
     * $reader->MoveFirst();
     * while($item=$reader->GetEach()){
     *      var_dump($item);
     *      echo "<hr />\n";
     * }
     * $reader->CloseService();
     * </code>
     */
    public function LoadList() {
        if ($this->list_sql === NULL) {
            throw new ServiceException('not initialized properly');
        }

        $sql = $this->list_sql;
        $sql->ClearFields();
        $sql->AddField('COUNT(clgID)');
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

    //the following is for convenience
    public function OpenSubCatalog($ID) {
        $this->SetParentCatalog($ID);
        $this->LoadList();
    }

    public function GetAdminList($CatalogID) {

        $conn = $this->GetDBConn();
        $sql = $conn->CreateSelectSTMT($this->GetDataTableName('admin'));
        $sql->AddField('admUID');
        $sql->AddEqual('admCatalogID', $CatalogID);
        $l = new ItemList();
        $sql->Execute();
        while ($r = $sql->Fetch(1)) {
            $l->AddItem($r->admUID);
        }
        $sql->CloseSTMT();
        return $l;
    }

}
