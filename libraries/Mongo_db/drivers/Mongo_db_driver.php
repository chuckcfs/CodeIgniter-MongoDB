<?php
// Codeigniter access check, remove it for direct use
if( !defined( 'BASEPATH' ) ) exit( 'No direct script access allowed' );

/**
 * Mongo_db_driver
 *
 * Driver that handles the connection to MongoDB and the querying to the database
 *
 * @author      Carlos Cessa <carlos@bitslice.net>
 * @author_url  http://www.bitslice.net
 */
class Mongo_db_driver extends CI_Driver {
    
    /**
     * Holder for the MongoDB connection object
     *
     * @var     Mongo
     * @access  private
     */
    private $_conn = NULL;
    
    /**
     * Holder for the active database selected
     * 
     * @var     resource
     * @access  private
     */
    private $_db = NULL;
    
    /**
     * Holder for the wheres of the query to execute
     *
     * @var     array
     * @access  private
     */
    private $_ws = array();
    
    /**
     * Holder for the selects part of the query
     *
     * @var     array
     * @access  private
     */
    private $_sls = array();
    
    /**
     * Holder for the from part of the query
     * 
     * @var     string
     * @access  private
     */
    private $_from = '';
    
    /**
     * Holder for the insertion data issued with the set method
     * 
     * @var     array
     * @access  private
     */
    private $_data = array();
    
    /**
     * Holder for the limit of the query
     * 
     * @var     int
     * @access  private
     */
    private $_lmt = 99999;
    
    /**
     * Holder for the offset of the query
     * 
     * @var     int
     * @access  private
     */
    private $_ost = 0;
    
    /**
     * Holder for the sort query info
     * 
     * @var     array
     * @access  private
     */
    private $_st = array();
    
    /**
     * Holder for the latest generated id on an insert operation
     * 
     * @var     string
     * @access  private
     */
    private $_insert_id = '';
    
    /**
     * Constructor method
     *
     * @return  void
     * @access  public
     */
    public function __construct() {
        // Check that the PHP MongoDB driver is installed
        if( ! class_exists( 'Mongo' ) ) {
            show_error( 'The MongoDB PECL extension isn\'t installed', 500 );
        }
    }
    
    /**
     * Method used to change the database to use in the active connection
     *
     * @param   string          The name of the database to switch to
     * @return  boolean         True on success, false otherwise
     * @access  public
     */
    public function change_db( $db ) {
        if( empty( $db ) ) {
            show_error( 'The database name must be specified' );
            return false;
        }
        try {
            $this->_db = $this->_conn->selectDB( $db );
            return true;
        } catch( Exception $e ) {
            show_error( "Unable to use {$db} - {$e->getMessage()}", 500 );
            return false;
        }
    }
    
    /**
     * Method used to connect to a mongo database
     *
     * @param   array           An array with the options for the connection
     * @return  boolean         True on success, false otherwise
     * @access  public
     */
    public function connect( $config ) {
        // Check the required configuration fields
        if( empty( $config['mongo_host'] ) ) {
            show_error( 'The host of the mongodb is required', 500 );
        }
        
        // Set the connection string
        $conn = 'mongodb://'.$config['mongo_host'];
        
        // Check that a port is specified
        if( ! empty( $config['mongo_port'] ) ) {
            $conn .= ":{$config['mongo_port']}";
        }
        
        // Check that a database is specified to use instead of admin
        if( ! empty( $config['mongo_db'] ) ) {
            $conn .= "/{$config['mongo_db']}";
        }
        
        // Check if username/password is beign used
        $options = array();
        if( ! empty( $config['mongo_user'] ) && ! empty( $config['mongo_pass'] ) ) {
            // Use the options method instead of passing the user and pass in the address directly
            // useful for the case the user includes ":" or "@" on the pass
            $options['username'] = $config['mongo_user'];
            $options['password'] = $config['mongo_pass'];
        }
        
        // Set the replicaset connection parameter
        $options['replicaSet'] = $config['mongo_replicaset'];
        
        // Connect to the database
        try {
            // Establish the connection to the server
            $this->_conn = new Mongo( $conn, $options );
            if ( $config['mongo_slave_ok'] ) {
                // TODO - Update drive to set slave in MongoDB PHP new driver
                //$this->_conn->setSlaveOkay( $config['mongo_slave_ok'] );
            }
            
            // Connect to the required database
            $this->_db = $this->_conn->selectDB( $config['mongo_db'] );
            return true;
        } catch( MongoConnectionException $e ) {
            show_error( "Unable to connect to MongoDB - {$e->getMessage()}", 500 );
            return false;
        }
    }
    
