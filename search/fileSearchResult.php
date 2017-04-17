<?php

class fileSearchResult
{
  var $indices;
  var $indexHeaders;
  var $creationDate;
  var $whoCreated;
  var $fileSize;
  var $path;
  var $fileName;
  var $doc_id;
  var $tab;
  var $ordering;
  var $fileID;
  var $hits;

  function fileSearchResult()
  {}

  function setIndices($indexList) {
    $this->indices = $indexList;
  }

  function getIndices() {
    return $this->indices;
  }

  function setIndexHeaders($headerList) {
    $this->indexHeaders = $headerList;
  }

  function setHits($hits) {
    $this->hits = $hits;
  }

  function getIndexHeaders() {
    return $this->indexHeaders;
  }

  function setCreationDate($date) {
    $this->creationDate = $date;
  }

  function getCreationDate() {
    return $this->creationDate;
  }

  function setWhoCreated($creator) {
    $this->whoCreated = $creator;
  }

  function getWhoCreated() {
    return $this->whoCreated;
  }

  function setFileSize($size) {
    $this->fileSize = $size;
  }

  function getFileSize() {
    return $this->fileSize;
  }

  function setPath($path) {
    $this->path = $path;
  }

  function getPath() {
    return $this->path;
  }

  function setFileName($name) {
    $this->fileName = $name;
  }

  function getFileName() {
    return $this->fileName;
  }

  function setDocID($docID) {
    $this->doc_id = $docID;
  }

  function getDocID() {
    return $this->doc_id;
  }

  function setTab($tab) {
    $this->tab = $tab;
  }

  function getTab() {
    return $this->tab;
  }

  function setOrdering($ordering) {
    $this->ordering = $ordering;
  }

  function getOrdering() {
    return $this->ordering;
  }

  function setFileID($fileID) {
    $this->fileID = $fileID;
  }

  function getHits() {
    return $this->hits;
  }

  function getFileID() {
    return $this->fileID;
  }
}
?>
