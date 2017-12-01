<?php
/*
 * This is proprietary source code
 * @author Sinevia Ltd
 * @date 12 Jan 2011
 * @version 2.0
 *
 * CHANGELOG:
 * Version 2.0
 *  - Added compression
 *  - Modified user/pass reading
 * Version 1.8
 *  - Added method "next_id"
 *  - Added method "move_up"
 *  - Added method "move_down"
 * Version 1.6
 *  - Added Tree_Store class
 * Version 1.4
 *  - Added method "encode"
 *  - Added method "decode"
 *
 * Version 1.2
 * - Added method "add"
 * - Added method "uid"
 *
 * Version 1.0
 */
interface Key_Value_Store {
	function add($value);
	function debug($debug);
	function get($key);
	function key_exists($key);
	function keys();
	function set($key,$value);
	function uid();
}

//=================== START OF CLASS =====================================//
// CLASS: File_Store                                                      //
//========================================================================//
class File_Store implements Key_Value_Store{
	/**
	 * Contains all the Elements in the database
	 * @var Array
	 */
	private $elements = array();
	/**
	 * The path to the store file
	 * @var String
	 */
	private $database_path;
	/**
	 * Username for accessing the store
	 * @var String
	 */
	private $username;
	/**
	 * Password for accessing the store
	 * @var String
	 */
	private $password;
	/**
	 * The debug output visibility
	 * @var Boolean
	 */
	private $debug=false;
	/**
	 * The compression of the database file
	 * @var Boolean
	 */
	private $compress=false;
	/**
	 * Is the database file already read
	 * @var Boolean
	 */
	private $is_read=false;
	/**
	 * Is the database saved
	 * @var Boolean
	 */
	private $is_saved = true;

	//========================= START OF METHOD ===========================//
	//  METHOD: __construct                                                //
	//=====================================================================//
	   /**
	    * The default constructor
	    *
	    * This is the default constructor for working with file store.
	    * It must be always called first, because it initializes the
	    * database and reads it.
	    * @param String $filepath The path to the database file
	    * @param String $options The username,password,saveonexit
	    * @since Version 1.0
	    */
	function __construct($filepath,$options=array()){
		$this->database_path=trim($filepath);
		$this->username=(isset($options['username'])==false)?'':trim($options['username']);
		$this->password=(isset($options['password'])==false)?'':trim($options['password']);
		$this->saveonexit=(isset($options['saveonexit'])==false)?true:$options['saveonexit'];
		$this->compress=(isset($options['compress'])==false)?false:$options['compress'];
		$this->database_path = str_replace("\\",DIRECTORY_SEPARATOR,str_replace("/",DIRECTORY_SEPARATOR,$this->database_path));
	}
	//=====================================================================//
	//  METHOD: __construct                                                //
	//========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: add                                                        //
    //=====================================================================//
       /**
        * Adds a new entry to the store with randomly generated unique ID
        *
        * This method is used to add a new entry to the store. The entry will
        * be auromaticly assigned a randomly generated unique ID.
        *
        * <code>
        * $db->add("Hello");
        * $db->add("Hello");
        * $db->save();
        * </code>
        *
        * @access public
        * @since Version: 1.2
        */
    public function add($value){
    	if($this->debug){$this->print_debug('<font color=red><b>START:</b></font> Adding new entry...');}
     	if($this->is_read==false)$this->file_read();
     	$uid = $this->uid();
     	if($uid!==false){
     		$result = $this->set($uid, $value);
     	}
     	if($this->debug){
    		if($result){
    			$this->print_debug('<font color=red><b>END:</b></font> Adding new entry SUCCESSFUL');
    		}else{
    			$this->print_debug('<font color=red><b>END:</b></font> Adding new entry FAILED.');
    		}
    	}
    	return $result;
    }
    //=====================================================================//
    //  METHOD: add                                                        //
    //========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: clear                                                      //
    //=====================================================================//
       /**
        * Removes all of the keys from this KeyStore. The KeyStore will be
        * empty after this call returns.
        * @result void
        * @version 2.0
        */
    function clear(){
        unset($this->elements);
        $this->elements = array();
        $this->is_saved=false;
    }
    //=====================================================================//
    //  METHOD: clear                                                      //
    //========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: create                                                     //
    //=====================================================================//
         /**
          * Creates a KeyStore with the specified username and password.
          *
          * Creates a KeyStore with the specified username and password
          * and opens it for use.
          * <code>
          * $keystore->create();
          * </code>
          * @access public
          * @return boolean true on success, false otherwise
          */
     function create(){
     	if(file_exists($this->database_path)==false){
     		if($this->debug){$this->print_debug('<font color=red><b>START:</b></font> Creating database <b>"'.$this->database_path.'"</b>...');}
     		$this->is_saved = false;
     		$this->save();
     		$exists = (file_exists($this->database_path)&&is_dir($this->database_path)==false);
     		if($exists){
     			if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> Creating database <b>"'.$this->database_path.'"</b> SUCCESSFUL.');}
     			return true;
     		}else{
     			if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> Creating database <b>"'.$this->database_path.'"</b> FAILED.');}
     			return false;
     		}
     	}else{
     		if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> The specified database <b>"'.$this->database_path.'"</b> already exists.');}
			return false;
     	}
     }
    //=====================================================================//
    //  METHOD: create                                                     //
    //========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: get                                                        //
    //=====================================================================//
    function get($key){
    	if($this->debug){$this->print_debug('<font color=red><b>START:</b></font> Getting key <b>"'.$key.'"</b>...');}
     	if($this->is_read==false)$this->file_read();
     	$result = (array_key_exists($key,$this->elements)===false)?NULL:$this->elements[$key];
     	if($this->debug){
     		if($result!==NULL){
     			$this->print_debug('<font color=red><b>END:</b></font> Getting key <b>"'.$key.'"</b> SUCCESSFUL');
     		}else{
     			$this->print_debug('<font color=red><b>END:</b></font> Getting key <b>"'.$key.'"</b> FAILED');
     		}
     	}
     	return $result;
    }
    //=====================================================================//
    //  METHOD: get                                                        //
    //========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: key_exists                                                 //
    //=====================================================================//
       /**
        * Checks, if a key exists in this KeyStore.
        * @return bool true, if key exists, false, otherwise
        * @version 2.0
        * @tested true
        */
    function key_exists($key){
    	if($this->debug){$this->print_debug('<font color=red><b>START:</b></font> Checking, if key <b>"'.$key.'"</b> exists...');}
     	if($this->is_read==false)$this->file_read();
     	$result = (isset($this->elements[$key]))?true:false;
     	if ($result==true) {
     		if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> Key <b>"'.$key.'"</b> EXISTS...');}
     	} else {
     		if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> Key <b>"'.$key.'"</b> DOES NOT exist...');}
     	}
        return $result;
    }
    //=====================================================================//
    //  METHOD: key_exists                                                 //
    //========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: keys                                                       //
    //=====================================================================//
    function keys(){
    	if($this->debug){$this->print_debug('<font color=red><b>START:</b></font> Getting keys...');}
     	if($this->is_read==false)$this->file_read();
     	if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> Getting keys SUCCESSFUL');}
    	return array_keys($this->elements);
    }
    //=====================================================================//
    //  METHOD: keys                                                       //
    //========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: next_id                                                    //
    //=====================================================================//
       /**
        * Generates a next in line numeric ID, that is not in the store
        *
        * This method is used to consecutive IDs, that can be used as
        * a key for a new entry in the Store.
        * @access public
        * @since Version: 1.8
        */
    public function next_id(){
    	if($this->debug){$this->print_debug('<font color=red><b>START:</b></font> Generating next ID for database <b>"'.$this->database_path.'"</b> ...');}
    	$keys = $this->keys();
    	rsort($keys,SORT_NUMERIC);
    	$next_id = (count($keys)>0)?$keys[0]+1:1;
    	if($this->key_exists($next_id)){
    		for($i=0;$i<1000;$i++){
    			$next_id = $next_id + 1;
    			if($this->key_exists($next_id)==false){
    				if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> Generating next ID SUCCESS.');}
    				return $next_id;
    			}
    		}
    	} else {
    		if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> Generating next ID SUCCESS.');}
    		return $next_id;
    	}
    	if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> Generating next ID FAILED.');}
    	return false;
    }
    //=====================================================================//
    //  METHOD: next_id                                                    //
    //========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: remove                                                     //
    //=====================================================================//
    function remove($key){
    	if($this->debug){$this->print_debug('<font color=red><b>START:</b></font> Removing key <b>"'.$key.'"</b>...');}
    	if($this->is_read==false)$this->read();
    	if(array_key_exists($key,$this->elements)===false){
    		if($this->debug)$this->print_debug('<font color=red><b>END:</b></font> Removing <b>"'.$key.'"</b> FAILED. Key DOES NOT Exist!');
    		return false;
    	}
    	unset($this->elements[$key]);
    	$this->is_saved=false;

    	$result = (isset($this->elements[$key]))?true:false;
        if($this->debug){
    		if($result==false){
    			$this->print_debug('<font color=red><b>END:</b></font> Removing key  <b>"'.$key.'"</b> SUCCESSFUL');
    		}else{
    			$this->print_debug('<font color=red><b>END:</b></font> Removing <b>"'.$key.'"</b> FAILED.');
    		}
    	}
    	return !$result;
    }
    //=====================================================================//
    //  METHOD: remove                                                     //
    //========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: set                                                        //
    //=====================================================================//
    function set($key,$value){
    	if($this->debug){$this->print_debug('<font color=red><b>START:</b></font> Setting key <b>"'.$key.'"</b>...');}
    	if(preg_match("/[^a-z0-9_]/", $key)){
    		throw new InvalidArgumentException("Only lowercase letters and numbers allowed as keys in File_Key_Value_Store. <b>".$key."</b> NOT ACCEPTED as valid key!");
    	}
     	if($this->is_read==false)$this->file_read();
    	$this->elements[$key]=$value;
    	$this->is_saved=false;
    	if($this->debug){
    		if(isset($this->elements[$key])&&$this->elements[$key]==$value){
    			$this->print_debug('<font color=red><b>END:</b></font> Setting key  <b>"'.$key.'"</b> SUCCESSFUL');
    		}else{
    			$this->print_debug('<font color=red><b>END:</b></font> Setting <b>"'.$key.'"</b> FAILED.');
    		}
    	}
    	return isset($this->elements[$key]);
    }
    //=====================================================================//
    //  METHOD: set                                                        //
    //========================== END OF METHOD ============================//