    /**
     * Return the number of documents in a given collection
     * 
     * @param   string         The collection to count
     * @return  int
     * @access  public
     */
    public function count_all( $collection ) {
        return $this->_db->{$collection}->count();
    }
    
    /**
     * Method used to close de connection to the database
     * 
     * @return  boolean         True on success, false otherwise
     * @access  public
     */
    public function close() {
        $this->_db	= NULL;
        return $this->_conn->close();
    }
    
    /**
     * Accesor method for the internally used database connection object
     * 
     * @return  MongoDB
     * @access  public
     */
    public function db() {
        return $this->_db;
    }
    
    /**
     * Method used to drop a collection from the database
     * 
     * @param   string          The name of the collection to drop
     * @return  array           The response obtainined from the database
     * @access  public
     */
    public function drop_collection( $collection ) {
        if( empty( $collection ) ) {
            show_error( 'The collection name must be specified', 500 );
        }
        
        return $this->_db->{$collection}->drop();
    }
    
    /**
     * Method used to specify the from part for the query
     *
     * @param   string         The name of the collection to perform the query against
     * @return  void
     * @access  public
     */
    public function from( $collection ) {
        if ( empty( $collection ) ) {
            show_error( 'The collection name must be specified', 500 );
        }
        $this->_from = $collection;
    }
    
    /**
     * Method used to retrieve the documents from a given collection, if the where, select or
     * any other narrower method is called previously it will affect this method call, also
     * note that after calling this method the flush method is called so the mentioned limit
     * options no longer exists for other queries
     *
     * @param   string          The name of the collection to retrieve the documents from
     * @return  MongoCursor     The cursor for the performed query
     * @access  public
     */
    public function get( $col ) {
        if ( empty( $col ) ) {
            if ( empty( $this->_from ) ) {
                show_error( 'The collection name must be specified', 500 );
            }
            $col = $this->_from;
        }
        
        $docs = $this->_db->{$col}
            ->find( $this->_ws, $this->_sls )
            ->limit( $this->_lmt )
            ->skip( $this->_ost )
            ->sort( $this->_st );
        $this->_flush();
        
        return $docs;
    }
    
    /**
     * Method used to insert data into a collection
     * 
     * @param   string          The collection name to insert the data to
     * @param   array           An array containing the data to insert to the collection
     * @param   options         An array of options to set to the MongoDB insert query
     * @param   boolean         Whether or not to use a batch insert
     * @return  stdClass        An object containing the result of the insert query
     * @access  public
     */
    public function insert( $collection, $data, $options, $batch = false ) {
        if( empty( $collection ) ) {
            show_error( 'The collection name must be specified', 500 );
        }
        
        // Check if there's data from the set method
        $data = array_merge( $this->_data, $data );
        if( empty( $data ) ) {
            show_error( 'No data provided to insert in the collection', 500 );
        }
        
        // Adjust the method to use
        $batch ? $method = 'batchInsert' : $method = 'insert';
        
        // Use the config values for keys not already set by the user
        if( ! array_key_exists( 'safe', $options ) ) {
            $options['safe']	= $this->config_item( 'mongo_ensure_replicas' );
        }
        if( ! array_key_exists( 'timeout', $options) ) {
            $options['timeout']	= $this->config_item( 'mongo_write_timeout' );
        }
        
        $action = new stdClass();
        try {
            if ( $this->_db->{$collection}->{$method}( $data, $options ) ) {
                $action->result     = TRUE;
                $action->id         = $data['_id']->{'$id'};
                $this->_insert_id   = $data['_id']->{'$id'};
            } else {
                $action->result     = FALSE;
            }
        } catch( MongoCursorException $e ) {
            $action->error  = $e->getMessage();
            $action->result = FALSE;
        }
        $this->_flush();
        
        return $action;
    }
    
    /**
     * Utility function that returns the last ID generated from an insert operation
     * 
     * @return  string
     * @access  public
     */
    public function insert_id() {
        return $this->_insert_id;
    }
    
    /**
     * Method used to set the limit of results for a given query
     * 
     * @param   int             The number of rows to limit the query to
     * @param   int             The offset to use for the query
     * @return  void
     * @access  public
     */
    public function limit( $limit, $offset = null ) {
        if ( $limit !== NULL && is_numeric( $limit ) && $limit >= 1 ) {
            $this->_lmt = $limit;
        }
        if ( $offset !== NULL && is_numeric( $offset ) && $offset >= 1 ) {
            $this->_ost = $offset;
        }
    }
    
