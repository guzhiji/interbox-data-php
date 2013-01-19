<?php

LoadIBC1Class('DataItem', 'datamodels');
LoadIBC1Lib('common', 'datamodels.catalog');

/**
 *
 * @version 0.7.20111212
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2012 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.datamodels.catalog
 */
class ContentItemEditor extends DataItem {

    protected $IsNew = TRUE;
    protected $ID = 0;
    protected $AdminGrade = -1;
    protected $VisitGrade = -1;

    function __construct($ServiceName) {
        parent::__construct();
        $this->OpenService($ServiceName);
    }

    public function OpenService($ServiceName) {
        parent::OpenService($ServiceName, 'catalog');
    }

    /**
     * prepares to create a new content
     * 
     * initializes the editor to receive data,
     *  but does not save data before Save() is invoked.
     */
    public function Create() {
        $this->ID = 0;
        $this->IsNew = TRUE;
    }

    /**
     * prepares to modify an existing content
     * 
     * initializes the editor to receive changes,
     *  but does not save changes before Save() is invoked.
     * @param int $ID content id
     */
    public function Open($ID) {

        $this->IsNew = FALSE;
        $this->ID = intval($ID);
    }

    /**
     * save the content into database
     * 
     * add if Create() invoked
     * <code>
     * LoadIBC1Class('ContentItemEditor', 'datamodels.catalog');
     * $editor=new ContentItemEditor('catalogtest');
     * $editor->Create();
     * $editor->SetName('content 2');
     * try{
     *     $editor->Save(1);
     *     echo 'succeeded:'.$editor->GetID()."\n";
     * }catch(Exception $ex){
     *     echo 'failed:'.$ex->getMessage()."\n";
     * }
     * $editor->CloseService();
     * </code>
     * 
     * update if Open() invoked
     * <code>
     * LoadIBC1Class('ContentItemEditor', 'datamodels.catalog');
     * $editor=new ContentItemEditor('catalogtest');
     * $editor->Open(1);
     * $editor->SetName('content 1');
     * try{
     *     $editor->Save();
     *     echo "succeeded\n";
     * }catch(Exception $ex){
     *     echo 'failed:'.$ex->getMessage()."\n";
     * }
     * $editor->CloseService();
     * </code>
     * @param int $CatalogID unnecessary if Open() invoked
     */
    public function Save($CatalogID = 0) {

        if ($this->IsNew) {//create content
            //check input parameter
            if ($CatalogID <= 0) {
                throw new ServiceException('parent catalog not found');
            }
            $conn = $this->GetDBConn();
            if (!catalogExists($CatalogID, $this->GetDataTableName('catalog'), $conn)) {
                throw new ServiceException('parent catalog not found');
            }
            $this->SetValue('cntCatalogID', $CatalogID, IBC1_DATATYPE_INTEGER);

            //check essential fields
            if ($this->GetValue('cntName') == NULL)
                throw new ServiceException('content name has not been set');
//            if ($this->GetValue("cntAdminUID") == NULL)
//                throw new ServiceException("admin has not been set");
            //insert
            $conn = $this->GetDBConn();
            $sql = $conn->CreateInsertSTMT($this->GetDataTableName('content'));
            $this->MoveFirst();
            while (list($key, $item) = $this->GetEach()) {
                $sql->AddValue($key, $item[0], $item[1]);
            }

            $sql->AddValue('cntTimeCreated', 'CURRENT_TIMESTAMP()', IBC1_DATATYPE_EXPRESSION);

            $sql->Execute();

            $this->ID = $sql->GetLastInsertID();
            $sql->CloseSTMT();
            $this->IsNew = FALSE;
        } else if ($this->ID > 0) {//modify content
            //if changed
            if ($this->Count() == 0) {
                throw new ServiceException('no fields have not been changed');
            }

            //update
            $conn = $this->GetDBConn();
            $sql = $conn->CreateUpdateSTMT();
            $sql->SetTable($this->GetDataTableName('content'));
            $this->MoveFirst();
            while (list($key, $item) = $this->GetEach()) {
                $sql->AddValue($key, $item[0], $item[1]);
            }
            $sql->AddValue('cntTimeUpdated', 'CURRENT_TIMESTAMP()', IBC1_DATATYPE_EXPRESSION);
            $sql->AddEqual('cntID', $this->ID);
            $sql->Execute();
            $sql->CloseSTMT();
        } else {
            throw new ServiceException('no content is open');
        }
    }