	//========================= START OF METHOD ===========================//
    //  METHOD: debug                                                      //
    //=====================================================================//
        /** The debug method provides easy possibility to debug the ongoing
         * database operations at any desired stage.
         * <code>
         * // Starting the debug
         * $database->debug(true);
         *
         * $database->exists(); // Code to be debugged
         *
         * // Stopping the debug
         * $database->debug(false);
         * </code>
         * @param boolean true, to start debugging, false, to stop
         * @return void
         * @access public
         */
     public function debug($debug){
     	if(is_bool($debug)==false){trigger_error('Class <b>'.get_class($this).'</b> in method <b>debug($debug)</b>: The Parameter $debug MUST BE Of Type Boolean - <b>'.gettype($debug).'</b> Given!',E_USER_ERROR);}
     	$this->debug = $debug;
     	return $this;
     }
     //=====================================================================//
     //  METHOD: debug                                                      //
     //========================== END OF METHOD ============================//

     //========================= START OF METHOD ===========================//
     //  METHOD: drop                                                       //
     //=====================================================================//
        /** Deletes the KeyStore file from the system.
         * <code>
         * $keystore->drop();
         * </code>
         * @return boolean true on success, false otherwise
         * @access public
         */
     public function drop(){
     	if($this->debug){$this->print_debug('<font color=red><b>START:</b></font> Dropping database <b>"'.$this->database_path.'"</b>...');}
     	if(@unlink($this->database_path)){
     		if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> Database <b>"'.$this->database_path.'"</b> was SUCCESSFULLY dropped.');}
     		return true;
     	}else{
     		if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> Database <b>"'.$this->database_path.'"</b> COULD NOT be dropped.');}
     		return false;
     	}
     }
     //=====================================================================//
     //  METHOD: drop                                                       //
     //========================== END OF METHOD ============================//

     //========================= START OF METHOD ===========================//
     //  METHOD: exists                                                     //
     //=====================================================================//
     public function exists(){
     	if($this->debug){$this->print_debug('<font color=red><b>START:</b></font> Checking, if database <b>"'.$this->database_path.'"</b> exists...');}
     	$exists = (file_exists($this->database_path)&&is_dir($this->database_path)==false);
     	if($exists==true){
     		if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> The database <b>"'.$this->database_path.'"</b> EXISTS.');}
     		return true;
     	}else{
     		if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> The database <b>"'.$this->database_path.'"</b> DOES NOT EXIST.');}
     		return false;
     	}
     }
     //=====================================================================//
     //  METHOD: exists                                                     //
     //========================== END OF METHOD ============================//

     //========================= START OF METHOD ===========================//
     //  METHOD: save                                                       //
     //=====================================================================//
        /**
         * Saves the KeyStore to the disk.
         *
         * This method is used to update and write down the KeyStore
         * to the file. Without saving the KeyStore all the changes
         * to the KeyStore will be lost on closing the browser window,
         * or redirecting the browser to another page.
         * @access public
         * @since Version: 1.0
         */
     function save(){
     	if($this->debug){$this->print_debug('<font color=red><b>START:</b></font> Saving database <b>"'.$this->database_path.'"</b> ...');}
        if($this->is_saved == true){
        	if($this->debug){
        		$this->print_debug('<font color=red> - FileStore is already saved.');
        	}
        	if($this->debug){
        		$this->print_debug('<font color=red><b>END:</b></font> Saving SUCCESSFUL.');
        	}
        	return true;
        }
     	$content = var_export($this->elements,true);
     	if($this->password != ''){
     		$enc = md5($this->username)."\n";
     		$enc .= md5($this->password)."\n";
     		$enc .= $this->encode($content,$this->password.$this->username);
     		$content = $enc;
     	}
        if($this->compress == true){
     		$compressed = gzcompress($content, 9);
     		$content = $compressed;
     	}
     	if($this->file_write($this->database_path, "w+",  $content)===true){
     		$this->is_saved=true;
     		if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> Saving SUCCESSFUL.');}
     		return true;
     	}
     	if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> Saving FAILED.');}
     	return false;
     }
     //=====================================================================//
     //  METHOD: save                                                       //
     //========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: uid                                                        //
    //=====================================================================//
       /**
        * Generates a new UID, that is not in the store
        *
        * This method is used to generate a unique ID, that kan be used as
        * a key in the for a new entry in the Store.
        * @access public
        * @since Version: 1.2
        */
    public function uid(){
    	if($this->debug){$this->print_debug('<font color=red><b>START:</b></font> Generating new unique ID for database <b>"'.$this->database_path.'"</b> ...');}
    	for($i=0;$i<1000;$i++){
    		$uid = strtolower(uniqid(true));
    		if($this->key_exists($uid)==false){
    			if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> Generating new unique ID SUCCESS.');}
    			return $uid;
    		}
    	}
    	// More random uid ?
        for($i=0;$i<1000;$i++){
    		$uid = strtolower(uniqid('',true));
    		if($this->key_exists($uid)==false){
    			if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> Generating new unique ID SUCCESS.');}
    			return $uid;
    		}
    	}
    	if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> Generating new unique ID FAILED.');}
    	return false;
    }
    //=====================================================================//
    //  METHOD: uid                                                        //
    //========================== END OF METHOD ============================//