    /**
     * Method used to run an update operation with MongoDB operators like '$inc' and '$desc'
     * 
     * @param   string          The name of the collection to update
     * @param   array           An array containing the data to modify in the document
     * @param   mixed           An array or a string with the identifier for the document
     * @param   array           An array of options for the operation
     * @return  stdClass        An object with the information of the executed query
     * @access  public
     */
    public function modify( $collection, $data, $where, $options ) {
        if( empty( $collection ) ) {
            if( empty( $this->_from ) ) {
                show_error( 'The collection name must be specified', 500 );
            } else {
                $collection	= $this->_from;
            }
        }
        
        $data = array_merge( $this->_data, $data );
        if( !$data || empty( $data ) ) {
            show_error( 'No data provided to insert to the collection', 500 );
        }
        
        // Prepare the where statements whether from an array or a string
        $this->_set_where( $where );
        
        // Use the config values for keys not already set by the user
        if ( ! array_key_exists( 'safe', $options ) ) {
            $options['safe'] = $this->config_item( 'mongo_ensure_replicas' );
        }
        if ( ! array_key_exists( 'timeout', $options) ) {
            $options['timeout'] = $this->config_item( 'mongo_write_timeout' );
        }
        if ( ! array_key_exists( 'upsert', $options ) ) {
            $options['upsert'] = $this->config_item( 'mongo_use_upsert' );
        }
        if ( ! array_key_exists( 'multiple', $options ) ) {
            $options['multiple'] = $this->config_item( 'mongo_update_all' );
        }
        
        $action = new stdClass();
        try {
            if ( $this->_db->{$collection}->update( $this->_ws, $data, $options ) ) {
                $action->result = true;
            } else {
                $action->result	= false;
            }
        } catch( MongoCursorException $e ) {
            $action->error  = $e->getMessage();
            $action->result = FALSE;
        }
        $this->_flush();
        
        return $action;
    }
    
    /**
     * Method used to set the sorting options for the query
     * 
     * @param   string          The key of the field to order the query from
     * @param   string          The direction of the sort, for the key
     * @return  void
     * @access  public
     */
    public function order_by( $key, $dir ) {
        if( ! is_string( $key ) || empty( $key ) ) {
            show_error( 'The field name to sort with must set', 500 );
        }
        
        if ( $dir == 'asc' ) {
            $this->_st[$key] = 1;
        } else if( $dir == 'desc' ) {
            $this->_st[$key] = -1;
        }
    }
    
    /**
     * Method used to remove a document from a selected collection
     * 
     * @param   string          The name of the collection to remove the document from
     * @param   array           An array with the information to select the document to remove
     * @param   array           An array of options to perform the query with
     * @return  stdClass        An object with the query results information
     * @access  public
     */
    public function remove( $collection, $where, $options ) {
        if ( empty( $collection ) ) {
            if ( empty( $this->_from ) ) {
                show_error( 'The collection name must be specified', 500 );
            }
            $collection	= $this->_from;
        }
        
        if( is_array( $where ) ) {
            foreach( $where as $k => $v ) {
                $this->_ws[$k] = $v;
            }
        }
        
        // Set whether to delete all the matching documents or not
        if( ! array_key_exists( 'justOne', $options ) ) {
            $options['justOne'] = !( $this->config_item( 'mongo_remove_all' ) );
        }
        
        $action = new stdClass();
        try {
            if ( $this->_db->{$collection}->remove( $this->_ws, $options ) ) {
                $action->result = true;
            } else {
                $action->result	= false;
            }
        } catch( MongoCursorException $e ) {
            $action->error  = $e->getMessage();
            $action->result = false;
        }
        $this->_flush();
        
        return $action;
    }
    
    /**
     * Method used to specify the selects for the query
     *
     * @param   string          A comma separated list of the fields to retrieve
     * @return  void
     * @access  public
     */
    public function select( $select = '' ) {
        // Check if at least a field is passed
        if( empty( $select ) ) {
            show_error( 'The fields to retrieve must be specified' );
        }
        
        // Get the fields from the given string
        $fields = explode( ',', $select );
        foreach( $fields as $field ) {
            $this->_sls[trim( $field )] = TRUE;
        }
    }
    
