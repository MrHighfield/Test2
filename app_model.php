<?php
class AppModel extends Model {

	var $autoIncrement = true;
	var $autoIncrementImplementation = false;
    protected static $_user;


    public static function getCurrentUser(){
        if(is_null(self::$_user)){
            App::Import('model', 'Employee');
            $oEmployee = new Employee();
            $aData = $oEmployee->find('first', array('conditions' => array('logincode' => $_SESSION['user']['aselectuid'])));
            self::$_user = $aData['Employee'];
        }
        return self::$_user;
    }
    
    function beforeSave($data){
        // Setup PK
        $new = false;
        if($this->autoIncrementImplementation && !isset($this->data[$this->name][$this->primaryKey])){
            $new = true;
            if(($primaryKey = $this->getNewPrimaryKey()) !== false){
                $this->data[$this->name][$this->primaryKey] = $primaryKey;
            }
        }
        if($new && isset($this->_schema['creator']) && isset($this->_schema['modifier'])){
            //Implement creator and modifier
           /**
			 * @todo asfasdasd
			 */
        }
        return true;	   
	}

	function afterSave(){
		if(isset($this->data[$this->name][$this->primaryKey.'_new']) && $this->data[$this->name][$this->primaryKey.'_new'] != $this->data[$this->name][$this->primaryKey]){
			if(!$this->query('UPDATE '.$this->useTable.' SET ['.$this->primaryKey.'] = \''.$this->data[$this->name][$this->primaryKey.'_new'].'\' WHERE ['.$this->primaryKey.'] = \''.$this->data[$this->name][$this->primaryKey].'\'')){
				return false;
			}
		} 
		return true;
	}

	function getNewPrimaryKey(){
	   $item = $this->find('first', array('order' => array($this->name.'.'.$this->primaryKey => 'DESC'), 'callbacks'=>false));
	   if(is_array($item[$this->name])){
           return $item[$this->name][$this->primaryKey] + 1;
	   }else{
	       return 1;
	   }
	}

	function uniquePrimaryKey(){
		if(isset($this->data[$this->name][$this->primaryKey.'_new']) && !empty($this->data[$this->name][$this->primaryKey.'_new']) && $this->data[$this->name][$this->primaryKey.'_new'] != $this->data[$this->name][$this->primaryKey]){
			$cond[$this->primaryKey] = $this->data[$this->name][$this->primaryKey.'_new'];
		}
		if(!isset($this->data[$this->name][$this->primaryKey.'_new'])){
			$cond[$this->primaryKey] = $this->data[$this->name][$this->primaryKey];
		}
		if(isset($cond)){
			if($this->find('count', array('conditions' => $cond)) > 0)
				return false;
		}
		return true;
	}

	function afterFind($results, $primary){
		if(isset($this->_schema[$this->primaryKey])){	
			if($this->_schema[$this->primaryKey]['type'] == 'string' || !$this->autoIncrement){
				for($i = 0;$i < count($results);$i++){
					if(isset($results[$i][$this->name])){
						if(isset($results[$i][$this->name][$this->primaryKey])){
							$results[$i][$this->name][$this->primaryKey.'_safe'] = bin2hex($results[$i][$this->name][$this->primaryKey]);
						}
					}
				}
			}
		}
		return $results;
	}

	function beforeFind($queryData){
		if(isset($this->_schema[$this->primaryKey])){	
			if($this->_schema[$this->primaryKey]['type'] == 'string' || !$this->autoIncrement){
				if(isset($queryData['conditions'])){
					$conditions = array();
					foreach($queryData['conditions'] as $name => $value){
						$conditions[$name] = $value;
					}
					$queryData['conditions'] = $conditions;
				}
			}
		}
		return $queryData;
	}

    /**
     *
     * Wrapper for the Cake DB Wrapper. Encode the selected data from the database to UTF8.
     * Note that the READ function uses the FIND to get its resource.
     * @return array
     */
    function find($conditions = null, $fields = array(), $order = null, $recursive = null) {
        $aData = parent::find($conditions, $fields, $order, $recursive);
        $aDecodedData = $this->encode($aData);
        return $aDecodedData;
    }

    /**
     *
     * Decode the data array to a latin-1 format. This way the data can be stored correctly
     * in the website its Database.
     * @return /
     */
    function save($data = null, $validate = true, $fieldList = array()){
        $aEncodedData = $this->decode($data);
        return parent::save($aEncodedData, $validate, $fieldList);
    }

    /**
     * Encode the LATIN1_GENERAL_UC to a UTF8 characterset.
     * Function will do automatic callback on a array
     *
     * @param string/array $Input
     * @return string
     */
    public function encode($Input){
        if(!is_array($Input)){
            return utf8_encode($Input);
        }
        return array_map(array('AppModel','encode'), $Input);
    }

    /**
     * Decode the UTF8 to a LATIN1_GENERAL_UC characterset.
     * Function will do automatic callback on a array
     *
     * @param string/array $Input
     * @return string
     */
    public function decode($Input){
        if(!is_array($Input)){
            return utf8_decode($Input);
        }
        return array_map(array('AppModel','decode'), $Input);
    }

    public function isUnique($field, $value, $id)
    {
        if (empty($id)){
            // add
            $condition = $this->name.".".$field." = '".$value."'";
        }
        else{
            // edit
            $conditions = '(('.$this->name.".".$field." = '".$value."') AND (".$this->name.".".$this->primaryKey." <> ".$id."))";
        }
        $this->recursive = -1;
        $res = $this->hasAny($conditions);
        if(empty($res) || is_null($res)){
            return true;
        }
        else{
            $this->invalidate('unique_'.$field);
            return false;
        }
   }

}