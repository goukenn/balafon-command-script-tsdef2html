<?php

// @ convert type.d.ts file to html 
// @command: balafon --run .test/js/type.d.2.html/main.php /Volumes/Data/Dev/tmp/theme-project/node_modules/vite-plugin-pwa/dist/index.d.ts --no-color > /tmp/sites/index.html

use IGK\Helper\IO;
use IGK\Helper\StringUtility;
use IGK\System\Console\Html\HtmlColorizer;
use IGK\System\Console\Logger;
use IGK\System\Text\RegexMatcherContainer;
use IGK\System\Text\RegexMatcherUtility;

use function igk_create_node_arg as _node;

/**
 * tranform typescript declaratoin file to html 
 * @package 
 */
class  TypeScriptDefToHtmlConverter
{
    const INTERFACE_GLOBAL_FUNC_DEF = '@global-interface-func-def';
    /**
     * 
     * @var RegexMatcherContainer
     */
    private $m_container;
    /**
     * entry definition 
     * @var mixed
     */
    private $m_entry;
    public function __construct()
    {
        $this->m_container = $this->_createContainer();
        $this->m_entry = null;
    }



    /**
     * create global matcher container 
     */
    protected function _createContainer()
    {
        $match = new RegexMatcherContainer;
        $block =  $match->begin('\{', '\}', 'block')->last();
        $brank_block =  $match->begin('\(', '\)', 'block')->last();
        $generic_block =  $match->begin('<', '>', 'block')->last();
        $i = $match->begin('interface\\s+(?P<name>\\w+)\\s*([^\}].)?\{', '\}', 'interface')->last();
        $i->patterns = [
            $block
        ];
        $i = $match->begin('\\btype\\b\\s+(?P<name>\\w+)\\s*(:|=)', ';', 'type')->last();
        $i->patterns = [
            $block
        ];
        $i = $match->begin('\\bdeclare\\s+\\b(?P<type>const|function)\\b\\s+(?P<name>\\w+)\\s*', ';', 'declare-type')->last();
        $i->patterns = [
            $block,
            $brank_block,
            $generic_block
        ];
        return $match;
    }
    /**
     * convert value 
     * @param string $buffer 
     * @param mixed $visitor 
     * @param bool $render 
     * @return mixed 
     * @throws Exception 
     * @throws IGKException 
     */
    public function Convert(string $buffer, $visitor = null, $render = true)
    {
        $this->m_entry = null;
        $entry = &$this->m_entry;
        $entry = (object)[
            'source' => $buffer,
            'visitor' => $visitor
        ];
        $doc = igk_create_xmlnode('html');
        $doc->head()->title()->setContent('Documentation');
        $body = $doc->body();
        $list = [];
        $this->m_container->treat($buffer, function ($g, $pos, $data) use (&$entry, &$list) {
            $t = $g->tokenID;
            switch ($t) {

                default:
                    if (!empty($t) && method_exists($this, $fc = '_visit_' . StringUtility::FuncName($t))) {
                        $v_arg = (object)['entry' => $entry, 'g' => $g, 'pos' => $pos, 'data' => $data, 'list' => &$list];
                        call_user_func_array([$this, $fc], [$v_arg]);
                    }
                    break;
            }
        });

        $lm = [];
        foreach ($list as $t => $m) {
            $n = self::_GetNodeRef($lm, $t, $body);
            $n->h1()->Content = $t;
            $ul = $n->ul();
            ksort($m);
            foreach ($m as $k => $s) {
                $li = $ul->li();
                $li->dt()->setContent($k);
                if ($s) {
                    $dd = $li->dd();
                    $tn = igk_create_notagnode(); // html resolution node                 
                    if (is_string($s)) {
                        $vv = $this->treatDefinition($s);
                        $tn->span($vv);
                    } else {
                        $mul = $tn->ul();
                        uksort($s, 'strcasecmp');
                        $g = igk_getv($s, self::INTERFACE_GLOBAL_FUNC_DEF);
                        if ($g) {
                            $mul->li()->bind(
                                _node('span.global', '@global'),
                                _node('br'),
                                array_map(
                                    function ($i) {
                                        return _node('span.gdef.dispb', '- ' . $this->treatDefinition($i));
                                    },
                                    $g
                                )
                            );
                        }
                        unset($s[self::INTERFACE_GLOBAL_FUNC_DEF]);

                        foreach ($s as $kk => $vv) {
                            $vv = $this->treatDefinition($vv);
                            $mul->li()->bind(
                                _node('span.prop', $kk),
                                _node('b', ':'),
                                _node('span.type#info', $vv)
                            );
                        }
                    }
                    $dd->add($tn);
                }
            }
        }
        unset($entry);
        if ($render)
            return $doc->render((object)['Indent' => false]);
        return $doc;
    }
    public static function _GetNodeRef(&$list, $type, $parent, $tag = 'div')
    {
        if ($n = igk_getv($list, $type)) {
            return $n;
        }
        $n = $parent->add($tag);
        $list[$type] = $list;
        return $n;
    }
    /**
     * load interface declaration 
     * @param string $v 
     * @return array 
     * @throws IGKException 
     * @throws Exception 
     */
    static function _LoadInterfaceDeclarationProperties(string $v)
    {
        $container = new RegexMatcherContainer;
        $block = $container->begin("{", "}", 'block')->last();
        $comment = $container->begin("\/\*", "\*\/", 'comment')->last();
        $line_comment = $container->match("\/\/.+", 'line-comment')->last();
        $litteral = $container->begin("(\"|')", "\\1", 'litteral')->last();
        $sqare_braket = $container->begin("\[", "]", 'sqare-braket')->last();
        $braket = $container->begin("\(", "\)", 'braket')->last();

        $block->patterns = [
            $litteral,
            $comment,
            $line_comment,
            $sqare_braket,
            $braket,
            $block
        ];

        $def = [];
        $container->treat($v, function ($g, $pos, $data) use (&$def) {
            if (($g->tokenID != 'block') || $g->parentInfo) {

                if ($g->parentInfo) {
                    $tab = &$g->parentInfo->replace ?? [];
                    $tab[] = ['token' => $g->tokenID, $g->value];
                    $g->parentInfo->replace = $tab;
                }
                return;
            }
            // + | remove trailing block {}
            $v = trim(substr(substr($g->value, 1), 0, -1));


            list($sb, $tdef) = self::_RemoveDeclareFuncInterface($v);

            if ($tdef) {
                $def[self::INTERFACE_GLOBAL_FUNC_DEF] = $tdef;
            }
            $v = trim($sb);
            if (empty($v)) {
                return;
            }




            $container = new RegexMatcherContainer;
            $multicomment = $container->begin("\/\*", "\*\/", 'comment')->last();
            $line_comment = $container->match("\/\/.+", 'line-comment')->last();
            $subblock = $container->begin('{', '}', 'subblock')->last();
            $string =   $container->appendStringDetection()->last();
            // $func_block->patterns = [
            //     $subblock,
            //     $string
            // ];
            $subblock->patterns = [
                $string,
                $subblock
            ];
            // define func declaration 

            $func_block_declaration = $container->begin('(?P<name>\w+)\b\\s*\(', ';', 'func-item', 'func-block-params')->last();
            $declare_subblock = $container->begin('\(', '\)', 'declare-subblock')->last();

            $func_block_declaration->patterns = [
                $declare_subblock,
                $subblock,
                $string
            ];
            $declare_subblock->patterns = [
                $string,
                $subblock
            ];



            $properties = $container->begin("(?P<name>\w+(?:\?|\b))\\s*:", ";|(?=>\})", 'item', 'items')->last();

            $sblock = $container->begin("{", "}", 'surround-block', 'surround-block')->last();
            $sblock->patterns = [
                $line_comment,
                $multicomment,
                $sblock
            ];
            $properties->patterns = [
                $line_comment,
                $multicomment,
                $sblock
            ];
            $s = 0;
            while ($tg = $container->detect($v, $s)) {
                // skip surround block;
                $tg = $container->end($tg, $v, $s);
                if ($tg) {
                    switch ($tg->tokenID) {
                        case 'item':
                            $n = trim($tg->beginCaptures['name'][0]);
                            $cv = RegexMatcherUtility::RemoveComment($tg->value);
                            $def[$n] = trim(explode(':', $cv, 2)[1], StringUtility::DEFAULT_TRIM_CHAR . ';');
                            break;
                        case 'func-item':
                            $n = trim($tg->beginCaptures['name'][0]);
                            $pos = 0;
                            $fd  =  RegexMatcherUtility::ExtractFirst(
                                $tg->value,
                                RegexMatcherUtility::ParameterReference(),
                                $pos
                            );
                            $def[$n . $fd] = trim(explode(':', substr($tg->value, $pos), 2)[1], StringUtility::DEFAULT_TRIM_CHAR . ';');
                            break;
                        default:
                            // | not handling token  
                            break;
                    }
                }
            }
        });
        return $def;
    }
    /**
     * visiting interface 
     * @param mixed $e 
     * @return void 
     * @throws Exception 
     * @throws IGKException 
     */
    public function _visit_interface($e)
    {
        $k = 'interface';
        if (!isset($e->list[$k])) {
            $e->list[$k] = [];
        }
        $list = &$e->list[$k];
        $name = igk_getv($e->g->beginCaptures['name'], 0);
        $sb = $e->g->value;

        $def = self::_LoadInterfaceDeclarationProperties($sb, $e->entry);
        $list[$name] = $def;
    }
    protected static function _RemoveDeclareFuncInterface(string $sb)
    {
        $ctn = new RegexMatcherContainer;
        $start = $ctn->begin("(\\bnew\\b\\s*)?(?=\()", ";")->last();
        $string = $ctn->appendStringDetection()->last();
        $mcomment = $ctn->appendSingleLineComment()->last();
        $brank = $ctn->appendBrank()->last();

        $start->patterns = [
            $string,
            $mcomment,
            $brank 
        ];

        return RegexMatcherUtility::TreatByRemoveRootScopePattern($ctn, $sb);

       
    }