    //======================= START OF DESTRUCTOR =========================//
    //  DESTRUCTOR: __destruct                                             //
    //=====================================================================//
	function __destruct(){
		if( $this->saveonexit==true && $this->is_saved == false){
		    $exists = (file_exists($this->database_path)&&is_dir($this->database_path)==false);
		    if($exists)$this->save();
		}
	}
	//=====================================================================//
    //  DESTRUCTOR: __destruct                                             //
    //======================== END OF DESTRUCTOR ==========================//

	//========================================================//
    // PRIVATE METHODS                                        //
    //========================================================//

    //========================= START OF METHOD ===========================//
    //  METHOD: file_write                                                 //
    //=====================================================================//
    private function file_write($filename, $flag, $content) {
    	if(file_exists($filename)) {
    		if(!is_writable($filename)) {
    			if(!chmod($filename, 0666)) {
    				echo "<br /><b>ERROR:</b> File IS NOT WRITABLE and mode COULD NOT BE CHANGED!"; exit;
     			}
     		}
     	}
     	ignore_user_abort(true);
     	if (!$fp = @fopen($filename, $flag)) { echo "Cannot open file ($filename)";exit; }
     	if(!flock($fp, LOCK_EX)){ echo "Cannot lock file ($filename)";exit; };
     	if(($result = fwrite($fp, $content))===false){ echo "Cannot write to file ($filename)";exit; }
     	if(!flock($fp, LOCK_UN)){ echo "Cannot unlock file ($filename)";exit; };
     	if (!fclose($fp)) { echo "Cannot close file ($filename)";exit; }
     	ignore_user_abort(false);
     	return ($result===false)?false:true;
    }
    //=====================================================================//
    //  METHOD: file_write                                                 //
    //========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: file_read                                                  //
    //=====================================================================//
     private function file_read() {
     	if(file_exists($this->database_path)){
     		if(is_readable($this->database_path)){
     			$text = file_get_contents($this->database_path);
     			// Uncompress
     			if($this->compress == true){
     				$text = gzuncompress($text);
     			}
     			// Unmake
     			if($this->password!=''){
     				$lines = explode("\n",$text);
     				$user=$lines[0];  $pass=$lines[1];
         			if(md5($this->username)!=$user){echo("<b>ERROR:</b> Username or password wrong ...");exit();}
     				if(md5($this->password)!=$pass){echo("<b>ERROR:</b> Username or password wrong ...");exit();}
     				unset($lines[0]);unset($lines[1]);
     				$text = implode("\n",$lines);
//     				$lines = file($this->database_path); // Reads the file into an array with the new lines still attached.
//     				//Remove any new line chars
//     				$user=implode("",explode("\r\n",$lines[0]));
//     				$user=implode("",explode("\n",$user));
//     				$pass=implode("",explode("\r\n",$lines[1]));
//     				$pass=implode("",explode("\n",$pass));
//     				if(md5($this->username)!=$user){echo("<b>ERROR:</b> Username or password wrong ...");exit();}
//     				if(md5($this->password)!=$pass){echo("<b>ERROR:</b> Username or password wrong ...");exit();}
//     				$text='';for($i=2;$i<count($lines);$i++){$text=$text . $lines[$i];}
     				$text = $this->decode($text, $this->password.$this->username);
     			}
     			eval('$this->elements='.(empty($text)?'array();':$text.';'));

     			$this->is_read = true;
     		}else{
     			$this->is_read=false;
     			echo "<br /><b>ERROR:</b> The database file IS NOT READABLE!<br />"; exit();
     		}
     	}else{
     		$this->is_read = false;
     		echo "<br /><b>ERROR:</b> The database file DOESN'T EXIST!<br />"; exit();
     	}
     	return $this->is_read;
     }
    //=====================================================================//
    //  METHOD: file_read                                                  //
    //========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: print_debug                                                //
    //=====================================================================//
       /**
        * Prints the debug message to the screen or to a supplied file path. If
        * the file doesn't exist it is created.
        * @param String the message to be printed
        * @return void
        */
    protected function print_debug($msg){
    	echo "<span style='font-weight:bold;color:red;'>DEBUG:</span> ".$msg."<br />";
    }
    //=====================================================================//
    //  METHOD: print_debug                                                //
    //========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: encode                                                     //
    //=====================================================================//
       /**
        * @param String
        * @param String
        * @return String
        */
    protected function encode($string,$key){
    	return $string;
    }
    //=====================================================================//
    //  METHOD: encode                                                     //
    //========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: decode                                                     //
    //=====================================================================//
       /**
        * @param String
        * @param String
        * @return String
        */
    protected function decode($string,$key){
    	return $string;
    }
    //=====================================================================//
    //  METHOD: encode                                                     //
    //========================== END OF METHOD ============================//
}
//========================================================================//
// CLASS: File_Store                                                      //
//===================  END OF CLASS  =====================================//

//=================== START OF CLASS =====================================//
// CLASS: Folder_Store                                                     //
//========================================================================//
class Folder_Store implements Key_Value_Store{
	/**
	 * The path to the database folder
	 * @var String
	 */
	private $database_path;
	/**
	 * Username for accessing the database
	 * @var String
	 */
	private $username;
	/**
	 * Password for accessing the database
	 * @var String
	 */
	private $password;
	/**
	 * The debug output visibility
	 * @var Boolean
	 */
	private $debug=false;

