<?php
namespace Solr;

$services = $serviceLocator;
$settings = $services->get('Omeka\Settings');
$connection = $services->get('Omeka\Connection');
$config = require dirname(dirname(__DIR__)) . '/config/module.config.php';
$translator = $serviceLocator->get('MvcTranslator');

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
    $settings = $serviceLocator->get('Omeka\Settings');
    $this->manageSettings($settings, 'install', 'settings');
    $this->manageSiteSettings($serviceLocator, 'install');
}
