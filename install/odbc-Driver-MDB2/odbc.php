<?php
// vim: set et ts=4 sw=4 fdm=marker:
// +----------------------------------------------------------------------+
// | PHP versions 4 and 5                                                 |
// +----------------------------------------------------------------------+
// | Copyright (c) 1998-2006 Manuel Lemos, Tomas V.V.Cox,                 |
// | Stig. S. Bakken, Lukas Smith                                         |
// | All rights reserved.                                                 |
// +----------------------------------------------------------------------+
// | MDB2 is a merge of PEAR DB and Metabases that provides a unified DB  |
// | API as well as database abstraction for PHP applications.            |
// | This LICENSE is in the BSD license style.                            |
// |                                                                      |
// | Redistribution and use in source and binary forms, with or without   |
// | modification, are permitted provided that the following conditions   |
// | are met:                                                             |
// |                                                                      |
// | Redistributions of source code must retain the above copyright       |
// | notice, this list of conditions and the following disclaimer.        |
// |                                                                      |
// | Redistributions in binary form must reproduce the above copyright    |
// | notice, this list of conditions and the following disclaimer in the  |
// | documentation and/or other materials provided with the distribution. |
// |                                                                      |
// | Neither the name of Manuel Lemos, Tomas V.V.Cox, Stig. S. Bakken,    |
// | Lukas Smith nor the names of his contributors may be used to endorse |
// | or promote products derived from this software without specific prior|
// | written permission.                                                  |
// |                                                                      |
// | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS  |
// | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT    |
// | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS    |
// | FOR A PARTICULAR PURPOSE ARE DISCLAIMED.  IN NO EVENT SHALL THE      |
// | REGENTS OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,          |
// | INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, |
// | BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS|
// |  OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED  |
// | AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT          |
// | LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY|
// | WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE          |
// | POSSIBILITY OF SUCH DAMAGE.                                          |
// +----------------------------------------------------------------------+
// | Author: Lukas Smith <smith@pooteeweet.org>                           |
// +----------------------------------------------------------------------+
//
// $Id: odbc.php,v 1.170 2006/10/31 18:46:43 lsmith Exp $
//

/**
 * MDB2 MySQL driver
 *
 * @package MDB2
 * @category Database
 * @author  Lukas Smith <smith@pooteeweet.org>
 */
class MDB2_Driver_odbc extends MDB2_Driver_Common
{
    // {{{ properties
    var $string_quoting = array('start' => "'", 'end' => "'", 'escape' => '\\', 'escape_pattern' => '\\');

    var $identifier_quoting = array('start' => '`', 'end' => '`', 'escape' => '`');

    var $sql_comments = array(
        array('start' => '-- ', 'end' => "\n", 'escape' => false),
        array('start' => '#', 'end' => "\n", 'escape' => false),
        array('start' => '/*', 'end' => '*/', 'escape' => false),
    );

    var $start_transaction = false;

    var $varchar_max_length = 255;
    // }}}
    // {{{ constructor

    /**
     * Constructor
     */
    function __construct()
    {
        parent::__construct();

        $this->phptype = 'odbc';
        $this->dbsyntax = 'unknown';

        $this->supported['sequences'] = 'emulated';
        $this->supported['indexes'] = true;
        $this->supported['affected_rows'] = true;
        $this->supported['transactions'] = false;
        $this->supported['savepoints'] = false;
        $this->supported['summary_functions'] = true;
        $this->supported['order_by_text'] = true;
        $this->supported['current_id'] = 'emulated';
        $this->supported['limit_queries'] = true;
        $this->supported['LOBs'] = true;
        $this->supported['replace'] = true;
        $this->supported['sub_selects'] = 'emulated';
        $this->supported['auto_increment'] = true;
        $this->supported['primary_key'] = true;
        $this->supported['result_introspection'] = true;
        $this->supported['prepared_statements'] = 'emulated';
        $this->supported['identifier_quoting'] = true;
        $this->supported['pattern_escaping'] = true;
        $this->supported['new_link'] = true;

        $this->options['default_table_type'] = '';
    }

    // }}}
    // {{{ errorInfo()

    /**
     * This method is used to collect information about an error
     *
     * @param integer $error
     * @return array
     * @access public
     */
    function errorInfo($error = null)
    {
        $native_code = odbc_error();
        $native_msg  = odbc_errormsg();
        if (is_null($error)) {
            static $ecode_map;
            if (empty($ecode_map)) {
                $ecode_map = array(
                    '01004' => MDB2_ERROR_TRUNCATED,
                    '07001' => MDB2_ERROR_MISMATCH,
                    '21S01' => MDB2_ERROR_MISMATCH,
                    '21S02' => MDB2_ERROR_MISMATCH,
                    '22003' => MDB2_ERROR_INVALID_NUMBER,
                    '22007' => MDB2_ERROR_INVALID_DATE,
                    '22018' => MDB2_ERROR_INVALID_NUMBER,
                    '22012' => MDB2_ERROR_DIVZERO,
                    '23000' => MDB2_ERROR_CONSTRAINT,
                    '23503' => MDB2_ERROR_CONSTRAINT,
                    '24000' => MDB2_ERROR_INVALID,
                    '34000' => MDB2_ERROR_INVALID,
                    '42000' => MDB2_ERROR_SYNTAX,
                    '42S01' => MDB2_ERROR_ALREADY_EXISTS,
                    '42S02' => MDB2_ERROR_NOSUCHTABLE,
                    '42S11' => MDB2_ERROR_ALREADY_EXISTS,
                    '42S12' => MDB2_ERROR_NOT_FOUND,
                    '42S21' => MDB2_ERROR_ALREADY_EXISTS,
                    '42S22' => MDB2_ERROR_NOSUCHFIELD,
                    '08004' => MDB2_ERROR_CONNECT_FAILED,
                    '08007' => MDB2_ERROR_CONNECT_FAILED,
                    '08S01' => MDB2_ERROR_CONNECT_FAILED,
                    'HY009' => MDB2_ERROR_INVALID,
                    'HY024' => MDB2_ERROR_INVALID,
                    'HY090' => MDB2_ERROR_INVALID,
                    'IM001' => MDB2_ERROR_UNSUPPORTED,
                    'ODBTPINV' => MDB2_ERROR_INVALID
                );
            }
            if ($this->options['portability'] & MDB2_PORTABILITY_ERRORS) {
                $ecode_map[1022] = MDB2_ERROR_CONSTRAINT;
                $ecode_map[1048] = MDB2_ERROR_CONSTRAINT_NOT_NULL;
                $ecode_map[1062] = MDB2_ERROR_CONSTRAINT;
            } else {
                // Doing this in case mode changes during runtime.
                $ecode_map[1022] = MDB2_ERROR_ALREADY_EXISTS;
                $ecode_map[1048] = MDB2_ERROR_CONSTRAINT;
                $ecode_map[1062] = MDB2_ERROR_ALREADY_EXISTS;
            }
            if (isset($ecode_map[$native_code])) {
                $error = $ecode_map[$native_code];
            }
        }
        return array($error, $native_code, $native_msg);
    }