	//========================= START OF METHOD ===========================//
	//  METHOD: __construct                                                //
	//=====================================================================//
	   /**
	    * The default constructor
	    *
	    * This is the default constructor for working with the store.
	    * It must be always called first, because it initializes the
	    * database and reads it.
	    * @param String $filepath The path to the database file
	    * @param String $options The username,password,saveonexit
	    * @since Version 1.0
	    */
	function __construct($filepath,$options=array()){
		$this->database_path=trim($filepath);
		$this->username=(isset($options['username'])==false)?'':trim($options['username']);
		$this->password=(isset($options['password'])==false)?'':trim($options['password']);
		$this->database_path = str_replace("\\",DIRECTORY_SEPARATOR,str_replace("/",DIRECTORY_SEPARATOR,$this->database_path));
	}
	//=====================================================================//
	//  METHOD: __construct                                                //
	//========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: add                                                        //
    //=====================================================================//
       /**
        * Adds a new entry to the store with randomly generated unique ID
        *
        * This method is used to add a new entry to the store. The entry will
        * be auromaticly assigned a randomly generated unique ID.
        *
        * <code>
        * $db->add("Hello");
        * $db->add("Hello");
        * $db->save();
        * </code>
        *
        * @access public
        * @since Version: 1.2
        */
    public function add($value){
    	if($this->debug){$this->print_debug('<font color=red><b>START:</b></font> Adding new entry...');}
     	$uid = $this->uid();
     	if($uid!==false){
     		$result = $this->set($uid, $value);
     	}
     	if($this->debug){
    		if($result){
    			$this->print_debug('<font color=red><b>END:</b></font> Adding new entry SUCCESSFUL');
    		}else{
    			$this->print_debug('<font color=red><b>END:</b></font> Adding new entry FAILED.');
    		}
    	}
    	return $result;
    }
    //=====================================================================//
    //  METHOD: add                                                        //
    //========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: clear                                                      //
    //=====================================================================//
       /**
        * Removes all of the keys from this KeyStore. The KeyStore will be
        * empty after this call returns.
        * @result void
        * @version 2.0
        */
	function clear(){
		$key_files = $this->list_files($this->database_path);
		foreach($key_files as $key_file){
			if(@unlink($this->database_path.DIRECTORY_SEPARATOR.$key_file)===true){
				if($this->debug){$this->print_debug('&nbsp;&nbsp;- Key file <b>"'.$key_file.'"</b> deleted...');}
			}else{
				if($this->debug){$this->print_debug('&nbsp;&nbsp;- Key file <b>"'.$key_file.'"</b> could not be dropped...');}
			}
		}
	}
    //=====================================================================//
    //  METHOD: clear                                                      //
    //========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: create                                                     //
    //=====================================================================//
         /**
          * Creates a KeyStore with the specified username and password.
          *
          * Creates a KeyStore with the specified username and password
          * and opens it for use.
          * <code>
          * $keystore->create();
          * </code>
          * @access public
          * @return boolean true on success, false otherwise
          */
     function create(){
     	if(file_exists($this->database_path)===true&&is_dir($this->database_path)==true){
     		if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> The specified database <b>"'.$this->database_path.'"</b> already exists.');}
     		return false;
     	}else{
     		// Creating the directory containing the database
     		if(mkdir($this->database_path)){
     			if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> Creating database <b>"'.$this->database_path.'"</b> SUCCESSFUL.');}
     			return true;
     		}else{
     			// The Text Database could not be created
     			if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> Creating database <b>"'.$this->database_path.'"</b> FAILED.');}
     			return false;
     		}
     	}
    }
	//=====================================================================//
    //  METHOD: create                                                     //
    //========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: get                                                        //
    //=====================================================================//
    function get($key){
    	if($this->debug){$this->print_debug('<font color=red><b>START:</b></font> Getting key <b>"'.$key.'"</b>...');}
     	$result = (file_exists($this->database_path.DIRECTORY_SEPARATOR.$key)===false)?NULL:file_get_contents($this->database_path.DIRECTORY_SEPARATOR.$key);
     	if($result!=NULL){
     		eval('$result='.(empty($result)?'':$result.';'));
     	}
    	if($this->debug){
     		if($result!==NULL){
     			$this->print_debug('<font color=red><b>END:</b></font> Getting key <b>"'.$key.'"</b> SUCCESSFUL');
     		}else{
     			$this->print_debug('<font color=red><b>END:</b></font> Getting key <b>"'.$key.'"</b> FAILED');
     		}
     	}
     	return $result;
    }
    //=====================================================================//
    //  METHOD: get                                                        //
    //========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: key_exists                                                 //
    //=====================================================================//
       /**
        * Checks, if a key exists in this KeyStore.
        * @return bool true, if key exists, false, otherwise
        * @version 2.0
        * @tested true
        */
    function key_exists($key){
        //return (isset($this->elements[$key]))?true:false;
    }
    //=====================================================================//
    //  METHOD: key_exists                                                 //
    //========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: keys                                                       //
    //=====================================================================//
    function keys(){
    	return $this->list_files($this->database_path);
    }
    //=====================================================================//
    //  METHOD: keys                                                       //
    //========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: next_id                                                    //
    //=====================================================================//
       /**
        * Generates a next in line numeric ID, that is not in the store
        *
        * This method is used to consecutive IDs, that can be used as
        * a key for a new entry in the Store.
        * @access public
        * @since Version: 1.8
        */
    public function next_id(){
    	if($this->debug){$this->print_debug('<font color=red><b>START:</b></font> Generating next ID for database <b>"'.$this->database_path.'"</b> ...');}
    	$keys = $this->keys();
    	rsort($keys,SORT_NUMERIC);
    	$next_id = (count($keys)>0)?(int)$keys[0]+1:1;

    	if($this->key_exists($next_id)){
    		for($i=0;$i<1000;$i++){
    			$next_id = $next_id + 1;
    			if($this->key_exists($next_id)==false){
    				if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> Generating next ID SUCCESS.');}
    				return $next_id;
    			}
    		}
    	} else {
    		if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> Generating next ID SUCCESS.');}
    		return $next_id;
    	}
    	if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> Generating next ID FAILED.');}
    	return false;
    }
    //=====================================================================//
    //  METHOD: next_id                                                    //
    //========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: remove                                                     //
    //=====================================================================//
    function remove($key){
    	if($this->debug){$this->print_debug('<font color=red><b>START:</b></font> Removing key <b>"'.$key.'"</b>...');}
    	if(file_exists($this->database_path.DIRECTORY_SEPARATOR.$key)===false){
    		if($this->debug)$this->print_debug('<font color=red><b>END:</b></font> Removing <b>"'.$key.'"</b> FAILED. Key DOES NOT Exist!');
    		return false;
    	}
    	$result = (@unlink($this->database_path.DIRECTORY_SEPARATOR.$key)===true)?true:false;
        if($this->debug){
    		if($result){
    			$this->print_debug('<font color=red><b>END:</b></font> Removing key  <b>"'.$key.'"</b> SUCCESSFUL');
    		}else{
    			$this->print_debug('<font color=red><b>END:</b></font> Removing <b>"'.$key.'"</b> FAILED.');
    		}
    	}
    	return $result;
    }
    //=====================================================================//
    //  METHOD: remove                                                     //
    //========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: set                                                        //
    //=====================================================================//
    function set($key,$value){
    	if($this->debug){$this->print_debug('<font color=red><b>START:</b></font> Setting key <b>"'.$key.'"</b>...');}
        if(preg_match("/[^a-z0-9_]/", $key)){
    		throw new InvalidArgumentException("Only lowercase letters and numbers allowed as keys in Folder_Key_Value_Store. <b>".$key."</b> NOT ACCEPTED as valid key!");
    	}
    	$value = var_export($value,true);
    	$result = ($this->file_write($this->database_path.DIRECTORY_SEPARATOR.$key, "w+", $value)===FALSE)?false:true;
    	if($this->debug){
    		if($result){
    			$this->print_debug('<font color=red><b>END:</b></font> Setting key  <b>"'.$key.'"</b> SUCCESSFUL');
    		}else{
    			$this->print_debug('<font color=red><b>END:</b></font> Setting <b>"'.$key.'"</b> FAILED.');
    		}
    	}
    	return $result;
    }
    //=====================================================================//
    //  METHOD: set                                                        //
    //========================== END OF METHOD ============================//

