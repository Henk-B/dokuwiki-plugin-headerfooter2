<?php
/**
 * @license    GPL (http://www.gnu.org/licenses/gpl.html)
 * @author     Hans-Juergen Schuemmer
 *
 */

if(!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once DOKU_PLUGIN.'syntax.php';

class syntax_plugin_footer extends DokuWiki_Syntax_Plugin {

    function getType() {
        return 'substition';
    }

    function getSort() {
        return 170;             /* ??? */
    }

    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('~~NOHEADER~~',$mode,'plugin_headerfooter2');
        $this->Lexer->addSpecialPattern('~~NOFOOTER~~',$mode,'plugin_headerfooter2');
    }

    function handle($match, $state, $pos, Doku_Handler $handler){	
        $match = str_replace("~~NOHEADER~~", '', $match);
        $match = str_replace("~~NOFOOTER~~", '', $match);
    }

    function render($mode, Doku_Renderer $renderer, $data) {
        if($mode == 'xhtml'){           
            return true;
        }
        return false;
    }

}
?>