    /**
     * delete content
     * 
     * @param int $id   optional, delete the opened one if not given
     */
    public function Delete($id = 0) {

        if ($id == 0)
            $id = $this->ID;
        $conn = $this->GetDBConn();
//        if (!contentExists($id,$this->GetDataTableName("content"), $conn)) {
//            throw new ServiceException("no access");
//        }
        $sql = $conn->CreateDeleteSTMT($this->GetDataTableName('content'));
        $sql->AddEqual('cntID', $id);
        $sql->Execute();
        $sql->CloseSTMT();
    }

    /**
     * move the catalog to a new parent catalog
     * @param int $CatalogID    parent catalog id
     */
    public function MoveTo($CatalogID) {
        if ($this->IsNew) {
            throw new ServiceException('cannot move an unsaved content');
        }
        $CatalogID = intval($CatalogID);
        $conn = $this->GetDBConn();
        if (!catalogExists($CatalogID, $this->GetDataTableName('catalog'), $conn)) {
            throw new ServiceException('the catalog does not exist');
        } else {
            $this->SetValue('cntCatalogID', $CatalogID);
        }
    }

    /**
     * get id if not new
     * @return int
     */
    public function GetID() {
        if ($this->IsNew) {
            throw new ServiceException('cannot get the id of an unsaved content');
        }
        return $this->ID;
    }

    /**
     * set content name (essential)
     * 
     * @param string $name pure text with no html
     */
    public function SetName($name) {
        $this->SetValue('cntName', $name, IBC1_DATATYPE_PLAINTEXT);
    }

    /**
     * set content author
     * 
     * @param string $author pure text with no html
     */
    public function SetAuthor($author) {
        $this->SetValue('cntAuthor', $author, IBC1_DATATYPE_PLAINTEXT);
    }

    public function SetKeywords($keywords) {
        $this->SetValue('cntKeywords', $keywords, IBC1_DATATYPE_WORDLIST);
    }

    public function SetPointValue($n) {
        $this->SetValue('cntPointValue', $n);
    }

    /**
     * set content ordinal for ordering
     * 
     * does not check for repetition nor continuity
     * @param int $n any integers
     */
    public function SetOrdinal($n) {
        $this->SetValue('cntOrdinal', $n);
    }

    public function SetAdminUID($uid) {
        $this->SetValue('cntUID', $uid, IBC1_DATATYPE_PLAINTEXT);
    }

    public function AddVisitCount() {
        $this->SetValue('cntVisitCount', 'cntVisitCount+1', IBC1_DATATYPE_EXPRESSION);
    }

    public function ClearVisitCount() {
        $this->SetValue('cntVisitCount', 0);
    }

    /**
     * set a minimal user level for visitors
     * 
     * <ul>
     * <li>level=0 - visible to everyone</li>
     * <li>level>0 - visible to users with or with higher levels</li>
     * <li>level=-1 - invisible to anyone except the author</li>
     * </ul>
     * @param int $g
     * @return boolean 
     */
    public function SetVisitLevel($g) {
        $g = intval($g);
        if ($g < 0)
            $g = -1;
        if ($g <= $this->AdminGrade) {
            $this->SetValue('cntVisitLevel', $g);
            return TRUE;
        }
        return FALSE;
    }

    /**
     * set a minimal user level for admins
     *
     * <ul>
     * <li>level>0 - admin by users with or with higher levels</li>
     * <li>level=-1 - only the author can make a change</li>
     * </ul>
     * @param int $g
     * @return boolean 
     */
    public function SetAdminLevel($g) {
        $g = intval($g);
        if ($g < 0)
            $g = -1;
        if ($g != 0) {
            if ($g >= $this->VisitGrade) {
                $this->SetValue('cntAdminLevel', $g);
                return TRUE;
            }
        }
        return FALSE;
    }

}