    // }}}
    // {{{ escape()

    /**
     * Quotes a string so it can be safely used in a query. It will quote
     * the text so it can safely be used within a query.
     *
     * @param   string  the input string to quote
     * @param   bool    escape wildcards
     *
     * @return  string  quoted string
     *
     * @access  public
     */
    function escape($text, $escape_wildcards = false)
    {
        if ($escape_wildcards) {
            $text = $this->escapePattern($text);
        }
        $connection = $this->getConnection();
        if (PEAR::isError($connection)) {
            return $connection;
        }
        $text = @odbc_real_escape_string($text, $connection);
        return $text;
    }

    // }}}
    // {{{

    /**
     * Start a transaction or set a savepoint.
     *
     * @param   string  name of a savepoint to set
     * @return  mixed   MDB2_OK on success, a MDB2 error on failure
     *
     * @access  public
     */
    function beginTransaction($savepoint = null)
    {
        $this->debug('Starting transaction/savepoint', __FUNCTION__, array('is_manip' => true, 'savepoint' => $savepoint));
        if (!is_null($savepoint)) {
            if (!$this->supports('savepoints')) {
                return $this->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
                    'savepoints are not supported', __FUNCTION__);
            }
            if (!$this->in_transaction) {
                return $this->raiseError(MDB2_ERROR_INVALID, null, null,
                    'savepoint cannot be released when changes are auto committed', __FUNCTION__);
            }
            $query = 'SAVEPOINT '.$savepoint;
            return $this->_doQuery($query, true);
        } elseif ($this->in_transaction) {
            return MDB2_OK;  //nothing to do
        }
        if (!$this->destructor_registered && $this->opened_persistent) {
            $this->destructor_registered = true;
            register_shutdown_function('MDB2_closeOpenTransactions');
        }
        $query = $this->start_transaction ? 'START TRANSACTION' : 'SET AUTOCOMMIT = 1';
        $result =& $this->_doQuery($query, true);
        if (PEAR::isError($result)) {
            return $result;
        }
        $this->in_transaction = true;
        return MDB2_OK;
    }

    // }}}
    // {{{ commit()

    /**
     * Commit the database changes done during a transaction that is in
     * progress or release a savepoint. This function may only be called when
     * auto-committing is disabled, otherwise it will fail. Therefore, a new
     * transaction is implicitly started after committing the pending changes.
     *
     * @param   string  name of a savepoint to release
     * @return  mixed   MDB2_OK on success, a MDB2 error on failure
     *
     * @access  public
     */
    function commit($savepoint = null)
    {
        $this->debug('Committing transaction/savepoint', __FUNCTION__, array('is_manip' => true, 'savepoint' => $savepoint));
        if (!$this->in_transaction) {
            return $this->raiseError(MDB2_ERROR_INVALID, null, null,
                'commit/release savepoint cannot be done changes are auto committed', __FUNCTION__);
        }
        if (!is_null($savepoint)) {
            if (!$this->supports('savepoints')) {
                return $this->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
                    'savepoints are not supported', __FUNCTION__);
            }
            $server_info = $this->getServerVersion();
            if (version_compare($server_info['major'].'.'.$server_info['minor'].'.'.$server_info['patch'], '5.0.3', '<')) {
                return MDB2_OK;
            }
            $query = 'RELEASE SAVEPOINT '.$savepoint;
            return $this->_doQuery($query, true);
        }

        if (!$this->supports('transactions')) {
            return $this->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
                'transactions are not supported', __FUNCTION__);
        }

        $result =& $this->_doQuery('COMMIT', true);
        if (PEAR::isError($result)) {
            return $result;
        }
        if (!$this->start_transaction) {
            $query = 'SET AUTOCOMMIT = 0';
            $result =& $this->_doQuery($query, true);
            if (PEAR::isError($result)) {
                return $result;
            }
        }
        $this->in_transaction = false;
        return MDB2_OK;
    }

    // }}}
    // {{{ rollback()

    /**
     * Cancel any database changes done during a transaction or since a specific
     * savepoint that is in progress. This function may only be called when
     * auto-committing is disabled, otherwise it will fail. Therefore, a new
     * transaction is implicitly started after canceling the pending changes.
     *
     * @param   string  name of a savepoint to rollback to
     * @return  mixed   MDB2_OK on success, a MDB2 error on failure
     *
     * @access  public
     */
    function rollback($savepoint = null)
    {
        $this->debug('Rolling back transaction/savepoint', __FUNCTION__, array('is_manip' => true, 'savepoint' => $savepoint));
        if (!$this->in_transaction) {
            return $this->raiseError(MDB2_ERROR_INVALID, null, null,
                'rollback cannot be done changes are auto committed', __FUNCTION__);
        }
        if (!is_null($savepoint)) {
            if (!$this->supports('savepoints')) {
                return $this->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
                    'savepoints are not supported', __FUNCTION__);
            }
            $query = 'ROLLBACK TO SAVEPOINT '.$savepoint;
            return $this->_doQuery($query, true);
        }

        $query = 'ROLLBACK';
        $result =& $this->_doQuery($query, true);
        if (PEAR::isError($result)) {
            return $result;
        }
        if (!$this->start_transaction) {
            $query = 'SET AUTOCOMMIT = 0';
            $result =& $this->_doQuery($query, true);
            if (PEAR::isError($result)) {
                return $result;
            }
        }
        $this->in_transaction = false;
        return MDB2_OK;
    }

    // }}}
    // {{{ function setTransactionIsolation()

    /**
     * Set the transacton isolation level.
     *
     * @param   string  standard isolation level
     *                  READ UNCOMMITTED (allows dirty reads)
     *                  READ COMMITTED (prevents dirty reads)
     *                  REPEATABLE READ (prevents nonrepeatable reads)
     *                  SERIALIZABLE (prevents phantom reads)
     * @return  mixed   MDB2_OK on success, a MDB2 error on failure
     *
     * @access  public
     * @since   2.1.1
     */
    function setTransactionIsolation($isolation)
    {
        $this->debug('Setting transaction isolation level', __FUNCTION__, array('is_manip' => true));
        if (!$this->supports('transactions')) {
            return $this->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
                'transactions are not supported', __FUNCTION__);
        }
        switch ($isolation) {
        case 'READ UNCOMMITTED':
        case 'READ COMMITTED':
        case 'REPEATABLE READ':
        case 'SERIALIZABLE':
            break;
        default:
            return $this->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
                'isolation level is not supported: '.$isolation, __FUNCTION__);
        }

        $query = "SET SESSION TRANSACTION ISOLATION LEVEL $isolation";
        return $this->_doQuery($query, true);
    }

    // }}}
    // {{{ connect()

    /**
     * Connect to the database
     *
     * @return true on success, MDB2 Error Object on failure
     */
    function connect()
    {
        if (is_resource($this->connection)) {
            if (count(array_diff($this->connected_dsn, $this->dsn)) == 0
                && $this->opened_persistent == $this->options['persistent']
            ) {
                return MDB2_OK;
            }
            $this->disconnect(false);
        }

        if (!PEAR::loadExtension($this->phptype)) {
            return $this->raiseError(MDB2_ERROR_NOT_FOUND, null, null,
                'extension '.$this->phptype.' is not compiled into PHP', __FUNCTION__);
        }
        $this->dbsyntax = 'unknown';
        $odbcinfo = array();
        $hostspec = '';
        $port = '';
        $conntype = 'normal';
        $rowcache = false;
        $unicode = false;
        $vardatasize = 0;
        $connid = '';
        $dbparam = 'DATABASE';

        foreach ($this->dsn as $option => $value) {
            $option = strtoupper($option);

            switch ($option) {
                // PEAR DB DSN specific options
                case 'PHPTYPE':
                    $this->phptype = $value; break;
                case 'DBSYNTAX':
                    $this->dbsyntax = strtolower($value);
                    switch( $this->dbsyntax ) {
                        case 'mssql':
                            $dbparam = 'DATABASE';
                            if (!isset($odbcinfo['DRIVER']))
                                $odbcinfo['DRIVER'] = '{SQL Server}';
                            if (!isset($odbcinfo['SERVER']))
                                $odbcinfo['SERVER'] = '(local)';
                            break;
                        case 'access':
                            $dbparam = 'DBQ';
                            if (!isset($odbcinfo['DRIVER']))
                                $odbcinfo['DRIVER'] = '{Microsoft Access Driver (*.mdb)}';
                            if (!isset($odbcinfo['UID']))
                                $odbcinfo['UID'] = 'admin';
                            if (!isset($odbcinfo['PWD']))
                                $odbcinfo['PWD'] = '';
                            break;
                        case 'vfp':
                            $dbparam = 'SOURCEDB';
                            if (!isset($odbcinfo['DRIVER']))
                                $odbcinfo['DRIVER'] = '{Microsoft Visual FoxPro Driver}';
                            if (!isset($odbcinfo['SOURCETYPE']))
                                $odbcinfo['SOURCETYPE'] = 'DBF';
                            if (!isset($odbcinfo['EXCLUSIVE']))
                                $odbcinfo['EXCLUSIVE'] = 'NO';
                            break;
                        case 'oracle':
                            $dbparam = 'DBQ';
                            if (!isset($odbcinfo['DRIVER']))
                                $odbcinfo['DRIVER'] = '{Oracle ODBC Driver}';
                            break;
                        case 'sybase':
                            $dbparam = 'DATABASE';
                            if (!isset($odbcinfo['DRIVER']))
                                $odbcinfo['DRIVER'] = '{Sybase ASE ODBC Driver}';
                            if (!isset($odbcinfo['SRVR']))
                                $odbcinfo['SRVR'] = 'localhost';
                            break;
                        case 'db2':
                            $dbparam = 'DATABASE';
                            if (!isset($odbcinfo['DRIVER']))
                                $odbcinfo['DRIVER'] = '{IBM DB2 ODBC Driver}';
                            if (!isset($odbcinfo['HOSTNAME']))
                                $odbcinfo['HOSTNAME'] = 'localhost';
                            if (!isset($odbcinfo['PORT']))
                                $odbcinfo['PORT'] = '50000';
                            if (!isset($odbcinfo['PROTOCOL']))
                                $odbcinfo['PROTOCOL'] = 'TCPIP';
                            break;
                        case 'mysql':
                            $dbparam = 'DATABASE';
                            if (!isset($odbcinfo['DRIVER']))
                                $odbcinfo['DRIVER'] = '{mySQL}';
                            if (!isset($odbcinfo['SERVER']))
                                $odbcinfo['SERVER'] = 'localhost';
                            if (!isset($odbcinfo['PORT']))
                                $odbcinfo['PORT'] = '3306';
                            if (!isset($odbcinfo['OPTION']))
                                $odbcinfo['OPTION'] = '131072';
                            if (!isset($odbcinfo['STMT']))
                                $odbcinfo['STMT'] = '';
                            break;
                        case 'text':
                            $dbparam = 'DBQ';
                            if (!isset($odbcinfo['DRIVER']))
                                $odbcinfo['DRIVER'] = '{Microsoft Text Driver (*.txt; *.csv)}';
                            if (!isset($odbcinfo['EXTENSIONS']))
                                $odbcinfo['EXTENSIONS'] = 'asc,csv,tab,txt';
                            break;
                        case 'excel':
                            $dbparam = 'DBQ';
                            if (!isset($odbcinfo['DRIVER']))
                                $odbcinfo['DRIVER'] = '{Microsoft Excel Driver (*.xls)}';
                            if (!isset($odbcinfo['DRIVERID']))
                                $odbcinfo['DRIVERID'] = '790';
                            break;
                        case 'dsn':
                            $dbparam = 'DSN';
                            if (!isset($odbcinfo['DSN']))
                                $odbcinfo['DSN'] = '';
                            $this->dbsyntax = 'unknown';
                            break;
                        default:
                            $this->dbsyntax = 'unknown';
                    }
                    break;
                case 'USERNAME':
                    $username = $value; break;
                case 'PASSWORD':
                    $password = $value; break;
                case 'PROTOCOL':
                    $protocol = $value; break;
                case 'SOCKET':
                    $socket = $value; break;
                case 'HOSTSPEC':
                    $hostspec = $value; break;
                case 'PORT':
                    $port = $value; break;
                case 'DATABASE':
                    $database = $value; break;

                // ODBTP specific options
                case 'CONNTIMEOUT':
                    $conntimeout = $value; break;
                case 'READTIMEOUT':
                    $readtimeout = $value; break;
                case 'CONNTYPE':
                    $conntype = strtolower($value); break;
                case 'ROWCACHE':
                    $rowcache = strtolower($value); break;
                case 'UNICODE':
                    $unicode = strtolower($value); break;
                case 'TXNISOL':
                    switch (strtolower($value)) {
                        case 'readuncommitted':
                            $this->txn_isolation = ODB_TXN_READUNCOMMITTED;
                            break;
                        case 'readcommitted':
                            $this->txn_isolation = ODB_TXN_READCOMMITTED;
                            break;
                        case 'repeatableread':
                            $this->txn_isolation = ODB_TXN_REPEATABLEREAD;
                            break;
                        case 'serializable':
                            $this->txn_isolation = ODB_TXN_SERIALIZABLE;
                            break;
                        default:
                            $this->txn_isolation = ODB_TXN_DEFAULT;
                    }
                case 'VARDATASIZE':
                    $vardatasize = intval($value); break;
                case 'CONNID':
                    $connid = strtolower($value); break;
                case 'DBPARAM':
                    $dbparam = strtoupper($value); break;
                case 'ODBCPROTOCOL':
                    $odbcinfo['PROTOCOL'] = $value; break;
                case 'ODBCPORT':
                    $odbcinfo['PORT'] = $value; break;
                case 'MODE':
                    break;
                // Unrecognized options are considered to be ODBC specific
                default:
                    $odbcinfo[$option] = $value;
            }
        }
        if ($conntype == 'reserved') {
            $connect_function = 'odbc_rconnect';
        } else if ($conntype == 'single') {
            $connect_function = 'odbc_sconnect';
        } else {
            $connect_function = 'odbc_connect';
        }
        if( !$hostspec ) $hostspec = '127.0.0.1';

        if ($port) {
            $hostspec .= ':' . $port;
        }
        if (isset($conntimeout)) {
            if ($port)
                $hostspec .= ':' . $conntimeout;
            else
                $hostspec .= '::' . $conntimeout;
        }
        if (isset($readtimeout)) {
            if ($port && isset($conntimeout))
                $hostspec .= ':' . $readtimeout;
            else if ($port)
                $hostspec .= '::' . $readtimeout;
            else
                $hostspec .= ':::' . $readtimeout;
        }
        if (count($odbcinfo) != 0) {
            if (is_string($username)) {
                $odbcinfo['UID'] = $username;
            }
            if (is_string($password)) {
                $odbcinfo['PWD'] = $password;
            }
            if (is_string($this->database_name)) $odbcinfo[$dbparam] = $this->database_name;
            $odbc_connect = '';
            foreach ($odbcinfo as $option => $value) {
                $odbc_connect .= "$option=$value;";
            }
            $conn = @$connect_function($hostspec, $odbc_connect);
        } else if ($connid) {
            $conn = @$connect_function($hostspec, $connid);
        } else {
            $conn = @$connect_function($hostspec, $username, $password, $this->database_name);
        }
        if (!$conn) {
            return $this->raiseError(MDB2_ERROR_CONNECT_FAILED,
                                     null, null, null, @odbc_errormsg());
        }
//        if ($connect_function == 'odbc_connect' && !$this->options['persistent']) {
//            @odbc_dont_pool_dbc($conn);
//        }
        switch ($rowcache) {
            case 1:
            case 'true':
            case 'yes':
                if (!@odbc_use_row_cache($conn)) {
                    return $this->raiseError(MDB2_ERROR, null, null, null,
                                             @odbc_errormsg());
                }
        }
        switch ($unicode) {
            case 1:
            case 'true':
            case 'yes':
                if (!@odbc_set_attr(ODB_ATTR_UNICODESQL, 1, $conn)) {
                    return $this->raiseError(MDB2_ERROR, null, null, null,
                                             @odbc_errormsg());
                }
        }
        if ($vardatasize) {
            if (!@odbc_set_attr(ODB_ATTR_VARDATASIZE, $vardatasize, $conn)) {
                return $this->raiseError(MDB2_ERROR, null, null, null,
                                         @odbc_errormsg());
            }
        }
/*      if( $this->dbsyntax == 'unknown' ) {
            switch (@odbc_get_attr(ODB_ATTR_DRIVER, $conn)) {
                case ODB_DRIVER_MSSQL:
                    $this->dbsyntax = 'mssql'; break;
                case ODB_DRIVER_FOXPRO:
                    $this->dbsyntax = 'vfp'; break;
                case ODB_DRIVER_ORACLE:
                    $this->dbsyntax = 'oracle'; break;
                case ODB_DRIVER_SYBASE:
                    $this->dbsyntax = 'sybase'; break;
                case ODB_DRIVER_MYSQL:
                    $this->dbsyntax = 'mysql'; break;
                default:
                    $dbms = strtolower(@odbc_get_attr(ODB_ATTR_DBMSNAME, $conn));
                    if ($dbms == 'access')
                        $this->dbsyntax = 'access';
                    else if ($dbms == 'text')
                        $this->dbsyntax = 'text';
                    else if ($dbms == 'excel')
                        $this->dbsyntax = 'excel';
                    else if (strncmp($dbms, 'db2', 3) == 0)
                        $this->dbsyntax = 'db2';
                    else
                        $this->dbsyntax = $dbms;
            }
        }
*/
        switch ($this->dbsyntax) {
            case 'access':
            case 'vfp':
            case 'text':
            case 'excel':
                $tc = false;
                break;
            case 'mssql':
            case 'oracle':
            case 'sybase':
            case 'db2':
                $tc = true;
                break;
            default:
                $tc = false; 
        }
        $this->supported['transactions'] = $tc ? true : false;

        $this->connection = $conn;
        $this->connected_database_name = $this->database_name;

        return MDB2_OK;
    }
    // }}}
    // {{{ setCharset()

    /**
     * Set the charset on the current connection
     *
     * @param string    charset
     * @param resource  connection handle
     *
     * @return true on success, MDB2 Error Object on failure
     */
    function setCharset($charset, $connection = null)
    {
        if (is_null($connection)) {
            $connection = $this->getConnection();
            if (PEAR::isError($connection)) {
                return $connection;
            }
        }

        $query = "SET character_set_client = '".odbc_real_escape_string($charset, $connection)."'";
        $result = @odbc_query($query, $connection);
        if (!$result) {
            return $this->raiseError(null, null, null,
                'Unable to set client charset: '.$charset, __FUNCTION__);
        }

        return MDB2_OK;
    }

    // }}}
    // {{{ disconnect()

    /**
     * Log out and disconnect from the database.
     *
     * @param  boolean $force if the disconnect should be forced even if the
     *                        connection is opened persistently
     * @return mixed true on success, false if not connected and error
     *                object on error
     * @access public
     */
    function disconnect($force = true)
    {
        if (is_resource($this->connection)) {
            if ($this->in_transaction) {
                $dsn = $this->dsn;
                $database_name = $this->database_name;
                $persistent = $this->options['persistent'];
                $this->dsn = $this->connected_dsn;
                $this->database_name = $this->connected_database_name;
                $this->options['persistent'] = $this->opened_persistent;
                $this->rollback();
                $this->dsn = $dsn;
                $this->database_name = $database_name;
                $this->options['persistent'] = $persistent;
            }

            if (!$this->opened_persistent || $force) {
                @odbc_close($this->connection);
            }
        }
        return parent::disconnect($force);
    }

    // }}}
    // {{{ _doQuery()

    /**
     * Execute a query
     * @param string $query  query
     * @param boolean $is_manip  if the query is a manipulation query
     * @param resource $connection
     * @param string $database_name
     * @return result or error object
     * @access protected
     */
    function &_doQuery($query, $is_manip = false, $connection = null, $database_name = null)
    {
        $this->last_query = $query;
        $result = $this->debug($query, 'query', array('is_manip' => $is_manip, 'when' => 'pre'));
        if ($result) {
            if (PEAR::isError($result)) {
                return $result;
            }
            $query = $result;
        }
        if ($this->options['disable_query']) {
            $result = $is_manip ? 0 : null;
            return $result;
        }

        if (is_null($connection)) {
            $connection = $this->getConnection();
            if (PEAR::isError($connection)) {
                return $connection;
            }
        }
        if (is_null($database_name)) {
            $database_name = $this->database_name;
        }

        if ($database_name) {
            if ($database_name != $this->connected_database_name) {
                if (!@odbc_select_db($database_name, $connection)) {
                    $err = $this->raiseError(null, null, null,
                        'Could not select the database: '.$database_name, __FUNCTION__);
                    return $err;
                }
                $this->connected_database_name = $database_name;
            }
        }

        $result = @odbc_exec ($connection, $query);
        if (!$result) {
            $err =& $this->raiseError(null, null, null,
                'Could not execute statement', __FUNCTION__);
            return $err;
        }

        $this->debug($query, 'query', array('is_manip' => $is_manip, 'when' => 'post', 'result' => $result));
        return $result;
    }

    // }}}
    // {{{ _affectedRows()

    /**
     * Returns the number of rows affected
     *
     * @param resource $result
     * @param resource $connection
     * @return mixed MDB2 Error Object or the number of rows affected
     * @access private
     */
    function _affectedRows($connection, $result = null)
    {
        if (is_null($connection)) {
            $connection = $this->getConnection();
            if (PEAR::isError($connection)) {
                return $connection;
            }
        }
        return @odbc_affected_rows($connection);
    }

    // }}}
    // {{{ _modifyQuery()

    /**
     * Changes a query string for various DBMS specific reasons
     *
     * @param string $query  query to modify
     * @param boolean $is_manip  if it is a DML query
     * @param integer $limit  limit the number of rows
     * @param integer $offset  start reading from given offset
     * @return string modified query
     * @access protected
     */
    function _modifyQuery($query, $is_manip, $limit, $offset)
    {
        if ($this->options['portability'] & MDB2_PORTABILITY_DELETE_COUNT) {
            // "DELETE FROM table" gives 0 affected rows in MySQL.
            // This little hack lets you know how many rows were deleted.
            if (preg_match('/^\s*DELETE\s+FROM\s+(\S+)\s*$/i', $query)) {
                $query = preg_replace('/^\s*DELETE\s+FROM\s+(\S+)\s*$/',
                                      'DELETE FROM \1 WHERE 1=1', $query);
            }
        }
        if ($limit > 0
            && !preg_match('/LIMIT\s*\d(\s*(,|OFFSET)\s*\d+)?/i', $query)
        ) {
            $query = rtrim($query);
            if (substr($query, -1) == ';') {
                $query = substr($query, 0, -1);
            }
            if ($is_manip) {
                return $query . " LIMIT $limit";
            } else {
                return $query . " LIMIT $offset, $limit";
            }
        }
        return $query;
    }

    // }}}
    // {{{ getServerVersion()

    /**
     * return version information about the server
     *
     * @param string     $native  determines if the raw version string should be returned
     * @return mixed array/string with version information or MDB2 error object
     * @access public
     */
    function getServerVersion($native = false)
    {
        $connection = $this->getConnection();
        if (PEAR::isError($connection)) {
            return $connection;
        }
        if ($this->connected_server_info) {
            $server_info = $this->connected_server_info;
        } else {
            $server_info = @odbc_get_server_info($connection);
        }
        if (!$server_info) {
            return $this->raiseError(null, null, null,
                'Could not get server information', __FUNCTION__);
        }
        // cache server_info
        $this->connected_server_info = $server_info;
        if (!$native) {
            $tmp = explode('.', $server_info, 3);
            if (isset($tmp[2]) && strpos($tmp[2], '-')) {
                $tmp2 = explode('-', @$tmp[2], 2);
            } else {
                $tmp2[0] = isset($tmp[2]) ? $tmp[2] : null;
                $tmp2[1] = null;
            }
            $server_info = array(
                'major' => isset($tmp[0]) ? $tmp[0] : null,
                'minor' => isset($tmp[1]) ? $tmp[1] : null,
                'patch' => $tmp2[0],
                'extra' => $tmp2[1],
                'native' => $server_info,
            );
        }
        return $server_info;
    }

    // }}}
    // {{{ prepare()

    /**
     * Prepares a query for multiple execution with execute().
     * With some database backends, this is emulated.
     * prepare() requires a generic query as string like
     * 'INSERT INTO numbers VALUES(?,?)' or
     * 'INSERT INTO numbers VALUES(:foo,:bar)'.
     * The ? and :[a-zA-Z] and  are placeholders which can be set using
     * bindParam() and the query can be send off using the execute() method.
     *
     * @param string $query the query to prepare
     * @param mixed   $types  array that contains the types of the placeholders
     * @param mixed   $result_types  array that contains the types of the columns in
     *                        the result set or MDB2_PREPARE_RESULT, if set to
     *                        MDB2_PREPARE_MANIP the query is handled as a manipulation query
     * @param mixed   $lobs   key (field) value (parameter) pair for all lob placeholders
     * @return mixed resource handle for the prepared query on success, a MDB2
     *        error on failure
     * @access public
     * @see bindParam, execute
     */
    function &prepare($query, $types = null, $result_types = null, $lobs = array())
    {
        if ($this->options['emulate_prepared']
            || $this->supported['prepared_statements'] !== true
        ) {
            $obj =& parent::prepare($query, $types, $result_types, $lobs);
            return $obj;
        }
        $is_manip = ($result_types === MDB2_PREPARE_MANIP);
        $offset = $this->offset;
        $limit = $this->limit;
        $this->offset = $this->limit = 0;
        $query = $this->_modifyQuery($query, $is_manip, $limit, $offset);
        $result = $this->debug($query, __FUNCTION__, array('is_manip' => $is_manip, 'when' => 'pre'));
        if ($result) {
            if (PEAR::isError($result)) {
                return $result;
            }
            $query = $result;
        }
        $placeholder_type_guess = $placeholder_type = null;
        $question = '?';
        $colon = ':';
        $positions = array();
        $position = 0;
        while ($position < strlen($query)) {
            $q_position = strpos($query, $question, $position);
            $c_position = strpos($query, $colon, $position);
            if ($q_position && $c_position) {
                $p_position = min($q_position, $c_position);
            } elseif ($q_position) {
                $p_position = $q_position;
            } elseif ($c_position) {
                $p_position = $c_position;
            } else {
                break;
            }
            if (is_null($placeholder_type)) {
                $placeholder_type_guess = $query[$p_position];
            }
            
            $new_pos = $this->_skipDelimitedStrings($query, $position, $p_position);
            if (PEAR::isError($new_pos)) {
                return $new_pos;
            }
            if ($new_pos != $position) {
                $position = $new_pos;
                continue; //evaluate again starting from the new position
            }
            
            if ($query[$position] == $placeholder_type_guess) {
                if (is_null($placeholder_type)) {
                    $placeholder_type = $query[$p_position];
                    $question = $colon = $placeholder_type;
                }
                if ($placeholder_type == ':') {
                    $parameter = preg_replace('/^.{'.($position+1).'}([a-z0-9_]+).*$/si', '\\1', $query);
                    if ($parameter === '') {
                        $err =& $this->raiseError(MDB2_ERROR_SYNTAX, null, null,
                            'named parameter with an empty name', __FUNCTION__);
                        return $err;
                    }
                    $positions[$p_position] = $parameter;
                    $query = substr_replace($query, '?', $position, strlen($parameter)+1);
                } else {
                    $positions[$p_position] = count($positions);
                }
                $position = $p_position + 1;
            } else {
                $position = $p_position;
            }
        }
        $connection = $this->getConnection();
        if (PEAR::isError($connection)) {
            return $connection;
        }
        $statement_name = sprintf($this->options['statement_format'], $this->phptype, md5(time() + rand()));
        $query = "PREPARE $statement_name FROM ".$this->quote($query, 'text');
        $statement =& $this->_doQuery($query, true, $connection);
        if (PEAR::isError($statement)) {
            return $statement;
        }

        $class_name = 'MDB2_Statement_'.$this->phptype;
        $ref = new $class_name($this, $statement_name, $positions, $query, $types, $result_types, $is_manip, $limit, $offset);
        $obj =& $ref;
        $this->debug($query, __FUNCTION__, array('is_manip' => $is_manip, 'when' => 'post', 'result' => $obj));
        return $obj;
    }

    // }}}
    // {{{ replace()

    /**
     * Execute a SQL REPLACE query. A REPLACE query is identical to a INSERT
     * query, except that if there is already a row in the table with the same
     * key field values, the REPLACE query just updates its values instead of
     * inserting a new row.
     *
     * The REPLACE type of query does not make part of the SQL standards. Since
     * practically only MySQL implements it natively, this type of query is
     * emulated through this method for other DBMS using standard types of
     * queries inside a transaction to assure the atomicity of the operation.
     *
     * @access public
     *
     * @param string $table name of the table on which the REPLACE query will
     *  be executed.
     * @param array $fields associative array that describes the fields and the
     *  values that will be inserted or updated in the specified table. The
     *  indexes of the array are the names of all the fields of the table. The
     *  values of the array are also associative arrays that describe the
     *  values and other properties of the table fields.
     *
     *  Here follows a list of field properties that need to be specified:
     *
     *    value:
     *          Value to be assigned to the specified field. This value may be
     *          of specified in database independent type format as this
     *          function can perform the necessary datatype conversions.
     *
     *    Default:
     *          this property is required unless the Null property
     *          is set to 1.
     *
     *    type
     *          Name of the type of the field. Currently, all types Metabase
     *          are supported except for clob and blob.
     *
     *    Default: no type conversion
     *
     *    null
     *          Boolean property that indicates that the value for this field
     *          should be set to null.
     *
     *          The default value for fields missing in INSERT queries may be
     *          specified the definition of a table. Often, the default value
     *          is already null, but since the REPLACE may be emulated using
     *          an UPDATE query, make sure that all fields of the table are
     *          listed in this function argument array.
     *
     *    Default: 0
     *
     *    key
     *          Boolean property that indicates that this field should be
     *          handled as a primary key or at least as part of the compound
     *          unique index of the table that will determine the row that will
     *          updated if it exists or inserted a new row otherwise.
     *
     *          This function will fail if no key field is specified or if the
     *          value of a key field is set to null because fields that are
     *          part of unique index they may not be null.
     *
     *    Default: 0
     *
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     */
    function replace($table, $fields)
    {
        $count = count($fields);
        $query = $values = '';
        $keys = $colnum = 0;
        for (reset($fields); $colnum < $count; next($fields), $colnum++) {
            $name = key($fields);
            if ($colnum > 0) {
                $query .= ',';
                $values.= ',';
            }
            $query.= $name;
            if (isset($fields[$name]['null']) && $fields[$name]['null']) {
                $value = 'NULL';
            } else {
                $type = isset($fields[$name]['type']) ? $fields[$name]['type'] : null;
                $value = $this->quote($fields[$name]['value'], $type);
            }
            $values.= $value;
            if (isset($fields[$name]['key']) && $fields[$name]['key']) {
                if ($value === 'NULL') {
                    return $this->raiseError(MDB2_ERROR_CANNOT_REPLACE, null, null,
                        'key value '.$name.' may not be NULL', __FUNCTION__);
                }
                $keys++;
            }
        }
        if ($keys == 0) {
            return $this->raiseError(MDB2_ERROR_CANNOT_REPLACE, null, null,
                'not specified which fields are keys', __FUNCTION__);
        }

        $connection = $this->getConnection();
        if (PEAR::isError($connection)) {
            return $connection;
        }

        $query = "REPLACE INTO $table ($query) VALUES ($values)";
        $result =& $this->_doQuery($query, true, $connection);
        if (PEAR::isError($result)) {
            return $result;
        }
        return $this->_affectedRows($connection, $result);
    }

    // }}}
    // {{{ nextID()

    /**
     * Returns the next free id of a sequence
     *
     * @param string $seq_name name of the sequence
     * @param boolean $ondemand when true the sequence is
     *                          automatic created, if it
     *                          not exists
     *
     * @return mixed MDB2 Error Object or id
     * @access public
     */
    function nextID($seq_name, $ondemand = true)
    {
        $sequence_name = $this->quoteIdentifier($this->getSequenceName($seq_name), true);
        $seqcol_name = $this->quoteIdentifier($this->options['seqcol_name'], true);
        $query = "INSERT INTO $sequence_name ($seqcol_name) VALUES (NULL)";
        $this->expectError(MDB2_ERROR_NOSUCHTABLE);
        $result =& $this->_doQuery($query, true);
        $this->popExpect();
        if (PEAR::isError($result)) {
            if ($ondemand && $result->getCode() == MDB2_ERROR_NOSUCHTABLE) {
                $this->loadModule('Manager', null, true);
                $result = $this->manager->createSequence($seq_name);
                if (PEAR::isError($result)) {
                    return $this->raiseError($result, null, null,
                        'on demand sequence '.$seq_name.' could not be created', __FUNCTION__);
                } else {
                    return $this->nextID($seq_name, false);
                }
            }
            return $result;
        }
        $value = $this->lastInsertID();
        if (is_numeric($value)) {
            $query = "DELETE FROM $sequence_name WHERE $seqcol_name < $value";
            $result =& $this->_doQuery($query, true);
            if (PEAR::isError($result)) {
                $this->warnings[] = 'nextID: could not delete previous sequence table values from '.$seq_name;
            }
        }
        return $value;
    }

    // }}}
    // {{{ lastInsertID()

    /**
     * Returns the autoincrement ID if supported or $id or fetches the current
     * ID in a sequence called: $table.(empty($field) ? '' : '_'.$field)
     *
     * @param string $table name of the table into which a new row was inserted
     * @param string $field name of the field into which a new row was inserted
     * @return mixed MDB2 Error Object or id
     * @access public
     */
    function lastInsertID($table = null, $field = null)
    {
        // not using odbc_insert_id() due to http://pear.php.net/bugs/bug.php?id=8051
        return $this->queryOne('SELECT LAST_INSERT_ID()', 'integer');
    }

    // }}}
    // {{{ currID()

    /**
     * Returns the current id of a sequence
     *
     * @param string $seq_name name of the sequence
     * @return mixed MDB2 Error Object or id
     * @access public
     */
    function currID($seq_name)
    {
        $sequence_name = $this->quoteIdentifier($this->getSequenceName($seq_name), true);
        $seqcol_name = $this->quoteIdentifier($this->options['seqcol_name'], true);
        $query = "SELECT MAX($seqcol_name) FROM $sequence_name";
        return $this->queryOne($query, 'integer');
    }
}