	//========================= START OF METHOD ===========================//
    //  METHOD: debug                                                      //
    //=====================================================================//
        /** The debug method provides easy possibility to debug the ongoing
         * database operations at any desired stage.
         * <code>
         * // Starting the debug
         * $database->debug(true);
         *
         * $database->exists(); // Code to be debugged
         *
         * // Stopping the debug
         * $database->debug(false);
         * </code>
         * @param boolean true, to start debugging, false, to stop
         * @return void
         * @access public
         */
    public function debug($debug){
    	if(is_bool($debug)==false){trigger_error('Class <b>'.get_class($this).'</b> in method <b>debug($debug)</b>: The Parameter $debug MUST BE Of Type Boolean - <b>'.gettype($debug).'</b> Given!',E_USER_ERROR);}
    	$this->debug = $debug;
    	return $this;
    }
    //=====================================================================//
    //  METHOD: debug                                                      //
    //========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: drop                                                       //
    //=====================================================================//
        /** Deletes the KeyStore file from the system.
         * <code>
         * $keystore->drop();
         * </code>
         * @return boolean true on success, false otherwise
         * @access public
         */
    public function drop(){
    	if($this->debug){$this->print_debug('<font color=red><b>START:</b></font> Dropping database <b>"'.$this->database_path.'"</b>...');}
    	if(file_exists($this->database_path)===true&&is_dir($this->database_path)==true){
    		$key_files = $this->list_files($this->database_path);
    		foreach($key_files as $key_file){
    			if(@unlink($this->database_path.DIRECTORY_SEPARATOR.$key_file)===true){
    				if($this->debug){$this->print_debug('&nbsp;&nbsp;- Key file <b>"'.$key_file.'"</b> deleted...');}
    			}else{
    				if($this->debug){$this->print_debug('&nbsp;&nbsp;- Key file <b>"'.$key_file.'"</b> could not be dropped...');}
    			}
    		}
    		if(@rmdir($this->database_path)){
    			if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> The database was SUCCESSFULLY dropped.');}
    			return true;
    		}else{
    			if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> The database COULD NOT be dropped.');}
    			return false;
    		}
    	}else{
    		if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> The database DOES NOT exist.');}
    		return false;
    	}
    }
    //=====================================================================//
    //  METHOD: drop                                                       //
    //========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: exists                                                     //
    //=====================================================================//
    public function exists(){
    	if($this->debug){$this->print_debug('<font color=red><b>START:</b></font> Checking, if database <b>"'.$this->database_path.'"</b> exists...');}
    	$exists = (file_exists($this->database_path)&&is_dir($this->database_path)==false);
    	if($exists==true){
    		if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> The database <b>"'.$this->database_path.'"</b> EXISTS.');}
    		return true;
    	}else{
    		if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> The database <b>"'.$this->database_path.'"</b> DOES NOT EXIST.');}
    		return false;
    	}
    }
    //=====================================================================//
    //  METHOD: exists                                                     //
    //========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: uid                                                        //
    //=====================================================================//
       /**
        * Generates a new UID, that is not in the store
        *
        * This method is used to generate a unique ID, that kan be used as
        * a key in the for a new entry in the Store.
        * @access public
        * @since Version: 1.2
        */
    public function uid(){
    	if($this->debug){$this->print_debug('<font color=red><b>START:</b></font> Generating new unique ID for database <b>"'.$this->database_path.'"</b> ...');}
    	for($i=0;$i<1000;$i++){
    		$uid = strtolower(uniqid(true));
    		if($this->key_exists($uid)==false){
    			if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> Generating new unique ID SUCCESS.');}
    			return $uid;
    		}
    	}
    	// More random uid ?
        for($i=0;$i<1000;$i++){
    		$uid = strtolower(uniqid('',true));
    		if($this->key_exists($uid)==false){
    			if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> Generating new unique ID SUCCESS.');}
    			return $uid;
    		}
    	}
    	if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> Generating new unique ID FAILED.');}
    	return false;
    }
    //=====================================================================//
    //  METHOD: uid                                                        //
    //========================== END OF METHOD ============================//

    //======================= START OF DESTRUCTOR =========================//
    //  DESTRUCTOR: __destruct                                             //
    //=====================================================================//
	function __destruct(){}
	//=====================================================================//
    //  DESTRUCTOR: __destruct                                             //
    //======================== END OF DESTRUCTOR ==========================//

	 //========================================================//
     // PRIVATE METHODS                                        //
     //========================================================//

    //========================= START OF METHOD ===========================//
    //  METHOD: file_write                                                 //
    //=====================================================================//
    private function file_write($filename, $flag, $content) {
    	if(file_exists($filename)) {
    		if(!is_writable($filename)) {
    			if(!chmod($filename, 0666)) {
    				echo "<br /><b>ERROR:</b> File IS NOT WRITABLE and mode COULD NOT BE CHANGED!"; exit;
     			}
     		}
     	}
     	ignore_user_abort(true);
     	if (!$fp = @fopen($filename, $flag)) { echo "Cannot open file ($filename)";exit; }
     	if(!flock($fp, LOCK_EX)){ echo "Cannot lock file ($filename)";exit; };
     	if(($result = fwrite($fp, $content))===false){ echo "Cannot write to file ($filename)";exit; }
     	if(!flock($fp, LOCK_UN)){ echo "Cannot unlock file ($filename)";exit; };
     	if (!fclose($fp)) { echo "Cannot close file ($filename)";exit; }
     	ignore_user_abort(false);
     	return ($result===false)?false:true;
    }
    //=====================================================================//
    //  METHOD: file_write                                                 //
    //========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: list_files                                                 //
    //=====================================================================//
       /** Lists all the files in a specified directory.
         * @return Array the files in a directory
         * @access private
         */
	private function list_files($directory){
		$files = array();
		$handler = opendir($directory);
		while ($file = readdir($handler)) {
			if ($file != '.' && $file != '..'){
				if(is_file($directory.DIRECTORY_SEPARATOR.$file)){$files[] = $file;}
			}
		}
		closedir($handler);
		return $files;
	}
	//=====================================================================//
    //  METHOD: list_files                                                 //
    //========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: print_debug                                                //
    //=====================================================================//
       /**
        * Prints the debug message to the screen or to a supplied file path. If
        * the file doesn't exist it is created.
        * @param String the message to be printed
        * @return void
        */
    protected function print_debug($msg){
    	echo "<span style='font-weight:bold;color:red;'>DEBUG:</span> ".$msg."<br />";
    }
    //=====================================================================//
    //  METHOD: print_debug                                                //
    //========================== END OF METHOD ============================//
}
//========================================================================//
// CLASS: Folder_Store                                                    //
//===================  END OF CLASS  =====================================//

//=================== START OF CLASS =====================================//
// CLASS: Tree_Store                                                      //
//========================================================================//
class Tree_Store{
	/**
	 * Contains all the Elements in the database
	 * @var Array
	 */
	private $store = array('keys'=>array(),'values'=>array(),'parent'=>array());
	/**
	 * The path to the store file
	 * @var String
	 */
	private $database_path;
	/**
	 * Username for accessing the store
	 * @var String
	 */
	private $username;
	/**
	 * Password for accessing the store
	 * @var String
	 */
	private $password;
	/**
	 * The debug output visibility
	 * @var Boolean
	 */
	private $debug=false;
	/**
	 * Is the database file already read
	 * @var Boolean
	 */
	private $is_read=false;
	/**
	 * Is the database saved
	 * @var Boolean
	 */
	private $is_saved = true;

