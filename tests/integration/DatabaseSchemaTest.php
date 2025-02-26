<?php
/**
 * Copyright (C) 2019 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2019 thirty bees
 * @license   Open Software License (OSL 3.0)
 */

require_once _PS_MODULE_DIR_ . '/coreupdater/classes/schema/autoload.php';
use \CoreUpdater\ObjectModelSchemaBuilder;
use \CoreUpdater\InformationSchemaBuilder;
use \CoreUpdater\DatabaseSchemaComparator;
use \CoreUpdater\SchemaDifference;

/**
 * Class DatabaseSchemaTest
 *
 * This tests verifies that database schema (loaded by InformationSchemaBuilder) matches schema generated
 * using ObjectModel metadata (generated by ObjectModelSchemaBuilder)
 *
 * @since 1.1.0
 */
class DatabaseSchemaTest extends \Codeception\Test\Unit
{

    /**
     * Test that there are no differences in real database schema, and schema generated by
     * ObjectModel's metatadata
     *
     * Since database schema is now created using ObjectModelSchemaBuilder, this should NEVER
     * happen.
     *
     * If this test fails, it means that someone introduced a bug inside one of these:
     *   - ObjectModelSchemaBuilder
     *   - InformationSchemaBuilder
     *   - DatabaseSchemaComparator
     *   - DatabaseSchema::createDDLStatement()
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws ReflectionException
     */
    public function testSchemaDifferences()
    {
        $modelSchema = (new ObjectModelSchemaBuilder())->getSchema();
        $realSchema = (new InformationSchemaBuilder())->getSchema();

        // ignore tables created by default modules
        $comparator = new DatabaseSchemaComparator([
            'ignoreTables' => [
                'cms_block',
                'cms_block_lang',
                'cms_block_page',
                'cms_block_shop',
                'homeslider',
                'homeslider_slides',
                'homeslider_slides_lang',
                'info',
                'info_lang',
                'layered_category',
                'layered_filter',
                'layered_filter_shop',
                'layered_friendly_url',
                'layered_indexable_attribute_group',
                'layered_indexable_attribute_group_lang_value',
                'layered_indexable_attribute_lang_value',
                'layered_indexable_feature',
                'layered_indexable_feature_lang_value',
                'layered_indexable_feature_value_lang_value',
                'layered_price_index',
                'layered_product_attribute',
                'linksmenutop',
                'linksmenutop_lang',
                'newsletter',
                'pagenotfound',
                'sekeyword',
                'statssearch',
                'themeconfigurator',
                'bees_blog_category',
                'bees_blog_category_lang',
                'bees_blog_category_shop',
                'bees_blog_image_type',
                'bees_blog_image_type_shop',
                'bees_blog_post',
                'bees_blog_post_lang',
                'bees_blog_post_product',
                'bees_blog_post_shop',
                'thememanager_template',
                'tbhtmlblock',
                'tbhtmlblock_hook',
                'tbhtmlblock_lang',
                'coreupdater_cache',
            ]
        ]);

        $differences = $comparator->getDifferences($realSchema, $modelSchema);
        $errors = implode("\n", array_map(function(SchemaDifference $difference) {
            return "  - " . $difference->describe();
        }, $differences));
        if ($errors) {
            self::fail("Database differences:\n$errors\nTotal problems: " . count($differences));
        }
    }

    /**
     * Because database is now derived from ObjectModel::$definition metadata, it is very easy to introduce
     * unwanted changes. For example, by simply changing order of fields in Product::$definition, we would get
     * different CREATE TABLE `tb_product` statement.
     *
     * This is usually what developer wants. But sometimes, developer might not be aware that his change impacts
     * database schema as well. That's the main reason behind this test
     *
     * Basically, this test verifies that generated database schema is the same as database schema saved in
     * golden file sql script: /tests/golden_files/db_schema.sql
     *
     * If change to core object model $definition should indeed impact database schema, please confirm this by
     * updating golden file. You can do it by hand, or you can use /tools/getDatabaseSchema.php script to regenerate
     * the whole golden file.
     *
     * Please check carefully differences in database schema
     *
     * Side effect of this test is that there always exists up-to-date `db_schema.sql` script for quick reference
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws ReflectionException
     */
    public function testGoldenFile()
    {
        $goldenFile = '/tests/golden_files/db_schema.sql' ;
        $content = file_get_contents(_PS_ROOT_DIR_ . $goldenFile);
        $stmts = preg_split('#;\s*[\r\n]+#', $content);
        $goldenFileTables = [];
        foreach($stmts as $stmt) {
            $stmt = trim($stmt);
            if ($stmt) {
                if (!preg_match("/CREATE TABLE `(.+)`/", $stmt, $matches)) {
                    throw new PrestaShopException("Invalid statement in `$goldenFile`: " . $stmt);
                }
                $origTable = $matches[1] ;
                $table = str_replace('PREFIX_', _DB_PREFIX_, $origTable);
                $goldenFileTables[$table] = str_replace($origTable, $table, $stmt);
            }
        }

        $modelSchema = (new ObjectModelSchemaBuilder())->getSchema();
        foreach ($modelSchema->getTables() as $tableName => $table) {
            $actual = $table->getDDLStatement();
            if (! isset($goldenFileTables[$tableName])) {
                self::fail("Missing table `$tableName` in golden file `$goldenFile`");
            }
            $expected = $goldenFileTables[$tableName];
            $message = "Database schema for table `$tableName` does not match definition from golden file";
            $message .= "\n - if this is expected change, please update golden file `$goldenFile`";
            $message .= "\n - you can use /tools/getDatabaseSchema.php script to regenerate the whole golden file";
            self::assertEquals($expected, $actual, $message);
        }
    }
}
