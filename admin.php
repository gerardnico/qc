<?php

/**
 * This plugin is used to display a summery of all FIXME pages
 *
 * @see http://dokuwiki.org/plugin:qc
 * @author Dominik Eckelmann <dokuwiki@cosmocode.de>
 */
class admin_plugin_qc extends DokuWiki_Admin_Plugin
{
    protected $data;
    protected $order;

    public function getMenuSort()
    {
        return 999;
    }

    public function forAdminOnly()
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getMenuIcon()
    {
        return __DIR__ . '/svg/good.svg';
    }

    /**
     * handle the request befor html output
     *
     * @see html()
     */
    public function handle()
    {
        global $conf;

        // load the quality data
        if (is_file($conf['tmpdir'] . '/qcgather')) {
            $this->data = file_get_contents($conf['tmpdir'] . '/qcgather');
            $this->data = unserialize($this->data);
        } else {
            $this->data = array();
        }

        // order the data
        if (!isset($_REQUEST['pluginqc']['order'])) {
            $_REQUEST['pluginqc']['order'] = 'quality';
        }

        switch ($_REQUEST['pluginqc']['order']) {
            case 'fixme':
                uasort($this->data, array($this, 'sortFixme'));
                $this->order = 'fixme';
                break;
            default:
                uasort($this->data, array($this, 'sortQuality'));
                $this->order = 'quality';
        }
    }

    /**
     * output html for the admin page
     */
    public function html()
    {
        global $ID;
        $max = $this->getConf('maxshowen');
        if (!$max || $max <= 0) $max = 25;

        echo '<div id="plugin__qc_admin">';
        echo '<h1>' . $this->getLang('admin_headline') . '</h1>';

        echo '<p>' . sprintf($this->getLang('admin_desc'), $max) . '</p>';

        echo '<table class="inline">';
        echo '  <tr>';
        echo '    <th>' . $this->getLang('admin_page') . '</th>';
        echo '    <th class="quality">' . $this->getOrderArrow('quality') . '<a href="' . wl($ID, array('do' => 'admin', 'page' => 'qc', 'pluginqc[order]' => 'quality')) . '">' . $this->getLang('admin_quality') . '</a></th>';
        echo '    <th class="fixme">' . $this->getOrderArrow('fixme') . '<a href="' . wl($ID, array('do' => 'admin', 'page' => 'qc', 'pluginqc[order]' => 'fixme')) . '">' . $this->getLang('admin_fixme') . '</a></th>';
        echo '  </tr>';

        if ($this->data) {
            foreach ($this->data as $id => $data) {
                if ($max == 0) break;
                echo '  <tr>';
                echo '    <td>';
                tpl_pagelink(':' . $id, $id);
                echo '</td>';
                echo '    <td class="centeralign">' . \dokuwiki\plugin\qc\Output::scoreIcon($data['score']) . '</td>';
                echo '    <td class="centeralign">' . $data['err']['fixme'] . '</td>';
                echo '  </tr>';
                $max--;
            }
        }

        echo '</table>';
        echo '</div>';
    }

    protected function getOrderArrow($type)
    {
        if ($type == $this->order) return '&darr; ';
        return '';
    }

    /**
     * order by quality
     */
    protected function sortQuality($a, $b)
    {
        if ($a['score'] == $b['score']) return 0;
        return ($a['score'] < $b['score']) ? 1 : -1;
    }

    /**
     * order by fixmes
     */
    protected function sortFixme($a, $b)
    {
        if ($a['err']['fixme'] == $b['err']['fixme']) return 0;
        return ($a['err']['fixme'] < $b['err']['fixme']) ? 1 : -1;
    }
}