/**
 * MDB2 MySQL result driver
 *
 * @package MDB2
 * @category Database
 * @author  Lukas Smith <smith@pooteeweet.org>
 */
class MDB2_Result_odbc extends MDB2_Result_Common
{
    // }}}
    // {{{ fetchRow()

    /**
     * Fetch a row and insert the data into an existing array.
     *
     * @param int       $fetchmode  how the array data should be indexed
     * @param int    $rownum    number of the row where the data can be found
     * @return int data array on success, a MDB2 error on failure
     * @access public
     */
    function &fetchRow($fetchmode = MDB2_FETCHMODE_DEFAULT, $rownum = null)
    {
        if (!is_null($rownum)) {
            $seek = $this->seek($rownum);
            if (PEAR::isError($seek)) {
                return $seek;
            }
        }
        if ($fetchmode == MDB2_FETCHMODE_DEFAULT) {
            $fetchmode = $this->db->fetchmode;
        }
        if ($fetchmode & MDB2_FETCHMODE_ASSOC) {
            $row = @odbc_fetch_array($this->result);
            if (is_array($row)
                && $this->db->options['portability'] & MDB2_PORTABILITY_FIX_CASE
            ) {
                $row = array_change_key_case($row, $this->db->options['field_case']);
            }
        } else {
           $row = @odbc_fetch_row($this->result);
        }

        if (!$row) {
            if ($this->result === false) {
                $err =& $this->db->raiseError(MDB2_ERROR_NEED_MORE_DATA, null, null,
                    'resultset has already been freed', __FUNCTION__);
                return $err;
            }
            $null = null;
            return $null;
        }
        $mode = $this->db->options['portability'] & MDB2_PORTABILITY_EMPTY_TO_NULL;
        if ($mode) {
            $this->db->_fixResultArrayValues($row, $mode);
        }
        if (!empty($this->types)) {
            $row = $this->db->datatype->convertResultRow($this->types, $row, false);
        }
        if (!empty($this->values)) {
            $this->_assignBindColumns($row);
        }
        if ($fetchmode === MDB2_FETCHMODE_OBJECT) {
            $object_class = $this->db->options['fetch_class'];
            if ($object_class == 'stdClass') {
                $row = (object) $row;
            } else {
                $row = &new $object_class($row);
            }
        }
        ++$this->rownum;
        return $row;
    }

