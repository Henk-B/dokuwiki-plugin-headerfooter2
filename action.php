<?php

/**
 * DokuWiki Plugin footer (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Original: from Plugin headerfooter, author Li Zheng <lzpublic@qq.com>
 * Modified by Juergen H-J-Schuemmer@Web.de
 * Only the footer component is supported in this plugin because the header functionality breaks the section edit mode
 * Modified by Henk Bliek hbliekwhitebream.com
 * Brought back the header part of the old plugin. Just disable section editing 'maxseclevel' for the header to work.
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class action_plugin_footer extends DokuWiki_Action_Plugin {
    public function register(Doku_Event_Handler $controller) {
        $controller->register_hook('PARSER_WIKITEXT_PREPROCESS', 'AFTER', $this, 'handle_parser_wikitext_preprocess');
        $controller->register_hook('PARSER_CACHE_USE', 'BEFORE', $this, '_purgecache');
    }
    public function handle_parser_wikitext_preprocess(Doku_Event &$event, $param) {
        global $INFO;
        global $ID;
        global $conf;

        if (isset($INFO['id'])) return;

        //helper array needed for parsePageTemplate
        //so that replacement like shown here is possible: https://www.dokuwiki.org/namespace_templates#replacement_patterns
        $data = array(
            'id'       => $ID, // the id of the page to be created
            'tpl'      => '', // the text used as template
        );

        // Auslesen der Konfiguration für das Präfix der Vorlage-Dateien:
        $pre_nsp = $this->getConf('prefix_namespace');
        if ($pre_nsp != '') {
             $pre_nsp = '/'.$pre_nsp.'_';
        } else {
             $pre_nsp = '/_';   // Defaultwert 1 Unterstrich für Namespace
        };      
        $pre_sub = $this->getConf('prefix_subnamespace');
        if ($pre_sub != '') {
            $pre_sub = '/'.$pre_sub.'_';
        } else {
            $pre_sub = '/__';  // Defaultwert 2 Unterstriche für Sub-Namespace
        };

        $headerpath = '';
        $headername = 'header.txt';   // Name der Vorlage
        $path = dirname(wikiFN($ID));
        if (@file_exists($path.$pre_nsp.$headername)) {
            $headerpath = $path.$pre_nsp.$headername;
        } else {
            // search upper namespaces for templates
            $len = strlen(rtrim($conf['datadir'], '/'));
            while (strlen($path) >= $len) {
                if (@file_exists($path.$pre_sub.$headername)) {
                    $headerpath = $path.$pre_sub.$headername;
                    break;
                }
                $path = substr($path, 0, strrpos($path, '/'));
            }
        }

        if (!empty($headerpath)) {
            $content = $event->data;
            if(strpos($content,"~~NOHEADER~~") == false) {
                if ($conf['maxseclevel'] == 0) {
                  // Prüfung. ob der Befehl "~~NOHEADER~~" im Quelltext enthalten ist
                  $header = file_get_contents($headerpath);
                  if ($header !== false) {
                      $data['tpl'] = cleanText($header);
                      $header = parsePageTemplate($data);
                      if ($this->getConf('separation') == 'paragraph') {
                          // Wenn Absätze zum Teilen verwendet werden
                          $header = rtrim($header, " \r\n\\") . "\n\n";
                      }
                      $event->data = $header . $event->data;
                  }
                }
            } else {
                $event->data = str_replace('~~NOHEADER~~','',$content);
                // Befehl "~~NOHEADER~~" soll nicht angezeigt werden
            }
        }
      
        $footerpath = '';
        $footername = 'footer.txt';   // Name der Vorlage
        $path = dirname(wikiFN($ID));
        if (@file_exists($path.$pre_nsp.$footername)) {
            $footerpath = $path.$pre_nsp.$footername;
        } else {
            // search upper namespaces for templates
            $len = strlen(rtrim($conf['datadir'], '/'));
            while (strlen($path) >= $len) {
                if (@file_exists($path.$pre_sub.$footername)) {
                    $footerpath = $path.$pre_sub.$footername;
                    break;
                }
                $path = substr($path, 0, strrpos($path, '/'));
            }
        }

        if (!empty($footerpath)) {
            $content = $event->data;
            if(strpos($content,"~~NOFOOTER~~") == false) {
                // Prüfung. ob der Befehl "~~NOFOOTER~~" im Quelltext enthalten ist
                $footer = file_get_contents($footerpath);
                if ($footer !== false) {
                    $data['tpl'] = cleanText($footer);
                    $footer = parsePageTemplate($data);
                    if ($this->getConf('separation') == 'paragraph') {
                        // Wenn Absätze zum Teilen verwendet werden
                        $footer = rtrim($footer, " \r\n\\") . "\n\n";
                    }
                    $event->data .= $footer;
                }
                /*
                // Code übernommen von Seite "https://www.dokuwiki.org/devel:event_handlers_code#caching":
                $event->preventDefault();  // stop dokuwiki carrying out its own checks
                $event->stopPropagation(); // avoid other handlers of this event, changing our decision here
                $event->result = false;    // don't use the cached version
                */
            } else {
                $event->data = str_replace('~~NOFOOTER~~','',$content);
                // Befehl "~~NOFOOTER~~" soll nicht angezeigt werden
            }
        }
    }

    // Codeschnipsel aus Seite "https://github.com/MrBertie/pagequery/commit/6cae014dc7cc779c0be8d0a660af42407b414806":
    /**
     * Check for pages changes and eventually purge cache.
     *
     * @author Samuele Tognini <samuele@samuele.netsons.org>
     *
     * @param Doku_Event $event
     * @param mixed     $param not defined
     */
    function _purgecache(&$event, $param) {
        global $ID;
        global $conf;
        /** @var cache_parser $cache */
        $cache = &$event->data;

        if(!isset($cache->page)) return;
        //purge only xhtml cache
        if($cache->mode != "xhtml") return;
        //Check if it is an pagequery page
        if(!p_get_metadata($ID, 'pagequery')) return;
        $aclcache = $this->getConf('aclcache');
        if($conf['useacl']) {
            $newkey = false;
            if($aclcache == 'user') {
                //Cache per user
                if($_SERVER['REMOTE_USER']) $newkey = $_SERVER['REMOTE_USER'];
            } else if($aclcache == 'groups') {
                //Cache per groups
                global $INFO;
                if($INFO['userinfo']['grps']) $newkey = implode('#', $INFO['userinfo']['grps']);
            }
            if($newkey) {
                $cache->key .= "#".$newkey;
                $cache->cache = getCacheName($cache->key, $cache->ext);
            }
        }
        //Check if a page is more recent than purgefile.
        if(@filemtime($cache->cache) < @filemtime($conf['cachedir'].'/purgefile')) {
            $event->preventDefault();
            $event->stopPropagation();
            $event->result = false;
        }
    }
}
