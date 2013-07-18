<?php
// Codeigniter access check, remove it for direct use
if( !defined( 'BASEPATH' ) ) exit( 'No direct script access allowed' );

/**
 * Mongo_db
 *
 * A Codeigniter driver created to interact with the mongoDB database system,
 * this driver perfoms the operations in a similar way to the active record
 * queries used in the framework for other databases
 *
 * @author      Carlos Cessa <carlos@bitslice.net>
 * @author_url  http://www.bitslice.net
 */
class Mongo_db extends CI_Driver_Library {
    
    /**
     * Holder for the driver configuration options
     *
     * @var        array
     * @access     private
     */
    private $_config = array ();
    
    /** 
     * Constructor method
     *
     * @param   array       An array with the configuration options for the mongodb interaction, if nothing
     *                      is passed the library will try to use a mongo_db.php config file
     * @return  void
     * @access  public
     */
    public function __construct( $config = array() ) {
        // List the drivers that this class will use
        $this->valid_drivers = array( 'mongo_db_driver', 'mongo_db_result' );
        $CI =& get_instance();
        
        if( empty( $config ) ) {
            $CI->config->load( 'mongo_db', TRUE );
            $this->_config = $this->config->item( 'mongo_db' );
        } else {
            $this->_config = $config;
        }
        
        // Connect to the mongodb database using the specified parameters
        if( ! $this->connect( $this->_config ) ) {
            show_error( 'Unable to connect to the MongoDB server', 500 );
        }
    }
    
    /**
     * Method used to change the database to use in the active connection
     *
     * @param   string         The name of the database to switch to
     * @return  boolean        True on success, false otherwise
     * @access  public
     */
    public function change_db( $db ) {
        $this->driver->change_db( $db );
    }
    
    /**
     * Return a configuration parameter
     * 
     * @param   string         The configuration parameter to return
     * @return  mixed          If the required key exists, the value of it, false otherwise
     * @access  protected
     */
    public function config_item( $item ) {
        if ( array_key_exists( $item, $this->_config ) ) {
            return $this->_config[$item];
        } else {
            return FALSE;
        }
    }
    
    /**
     * Method used to connect to a mongo database
     *
     * @param   array         An array with the options for the connection
     * @return  boolean       True on success, false otherwise
     * @access  public
     */
    public function connect( $config ) {
        return $this->driver->connect( $config );
    }
    
    /**
     * Method used to terminate the active connection to the database
     *
     * @return  void
     * @access  public
     */
    public function close() {
        return $this->driver->close();
    }
    
    /**
     * Method used to specify the where part of the query to perform
     *
     * @param   mixed         This can be an array or a string depending on the circumstance
     * @param   mixed         If the previous param is a string this is the value to match the key to
     * @return  void
     * @access  public
     */
    public function where( $key, $value = null ) {
        $this->driver->where( $key, $value );
    }
    
    /**
     * Method used to set a OR clause to the where part of the query to perform
     *
     * @param  Array           An array containing the where clauses to set as conditionals
     * @return void
     * @access public
     */
    public function or_where( $conds ) {
        $this->driver->where( '$or', $conds );
    }
    
    /**
     * Method used to set a 'where less than' condition
     * 
     * @param   string         The document field to use
     * @param   mixed          The value to use on the comparission
     * @return  void
     * @access  public
     */
    public function where_lt( $key, $value ) {
        $this->driver->where( $key, array( '$lt' => $value ) );
    }
    
    /**
     * Method used to set a 'where less than or equal' condition
     * 
     * @param   string         The document field to use
     * @param   mixed          The value to use on the comparission
     * @return  void
     * @access  public
     */
    public function where_lte( $key, $value ) {
        $this->driver->where( $key, array( '$lte' => $value ) );
    }
    
    /**
     * Method used to set a 'where greater than' condition
     * 
     * @param   string         The document field to use
     * @param   mixed          The value to use on the comparission
     * @return  void
     * @access  public
     */
    public function where_gt( $key, $value ) {
        $this->driver->where( $key, array( '$gt' => $value ) );
    }
    
    /**
     * Method used to set a 'where greater than or equal' condition
     * 
     * @param   string         The document field to use
     * @param   mixed          The value to use on the comparission
     * @return  void
     * @access  public
     */
    public function where_gte( $key, $value ) {
        $this->driver->where( $key, array( '$gte' => $value ) );
    }
    
    /**
     * Method used to set a 'where not equal to' condition
     * 
     * @param   string         The document field to use
     * @param   mixed          The value to use on the comparission
     * @return  void
     * @access  public
     */
    public function where_ne( $key, $value ) {
        $this->driver->where( $key, array( '$ne' => $value ) );
    }
    
