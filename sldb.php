<?php


class sldbRequest {

  private $connection;

  var $output;
  var $result;
  var $table;

  function __construct($db_host, $db_user, $db_pass, $db_name, $table) {
    require_once('config.php');
    $mysqli =  new mysqli($db_host, $db_user, $db_pass,$db_name);
    if ($mysqli->connect_error) {
        die('Connect Error (' . $mysqli->connect_errno . ') '.$mysqli->connect_error);
    }
    $this->connection = $mysqli;
    $this->table = $table;
  }


  /**
   * Getter function for the output of the query.
   * @param  string $separator
   *   (optional) The separator to use. LSL cannot handle arrays or objects
   *   because it was slapped together and never fixed, so the output has to be
   *   a list with a constant separator that can be used to parse it.
   */
  function getOutput($separator = '&') {
    return implode($separator, (array)$this->output);
  }


  /**
   * Create a table for use.
   */
  function createTable() {
    $sql = "CREATE TABLE IF NOT EXISTS `" . $this->table . "` (`uuid` varchar(64) NOT NULL DEFAULT '', `field` varchar(255) NOT NULL DEFAULT '', `value` longtext, `changed` int(11) NOT NULL DEFAULT '0', PRIMARY KEY (`uuid`,`field`)) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
    $this->result = $this->connection->query($sql) or die($this->connection->error);
    $this->output = "SUCCESS";
  }


  /**
   * Update uuid/field data pairs.
   *
   * @param  string  $uuid
   *   The user's uuid
   * @param  array  $data
   *   An array of data to update, where the keys are the fields and their
   *   values are the values stored in the db.
   * @param  boolean $verbose
   *   TRUE for longer output.
   */
  function updateData($uuid, $data, $verbose = FALSE) {
    foreach($data as $key => $value) {
      $sql = "INSERT INTO " . $this->table . " (uuid, field, value, changed) VALUES ('$uuid', '$key', '$value', UNIX_TIMESTAMP(NOW())) ON DUPLICATE KEY UPDATE value = '$value', changed = UNIX_TIMESTAMP(NOW())";
      $this->result = $this->connection->query($sql) or die($this->connection->error);
      $this->output = $verbose ? "SUCCESS: " . $this->connection->affected_rows() : $this->connection->affected_rows();
    }
  }


  /**
   * Read data for a uuid or uuid/field combination.
   *
   * @param  string  $uuid
   *   The uuid for the user. The uuid can also be any unique string.
   * @param  array  $fields
   *   (optional) The fields element provided with the request. Contains a list
   *   of fields. If not provided, all field/value combinations will be returned.
   * @param  boolean $verbose
   *   (optional) TRUE to return field/value pairs, FALSE for just the value.
   * @param  string  $separator
   *   (optional) A glue string to implode the results. Default is '='.
   */
  function readData($uuid, $fields = array(), $verbose = FALSE, $separator = '=') {
    $fields = (array)$fields;
    foreach($fields AS $key => $field) {
      $fields[$key] = "'" . $field . "'";
    }

    $columns = $verbose ? 'field, value' : 'value';

    $sql = "SELECT $columns FROM " . $this->table . " WHERE uuid = '$uuid'";
    $sql .= empty($fields) ? '' : " AND field IN (" . implode(', ', (array)$fields) . ")";

    $this->result = $this->connection->query($sql) or die($this->connection->error);
    while($record = $this->result->fetch_assoc()) {
      $record['value'] = $record['value'] == '' ? 'NULL' : $record['value'];
      $this->output[] = implode($separator, $record);
    }
  }


  /**
   * Delete data for a uuid or a uuid/field combination.
   *
   * @param  string  $uuid
   *   The uuid for the user. The uuid can also be any unique string.
   * @param  array   $fields
   *   (optional) An array of fields to delete. If no fields are provided, all
   *   fields for that user will be deleted.
   * @param  boolean $verbose
   *   (optional) TRUE for longer output.
   */
  function deleteData($uuid, $fields = array(), $verbose = FALSE) {
    $fields = (array)$fields;
    foreach($fields AS $key => $field) {
      $fields[$key] = "'" . $field . "'";
    }

    $sql = "DELETE FROM " . $this->table . " WHERE uuid = '$uuid'";
    $sql .= empty($fields) ? '' : " AND field IN (" . implode(', ', (array)$fields) . ")";

    $this->result = mysql_query($sql) or die(mysql_error());
    $this->output = $verbose ? "SUCCESS: " . mysql_affected_rows() : mysql_affected_rows();
  }
}
