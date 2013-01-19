<?php

/**
 *
 * @version 0.1.20111212
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2012 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.datamodels.catalog
 */

/**
 * check if the catalog exists
 * 
 * @param int $id catalog id
 * @param string $tablename
 * @param DBConn $conn
 * @return bool 
 */
function catalogExists($id, $tablename, DBConn $conn) {
    //$sql = $conn->CreateSelectSTMT($this->GetDataTableName("catalog"));
    $sql = $conn->CreateSelectSTMT($tablename);
    $sql->AddField("clgID");
    $sql->AddEqual("clgID", $id);
    $sql->Execute();
    $r = $sql->Fetch(1);
    $sql->CloseSTMT();
    return !!$r;
}

/**
 * check if the content exists
 * 
 * @param int $id content id
 * @param string $tablename
 * @param DBConn $conn
 * @return bool 
 */
function contentExists($id, $tablename, DBConn $conn) {
    //$sql = $conn->CreateSelectSTMT($this->GetDataTableName("content"));
    $sql = $conn->CreateSelectSTMT($tablename);
    //$sql->AddField("cntAdminLevel");
    //$sql->AddField("cntAdminUID");
    $sql->AddField("cntID");
    $sql->AddEqual("cntID", $id);
    $sql->Execute();
    $r = $sql->Fetch(1);
    $sql->CloseSTMT();
    return !!$r;
}

/**
 * check if the catalog is accessibility
 * 
 * @param int $id catalog id
 * @param string $tablename
 * @param DBConn $conn
 * @return bool 
 */
function getCatalogAccessibility($id, $tablename, DBConn $conn) {
    //$sql = $conn->CreateSelectSTMT($this->GetDataTableName("catalog"));
    $sql = $conn->CreateSelectSTMT($tablename);
    $sql->AddField("clgID");
    //TODO check accessibility
    $sql->AddEqual("clgID", $id);
    $sql->Execute();
    $r = $sql->Fetch(1);
    $sql->CloseSTMT();
    return !!$r; //TODO 0=no access, 1=read only, 2=read or write
}