    protected function _visit_type($e)
    {
        $k = 'type';
        if (!isset($e->list[$k])) {
            $e->list[$k] = [];
        }
        $list = &$e->list[$k];
        $name = igk_getv($e->g->beginCaptures['name'], 0);
        $sb = $e->g->value;
        $tb = explode('=', $sb, 2);
        $sb = trim($tb[1], StringUtility::DEFAULT_TRIM_CHAR . ';');
        $list[$name] = $sb;
    }

    protected function _visit_declare_type($e)
    {
        $k = 'declare-type';
        if (!isset($e->list[$k])) {
            $e->list[$k] = [];
        }
        $list = &$e->list[$k];
        $name = igk_getv($e->g->beginCaptures['name'], 0);
        $type = igk_getv($e->g->beginCaptures['type'], 0);
        $sb = $e->g->value;
        //tb = explode('=', $sb, 2);
        $sb = trim($sb, StringUtility::DEFAULT_TRIM_CHAR . ';');
        $list[$name] = ['type' => $type, 'code' => $sb];
    }

    /**
     * 
     * @param mixed $d 
     * @return mixed 
     * @throws IGKException 
     * @throws Exception 
     */
    public function treatDefinition($d)
    {
        $visitor = $this->m_entry->visitor;
        if ($visitor) {
            $d = $visitor->treat($d);
        } else {
            $container = new RegexMatcherContainer;
            $container->appendStringDetection();
            $container->match("\b(new|else|if|then)\b", 'operator');
            $container->match("\b(string|number|Array|void|boolean|float|array|true|null|false|undefined|\w+)\b", 'number');
            $sb = '';
            $lpos = 0;
            $container->treat($d, function ($g, $pos, $data) use (&$sb, &$lpos) {
                // review 
                $sb .= htmlentities(substr($data, $lpos, $g->from - $lpos));
                switch ($g->tokenID) {
                    case 'string':
                        $sb .= _node('span.str.text-red-300', htmlentities($g->value))->render();
                        break;
                    case 'number':
                        $sb .= _node('span.number.text-green-300', htmlentities($g->value))->render();
                        break;
                    default:
                        $cl = igk_css_str2class_name($g->tokenID);
                        $sb .= _node('span.'.$cl.'.text', htmlentities($g->value))->render();
                        break;
                }
                $lpos =  $pos;
            });
            $sb .= htmlentities(substr($d, $lpos));

            return $sb;
        }
        return $d;
    }
}
function igk_html_node_listitem(array $list)
{
    $ul = igk_create_node('ul');
    while (count($list) > 0) {
        $q = array_shift($list);
        $ul->li()->setContent($q);
    }
    return $ul;
}
$file = igk_getv($params, 0) ?? igk_die('require file');
$buffer = (file_exists($file) ? file_get_contents($file) : null) ?? igk_die('missing file content');
$mimeType = IO::MimeTypeFromBuffer($buffer);
if ($mimeType != 'text/x-java') {
    return -1;
}
$no_color = property_exists($command->options, '--no-color');
$indent = property_exists($command->options, '--indent');
$outfile = igk_getv($command->options, '--output');
$title = igk_getv($command->options, '--title');