	//========================= START OF METHOD ===========================//
	//  METHOD: __construct                                                //
	//=====================================================================//
	   /**
	    * The default constructor
	    *
	    * This is the default constructor for working with file store.
	    * It must be always called first, because it initializes the
	    * database and reads it.
	    * @param String $filepath The path to the database file
	    * @param String $options The username,password,saveonexit
	    * @since Version 1.0
	    */
	function __construct($filepath,$options=array()){
		$this->database_path=trim($filepath);
		$this->username=(isset($options['username'])==false)?'':trim($options['username']);
		$this->password=(isset($options['password'])==false)?'':trim($options['password']);
		$this->saveonexit=(isset($options['saveonexit'])==false)?true:$options['saveonexit'];
		$this->database_path = str_replace("\\",DIRECTORY_SEPARATOR,str_replace("/",DIRECTORY_SEPARATOR,$this->database_path));
	}
	//=====================================================================//
	//  METHOD: __construct                                                //
	//========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: clear                                                      //
    //=====================================================================//
       /**
        * Removes all of the keys from this KeyStore. The KeyStore will be
        * empty after this call returns.
        * @result void
        * @version 2.0
        */
    function clear(){
        unset($this->store);
        $this->store = array('keys'=>array(),'values'=>array(),'parent'=>array(),'children'=>array());
        $this->is_saved=false;
    }
    //=====================================================================//
    //  METHOD: clear                                                      //
    //========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: create                                                     //
    //=====================================================================//
         /**
          * Creates a KeyStore with the specified username and password.
          *
          * Creates a KeyStore with the specified username and password
          * and opens it for use.
          * <code>
          * $keystore->create();
          * </code>
          * @access public
          * @return boolean true on success, false otherwise
          */
     function create(){
     	if(file_exists($this->database_path)==false){
     		if($this->debug){$this->print_debug('<font color=red><b>START:</b></font> Creating database <b>"'.$this->database_path.'"</b>...');}
     		$this->is_saved = false;
     		$this->save();
     		$exists = (file_exists($this->database_path)&&is_dir($this->database_path)==false);
     		if($exists){
     			if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> Creating database <b>"'.$this->database_path.'"</b> SUCCESSFUL.');}
     			return true;
     		}else{
     			if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> Creating database <b>"'.$this->database_path.'"</b> FAILED.');}
     			return false;
     		}
     	}else{
     		if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> The specified database <b>"'.$this->database_path.'"</b> already exists.');}
			return false;
     	}
     }
	//=====================================================================//
    //  METHOD: create                                                     //
    //========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: get                                                        //
    //=====================================================================//
    function get($key){
    	if($this->debug){$this->print_debug('<font color=red><b>START:</b></font> Getting key <b>"'.$key.'"</b>...');}
     	if($this->is_read==false)$this->file_read();
     	$key_position = array_search($key, $this->store['keys'],true);
     	$result = ($key_position===false)?false:true;
     	if($this->debug){
     		if($result===true){
     			$this->print_debug('<font color=red><b>END:</b></font> Getting key <b>"'.$key.'"</b> SUCCESSFUL');
     		}else{
     			$this->print_debug('<font color=red><b>END:</b></font> Getting key <b>"'.$key.'"</b> FAILED');
     		}
     	}
        if($result===false) {
     		throw new RuntimeException("Key <b>".$key."</b> DOES NOT exist in the Tree_Key_Value_Store");
     	}
     	return $this->store['values'][$key_position];
    }
    //=====================================================================//
    //  METHOD: get                                                        //
    //========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: parent                                                     //
    //=====================================================================//
    /**
     * Gets the parent key of the specified key
     * @param string|int $key
     * @return string|int the parent key
     * @throws RuntimeException
     */
    function parent($key){
    	if($this->debug){$this->print_debug('<font color=red><b>START:</b></font> Getting parent of key <b>"'.$key.'"</b>...');}
     	if($this->is_read==false)$this->file_read();
     	$key_position = array_search($key, $this->store['keys'],true);
     	$result = ($key_position===false)?false:true;
     	if($this->debug){
     		if($result===true){
     			$this->print_debug('<font color=red><b>END:</b></font> Getting parent of key <b>"'.$key.'"</b> SUCCESSFUL');
     		}else{
     			$this->print_debug('<font color=red><b>END:</b></font> Getting parent of key <b>"'.$key.'"</b> FAILED');
     		}
     	}
        if($result===false) {
     		throw new RuntimeException("Key <b>".$key."</b> DOES NOT exist in the Tree_Key_Value_Store");
     	}
     	return $this->store['parent'][$key_position];
    }
    //=====================================================================//
    //  METHOD: parent                                                     //
    //========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: path                                                       //
    //=====================================================================//
    /**
     * Returns the path to the specified key
     * @param string|int $key
     * @return array
     */
    function path($key){
    	if($this->debug){$this->print_debug('<font color=red><b>START:</b></font> Getting path of key <b>"'.$key.'"</b>...');}
     	if($this->is_read==false)$this->file_read();
     	$path = array($key);
     	$parent_key = $this->parent($key);
     	if($this->key_exists($parent_key)) {
     		$path = array_merge($this->path($parent_key),$path);
     	}
     	if($this->debug){
     		$this->print_debug('<font color=red><b>END:</b></font> Getting path of key <b>"'.$key.'"</b> SUCCESSFUL');
     	}
     	return $path;
    }
    //=====================================================================//
    //  METHOD: path                                                       //
    //========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: traverse                                                   //
    //=====================================================================//
    /**
     * Traverses the specified element and returns all the children with
     * all their children traversed.
     * @param type $key
     * @return type
     */
    function traverse($key){
    	if($this->debug){$this->print_debug('<font color=red><b>START:</b></font> Traversing key <b>"'.$key.'"</b>...');}
     	if($this->is_read==false)$this->file_read();
     	$traversed = array();
     	$traversed[] = $key;
     	$children = $this->children($key);
        foreach($children as $child_key){
	    $traversed=array_merge($traversed,$this->traverse($child_key));
	}
     	if($this->debug){
     	    $this->print_debug('<font color=red><b>END:</b></font> Traversing key <b>"'.$key.'"</b> SUCCESSFUL');
     	}
     	return $traversed;
    }
    //=====================================================================//
    //  METHOD: traverse                                                   //
    //========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: children                                                   //
    //=====================================================================//
    /**
     * Gets all the children of the specified key
     * @param string|int $key
     * @return string|int
     */
    function children($key){
    	if($this->debug){$this->print_debug('<font color=red><b>START:</b></font> Getting children of key <b>"'.$key.'"</b>...');}
     	if($this->is_read==false)$this->file_read();
//     	$key_position = array_search($key, $this->store['keys'],true);
//     	$result = ($key_position===false)?false:true;
     	$children = array();
     	$children_keys = array_keys($this->store['parent'],$key,true);
     	foreach($children_keys as $child_key){
     		$children[] = $this->store['keys'][$child_key];
     	}
     	if($this->debug){
     		$this->print_debug('<font color=red><b>END:</b></font> Getting children of key <b>"'.$key.'"</b> SUCCESSFUL');
   		}
     	return $children;
    }
    //=====================================================================//
    //  METHOD: parent                                                     //
    //========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: key_exists                                                 //
    //=====================================================================//
       /**
        * Checks, if a key exists in this KeyStore.
        * @return bool true, if key exists, false, otherwise
        * @version 2.0
        * @tested true
        */
    function key_exists($key){
    	if($this->debug){$this->print_debug('<font color=red><b>START:</b></font> Checking, if key <b>"'.$key.'"</b> exists...');}
     	if($this->is_read==false)$this->file_read();
     	$result = (array_search($key, $this->store['keys'],true)!==false)?true:false;
     	if ($result==true) {
     		if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> Key <b>"'.$key.'"</b> EXISTS...');}
     	} else {
     		if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> Key <b>"'.$key.'"</b> DOES NOT exist...');}
     	}
        return $result;
    }
    //=====================================================================//
    //  METHOD: key_exists                                                 //
    //========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: keys                                                       //
    //=====================================================================//
    function keys(){
    	if($this->debug){$this->print_debug('<font color=red><b>START:</b></font> Getting keys...');}
     	if($this->is_read==false)$this->file_read();
     	if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> Getting keys SUCCESSFUL');}
    	return $this->store['keys'];
    }
    //=====================================================================//
    //  METHOD: keys                                                       //
    //========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: move_down                                                  //
    //=====================================================================//
    function move_down($key){
    	if($this->debug){$this->print_debug('<font color=red><b>START:</b></font> Moving key down<b>"'.$key.'"</b>...');}
    	if($this->is_read==false)$this->file_read();
    	$parent_key = $this->parent($key);
    	$parent_children = $this->children($parent_key);
    	$current_position = array_search($key, $parent_children, true);
    	if($current_position==(count($parent_children)-1)){
    		if($this->debug)$this->print_debug('<font color=red><b>END:</b></font> Moving key down<b>"'.$key.'"</b> FAILED. Key IS ALREADY last!');
    	    return false;
    	}
    	$next_key = $parent_children[$current_position+1];

    	$current_key_position = array_search($key, $this->store['keys'], true);
    	$next_key_position = array_search($next_key, $this->store['keys'], true);
    	$current_value = $this->store['values'][$current_key_position];
    	$next_value = $this->store['values'][$next_key_position];
    	// START: Swap
    	$this->store['keys'][$current_key_position] = $next_key;
    	$this->store['keys'][$next_key_position] = $key;
    	$this->store['values'][$current_key_position] = $next_value;
    	$this->store['values'][$next_key_position] = $current_value;
    	// END: Swap

    	$this->is_saved=false;

    	if($this->debug)$this->print_debug('<font color=red><b>END:</b></font> Moving key down<b>"'.$key.'"</b> SUCCESS.');
    	return true;
    }
    //=====================================================================//
    //  METHOD: move_down                                                  //
    //========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: move_up                                                    //
    //=====================================================================//
    function move_up($key){
    	if($this->debug){$this->print_debug('<font color=red><b>START:</b></font> Moving key up<b>"'.$key.'"</b>...');}
    	if($this->is_read==false)$this->file_read();
    	$parent_key = $this->parent($key);
    	$parent_children = $this->children($parent_key);
    	$current_position = array_search($key, $parent_children, true);
    	if($current_position==0){
    		if($this->debug)$this->print_debug('<font color=red><b>END:</b></font> Moving key up<b>"'.$key.'"</b> FAILED. Key IS ALREADY first!');
    	    return false;
    	}
    	$previous_key = $parent_children[$current_position-1];

    	$current_key_position = array_search($key, $this->store['keys'], true);
    	$previous_key_position = array_search($previous_key, $this->store['keys'], true);
    	$current_value = $this->store['values'][$current_key_position];
    	$previous_value = $this->store['values'][$previous_key_position];
    	// START: Swap
    	$this->store['keys'][$current_key_position] = $previous_key;
    	$this->store['keys'][$previous_key_position] = $key;
    	$this->store['values'][$current_key_position] = $previous_value;
    	$this->store['values'][$previous_key_position] = $current_value;
    	// END: Swap

    	$this->is_saved=false;

    	if($this->debug)$this->print_debug('<font color=red><b>END:</b></font> Moving key up<b>"'.$key.'"</b> SUCCESS.');
    	return true;
    }
    //=====================================================================//
    //  METHOD: move_up                                                    //
    //========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: remove                                                     //
    //=====================================================================//
    function remove($key){
    	if($this->debug){$this->print_debug('<font color=red><b>START:</b></font> Removing key <b>"'.$key.'"</b>...');}
    	if($this->is_read==false)$this->file_read();
    	$key_position = array_search($key, $this->store['keys'], true);
    	if($key_position===false){
    		if($this->debug)$this->print_debug('<font color=red><b>END:</b></font> Removing <b>"'.$key.'"</b> FAILED. Key DOES NOT Exist!');
    		return false;
    	}
    	// START: Remove children
    	if($this->debug)$this->print_debug('<font color=red><b>START:</b></font> Removing children of key <b>"'.$key.'"</b>...');
    	$children = $this->children($key);
    	foreach($children as $child_key){
    		$this->remove($child_key);
    	}
    	if($this->debug)$this->print_debug('<font color=red><b>END:</b></font> Removing children of key <b>"'.$key.'"</b>.');
    	// END: Remove children
    	unset($this->store['keys'][$key_position]);
    	unset($this->store['values'][$key_position]);
    	unset($this->store['parent'][$key_position]);

    	$this->is_saved=false;

    	$result = (array_search($key, $this->store['keys'],true)!==false)?true:false;
        if($this->debug){
    		if($result==false){
    			$this->print_debug('<font color=red><b>END:</b></font> Removing key  <b>"'.$key.'"</b> SUCCESSFUL');
    		}else{
    			$this->print_debug('<font color=red><b>END:</b></font> Removing key <b>"'.$key.'"</b> FAILED.');
    		}
    	}
    	return !$result;
    }
    //=====================================================================//
    //  METHOD: remove                                                     //
    //========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: set                                                        //
    //=====================================================================//
    /**
     * Sets a key as child to a parent key
     * @param string|int $key
     * @param string|int $parent_key
     * @param string $value
     * @return bool
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    function set($key,$parent_key,$value){
    	if($this->debug){$this->print_debug('<font color=red><b>START:</b></font> Setting key <b>"'.$key.'"</b>...');}
        if(func_num_args()<3){
        	$msg = 'Method <b>set($key, $parent_key, $value)</b> in class Three_Key_Value_Store requires three argiments. Only '.func_num_args().' given';
    		throw new RuntimeException($msg);
    	}
    	if(preg_match("/[^a-z0-9_]/", $key)){
    		throw new InvalidArgumentException("Only lowercase letters and numbers allowed as keys in Three_Key_Value_Store. <b>".$key."</b> NOT ACCEPTED as valid key!");
    	}
        if(preg_match("/[^a-z0-9_]/", $key)){
    		throw new InvalidArgumentException("Only lowercase letters and numbers allowed as parent keys in Three_Key_Value_Store. <b>".$parent_key."</b> NOT ACCEPTED as valid key!");
    	}
     	if($this->is_read==false)$this->file_read();
     	if($this->key_exists($key) == false){
     	    $this->store['keys'][]=$key;
     	}
    	$key_position = array_search($key, $this->store['keys'], true);
    	$this->store['parent'][$key_position]=$parent_key;
    	$this->store['values'][$key_position]=$value;

    	$this->is_saved=false;
    	if($this->debug){
    		if($key_position!==false){
    			$this->print_debug('<font color=red><b>END:</b></font> Setting key  <b>"'.$key.'"</b> SUCCESSFUL');
    		}else{
    			$this->print_debug('<font color=red><b>END:</b></font> Setting <b>"'.$key.'"</b> FAILED.');
    		}
    	}
    	return ($key_position===false)?false:true;
    }
    //=====================================================================//
    //  METHOD: set                                                        //
    //========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: debug                                                      //
    //=====================================================================//
        /** The debug method provides easy possibility to debug the ongoing
         * database operations at any desired stage.
         * <code>
         * // Starting the debug
         * $database->debug(true);
         *
         * $database->exists(); // Code to be debugged
         *
         * // Stopping the debug
         * $database->debug(false);
         * </code>
         * @param boolean true, to start debugging, false, to stop
         * @return void
         * @access public
         */
     public function debug($debug){
     	if(is_bool($debug)==false){trigger_error('Class <b>'.get_class($this).'</b> in method <b>debug($debug)</b>: The Parameter $debug MUST BE Of Type Boolean - <b>'.gettype($debug).'</b> Given!',E_USER_ERROR);}
     	$this->debug = $debug;
     	return $this;
     }
     //=====================================================================//
     //  METHOD: debug                                                      //
     //========================== END OF METHOD ============================//

