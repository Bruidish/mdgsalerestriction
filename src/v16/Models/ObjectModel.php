<?php
/**
 * @author Michel Dumont <michel.dumont.io>
 * @version 2.3.2 - 2020-07-25
 * @package prestashop 1.6
 */

namespace mdg\salerestriction\v16\Models;

if (!defined('_CAN_LOAD_FILES_')) {
    exit;
}

class ObjectModel extends \mdg\salerestriction\core\Models\ObjectModel
{
    public $id_shop;

    /**
     * OVERRIDE pour les namespace
     * @since 2019-07-10
     * ------------------------------------------------------------------------------------------------------- */

    /**
     * Adds current object to the database
     *
     * @param bool $auto_date
     * @param bool $null_values
     *
     * @return bool Insertion result
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add($auto_date = true, $null_values = false)
    {
        $that_name = str_replace('\\', '', get_class($this));

        if (!$this->id_shop || empty($this->id_shop)) {
            $this->id_shop = (int) \Context::getContext()->shop->id;
        }

        if (isset(static::$definition['fields']['position'])) {
            $this->position = static::getNextPosition();
        }

        if (isset($this->id) && !$this->force_id) {
            unset($this->id);
        }

        // @hook actionObject*AddBefore
        \Hook::exec("actionObjectAddBefore", ['object' => $this]);
        \Hook::exec("actionObject{$that_name}AddBefore", ['object' => $this]);

        // Automatically fill dates
        if ($auto_date && property_exists($this, 'date_add')) {
            $this->date_add = date('Y-m-d H:i:s');
        }
        if ($auto_date && property_exists($this, 'date_upd')) {
            $this->date_upd = date('Y-m-d H:i:s');
        }

        if (\Shop::isTableAssociated($this->def['table'])) {
            $id_shop_list = \Shop::getContextListShopID();
            if (count($this->id_shop_list) > 0) {
                $id_shop_list = $this->id_shop_list;
            }
        }

        // Database insertion
        if (\Shop::checkIdShopDefault($this->def['table'])) {
            $this->id_shop_default = (in_array(\Configuration::get('PS_SHOP_DEFAULT'), $id_shop_list) == true) ? \Configuration::get('PS_SHOP_DEFAULT') : min($id_shop_list);
        }
        if (!$result = \Db::getInstance()->insert($this->def['table'], $this->getFields(), $null_values)) {
            return false;
        }

        // Get object id in database
        $this->id = \Db::getInstance()->Insert_ID();

        // Database insertion for multishop fields related to the object
        if (\Shop::isTableAssociated($this->def['table'])) {
            $fields = $this->getFieldsShop();
            $fields[$this->def['primary']] = (int) $this->id;

            foreach ($id_shop_list as $id_shop) {
                $fields['id_shop'] = (int) $id_shop;
                $result &= \Db::getInstance()->insert($this->def['table'] . '_shop', $fields, $null_values);
            }
        }

        if (!$result) {
            return false;
        }

        // Database insertion for multilingual fields related to the object
        if (!empty($this->def['multilang'])) {
            $fields = $this->getFieldsLang();
            if ($fields && is_array($fields)) {
                $shops = \Shop::getCompleteListOfShopsID();
                $asso = \Shop::getAssoTable($this->def['table'] . '_lang');
                foreach ($fields as $field) {
                    foreach (array_keys($field) as $key) {
                        if (!\Validate::isTableOrIdentifier($key)) {
                            throw new \PrestaShopException('key ' . $key . ' is not table or identifier');
                        }
                    }
                    $field[$this->def['primary']] = (int) $this->id;

                    if ($asso !== false && $asso['type'] == 'fk_shop') {
                        foreach ($shops as $id_shop) {
                            $field['id_shop'] = (int) $id_shop;
                            $result &= \Db::getInstance()->insert($this->def['table'] . '_lang', $field);
                        }
                    } else {
                        $result &= \Db::getInstance()->insert($this->def['table'] . '_lang', $field);
                    }
                }
            }
        }

        // @hook actionObject*AddAfter
        \Hook::exec("actionObjectAddAfter", ['object' => $this]);
        \Hook::exec("actionObject{$that_name}AddAfter", ['object' => $this]);

        return $result;
    }
    /**
     * Updates the current object in the database
     *
     * @param bool $null_values
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function update($null_values = false)
    {
        $that_name = str_replace('\\', '', get_class($this));

        if (!$this->id_shop || empty($this->id_shop)) {
            $this->id_shop = (int) \Context::getContext()->shop->id;
        }

        // @hook actionObject*UpdateBefore
        \Hook::exec("actionObjectUpdateBefore", ['object' => $this]);
        \Hook::exec("actionObject{$that_name}UpdateBefore", ['object' => $this]);

        $this->clearCache();

        // Automatically fill dates
        if (isset($this->date_upd)) {
            $this->date_upd = date('Y-m-d H:i:s');
            if (isset($this->update_fields) && is_array($this->update_fields) && count($this->update_fields)) {
                $this->update_fields['date_upd'] = true;
            }
        }

        // Automatically fill dates
        if (isset($this->date_add) && $this->date_add == null) {
            $this->date_add = date('Y-m-d H:i:s');
            if (isset($this->update_fields) && is_array($this->update_fields) && count($this->update_fields)) {
                $this->update_fields['date_add'] = true;
            }
        }

        $id_shop_list = \Shop::getContextListShopID();
        if (count($this->id_shop_list) > 0) {
            $id_shop_list = $this->id_shop_list;
        }

        if (\Shop::checkIdShopDefault($this->def['table']) && !$this->id_shop_default) {
            $this->id_shop_default = (in_array(\Configuration::get('PS_SHOP_DEFAULT'), $id_shop_list) == true) ? \Configuration::get('PS_SHOP_DEFAULT') : min($id_shop_list);
        }
        // Database update
        if (!$result = \Db::getInstance()->update($this->def['table'], $this->getFields(), '`' . pSQL($this->def['primary']) . '` = ' . (int) $this->id, 0, $null_values)) {
            return false;
        }

        // Database insertion for multishop fields related to the object
        if (\Shop::isTableAssociated($this->def['table'])) {
            $fields = $this->getFieldsShop();
            $fields[$this->def['primary']] = (int) $this->id;
            if (is_array($this->update_fields)) {
                $update_fields = $this->update_fields;
                $this->update_fields = null;
                $all_fields = $this->getFieldsShop();
                $all_fields[$this->def['primary']] = (int) $this->id;
                $this->update_fields = $update_fields;
            } else {
                $all_fields = $fields;
            }

            foreach ($id_shop_list as $id_shop) {
                $fields['id_shop'] = (int) $id_shop;
                $all_fields['id_shop'] = (int) $id_shop;
                $where = $this->def['primary'] . ' = ' . (int) $this->id . ' AND id_shop = ' . (int) $id_shop;

                // A little explanation of what we do here : we want to create multishop entry when update is called, but
                // only if we are in a shop context (if we are in all context, we just want to update entries that alread exists)
                $shop_exists = \Db::getInstance()->getValue('SELECT ' . $this->def['primary'] . ' FROM ' . _DB_PREFIX_ . $this->def['table'] . '_shop WHERE ' . $where);

                if ($shop_exists) {
                    if (\Shop::isFeatureActive() && \Shop::getContext() != \Shop::CONTEXT_SHOP) {
                        foreach ($fields as $key => $val) {
                            if (!array_key_exists($key, (array) $this->update_fields)) {
                                unset($fields[$key]);
                            }
                        }
                    }
                    $result &= \Db::getInstance()->update($this->def['table'] . '_shop', $fields, $where, 0, $null_values);
                } elseif (\Shop::getContext() == \Shop::CONTEXT_SHOP) {
                    $result &= \Db::getInstance()->insert($this->def['table'] . '_shop', $all_fields, $null_values);
                }
            }
        }

        // Database update for multilingual fields related to the object
        if (isset($this->def['multilang']) && $this->def['multilang']) {
            $fields = $this->getFieldsLang();
            if (is_array($fields)) {
                foreach ($fields as $field) {
                    foreach (array_keys($field) as $key) {
                        if (!\Validate::isTableOrIdentifier($key)) {
                            throw new \PrestaShopException('key ' . $key . ' is not a valid table or identifier');
                        }
                    }

                    // If this table is linked to multishop system, update / insert for all shops from context
                    if ($this->isLangMultishop()) {
                        $id_shop_list = \Shop::getContextListShopID();
                        if (count($this->id_shop_list) > 0) {
                            $id_shop_list = $this->id_shop_list;
                        }
                        foreach ($id_shop_list as $id_shop) {
                            $field['id_shop'] = (int) $id_shop;
                            $where = pSQL($this->def['primary']) . ' = ' . (int) $this->id
                            . ' AND id_lang = ' . (int) $field['id_lang']
                            . ' AND id_shop = ' . (int) $id_shop;

                            if (\Db::getInstance()->getValue('SELECT COUNT(*) FROM ' . pSQL(_DB_PREFIX_ . $this->def['table']) . '_lang WHERE ' . $where)) {
                                $result &= \Db::getInstance()->update($this->def['table'] . '_lang', $field, $where);
                            } else {
                                $result &= \Db::getInstance()->insert($this->def['table'] . '_lang', $field);
                            }
                        }
                    }
                    // If this table is not linked to multishop system ...
                    else {
                        $where = pSQL($this->def['primary']) . ' = ' . (int) $this->id
                        . ' AND id_lang = ' . (int) $field['id_lang'];
                        if (\Db::getInstance()->getValue('SELECT COUNT(*) FROM ' . pSQL(_DB_PREFIX_ . $this->def['table']) . '_lang WHERE ' . $where)) {
                            $result &= \Db::getInstance()->update($this->def['table'] . '_lang', $field, $where);
                        } else {
                            $result &= \Db::getInstance()->insert($this->def['table'] . '_lang', $field, $null_values);
                        }
                    }
                }
            }
        }

        // @hook actionObject*UpdateAfter
        \Hook::exec("actionObjectUpdateAfter", ['object' => $this]);
        \Hook::exec("actionObject{$that_name}UpdateAfter", ['object' => $this]);

        return $result;
    }

    /**
     * Deletes current object from database
     *
     * @return bool True if delete was successful
     * @throws PrestaShopException
     */
    public function delete()
    {
        $that_name = str_replace('\\', '', get_class($this));

        // @hook actionObject*DeleteBefore
        \Hook::exec("actionObjectDeleteBefore", ['object' => $this]);
        \Hook::exec("actionObject{$that_name}DeleteBefore", ['object' => $this]);

        $this->clearCache();
        $result = true;
        // Remove association to multishop table
        if (\Shop::isTableAssociated($this->def['table'])) {
            $id_shop_list = \Shop::getContextListShopID();
            if (count($this->id_shop_list)) {
                $id_shop_list = $this->id_shop_list;
            }

            $result &= \Db::getInstance()->delete($this->def['table'] . '_shop', '`' . $this->def['primary'] . '`=' . (int) $this->id . ' AND id_shop IN (' . implode(', ', $id_shop_list) . ')');
        }

        // Database deletion
        $has_multishop_entries = $this->hasMultishopEntries();
        if ($result && !$has_multishop_entries) {
            $result &= \Db::getInstance()->delete($this->def['table'], '`' . bqSQL($this->def['primary']) . '` = ' . (int) $this->id);
        }

        if (!$result) {
            return false;
        }

        // Database deletion for multilingual fields related to the object
        if (!empty($this->def['multilang']) && !$has_multishop_entries) {
            $result &= \Db::getInstance()->delete($this->def['table'] . '_lang', '`' . bqSQL($this->def['primary']) . '` = ' . (int) $this->id);
        }

        // Datatable deletion for associations
        foreach ($this->def['associations'] as $association) {
            if (isset($association['association'])) {
                $result &= \Db::getInstance()->delete($association['association'], $this->def['primary'] . '=' . $this->id);
            }
        }

        // @hook actionObject*DeleteAfter
        \Hook::exec("actionObjectDeleteAfter", ['object' => $this]);
        \Hook::exec("actionObject{$that_name}DeleteAfter", ['object' => $this]);

        // Rafraichi les positions des aurtes objets
        if (isset(static::$definition['fields']['position']) && $result) {
            $result &= static::cleanPositions(isset(static::$definition['fields']['id_parent']) ? $this->id_parent : null);
        }

        return $result;
    }

}