// $buffer = <<<JS
// interface BONDJE{
//     // x?: {h : '{string' };
//     y : number;
//     z : float
// }
// JS;


// $buffer = <<<JS
// interface VitePluginPWAAPI { 
//     /**
//      * Returns the PWA web manifest url for the manifest link:
//      * <link rel="manifest" href="<webManifestUrl>" />
//      *
//      * Will also return if the manifest will require credentials:
//      * <link rel="manifest" href="<webManifestUrl>" crossorigin="use-credentials" />
//      */
//     webManifestData(): WebManifestData | undefined;
//     inpo(x: string):action
//      }
// JS;
// $buffer = <<<JS
// interface VitePluginPWAAPI { 
//     integration?: {
//         /**
//          * The base url for the PWA assets.
//          *
//          * @default `vite.base`
//          */
//         baseUrl?: string;
//     }
// }
// JS;

$buffer = <<<JS
declare const defaultInjectManifestVitePlugins: string[];
type info = 'igkdev' | 'recal';
type rascal = 'basi';
declare function vitePWA(userOptions?: Partial<VitePWAOptions>): Plugin[];
JS;
$buffer = <<<JS
interface ILitterDraw{
    (x: number):void;
    cxy: string;
    new (x:float): IRenderObj;
    axy: string;
} 
JS;