     //========================= START OF METHOD ===========================//
     //  METHOD: drop                                                       //
     //=====================================================================//
        /** Deletes the KeyStore file from the system.
         * <code>
         * $keystore->drop();
         * </code>
         * @return boolean true on success, false otherwise
         * @access public
         */
     public function drop(){
     	if($this->debug){$this->print_debug('<font color=red><b>START:</b></font> Dropping database <b>"'.$this->database_path.'"</b>...');}
     	if(@unlink($this->database_path)){
     		if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> Database <b>"'.$this->database_path.'"</b> was SUCCESSFULLY dropped.');}
     		return true;
     	}else{
     		if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> Database <b>"'.$this->database_path.'"</b> COULD NOT be dropped.');}
     		return false;
     	}
     }
     //=====================================================================//
     //  METHOD: drop                                                       //
     //========================== END OF METHOD ============================//

     //========================= START OF METHOD ===========================//
     //  METHOD: exists                                                     //
     //=====================================================================//
     public function exists(){
     	if($this->debug){$this->print_debug('<font color=red><b>START:</b></font> Checking, if database <b>"'.$this->database_path.'"</b> exists...');}
     	$exists = (file_exists($this->database_path)&&is_dir($this->database_path)==false);
     	if($exists==true){
     		if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> The database <b>"'.$this->database_path.'"</b> EXISTS.');}
     		return true;
     	}else{
     		if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> The database <b>"'.$this->database_path.'"</b> DOES NOT EXIST.');}
     		return false;
     	}
     }
     //=====================================================================//
     //  METHOD: exists                                                     //
     //========================== END OF METHOD ============================//