    /**
     * Method used to set a 'where in' condition
     * 
     * @param   string         The document field to use
     * @param   array          The array of values to use on the comparission
     * @return  void
     * @access  public
     */
    public function where_in( $key, $values ) {
        $this->driver->where( $key, array( '$in' => $values ) );
    }
    
    /**
     * Method used to set a 'where not in' condition
     * 
     * @param   string         The document field to use
     * @param   array          The array of values to use on the comparission
     * @return  void
     * @access  public
     */
    public function where_not_in( $key, $values ) {
        $this->driver->where( $key, array( '$nin' => $values ) );
    }
    
    /**
     * Method used to set a 'where > $max and < $min' condition
     * 
     * @param   string         The document field to use
     * @param   mixed          The minimum value to use on the comparission
     * @param   mixed          The maximum value to use on the comparission
     * @param   boolean        Whether to run the comparissions on inclusive mode or not
     * @return  void
     * @access  public
     */
    public function where_between( $key, $min, $max, $inclusive = true ) {
        if( $inclusive ) {
            $this->driver->where( $key, array( '$gte' => $min, '$lte' => $max ) );
        } else {
            $this->driver->where( $key, array( '$gt' => $min, '$lt' => $max ) );
        }
    }
    
    /**
     * Method used to set a 'where like' condition
     * 
     * @param   string         The document field to use
     * @param   mixed          The value to use on the comparission
     * @return  void
     * @access  public
     */
    public function where_like( $key, $value ) {
        $regex = new MongoRegex( "/^".$value."/i" );
        $this->driver->where( $key, array( '$regex' => $regex ) );
    }
    
    /**
     * Method used to set the order info for the query to perform
     * 
     * @param   string        The key to order the query from
     * @param   string        The type of the ordering, 'asc' or 'desc'
     * @return  void
     * @access  public
     */
    public function order_by( $key, $dir ) {
        $this->driver->order_by( $key, $dir );
    }
    
    /**
     * Method used to set the number of rows and the offset to be returned by the query
     * 
     * @param   int           The number of rows to limit the query to
     * @return  void
     * @access  public
     */
    public function limit( $limit, $offset = 0 ) {
        $this->driver->limit( $limit, $offset );
    }
    
    /**
     * Method used to specify the selects for the query
     *
     * @param   string        A comma separated list of the fields to retrieve from the collection
     * @return  void
     * @access  public
     */
    public function select( $select = '' ) {
        $this->driver->select( $select );
    }
    
    /**
     * Method used to retrieve the documents from a given collection, if the where, select or
     * any other narrower method is called previously it will affect this method call, also
     * note that after calling this method the flush method is called so the mentioned limit
     * options no longer exists for other queries
     *
     * @param   string         The name of the collection to retrieve the documents from
     * @param   int            The number of results to limit the query to
     * @param   int            The number of rows to skip from the query
     * @return  MongoCursor    The cursor for the performed query
     * @access  public
     */
    public function get( $collection, $limit = null, $offset = null ) {
        return $this->_get( $collection, $limit, $offset );
    }
    
    /**
     * Identical to the get method, only that adds a where array parameter
     *
     * @param   string          The name of the collection to retrieve the documents from
     * @param   array           An array with the where clauses for the query
     * @param   int             The number of results to limit the query to
     * @param   int             The number of rows to skip from the query
     * @return  MongoCursor     The cursor for the performed query
     * @access  public
     */
    public function get_where( $collection, $where, $limit = null, $offset = null ) {
        foreach( $where as $k => $v ) {
            $this->driver->where( $k, $v );
        }
        
        return $this->_get( $collection, $limit, $offset );
    }
    
    /**
     * Method used to manually set the data to insert into a collection
     * 
     * @param   string          The key of the value to insert
     * @param   mixed           The data to insert into the collection
     * @return  void
     * @access  public
     */
    public function set( $k, $v = null ) {
        $this->driver->set( $k, $v );
    }
    
    /**
     * Method used to insert data into a database collection
     * 
     * @param   string          The collection name to insert the data to
     * @param   array           An array containing the data to insert to the collection
     * @param   array           An array of options to perform the insert query with
     * @return  stdClass        An object containing the result of the insert query
     * @access  public
     */
    public function insert( $collection, $data = array(), $options = array() ) {
        return $this->driver->insert( $collection, $data, $options );
    }
    
