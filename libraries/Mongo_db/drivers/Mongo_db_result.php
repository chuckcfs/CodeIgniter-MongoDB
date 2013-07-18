<?php
// Codeigniter access check, remove it for direct use
if( !defined( 'BASEPATH' ) ) exit( 'No direct script access allowed' );

/**
 * Mongo_db_result
 *
 * Driver that handles the connection to MongoDB and the result methods of the MongoDB cursor
 *
 * @author      Carlos Cessa <carlos@bitslice.net>
 * @author_url  http://www.bitslice.net
 */
class Mongo_db_result extends CI_Driver {
    
    /**
     * Holder for the result of the query excuted
     * 
     * @var     resource
     * @access  private
     */
    private $_result = NULL;
    
    /**
     * Holder for the database connection 
     * 
     * @var     resource
     * @access  private
     */
    private $_db = NULL;
    
    /**
     * Method used to create a result instance to perform the operations to the
     * MongoDB cursor
     * 
     * @param   MongoCursor         The cursor obtained from the query executed
     * @param   MongoDB             The MongoDB connection used
     * @return  Mongo_db_result     The result object
     * @access  public
     */
    public function get_result( $cursor = NULL, &$db ) {
        $this->_result = $cursor;
        $this->_db = $db;
        
        // Move the cursor to the next location for the individual rows queries
        $this->_result->next();
        return $this;
    }
    
    /**
     * Method used to get the results of the issued query as an array of objects
     * 
     * @return  array               An array of the result objects
     * @access  public 
     */
    public function result() {
        $result = array();
        foreach( $this->_result as $row ) {
            if( $this->config_item( 'mongo_expand_dbrefs' ) )
                $this->_deref( $row );
            $result[] = (object) $row;
        }
        return $result;
    }
    
    /**
     * Method used to get the results of the query as an array of arrays
     * 
     * @return  array              An array containing the results array
     * @access  public
     */
    public function result_array() {
        $result = array();
        foreach( $this->_result as $row ) {
            if( $this->config_item( 'mongo_expand_dbrefs' ) ) {
                $this->_deref( $row );
            }
            $result[] = $row;
        }
        return $result;
    }
    
    /**
     * Method used to get an object with a row of the performed query
     * 
     * @param   int                The position of the required row to be returned
     * @return  stdClass           An object containing the first result of the query
     * @access  public
     */
    public function row( $row = NULL ) {
        return (object)$this->_row( $row );
    }
    
    /**
     * Method used to get an array with a row of the performed query
     *
     * @param   int                 The position of the required row to be returned
     * @return  array               An array containing the first result of the query
     * @access  public
     */
    public function row_array( $row = NULL ) {
        return $this->_row( $row );
    }
    
    /**
     * Method used to retrieve the first row of the result of a query
     * 
     * @param   string             Whether or not the result should be returned as an array
     * @return  mixed              An array or an object with the result of the first query result
     * @access  public
     */
    public function first_row( $array = '' ) {
        $this->_result->rewind();
        
        $result = $this->_get_result();
        return $this->_cast_result( $array, $result );
    }
    
    /**
     * Method used to retrieve the last row from a query result
     * 
     * @param   string             Whether or not the result should be returned as an array
     * @return  mixed              An array or an object with the result of the last query result
     * @access  public
     */
    public function last_row( $array = '' ) {
        // Advance the cursor to the last position
        for( $i = 1; $i < $this->_result->count(); $i++ ) {
            $this->_result->next();
        }
        
        $result = $this->_get_result();
        return $this->_cast_result( $array, $result );
    }
    
    /**
     * Method used to retrieve the previous row from a query result
     *
     * @param   string             Whether or not the result should be returned as an array
     * @return  mixed              An array or an object with the result of the previous query result
     * @access  public
     */
    public function previous_row( $array = '' ) {
        $info = $this->_result->info();
        $this->_result->rewind();
        
        // Check that the cursor has started iterating
        if( isset( $info['at'] ) ) {
            // Move the cursor to the required position
            for( $i = 1; $i < ( $info['at'] - 1 ); $i++ ) {
                $this->_result->next();
            }
        }
        
        $result = $this->_get_result();
        return $this->_cast_result( $array, $result );
    }
    
    /**
     * Method used to retrieve the next row from a query result
     *
     * @param   string             Whether or not the result should be returned as an array
     * @return  mixed              An array or an object with the result of the next query result
     * @access  public
     */
    public function next_row( $array = '' ) {
        $this->_result->next();
        
        $result = $this->_get_result();
        return $this->_cast_result( $array, $result );
    }
    
    /**
     * Method used to get the number of rows returned by the query
     * 
     * @return  int                The number of rows returned by the executed query
     * @access  public
     */
    public function num_rows() {
        return $this->_result->count();
    }
    
    /**
     * Method used to retrieve the fields number in the current row 
     * 
     * @return  int                The number of the fields in the row
     * @access  public
     */
    public function num_fields() {
        return count( $this->_result->current() );
    }
    
    /**
     * Returns information about the executed query, useful for debugging and optimization
     * 
     * @return  array
     * @access public
     */
    public function explain_query() {
        return $this->_result->explain();
    }
    
    /**
     * Method used to get a row resulting from the executed query
     * 
     * @param   int                The index of the row to be returned
     * @return  array              An array containing the result of the query
     * @access  private
     */
    private function _row( $row ) {
        if( ! $row ) {
            return $this->_get_result();
        }
        
        // Move the cursor to the required position
        for( $i = 1; $i < $row; $i++ ) {
            $this->_result->next();
        }
        
        return $this->_get_result();
    }
    
    /**
     * Method used to get a row from the executed query
     * 
     * @return  array              An array containing the row data of the result
     * @access  private
     */
    private function _get_result() {
        if( $this->_result->valid() ) {
            $row = $this->_result->current();
            if( $this->config_item( 'mongo_expand_dbrefs' ) ) {
                $this->_deref( $row );
            }
            return $row;
        } else {
            return array();
        }
    }
    
    /**
     * Method used to cast the result row to an object or an array
     * 
     * @param   string             Whether or not the result should be returned as an array
     * @param   array              The array containing the result of the query
     * @return  mixed              An array or an object with the result of the last query result
     * @access  private
     */
    private function _cast_result( $array, $result ) {
        if( $array == 'array' ) {
            return $result;
        } else {
            return (object) $result;
        }
    }
    
    /**
     * Utility function to expand any DBRefs present in a document
     * 
     * @param   mixed            The document to scan for DBRefs instances
     * @return  void
     * @access  private
     */
    private function _deref( &$data ) {
        foreach( $data as $key => $value ) {
            if( is_object( $value ) || is_array( $value ) ) {
                if( is_object( $data ) ) {
                    $data->{$key} = $this->_deref( $value );
                } else {
                    $data[ $key ] = $this->_deref( $value );
                }
            }
            
            if( MongoDBRef::isRef( $value ) ) {
                if( is_object( $data ) ) {
                    $data->{$key} = $this->_db->getDBRef( $value );
                } else {
                    $data[ $key ] = $this->_db->getDBRef( $value );
                }
            }
        }
        return $data;
    }
}
// END Mongo_db_result.php Class

/* End of file Mongo_db_result.php */
/* Location: ./{APPLICATION}/libraries/Mongo_db/drivers/Mongo_db_result.php */