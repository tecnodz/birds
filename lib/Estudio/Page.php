<?php
/**
 * E-studio Content Managemt System: page
 *
 * PHP version 5.3
 *
 * @category  Estudio
 * @package   Birds
 * @author    Guilherme Capilé, Tecnodesign <ti@tecnodz.com>
 * @copyright 2014 Tecnodesign
 * @license   not defined
 * @link      https://tecnodz.com/
 */
namespace Estudio;
class Page extends \Birds\Model
{
    public static $schemaid='estudio_page';
    protected $id, $url, $language, $title, $formats, $script, $stylesheet, $multiviews, $created, $modified, $published, $EstudioContent;


    public static function match($url, $exception=true)
    {
        if($url[0]!='/') $url = '/'.$url;
        $f=array('where'=>array('url'=>$url,'published<'=>date('Y-m-d\TH:i:s')));
        $P = static::find($f);
        if($i=$P->count()) {
            return $P->fetch(0);
        } else {
            unset($P);
            $f['where']['multiview']=1;
            while(strlen($url)>1) {
                $url = substr($url, 0, strrpos($url, '/'));
                if(!$url) $url='/';
                $f['where']['url']=$url;
                $P = static::find($f);
                if($P->count()) break;
                unset($P);
            }
            if(isset($P)) {
                return $P->fetch(0);
            }
        }
        if($exception) {
            throw new \Birds\App\HttpException(404);
        } else {
            return false;
        }
    }

    public function getFormats($format='text/html')
    {
        if($this->formats) {
            return preg_split('/\s*[,:;]+\s*/', $this->formats, null, PREG_SPLIT_NO_EMPTY);
        } else {
            return array($format);
        }
    }

    public function getUid()
    {
        return (int) $this->id;
    }

    public function getOptions()
    {
        $o=array();
        $l=array('multiviews','shell');
        foreach($l as $n) {
            if(isset($this->$n)) {
                $o[$n] = (bool) $this->$n;
            }
            unset($n);
        }
        unset($l);
        if(!$o) $o=null;
        return $o;
    }

    public function getMeta()
    {
        $o=array();
        $l=array('title','script','stylesheet','@language','@modified','@published');
        foreach($l as $n) {
            $k=$n;
            if($n[0]=='@') $n=substr($n,1);
            if(isset($this->$n)) {
                $o[$k] = $this->$n;
            }
            unset($n, $k);
        }
        unset($l);
        if(!$o) $o=null;
        return $o;
    }

    public function getContent()
    {
        $C = $this->relation('EstudioContent')->fetch();
        //$C = $this->relation('EstudioContent')->select(\EstudioContent::scope('route'))->fetchArray();
        $r=array();
        foreach($C as $i=>$c) {
            if(!$c['slot']) $c['slot'] = 'body';
            $r[$c['slot']][] = $c->asArray('route');
            unset($C[$i], $i, $c);
        }
        unset($C);
        return $r;
    }

    public function render($format='text/html')
    {
        if(!is_dir($d=BIRD_SITE_ROOT.'/data/e-studio')) $d = \bird::app()->Birds['routes-dir'][0];
        $f = $d.$this->url.'.yml';
        $a = $this->asArray('route');
        if(!$a['formats']) $a['formats']=$this->getFormats();

        if(!file_exists($f) || filemtime($f)<strtotime($this->modified))
            \Birds\Yaml::save($f, $a);
        unset($f);
        \Birds\App\Route::create($a)->render($format);
    }

    public static function redirect()
    {
        \bird::debug(__METHOD__, func_get_args());
    }

}