     //========================= START OF METHOD ===========================//
     //  METHOD: save                                                       //
     //=====================================================================//
        /**
         * Saves the KeyStore to the disk.
         *
         * This method is used to update and write down the KeyStore
         * to the file. Without saving the KeyStore all the changes
         * to the KeyStore will be lost on closing the browser window,
         * or redirecting the browser to another page.
         * @access public
         * @since Version: 1.0
         */
     function save(){
     	if($this->debug){$this->print_debug('<font color=red><b>START:</b></font> Saving database <b>"'.$this->database_path.'"</b> ...');}
        if($this->is_saved == true){
        	if($this->debug){
        		$this->print_debug('<font color=red> - FileStore is already saved.');
        	}
        	if($this->debug){
        		$this->print_debug('<font color=red><b>END:</b></font> Saving SUCCESSFUL.');
        	}
        	return;
        }
     	$content = var_export($this->store,true);
     	if($this->password != ''){
     		$enc = md5($this->username)."\n";
     		$enc .= md5($this->password)."\n";
     		$enc .= $this->encode($content,$this->password.$this->username);
     		$content = $enc;
     	}
     	if($this->file_write($this->database_path, "w+",  $content)===true){
     		$this->is_saved=true;
     		if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> Saving SUCCESSFUL.');}
     		return true;
     	}
     	if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> Saving FAILED.');}
     	return false;
     }
     //=====================================================================//
     //  METHOD: save                                                       //
     //========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: uid                                                        //
    //=====================================================================//
       /**
        * Generates a new UID, that is not in the store
        *
        * This method is used to generate a unique ID, that kan be used as
        * a key in the for a new entry in the Store.
        * @access public
        * @since Version: 1.2
        */
    public function uid(){
    	if($this->debug){$this->print_debug('<font color=red><b>START:</b></font> Generating new unique ID for database <b>"'.$this->database_path.'"</b> ...');}
    	for($i=0;$i<1000;$i++){
    		$uid = strtolower(uniqid(true));
    		if($this->key_exists($uid)==false){
    			if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> Generating new unique ID SUCCESS.');}
    			return $uid;
    		}
    	}
    	// More random uid ?
        for($i=0;$i<1000;$i++){
    		$uid = strtolower(uniqid('',true));
    		if($this->key_exists($uid)==false){
    			if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> Generating new unique ID SUCCESS.');}
    			return $uid;
    		}
    	}
    	if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> Generating new unique ID FAILED.');}
    	return false;
    }
    //=====================================================================//
    //  METHOD: uid                                                        //
    //========================== END OF METHOD ============================//


    //========================= START OF METHOD ===========================//
    //  METHOD: next_id                                                    //
    //=====================================================================//
       /**
        * Generates a next in line numeric ID, that is not in the store
        *
        * This method is used to consecutive IDs, that can be used as
        * a key for a new entry in the Store.
        * @access public
        * @since Version: 1.8
        */
    public function next_id(){
    	if($this->debug){$this->print_debug('<font color=red><b>START:</b></font> Generating next ID for database <b>"'.$this->database_path.'"</b> ...');}
    	$keys = $this->store['keys'];
    	rsort($keys,SORT_NUMERIC);
    	$next_id = (count($keys)>0)?$keys[0]+1:1;

    	if($this->key_exists($next_id)){
    		for($i=0;$i<1000;$i++){
    			$next_id = $next_id + 1;
    			if($this->key_exists($next_id)==false){
    				if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> Generating next ID SUCCESS.');}
    				return $next_id;
    			}
    		}
    	} else {
    		if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> Generating next ID SUCCESS.');}
    		return $next_id;
    	}
    	if($this->debug){$this->print_debug('<font color=red><b>END:</b></font> Generating next ID FAILED.');}
    	return false;
    }
    //=====================================================================//
    //  METHOD: next_id                                                    //
    //========================== END OF METHOD ============================//

    //======================= START OF DESTRUCTOR =========================//
    //  DESTRUCTOR: __destruct                                             //
    //=====================================================================//
	function __destruct(){
		if( $this->saveonexit==true && $this->is_saved == false){
		    $exists = (file_exists($this->database_path)&&is_dir($this->database_path)==false);
		    if($exists)$this->save();
		}
	}
	//=====================================================================//
    //  DESTRUCTOR: __destruct                                             //
    //======================== END OF DESTRUCTOR ==========================//

	//========================================================//
    // PRIVATE METHODS                                        //
    //========================================================//

    //========================= START OF METHOD ===========================//
    //  METHOD: file_write                                                 //
    //=====================================================================//
    private function file_write($filename, $flag, $content) {
    	if(file_exists($filename)) {
    		if(!is_writable($filename)) {
    			if(!chmod($filename, 0666)) {
    				echo "<br /><b>ERROR:</b> File IS NOT WRITABLE and mode COULD NOT BE CHANGED!"; exit;
     			}
     		}
     	}
     	ignore_user_abort(true);
     	if (!$fp = @fopen($filename, $flag)) { echo "Cannot open file ($filename)";exit; }
     	if(!flock($fp, LOCK_EX)){ echo "Cannot lock file ($filename)";exit; };
     	if(($result = fwrite($fp, $content))===false){ echo "Cannot write to file ($filename)";exit; }
     	if(!flock($fp, LOCK_UN)){ echo "Cannot unlock file ($filename)";exit; };
     	if (!fclose($fp)) { echo "Cannot close file ($filename)";exit; }
     	ignore_user_abort(false);
     	return ($result===false)?false:true;
    }
    //=====================================================================//
    //  METHOD: file_write                                                 //
    //========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: file_read                                                  //
    //=====================================================================//
     private function file_read() {
     	if(file_exists($this->database_path)){
     		if(is_readable($this->database_path)){
     			if($this->password!=''){
     				$lines = file($this->database_path); // Reads the file into an array with the new lines still attached.
     				//Remove any new line chars
     				$user=implode("",explode("\r\n",$lines[0]));
     				$user=implode("",explode("\n",$user));
     				$pass=implode("",explode("\r\n",$lines[1]));
     				$pass=implode("",explode("\n",$pass));
     				if(md5($this->username)!=$user){echo("<b>ERROR:</b> Username or password wrong ...");exit();}
     				if(md5($this->password)!=$pass){echo("<b>ERROR:</b> Username or password wrong ...");exit();}
     				$text='';for($i=2;$i<count($lines);$i++){$text=$text . $lines[$i];}
     				$text = $this->decode($text, $this->password.$this->username);
     			}else{
     				$text = file_get_contents($this->database_path);
     			}
     			eval('$this->store='.(empty($text)?"array('keys'=>array(),'values'=>array(),'parent'=>array());":$text.';'));

     			$this->is_read = true;
     		}else{
     			$this->is_read=false;
     			echo "<br /><b>ERROR:</b> The database file IS NOT READABLE!<br />"; exit();
     		}
     	}else{
     		$this->is_read = false;
     		echo "<br /><b>ERROR:</b> The database file DOESN'T EXIST!<br />"; exit();
     	}
     	return $this->is_read;
     }
    //=====================================================================//
    //  METHOD: file_read                                                  //
    //========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: print_debug                                                //
    //=====================================================================//
       /**
        * Prints the debug message to the screen or to a supplied file path. If
        * the file doesn't exist it is created.
        * @param String the message to be printed
        * @return void
        */
    protected function print_debug($msg){
    	echo "<span style='font-weight:bold;color:red;'>DEBUG:</span> ".$msg."<br />";
    }
    //=====================================================================//
    //  METHOD: print_debug                                                //
    //========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: encode                                                     //
    //=====================================================================//
       /**
        * @param String
        * @param String
        * @return String
        */
    protected function encode($string,$key){
    	return $string;
    }
    //=====================================================================//
    //  METHOD: encode                                                     //
    //========================== END OF METHOD ============================//

    //========================= START OF METHOD ===========================//
    //  METHOD: decode                                                     //
    //=====================================================================//
       /**
        * @param String
        * @param String
        * @return String
        */
    protected function decode($string,$key){
    	return $string;
    }
    //=====================================================================//
    //  METHOD: encode                                                     //
    //========================== END OF METHOD ============================//
}
//========================================================================//
// CLASS: Tree_Store                                                      //
//===================  END OF CLASS  =====================================//
?>