$converter = new TypeScriptDefToHtmlConverter;
$sb = $converter->Convert($buffer, null, false);
// $td = igk_create_notagnode();
// $td->span('ok');
// $td->span('bad');
// $d = igk_create_node('div');
// // $d->p('OK');
// $d->add($td);
// $sb = $d->render((object)['Indent'=>true]); 


// $container = new RegexMatcherContainer;
// $container->match("\/\/.+", "line-comment");

// $ss = <<<EOF
// // bonjour 
// TTtreDocument
// // aqua
// EOF;

// igk_debug(true);
// $container->treat($ss, function($g){
//     igk_wln($g->tokenID ." / ".$g->value);
// });

// exit;



!$no_color && Logger::SetColorizer(new HtmlColorizer);
$t =  $sb->getElementsByTagName('body');

$b = igk_getv($t, 0);
if ($b) {
    $b->style()->Content = <<<CSS
li{
    column: 3; 
    flex-direction:column;
}

body{
    font-family: system-ui ;
}
.dispb{ display:block;}
.str{ color:red; } 
.number{ color: #1983cc; } 
.operator { color: var(--igk-code-operator-fg-color)}
.prop + :after {content:' '; }
.prop{ color: var(--property-color)} 
:root{
    --property-color: #4DC6AE; 
    --igk-code-operator-fg-color: #ff009b; 
}
CSS;
}

$i = (object)['Indent' => $indent];
ob_start();
Logger::print('<!DOCTYPE html>');
Logger::print($sb->render($i));
$c = ob_get_contents();
ob_end_clean();

if ($outfile) {
    igk_io_w2file($outfile, $c);
} else
    echo $c;

igk_exit();


/// TASK : remove single comment from interface definition - OK 
///