    // }}}
    // {{{ _getColumnNames()

    /**
     * Retrieve the names of columns returned by the DBMS in a query result.
     *
     * @return  mixed   Array variable that holds the names of columns as keys
     *                  or an MDB2 error on failure.
     *                  Some DBMS may not return any columns when the result set
     *                  does not contain any rows.
     * @access private
     */
    function _getColumnNames()
    {
        $columns = array();
        $numcols = $this->numCols();
        if (PEAR::isError($numcols)) {
            return $numcols;
        }
        for ($column = 0; $column < $numcols; $column++) {
            $column_name = @odbc_field_name($this->result, $column);
            $columns[$column_name] = $column;
        }
        if ($this->db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
            $columns = array_change_key_case($columns, $this->db->options['field_case']);
        }
        return $columns;
    }

    // }}}
    // {{{ numCols()

    /**
     * Count the number of columns returned by the DBMS in a query result.
     *
     * @return mixed integer value with the number of columns, a MDB2 error
     *                       on failure
     * @access public
     */
    function numCols()
    {
        $cols = @odbc_num_fields($this->result);
        if (is_null($cols)) {
            if ($this->result === false) {
                return $this->db->raiseError(MDB2_ERROR_NEED_MORE_DATA, null, null,
                    'resultset has already been freed', __FUNCTION__);
            } elseif (is_null($this->result)) {
                return count($this->types);
            }
            return $this->db->raiseError(null, null, null,
                'Could not get column count', __FUNCTION__);
        }
        return $cols;
    }