    /**
     * Method used to quickly insert multiple documents into a collection
     *
     * @param   string          The collection name to insert the data to
     * @param   array           An array containing the data to insert to the collection
     * @param   array           An array of options to perform the insert query with
     * @return  stdClass        An object containing the result of the insert query
     * @access  public
     */
    public function insert_batch( $collection, $data = null, $options = array() ) {
        return $this->driver->insert( $collection, $data, $options, TRUE );
    }
    
    /**
     * Run an update operation with an implicit '$set' operator to avoid unintended replacements 
     * 
     * @param   string          The name of the collection to update
     * @param   array           An array containing the data to modify in the document
     * @param   mixed           An array or a string with the identifier for the document
     * @param   array           An array of options for the operation
     * @return  stdClass        An object with the information of the executed query
     * @access  public
     */
    public function update( $collection, $data, $where = null, $options = array() ) {
        return $this->driver->update( $collection, $data, $where, $options );
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
    public function modify( $collection, $data, $where = null, $options = array() ) {
        return $this->driver->modify( $collection, $data, $where, $options );
    }
    
    /**
     * Method used to remove a document from a given collection
     * 
     * @param   strin           The collection name to remove the document from
     * @param   array           An array containing the document identifiers
     * @param   array           An array of options to perform the action with
     * @return  stdClass        An object with the information of the executed query
     * @access  public
     */
    public function remove( $collection, $where = null, $options = array() ) {
        return $this->driver->remove( $collection, $where, $options );
    }
    
    /**
     * Return the number of documents in a given collection
     * 
     * @param   string         The collection to count
     * @return  int
     * @access  public
     */
    public function count_all( $collection ) {
        return $this->driver->count_all( $collection );
    }
    
    /**
     * Utility function that returns the last ID generated from an insert operation
     * 
     * @return  string
     * @access  public
     */
    public function insert_id() {
        return $this->driver->insert_id();
    }
    
    /**
     * Method used to drop a collection from the database
     * 
     * @param   string          The collection name to empty
     * @return  array           The database server response, check the MongoDB docs for more information
     * @access  public
     */
    public function drop_collection( $collection ) {
        return $this->driver->drop_collection( $collection );
    }
    
    /**
     * Utility function to generate a 'MongoDate' object for general purposes
     * 
     * @param   mixed           Either a timestamp(int) or a string based date
     * @return  MongoDate       The date object, access the 'sec' property for the timestamp
     * @access  public
     */
    public function gen_date( $param = null ) {
        if( $param ) {
            is_string( $param ) ? $date = new MongoDate( strtotime( $param ) ) : $date = new MongoDate( $param );
        } else {
            $date = new MongoDate();
        }
        return $date;
    }
    
    /**
     * Utility function to generate a 'MongoBinData' object to store a file as part of a document
     * 
     * @param   string          The content of the file to store
     * @return  MongoBinData    A ready-to-store representation of the binary data
     * @access  public
     */
    public function gen_bindata( $contents ) {
        if( empty( $contents ) ) {
            show_error( 'You must provide the contents a file to this function', 500 );
        }
        return new MongoBinData( $contents );
    }
    
    /**
     * Utility function that generate a 'MongoDBRef' ready for storage
     * 
     * @param   string          The collection the referenced document is in
     * @param   MongoID         The '_id' of the referenced document
     * @param   string          Optionally you can reference documents of another database on the server
     * @return  MongoDBRef
     * @access  public
     */
    public function gen_dbref( $collection, $id, $db = null ) {
        if( $db ) {
            return MongoDBRef::create( $collection, $id, $db );
        } else {
            return MongoDBRef::create( $collection, $id );
        }
    }
    
    /**
     * Utility function that generates a MongoID instance base on a string representation
     *
     * @param   string          The string representation of the desired ID object
     * @return  MongoId
     * @access  public
     */
    public function gen_id( $id_string ) {
        return new MongoId( $id_string );
    }
    
    /**
     * Method used to retrieve the cursor for the prepared query
     * 
     * @param   int             The number of results to limit the query to
     * @param   int             The number of rows to skip from the query
     * @return  MongoCursor     The cursor for the performed query
     * @access  private
     */
    private function _get( $collection, $limit = false, $offset = false ) {
        if( $limit ) {
            $this->driver->limit( $limit );
        }
        if( $offset ) {
            $this->driver->offset( $offset );
        }
        
        $result = $this->driver->get( $collection );
        return $this->result->get_result( $result, $this->driver->db() );
    }
}
// END Mongo_db Class

/* End of file Mongo_db.php */
/* Location: ./{APPLICATION}/libraries/Mongo_db/Mongo_db.php */