    /**
     * Method used to set the data to insert into a collection
     * 
     * @param   mixed           The key for the data to insert or an array of data
     * @param   mixed           The value of the given key, if not value is passed null will be used
     * @return  void
     * @access  public
     */
    public function set( $k, $v = null ) {
        if( is_array( $k ) ) {
            $this->_data = $k;
        } else if( is_object( $k ) ) {
            $this->_data = (array)$k;
        } else {
            if( !is_string( $k ) || empty( $k ) ) {
                show_error( 'The key for the data to insert must be a string', 500 );
            } else {
                $this->_data[$k] = $v;
            }
        }
    }
    
    /**
     * Method used to update a document from a given collection
     * 
     * @param   string          The name of the collection to update the document from
     * @param   array           An array containing the data to update the document with
     * @param   mixed           An array or string with the document identifier
     * @param   array           An array of options for the update
     * @return  stdClass        An object containing the update query result
     * @access  public
     */
    public function update( $collection, $data, $where, $options ) {
        if( empty( $collection ) ) {
            if ( empty( $this->_from ) ) {
                show_error( 'The collection name must be specified', 500 );
            }
            $collection	= $this->_from;
        }
        
        // Check if there's data from the set method
        $data = array_merge( $this->_data, $data );
        if( empty( $data ) ) {
            show_error( 'No data provided to update the collection with', 500 );
        }
        
        // Prepare the where statements whether from an array or a string
        $this->_set_where( $where );
        
        // Use the config values for keys not already set by the user
        if ( ! array_key_exists( 'safe', $options ) ) {
            $options['safe'] = $this->config_item( 'mongo_ensure_replicas' );
        }
        if ( ! array_key_exists( 'timeout', $options) ) {
            $options['timeout'] = $this->config_item( 'mongo_write_timeout' );
        }
        if ( ! array_key_exists( 'upsert', $options ) ) {
            $options['upsert'] = $this->config_item( 'mongo_use_upsert' );
        }
        if ( ! array_key_exists( 'multiple', $options ) ) {
            $options['multiple'] = $this->config_item( 'mongo_update_all' );
        }
        
        // Automatically add a '$set' operator to avoid replacing the hole document
        if ( array_key_exists( '$inc', $data ) ) {
            $newdoc   = $data;
        } else if ( array_key_exists( '$set', $data ) ) {
            $newdoc   = $data;
        } else {
            $newdoc   = array( '$set' => $data );
        }
        
        $action = new stdClass();
        try {
            if( $this->_db->{$collection}->update( $this->_ws, $newdoc, $options ) ) {
                $action->result = true;
            } else {
                $action->result = false;
            }
        } catch( MongoCursorException $e ) {
            $action->error  = $e->getMessage();
            $action->result = false;
        }
        $this->_flush();
        
        return $action;
    }
    
    /**
     * Method used to specify the where part of the query to perform
     *
     * @param   mixed          This can be an array or a string depending on the circumstance
     * @param   mixed          If the previous param is a string this is the value to match that key to
     * @return  void
     * @access  public
     */
    public function where( $key, $value = null ) {
        // Check if the method is being called with just a value, or an array
        if( is_array( $key ) ) {
            foreach( $key as $k => $v ) {
                $this->_ws[$k] = $v;
            }
        } else {
            if( ! $value ) {
                show_error( 'A value must be provided for the where statement' );
            } else {
                $this->_ws[$key] = $value;
            }
        }
    }
    
    /**
     * Method used to reset all the delimiters for a query
     *
     * @return  void
     * @access  private
     */
    private function _flush() {
        $this->_ws        = array();
        $this->_sls       = array();
        $this->_data      = array();
        $this->_from      = '';
        $this->_query     = '';
    }
    
    /**
     * Prepare the where statements whether from an array or a string
     * 
     * @param   mixed         The "where" statements to process
     * @return  void
     * @access  private
     */
    private function _set_where( $where = null ) {
        if ( isset( $where ) && $where !== null ) {
            if ( is_array( $where ) ) {
                foreach ( $where as $k => $v ) {
                    $this->_ws[$k] = $v;
                }
            } else if( is_string( $where ) ) {
                $wheres = explode( ',', $where );
                foreach ( $wheres as $wr ) {
                    $pair = explode( '=', trim( $wr ) );
                    $this->_ws[trim( $pair[0] )] = trim( $pair[1] );
                }
            }
        }
    }
}
// END Mongo_db_driver Class

/* End of file Mongo_db_driver.php */
/* Location: ./{APPLICATION}/libraries/Mongo_db/drivers/Mongo_db_driver.php */