    // }}}
    // {{{ free()

    /**
     * Free the internal resources associated with result.
     *
     * @return boolean true on success, false if result is invalid
     * @access public
     */
    function free()
    {
        if (is_resource($this->result) && $this->db->connection) {
            $free = @odbc_free_result($this->result);
            if ($free === false) {
                return $this->db->raiseError(null, null, null,
                    'Could not free result', __FUNCTION__);
            }
        }
        $this->result = false;
        return MDB2_OK;
    }
}

/**
 * MDB2 MySQL buffered result driver
 *
 * @package MDB2
 * @category Database
 * @author  Lukas Smith <smith@pooteeweet.org>
 */
class MDB2_BufferedResult_odbc extends MDB2_Result_odbc
{
    // }}}
    // {{{ seek()

    /**
     * Seek to a specific row in a result set
     *
     * @param int    $rownum    number of the row where the data can be found
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function seek($rownum = 0)
    {
        if ($this->rownum != ($rownum - 1) && !@odbc_data_seek($this->result, $rownum)) {
            if ($this->result === false) {
                return $this->db->raiseError(MDB2_ERROR_NEED_MORE_DATA, null, null,
                    'resultset has already been freed', __FUNCTION__);
            } elseif (is_null($this->result)) {
                return MDB2_OK;
            }
            return $this->db->raiseError(MDB2_ERROR_INVALID, null, null,
                'tried to seek to an invalid row number ('.$rownum.')', __FUNCTION__);
        }
        $this->rownum = $rownum - 1;
        return MDB2_OK;
    }

    // }}}
    // {{{ valid()

    /**
     * Check if the end of the result set has been reached
     *
     * @return mixed true or false on sucess, a MDB2 error on failure
     * @access public
     */
    function valid()
    {
        $numrows = $this->numRows();
        if (PEAR::isError($numrows)) {
            return $numrows;
        }
        return $this->rownum < ($numrows - 1);
    }

