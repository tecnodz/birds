<?php
/**
 * Model
 *
 * PHP version 5.3
 *
 * @category  Model
 * @package   Birds
 * @author    Guilherme Capilé, Tecnodesign <ti@tecnodz.com>
 * @copyright 2013 Tecnodesign
 * @license   http://creativecommons.org/licenses/by/3.0  CC BY 3.0
 * @version   SVN: $Id$
 * @link      http://tecnodz.com/
 */

/**
 * Model
 *
 * @category  Model
 * @package   Birds
 * @author    Guilherme Capilé, Tecnodesign <ti@tecnodz.com>
 * @copyright 2013 Tecnodesign
 * @license   http://creativecommons.org/licenses/by/3.0  CC BY 3.0
 * @link      http://tecnodz.com/
 */
namespace Birds;
class Model extends Data
{
    private $_new, $_del, $_scope;

    public function __toString()
    {
        return $this->render('text/plain');
    }

    public static function catalog($format='text/html', $scope=null)
    {
        $cn = get_called_class();
        $o = Schema::load($cn, false, 'active-data');

        if(!$o) $o = array();
        if(!is_null($scope)) $o['scope'] = $scope;
        return implode('', $cn::find($o)->fetch());
    }

    public function render($format='text/html', $scope=null)
    {
        $s = '<dl>';
        if(is_null($scope)) $scope = $this->_scope;
        foreach($this->scope($scope) as $k) {
            $s .= '<dt>'.$this->label($k).'</dt>'
                . '<dd>'.((method_exists($this, $m='render'.bird::camelize($k)))?($this->$m($format)):(bird::xml($this->$k))).'</dd>';
        }
        $s .= '</dl>';
        return $s;
    }

    public static function create($o=array())
    {
        $cn = get_called_class();
        return new $cn($o);
    }

    public static function find($o=array())
    {
        return Data::handler(get_called_class())->find($o);
    }

    public static function label($fn)
    {
        return $fn;
    } 

    public function isNew($new=null)
    {
        if($this->_del) return false;
        else if(!is_null($new)) {
            $this->_new = $new;
            return $this;
        } else if(is_null($this->_new)) {
            $this->_new = (implode('', $this->asArray('primary'))!=='')?(false):(true);
        }
        return $this->_new;
    }

    public function delete($save=false, $trans=null, $relDepth=2)
    {
        $this->_del = true;
        if($save) {
            return $this->save($trans, $relDepth);
        }
        return $this;
    }

    public function save($trans=null, $relDepth=2)
    {
        if(!$this->event('before-save')) return false;
        $cn = get_called_class();
        $sc = Schema::load($cn);
        $insert = $this->isNew();
        try {
            $H = Data::handler($sc);
            // start transaction
            if($trans!==false) $trans = $H->transaction();

            // send data to update to handler
            if($this->_del) {
                $H->delete($this);
            } else if($insert) {
                $H->insert($this);
            } else {
                $H->update($this);
            }
            // lower $relDepth--;
            if(!$this->_del && $relDepth) {
                $relDepth--;
                // save relations
                foreach($sc->relations as $rn=>$ro) {
                    if(isset($this->$rn) && $this->$rn) {
                        if(is_array($this->$rn)) {
                            foreach($this->$rn as $i=>$roo) {
                                $this->$rn[$i]->save(false, $relDepth);
                                unset($i, $roo);
                            }
                        } else {
                            $this->$rn->save(false, $relDepth);
                        }
                    }
                    unset($rn, $ro);
                }
            }
            // commit transaction
            if($trans!==false) $H->commit($trans, true);

        } catch(Exception $e) {
            \bird::debug(__METHOD__, $e->getMessage());
            // rollback transaction
            if($trans!==false) $H->rollback($trans, false);
            return false;
        }
        return true;
    }

    public function event($e)
    {
        $E = $this->schema('events');
        if(!$E || !isset($E[$e])) return true;
        foreach($E[$e] as $m=>$c) {
            if(!isset($c['method'])) $c['method'] = $m;
            $c += array('class'=>$this, 'prepare'=>true);
            $c = App\Content::create($c);
            $r = ($c->content!==false)?(true):(false);
            unset($c->class, $c);
            if(!$r) break;
        }
        return $r;
    }

    public function timestamp()
    {
        list($u, $t) = explode('.', (string) BIRD_TIME);
        $t = date('Y-m-d\TH:i:s', BIRD_TIME).substr(fmod(BIRD_TIME,1),1,7);
        foreach(func_get_args() as $k) {
            $this->$k = $t;
        }
    }

    public function relation($r)
    {
        if(!isset($this->$r)) {
            $this->$r = Data::handler(get_called_class())->getRelation($r, null, array($this->asArray('primary')));
        }
        return $this->$r;
    }

    public function asArray($scope='')
    {
        $cn = get_called_class();
        $r = array();
        foreach($cn::scope($scope) as $c) {
            $r[$c] = $this->$c;
            unset($c);
        }
        return $r;
    }

    public function asJson($scope='')
    {
        return json_encode($this->asArray($scope));
    }

    public function schema($prop=null)
    {
        return Schema::load(get_called_class(), false, $prop);
    }

    public static function scope($scope='')
    {
        return Schema::load(get_called_class())->getScope($scope);
    }

    public function getScope()
    {
        return $this->_scope;
    }

    public function setScope($scope)
    {
        $this->_scope = $scope;
    }
    /**
     * Magic getter. Searches for a get$Name method, or gets the stored value in
     * $_vars.
     *
     * @param string $name parameter name, should start with lowercase
     * 
     * @return mixed the stored value, or method results
     */
    public function  __get($name)
    {
        if (method_exists($this, $m='get'.bird::camelize($name, true))) {
            return $this->$m();
        } else if (isset($this->$name)) {
            return $this->$name;
        } else if(strpos($name, '.') || strstr('ABCDEFGHIJKLMNOPQRSTUVWXYZ!', substr($name, 0, 1))) {
            return $this->getRelation($name);
        }
        return null;
    }

    /**
     * Magic setter. Searches for a set$Name method, and stores the value in $_vars
     * for later use.
     *
     * @param string $name  parameter name, should start with lowercase
     * @param mixed  $value value to be set
     *
     * @return void
     */
    public function  __set($name, $value)
    {
        $this->$name = $value;
        return $this;
        bird::debug(__METHOD__, $name, $value);
        if($name=='ROWSTAT') return $this;
        $mn=bird::camelize($name, true);
        try {
            $schema = $this->schema();
            if (method_exists($this, $m='set'.$mn)) {
                $this->$m($value);
            } else {
                if (method_exists($this, $m='validate'.$mn)) {
                    $this->$name = $this->$m($value);
                } else if(isset($schema->columns[$name])) {
                    $this->$name = Validator::check($schema->columns[$name], $value);
                }
                $this->$name = $value;
            }
        } catch(Exception $e) {
            \bird::log('Could not validate '.get_called_class().'::'.$name.':', $value);
        }
        return $this;
    }

    /*
    public static function find($where=null, $limit=1, $scope=null, $collection=true, $orderBy=null, $groupBy=null)
    {
        $cn = get_called_class();
        $o = array();
        if($where) $o['where']=$where;
        if($limit) $o['limit']=$limit;
        // scope
        if($orderBy) $o['orderBy']=$orderBy;
        if($groupBy) $o['groupBy']=$groupBy;
        $q = Data::connect()->find($cn::$schemaid, $o);
        unset($o);
        if($q->count()==0) return false;
        else if($limit==1) return array_shift($q->fetch());
        else if(!$collection) return $q->fetch();
        else return $q;
    }
    */
}