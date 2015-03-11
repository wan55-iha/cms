<?php
/**
 * Licensed under The GPL-3.0 License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @since     2.0.0
 * @author     Christopher Castro <chris@quickapps.es
 * @link     http://www.quickappscms.org
 * @license     http://opensource.org/licenses/gpl-3.0.html GPL-3.0 License
 */
namespace Block\Test\TestCase\View;

use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Installer\Utility\DatabaseInstaller;

/**
 * DatabaseInstallerTest class.
 */
class DatabaseInstallerTest extends TestCase
{

    /**
     * Instance of DatabaseInstaller.
     *
     * @var \Installer\Utility\DatabaseInstaller
     */
    public $installer = null;

    /**
     * setUp.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->installer = new DatabaseInstaller([
            'settingsPath' => TMP . 'settings_test.php'
        ]);
        $this->_dropTables();
    }

    /**
     * tearDown.
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        if (is_readable(TMP . 'settings_test.php')) {
            unlink(TMP . 'settings_test.php');
        }
        $this->_dropTables();
        foreach ($this->fixtureManager->loaded() as $fixture) {
            $fixture->created = [];
        }
    }

    /**
     * Removes all tables in current DB connection.
     *
     * @return void
     */
    protected function _dropTables()
    {
        // drop all tables
        $db = ConnectionManager::get('test');
        $db->connect();
        $tables = $db->schemaCollection()->listTables();
        foreach ($tables as $table) {
            $Table = TableRegistry::get($table, ['connection' => $db]);
            $schema = $Table->schema();
            $sql = $schema->dropSql($db);

            foreach ($sql as $stmt) {
                $db->execute($stmt)->closeCursor();
            }
        }
    }

    /**
     * test database population.
     *
     * @return void
     */
    public function testPopulate()
    {
        $config = include SITE_ROOT . '/config/settings.php';
        $result = $this->installer->install($config['Datasources']['test']);

        if (!$result) {
            debug($this->installer->config());
            debug($this->installer->errors());
        }

        //$this->assertTrue($result);
    }
}