    // }}}
    // {{{ numRows()

    /**
     * Returns the number of rows in a result object
     *
     * @return mixed MDB2 Error Object or the number of rows
     * @access public
     */
    function numRows()
    {
        $rows = @odbc_num_rows($this->result);
        if (is_null($rows)) {
            if ($this->result === false) {
                return $this->db->raiseError(MDB2_ERROR_NEED_MORE_DATA, null, null,
                    'resultset has already been freed', __FUNCTION__);
            } elseif (is_null($this->result)) {
                return 0;
            }
            return $this->db->raiseError(null, null, null,
                'Could not get row count', __FUNCTION__);
        }
        return $rows;
    }
}

/**
 * MDB2 MySQL statement driver
 *
 * @package MDB2
 * @category Database
 * @author  Lukas Smith <smith@pooteeweet.org>
 */
class MDB2_Statement_odbc extends MDB2_Statement_Common
{
    // {{{ _execute()

    /**
     * Execute a prepared query statement helper method.
     *
     * @param mixed $result_class string which specifies which result class to use
     * @param mixed $result_wrap_class string which specifies which class to wrap results in
     * @return mixed a result handle or MDB2_OK on success, a MDB2 error on failure
     * @access private
     */
    function &_execute($result_class = true, $result_wrap_class = false)
    {
        if (is_null($this->statement)) {
            $result =& parent::_execute($result_class, $result_wrap_class);
            return $result;
        }
        $this->db->last_query = $this->query;
        $this->db->debug($this->query, 'execute', array('is_manip' => $this->is_manip, 'when' => 'pre', 'parameters' => $this->values));
        if ($this->db->getOption('disable_query')) {
            $result = $this->is_manip ? 0 : null;
            return $result;
        }

        $connection = $this->db->getConnection();
        if (PEAR::isError($connection)) {
            return $connection;
        }

        $query = 'EXECUTE '.$this->statement;
        if (!empty($this->positions)) {
            $parameters = array();
            foreach ($this->positions as $parameter) {
                if (!array_key_exists($parameter, $this->values)) {
                    return $this->db->raiseError(MDB2_ERROR_NOT_FOUND, null, null,
                        'Unable to bind to missing placeholder: '.$parameter, __FUNCTION__);
                }
                $value = $this->values[$parameter];
                $type = array_key_exists($parameter, $this->types) ? $this->types[$parameter] : null;
                if (is_resource($value) || $type == 'clob' || $type == 'blob') {
                    if (!is_resource($value) && preg_match('/^(\w+:\/\/)(.*)$/', $value, $match)) {
                        if ($match[1] == 'file://') {
                            $value = $match[2];
                        }
                        $value = @fopen($value, 'r');
                        $close = true;
                    }
                    if (is_resource($value)) {
                        $data = '';
                        while (!@feof($value)) {
                            $data.= @fread($value, $this->db->options['lob_buffer_length']);
                        }
                        if ($close) {
                            @fclose($value);
                        }
                        $value = $data;
                    }
                }
                $param_query = 'SET @'.$parameter.' = '.$this->db->quote($value, $type);
                $result = $this->db->_doQuery($param_query, true, $connection);
                if (PEAR::isError($result)) {
                    return $result;
                }
            }
            $query.= ' USING @'.implode(', @', array_values($this->positions));
        }

        $result = $this->db->_doQuery($query, $this->is_manip, $connection);
        if (PEAR::isError($result)) {
            return $result;
        }

        if ($this->is_manip) {
            $affected_rows = $this->db->_affectedRows($connection, $result);
            return $affected_rows;
        }

        $result =& $this->db->_wrapResult($result, $this->result_types,
            $result_class, $result_wrap_class, $this->limit, $this->offset);
        $this->db->debug($this->query, 'execute', array('is_manip' => $this->is_manip, 'when' => 'post', 'result' => $result));
        return $result;
    }

    // }}}
    // {{{ free()

    /**
     * Release resources allocated for the specified prepared query.
     *
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function free()
    {
        if (is_null($this->positions)) {
            return $this->db->raiseError(MDB2_ERROR, null, null,
                'Prepared statement has already been freed', __FUNCTION__);
        }
        $result = MDB2_OK;

        if (!is_null($this->statement)) {
            $connection = $this->db->getConnection();
            if (PEAR::isError($connection)) {
                return $connection;
            }
            $query = 'DEALLOCATE PREPARE '.$this->statement;
            $result = $this->db->_doQuery($query, true, $connection);
        }

        parent::free();
        return $result;
    }
}
?>
