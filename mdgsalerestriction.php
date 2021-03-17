<?php
/**
 * @author:  Michel Dumont <michel.dumont.io>
 * @version: 1.0.0 - 2021-03-15
 * @license: http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * @package: prestashop 1.7
 */

require_once __DIR__ . '/vendor/autoload.php';

if (!defined('_PS_VERSION_')) {
    exit;
}

class mdgsalerestriction extends \Module
{
    use mdg\salerestriction\Traits\HookTrait;

    public function __construct()
    {
        $this->name = 'mdgsalerestriction';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Michel Dumont';
        $this->need_instance = 0;
        $this->bootstrap = 1;
        $this->ps_versions_compliancy = ['min' => '1.7.6.0', 'max' => _PS_VERSION_];
        $this->ps_versions_dir = version_compare(_PS_VERSION_, '1.7', '<') ? 'v16' : 'v17';

        foreach (glob(_PS_MODULE_DIR_ . "{$this->name}/controllers/front/*.php") as $file) {
            $fileName = basename($file, '.php');
            if ($fileName !== 'index') {
                $this->controllers[] = $fileName;
            }
        }

        parent::__construct();

        $this->displayName = $this->l('(mdg) Sale restriction');
        $this->description = $this->l('Allows the restriction of sales. You can limit the number of sales per product');
    }

    #region INSTALL
    /**
     * @inheritdoc
     */
    public function install()
    {
        if (parent::install()) {
            return (new \mdg\salerestriction\Controllers\InstallerController)->install();
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function uninstall()
    {
        if (parent::uninstall()) {
            return (new \mdg\salerestriction\Controllers\InstallerController)->uninstall();
        }

        return false;
    }
    #endregion

}
