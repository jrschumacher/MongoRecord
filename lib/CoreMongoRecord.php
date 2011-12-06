<?php
  /**
   * Mongo Record, a simple Mongo ORM for PHP with ActiveRecord-like features.
   * 
   * @author     Ryan Schumacher <https://github.com/jrschumacher/>
   * @author     Lu Wang <https://github.com/lunaru/>
   * @license    See LICENSE
   * @version    1.5
   */

  namespace MongoRecord;
  
  /**
   * Core Mongo Record, core functionality Mongo Record library
   * 
   * This class is used to define core functionality, including opening a conn-,
   * ection to the server, connecting to the database, and selecting the coll-
   * ection.
   * 
   * @package    MongoRecord
   */
  class CoreMongoRecord {
    
    /**
     * Mongo connection
     */
    public static $connection = NULL;
    
    /**
     * Mongo database
     */
    public static $database = NULL;
    
    /**
     * Mongo collection
     */
    protected static $collection = NULL;
    
    /**
     * Mongo find timeout
     */
    protected static $findTimeout = 20000;
    
    /**
     * Set Mongo connection
     * 
     * Sets the connection of all or models or collection of models. 
     * Accepts Mongo DSN or a mongo connection resource handler.
     * 
     * @link http://www.php.net/manual/en/mongo.construct.php
     * @param mixed $connection the connection variable either Mongo resource or mongo DSN
     * @param array $options as defined in the link above
     */
    public static function mongoSetConnection($connection = 'localhost', $options = array('connect' => FALSE)) {
      // If is an existing connection
      if(is_object($connection) && get_class($connection) == 'Mongo') {
        // Set connection if connected
        if($connection->connected == TRUE) {
          self::$connection = $connection;
          return; 
        }
        
        // Try to connect to connection
        try {
          self::$connection->connect();
          self::$connection->close();
        }
        catch(MongoException $e) {
          throw new CoreMongoRecordException("Could not connect to Mongo server, $connection: {$e->getMessage()}");
        }
      }
      
      // Connect based on given dsn
      try {
        self::$connection = new \Mongo($connection, $options);
      }
      catch(MongoException $e) {
        throw new CoreMongoRecordException("Could not connect to Mongo server, $connection: {$e->getMessage()}");
      }
    }
    
    /**
     * Set Mongo database
     * 
     * @param string $database name of the database
     */
    public static function mongoSetDatabase($database) {
      self::$database = $database;
    }

    /**
     * Sets the find timeout of the Mongo connection
     * 
     * @param int $timeout
     */
    public static function mongoSetFindTimeout($timeout) {
      self::$findTimeout = $timeout;
    }
    
    /**
     * Sets the Mongo find timeout
     * 
     * **DEPRECIATED** due to conflic with getters and setters
     * 
     */
    public static function setFindTimeOut() {
      trigger_error('Depreciated, use mongoSetFindTimeout($timeout) instead', E_USER_DEPRECIATED);
    }
    
    /**
     * Is anonymous Mongo Record
     */
    protected static function mongoIsAnonymous() {
      $class_name = get_called_class();
      if($class_name === 'MongoRecord\MongoRecord') {
        return TRUE;
      }
      return FALSE;
    }
    
    /**
     * Get the Mongo collection
     * 
     * Will get the collection from the class name.
     * 
     * Anonymous MongoRecords use the passed collection name. Note setting the
     * database for each collection of an anonymous MongoRecord is not possib-
     * le at the moment.
     * 
     * @return MongoCollection
     */
    protected static function &mongoGetCollection() {
      $collection_name = get_called_class();
      $collection =& self::$collection;
      
      // Anonymous MongoRecords
      if(self::mongoIsAnonymous()) {
        if(!is_array(self::$collection)) {
          self::$collection = array();
        }
        $collection_name = func_get_arg(0);
        $collection =& self::$collection[$collection_name];
      }
      
      // The collection hasn't been opened
      if(empty($collection)) {
        
        // Remove namespace
        if(($pos = strrpos($collection_name, '\\')) !== FALSE) {
          $collection_name = substr($collection_name, $pos + 1);
        }
      
        $inflector = Inflector::getInstance();
        $collection_name = $inflector->tableize($collection_name);
    
        if(self::$database == NULL) {
          throw new Exception("CoreMongoRecord::database must be initialized to a proper database string");
        }
    
        if(self::$connection == NULL) {
          throw new Exception("CoreMongoRecord::connection must be initialized to a valid Mongo object");
        }
        
        if(!(self::$connection->connected)) {
          self::$connection->connect();
        }
        
        $collection = self::$connection->selectCollection(self::$database, $collection_name);
      }
  
      return $collection;
    }    
  }
