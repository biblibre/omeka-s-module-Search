<?php
namespace Search;

/**
 * @var Module $this
 * @var \Zend\ServiceManager\ServiceLocatorInterface $serviceLocator
 * @var string $oldVersion
 * @var string $newVersion
 */
$services = $serviceLocator;

/**
 * @var \Omeka\Settings\Settings $settings
 * @var \Doctrine\DBAL\Connection $connection
 * @var \Omeka\Api\Manager $api
 * @var array $config
 */
$settings = $services->get('Omeka\Settings');
$connection = $services->get('Omeka\Connection');
$api = $services->get('Omeka\ApiManager');
$config = require dirname(dirname(__DIR__)) . '/config/module.config.php';

if (version_compare($oldVersion, '0.1.1', '<')) {
    $connection->exec('
        ALTER TABLE search_page
        CHANGE `form` `form_adapter` varchar(255) NOT NULL
    ');
}

if (version_compare($oldVersion, '0.5.0', '<')) {
    // There is no "drop foreign key if exists", so check it.
    $sql = '';
    $sm = $connection->getSchemaManager();
    $keys = ['search_page_ibfk_1', 'index_id', 'IDX_4F10A34984337261', 'FK_4F10A34984337261'];
    $foreignKeys = $sm->listTableForeignKeys('search_page');
    foreach ($foreignKeys as $foreignKey) {
        if ($foreignKey && in_array($foreignKey->getName(), $keys)) {
            $sql .= 'ALTER TABLE search_page DROP FOREIGN KEY ' . $foreignKey->getName() . ';' . PHP_EOL;
        }
    }
    $indexes = $sm->listTableIndexes('search_page');
    foreach ($indexes as $index) {
        if ($index && in_array($index->getName(), $keys)) {
            $sql .= 'DROP INDEX ' . $index->getName() . ' ON search_page;' . PHP_EOL;
        }
    }

    $sql .= <<<'SQL'
ALTER TABLE search_index CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE settings settings LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json_array)';
ALTER TABLE search_page CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE index_id index_id INT NOT NULL AFTER id, CHANGE settings settings LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json_array)';
CREATE INDEX IDX_4F10A34984337261 ON search_page (index_id);
ALTER TABLE search_page ADD CONSTRAINT search_page_ibfk_1 FOREIGN KEY (index_id) REFERENCES search_index (id);
SQL;
    $sqls = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($sqls as $sql) {
        $connection->exec($sql);
    }
}

if (version_compare($oldVersion, '0.5.1', '<')) {
    // There is no "drop foreign key if exists", so check it.
    $sql = '';
    $sm = $connection->getSchemaManager();
    $keys = ['search_page_ibfk_1', 'index_id', 'IDX_4F10A34984337261', 'FK_4F10A34984337261'];
    $foreignKeys = $sm->listTableForeignKeys('search_page');
    foreach ($foreignKeys as $foreignKey) {
        if ($foreignKey && in_array($foreignKey->getName(), $keys)) {
            $sql .= 'ALTER TABLE search_page DROP FOREIGN KEY ' . $foreignKey->getName() . ';' . PHP_EOL;
        }
    }
    $indexes = $sm->listTableIndexes('search_page');
    foreach ($indexes as $index) {
        if ($index && in_array($index->getName(), $keys)) {
            $sql .= 'DROP INDEX ' . $index->getName() . ' ON search_page;' . PHP_EOL;
        }
    }

    $sql .= <<<'SQL'
CREATE INDEX IDX_4F10A34984337261 ON search_page (index_id);
ALTER TABLE search_page ADD CONSTRAINT FK_4F10A34984337261 FOREIGN KEY (index_id) REFERENCES search_index (id) ON DELETE CASCADE;
SQL;
    $sqls = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($sqls as $sql) {
        $connection->exec($sql);
    }
}

if (version_compare($oldVersion, '0.5.2', '<')) {
    $this->manageSettings($settings, 'install', 'settings');
    $this->manageSiteSettings($services, 'install');
}

if (version_compare($oldVersion, '3.5.7', '<')) {
    // Ideally, each theme of the site should be checked, but it is useless since only one public theme requried Search.
    /** @var \Omeka\Site\Theme\Manager $themeManager */
    // $themeManager = $services->get('Omeka\Site\ThemeManager');
    $siteSettings = $services->get('Omeka\Settings\Site');
    /** @var \Omeka\Api\Representation\SiteRepresentation[] $sites */
    $sites = $api->search('sites')->getContent();
    foreach ($sites as $site) {
        $theme = $site->theme();
        $siteSettings->setTargetId($site->id());
        $key = 'theme_settings_' . $theme;
        $themeSettings = $siteSettings->get($key, []);
        if (array_key_exists('search_page_id', $themeSettings)) {
            $siteSettings->set('search_main_page', $themeSettings['search_page_id']);
            unset($themeSettings['search_page_id']);
            $siteSettings->set($key, $themeSettings);
        }
    }
}

if (version_compare($oldVersion, '3.5.8', '<')) {
    $defaultConfig = $config[strtolower(__NAMESPACE__)]['config'];
    $settings->set(
        'search_batch_size',
        $defaultConfig['search_batch_size']
    );

    // Reorder the search pages by weight to avoid to do it each time.
    // The api is not available for search pages during upgrade, so use sql.
    $sql = <<<'SQL'
SELECT id, settings FROM search_page;
SQL;
    $stmt = $connection->query($sql);
    $result = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
    if ($result) {
        foreach ($result as $id => $params) {
            $params = json_decode($params, true) ?: [];
            if ($params) {
                foreach (['facets', 'sort_fields'] as $type) {
                    if (!isset($params[$type])) {
                        $params[$type] = [];
                    } else {
                        //  @see \Search\Controller\Admin\SearchPageController::configureAction()
                        // Sort enabled first, then available, else sort by weigth.
                        uasort($params[$type], function ($a, $b) {
                            // Sort by availability.
                            if (isset($a['enabled']) && isset($b['enabled'])) {
                                if ($a['enabled'] > $b['enabled']) {
                                    return -1;
                                } elseif ($a['enabled'] < $b['enabled']) {
                                    return 1;
                                }
                            } elseif (isset($a['enabled'])) {
                                return -1;
                            } elseif (isset($b['enabled'])) {
                                return 1;
                            }
                            // In other cases, sort by weight.
                            if (isset($a['weight']) && isset($b['weight'])) {
                                return $a['weight'] == $b['weight']
                                    ? 0
                                    : ($a['weight'] < $b['weight'] ? -1 : 1);
                            } elseif (isset($a['weight'])) {
                                return -1;
                            } elseif (isset($b['weight'])) {
                                return 1;
                            }
                            return 0;
                        });
                    }
                }
            }
            $params = $connection->quote(json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            $sql = <<<SQL
UPDATE search_page
SET `settings` = $params
WHERE id = $id;
SQL;
            $connection->exec($sql);
        }
    }
}
