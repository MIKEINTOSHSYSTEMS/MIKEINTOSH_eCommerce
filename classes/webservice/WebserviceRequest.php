<?php
/**
 * 2007-2016 PrestaShop
 *
 * thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017-2018 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.thirtybees.com for more information.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2018 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class WebserviceRequestCore
 *
 * @since 1.0.0
 */
class WebserviceRequestCore
{
    const HTTP_GET = 1;
    const HTTP_POST = 2;
    const HTTP_PUT = 4;

    const HEADER_IO_FORMAT = 'Io-Format';
    const HEADER_OUTPUT_FORMAT = 'Output-Format';

    // @codingStandardsIgnoreStart
    protected $_available_languages = null;
    /**
     * Errors triggered at execution
     *
     * @var array
     */
    public $errors = [];

    /**
     * Set if return should display content or not
     *
     * @var bool
     */
    protected $_outputEnabled = true;

    /**
     * Set if the management is specific or if it is classic (entity management)
     *
     * @var WebserviceSpecificManagementImages|WebserviceSpecificManagementSearch|false
     */
    protected $objectSpecificManagement = false;

    /**
     * Base PrestaShop webservice URL
     *
     * @var string
     */
    public $wsUrl;

    /**
     * @var array
     */
    protected $params;

    /**
     * PrestaShop Webservice Documentation URL
     *
     * @var string
     */
    protected $_docUrl = 'http://example.com';

    /**
     * Set if the authentication key was checked
     *
     * @var bool
     */
    protected $_authenticated = false;

    /**
     * HTTP Method to support
     *
     * @var string
     */
    public $method;

    /**
     * The segment of the URL
     *
     * @var array
     */
    public $urlSegment = [];

    /**
     * The segment list of the URL after the "api" segment
     *
     * @var array
     */
    public $urlFragments = [];

    /**
     * The time in microseconds of the start of the execution of the web service request
     *
     * @var int
     */
    protected $_startTime = 0;

    /**
     * The list of each resources manageable via web service
     *
     * @var array
     */
    public $resourceList;

    /**
     * The configuration parameters of the current resource
     *
     * @var array
     */
    public $resourceConfiguration;

    /**
     * The permissions for the current key
     *
     * @var array
     */
    public $keyPermissions;

    /**
     * The XML string to display if web service call succeed
     *
     * @var string
     */
    protected $specificOutput = '';

    /**
     * The list of objects to display
     *
     * @var array
     */
    public $objects;

    /**
     * The current object to support, it extends the PrestaShop ObjectModel
     *
     * @var ObjectModel
     */
    protected $_object;

    /**
     * The schema to display.
     * If null, no schema have to be displayed and normal management has to be performed
     *
     * @var string
     */
    public $schemaToDisplay;

    /**
     * The fields to display. These fields will be displayed when retrieving objects
     *
     * @var string
     */
    public $fieldsToDisplay = 'minimum';

    /**
     * If we are in PUT or POST case, we use this attribute to store the xml string value during process
     *
     * @var string
     */
    protected $_inputXml;

    /**
     * Object instance for singleton
     *
     * @var WebserviceRequest
     */
    protected static $_instance;

    /**
     * Key used for authentication
     *
     * @var string
     */
    protected $_key;

    /**
     * This is used to have a deeper tree diagram.
     *
     * @var int
     */
    public $depth = 0;

    /**
     * Name of the output format
     *
     * @var string
     */
    protected $outputFormat = 'xml';

    /**
     * The object to build the output.
     *
     * @var WebserviceOutputBuilder
     */
    protected $objOutput;

    /**
     * Save the class name for override used in getInstance()
     *
     * @var string
     */
    public static $ws_current_classname;

    public static $shopIDs;

    /**
     * @var WebserviceLogger
     */
    protected $logger = null;

    // @codingStandardsIgnoreEnd

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getOutputEnabled()
    {
        return $this->_outputEnabled;
    }

    /**
     * @param $bool
     *
     * @return $this
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setOutputEnabled($bool)
    {
        if (Validate::isBool($bool)) {
            $this->_outputEnabled = $bool;
        }

        return $this;
    }

    /**
     * Get WebserviceRequest object instance (Singleton)
     *
     * @return static WebserviceRequest instance
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getInstance()
    {
        if (!isset(static::$_instance)) {
            static::$_instance = new WebserviceRequest::$ws_current_classname();
        }

        return static::$_instance;
    }

    /**
     * @param $type
     *
     * @return WebserviceOutputJSON|WebserviceOutputXML
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function getOutputObject($type)
    {
        // set header param in header or as get param
        $headers = static::getWebserviceHeaders();
        if (isset($headers[static::HEADER_IO_FORMAT])) {
            $type = $headers[static::HEADER_IO_FORMAT];
        } elseif (isset($headers[static::HEADER_OUTPUT_FORMAT])) {
            $type = $headers[static::HEADER_OUTPUT_FORMAT];
        } elseif (isset($_GET['output_format'])) {
            $type = $_GET['output_format'];
        } elseif (isset($_GET['io_format'])) {
            $type = $_GET['io_format'];
        }
        $this->outputFormat = $type;
        switch ($type) {
            case 'JSON' :
                require_once dirname(__FILE__).'/WebserviceOutputJSON.php';
                $objRender = new WebserviceOutputJSON();
                break;
            case 'XML' :
            default :
                $objRender = new WebserviceOutputXML();
                break;
        }

        return $objRender;
    }

    public static function getResources()
    {
        $resources = [
            'addresses'                      => ['description' => 'The Customer, Manufacturer and Customer addresses', 'class' => 'Address'],
            'carriers'                       => ['description' => 'The Carriers', 'class' => 'Carrier'],
            'carts'                          => ['description' => 'Customer\'s carts', 'class' => 'Cart'],
            'cart_rules'                     => ['description' => 'Cart rules management', 'class' => 'CartRule'],
            'categories'                     => ['description' => 'The product categories', 'class' => 'Category'],
            'combinations'                   => ['description' => 'The product combinations', 'class' => 'Combination'],
            'configurations'                 => ['description' => 'Shop configuration', 'class' => 'Configuration'],
            'contacts'                       => ['description' => 'Shop contacts', 'class' => 'Contact'],
            'countries'                      => ['description' => 'The countries', 'class' => 'Country'],
            'currencies'                     => ['description' => 'The currencies', 'class' => 'Currency'],
            'customers'                      => ['description' => 'The e-shop\'s customers', 'class' => 'Customer'],
            'customer_threads'               => ['description' => 'Customer services threads', 'class' => 'CustomerThread'],
            'customer_messages'              => ['description' => 'Customer services messages', 'class' => 'CustomerMessage'],
            'deliveries'                     => ['description' => 'Product delivery', 'class' => 'Delivery'],
            'groups'                         => ['description' => 'The customer\'s groups', 'class' => 'Group'],
            'guests'                         => ['description' => 'The guests', 'class' => 'Guest'],
            'images'                         => ['description' => 'The images', 'specific_management' => true],
            'image_types'                    => ['description' => 'The image types', 'class' => 'ImageType'],
            'languages'                      => ['description' => 'Shop languages', 'class' => 'Language'],
            'manufacturers'                  => ['description' => 'The product manufacturers', 'class' => 'Manufacturer'],
            'order_carriers'                 => ['description' => 'The Order carriers', 'class' => 'OrderCarrier'],
            'order_details'                  => ['description' => 'Details of an order', 'class' => 'OrderDetail'],
            'order_histories'                => ['description' => 'The Order histories', 'class' => 'OrderHistory'],
            'order_invoices'                 => ['description' => 'The Order invoices', 'class' => 'OrderInvoice'],
            'orders'                         => ['description' => 'The Customers orders', 'class' => 'Order'],
            'order_payments'                 => ['description' => 'The Order payments', 'class' => 'OrderPayment'],
            'order_states'                   => ['description' => 'The Order statuses', 'class' => 'OrderState'],
            'order_slip'                     => ['description' => 'The Order slips', 'class' => 'OrderSlip'],
            'price_ranges'                   => ['description' => 'Price ranges', 'class' => 'RangePrice'],
            'product_features'               => ['description' => 'The product features', 'class' => 'Feature'],
            'product_feature_values'         => ['description' => 'The product feature values', 'class' => 'FeatureValue'],
            'product_options'                => ['description' => 'The product options', 'class' => 'AttributeGroup'],
            'product_option_values'          => ['description' => 'The product options value', 'class' => 'ProductAttribute'],
            'products'                       => ['description' => 'The products', 'class' => 'Product'],
            'states'                         => ['description' => 'The available states of countries', 'class' => 'State'],
            'stores'                         => ['description' => 'The stores', 'class' => 'Store'],
            'suppliers'                      => ['description' => 'The product suppliers', 'class' => 'Supplier'],
            'tags'                           => ['description' => 'The Products tags', 'class' => 'Tag'],
            'translated_configurations'      => ['description' => 'Shop configuration', 'class' => 'TranslatedConfiguration'],
            'weight_ranges'                  => ['description' => 'Weight ranges', 'class' => 'RangeWeight'],
            'zones'                          => ['description' => 'The Countries zones', 'class' => 'Zone'],
            'employees'                      => ['description' => 'The Employees', 'class' => 'Employee'],
            'search'                         => ['description' => 'Search', 'specific_management' => true, 'forbidden_method' => ['PUT', 'POST', 'DELETE']],
            'content_management_system'      => ['description' => 'Content management system', 'class' => 'CMS'],
            'shops'                          => ['description' => 'Shops from multi-shop feature', 'class' => 'Shop'],
            'shop_groups'                    => ['description' => 'Shop groups from multi-shop feature', 'class' => 'ShopGroup'],
            'taxes'                          => ['description' => 'The tax rate', 'class' => 'Tax'],
            'stock_movements'                => ['description' => 'Stock movements', 'class' => 'StockMvtWS', 'forbidden_method' => ['PUT', 'POST', 'DELETE']],
            'stock_movement_reasons'         => ['description' => 'Stock movement reason', 'class' => 'StockMvtReason'],
            'warehouses'                     => ['description' => 'Warehouses', 'class' => 'Warehouse', 'forbidden_method' => ['DELETE']],
            'stocks'                         => ['description' => 'Stocks', 'class' => 'Stock', 'forbidden_method' => ['PUT', 'POST', 'DELETE']],
            'stock_availables'               => ['description' => 'Available quantities', 'class' => 'StockAvailable', 'forbidden_method' => ['POST', 'DELETE']],
            'warehouse_product_locations'    => ['description' => 'Location of products in warehouses', 'class' => 'WarehouseProductLocation', 'forbidden_method' => ['PUT', 'POST', 'DELETE']],
            'supply_orders'                  => ['description' => 'Supply Orders', 'class' => 'SupplyOrder', 'forbidden_method' => ['PUT', 'POST', 'DELETE']],
            'supply_order_details'           => ['description' => 'Supply Order Details', 'class' => 'SupplyOrderDetail', 'forbidden_method' => ['PUT', 'POST', 'DELETE']],
            'supply_order_states'            => ['description' => 'Supply Order Statuses', 'class' => 'SupplyOrderState', 'forbidden_method' => ['PUT', 'POST', 'DELETE']],
            'supply_order_histories'         => ['description' => 'Supply Order Histories', 'class' => 'SupplyOrderHistory', 'forbidden_method' => ['PUT', 'POST', 'DELETE']],
            'supply_order_receipt_histories' => ['description' => 'Supply Order Receipt Histories', 'class' => 'SupplyOrderReceiptHistory', 'forbidden_method' => ['PUT', 'POST', 'DELETE']],
            'product_suppliers'              => ['description' => 'Product Suppliers', 'class' => 'ProductSupplier'],
            'tax_rules'                      => ['description' => 'Tax rules entity', 'class' => 'TaxRule'],
            'tax_rule_groups'                => ['description' => 'Tax rule groups', 'class' => 'TaxRulesGroup'],
            'specific_prices'                => ['description' => 'Specific price management', 'class' => 'SpecificPrice'],
            'specific_price_rules'           => ['description' => 'Specific price management', 'class' => 'SpecificPriceRule'],
            'shop_urls'                      => ['description' => 'Shop URLs from multi-shop feature', 'class' => 'ShopUrl'],
            'product_customization_fields'   => ['description' => 'Customization Field', 'class' => 'CustomizationField'],
            'customizations'                 => ['description' => 'Customization values', 'class' => 'Customization'],
        ];
        ksort($resources);

        return $resources;
    }

    /* @todo Check how get parameters */
    /* @todo : set this method out */
    /**
     * This method is used for calculate the price for products on the output details
     *
     * @param $field
     * @param $entityObject
     * @param $wsParams
     *
     * @return array field parameters.
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getPriceForProduct($field, $entityObject, $wsParams)
    {
        if (is_int($entityObject->id)) {
            $arrReturn = $this->specificPriceForProduct($entityObject, ['default_price' => '']);
            $field['value'] = $arrReturn['default_price']['value'];
        }

        return $field;
    }

    /* @todo : set this method out */
    /**
     * This method is used for calculate the price for products on a virtual fields
     *
     * @param       $entityObject
     * @param array $parameters
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function specificPriceForProduct($entityObject, $parameters)
    {
        foreach (array_keys($parameters) as $name) {
            $parameters[$name]['object_id'] = $entityObject->id;
        }
        $arrReturn = $this->specificPriceCalculation($parameters);

        return $arrReturn;
    }

    /**
     * @param $parameters
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function specificPriceCalculation($parameters)
    {
        $arrReturn = [];
        foreach ($parameters as $name => $value) {
            $idShop = (int) Context::getContext()->shop->id;
            $idCountry = (int) (isset($value['country']) ? $value['country'] : (Configuration::get('PS_COUNTRY_DEFAULT')));
            $idState = (int) (isset($value['state']) ? $value['state'] : 0);
            $idCurrency = (int) (isset($value['currency']) ? $value['currency'] : Configuration::get('PS_CURRENCY_DEFAULT'));
            $idGroup = (int) (isset($value['group']) ? $value['group'] : (int) Configuration::get('PS_CUSTOMER_GROUP'));
            $quantity = (int) (isset($value['quantity']) ? $value['quantity'] : 1);
            $useTax = (int) (isset($value['use_tax']) ? $value['use_tax'] : Configuration::get('PS_TAX'));
            $decimals = (int) (isset($value['decimals']) ?
                $value['decimals'] :
                _TB_PRICE_DATABASE_PRECISION_
            );
            $idProductAttribute = (int) (isset($value['product_attribute']) ? $value['product_attribute'] : null);
            $onlyReduc = (int) (isset($value['only_reduction']) ? $value['only_reduction'] : false);
            $useReduc = (int) (isset($value['use_reduction']) ? $value['use_reduction'] : true);
            $useEcotax = (int) (isset($value['use_ecotax']) ? $value['use_ecotax'] : Configuration::get('PS_USE_ECOTAX'));
            $specificPriceOutput = null;
            $idCounty = (int) (isset($value['county']) ? $value['county'] : 0);
            $returnValue = Product::priceCalculation(
                $idShop, $value['object_id'], $idProductAttribute, $idCountry, $idState, $idCounty, $idCurrency, $idGroup, $quantity,
                $useTax, $decimals, $onlyReduc, $useReduc, $useEcotax, $specificPriceOutput, null
            );
            $arrReturn[$name] = ['sqlId' => strtolower($name), 'value' => sprintf('%f', $returnValue)];
        }

        return $arrReturn;
    }

    /**
     * This method is used for calculate the price for products on a virtual fields
     *
     * @param       $entityObject
     * @param array $parameters
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function specificPriceForCombination($entityObject, $parameters)
    {
        foreach (array_keys($parameters) as $name) {
            $parameters[$name]['object_id'] = $entityObject->id_product;
            $parameters[$name]['product_attribute'] = $entityObject->id;
        }
        $arrReturn = $this->specificPriceCalculation($parameters);

        return $arrReturn;
    }

    /**
     * Start Webservice request
     *    Check webservice activation
     *    Check autentication
     *    Check resource
     *    Check HTTP Method
     *    Execute the action
     *    Display the result
     *
     * @param string $key
     * @param string $method
     * @param string $url
     * @param array $params GET parameters
     * @param $badClassName
     * @param string $inputXml
     *
     * @return array Returns an array of results (headers, content, type of resource...)
     *
     * @throws PrestaShopException
     * @throws WebserviceException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function fetch($key, $method, $url, $params, $badClassName, $inputXml = null)
    {
        $logger = $this->getLogger();

        // Time logger
        $this->_startTime = microtime(true);
        $this->objects = [];

        // Error handler
        set_error_handler([$this, 'webserviceErrorHandler']);
        ini_set('html_errors', 'off');

        // Two global vars, for compatibility with the PS core...
        global $webserviceCall, $displayErrors;
        $webserviceCall = true;
        $displayErrors = strtolower(ini_get('display_errors')) != 'off';

        if (! $params) {
            $params = [];
        }
        $this->params = $params;

        // __PS_BASE_URI__ is from Shop::$current_base_uri
        $this->wsUrl = Tools::getHttpHost(true).__PS_BASE_URI__.'api/';
        // set the output object which manage the content and header structure and informations
        $this->objOutput = new WebserviceOutputBuilder($this->wsUrl);
        $this->_key = trim($key);


        $this->outputFormat = isset($params['output_format']) ? $params['output_format'] : $this->outputFormat;
        // Set the render object to build the output on the asked format (XML, JSON, CSV, ...)
        $this->objOutput->setObjectRender($this->getOutputObject($this->outputFormat));

        // Check webservice activation and request authentication
        if ($this->webserviceChecks()) {

            $logger->setKey(WebserviceKey::getInstanceByKey($this->_key));

            $headers = static::getWebserviceHeaders();
            $logger->logRequest($method, $_SERVER['REQUEST_URI'], $headers, $inputXml);

            if ($badClassName) {
                $this->setError(500, 'Class "'.htmlspecialchars($badClassName).'" not found. Please update the class_name field in the webservice_account table.', 126);
            }
            // parse request url
            $this->method = $method;
            $this->urlSegment = explode('/', $url);
            $this->urlFragments = $params;
            $this->_inputXml = $inputXml;
            $this->depth = isset($this->urlFragments['depth']) ? (int) $this->urlFragments['depth'] : $this->depth;

            try {
                // Method below set a particular fonction to use on the price field for products entity
                // @see WebserviceRequest::getPriceForProduct() method
                // @see WebserviceOutputBuilder::setSpecificField() method
                //$this->objOutput->setSpecificField($this, 'getPriceForProduct', 'price', 'products');
                if (isset($this->urlFragments['price'])) {
                    $this->objOutput->setVirtualField($this, 'specificPriceForCombination', 'combinations', $this->urlFragments['price']);
                    $this->objOutput->setVirtualField($this, 'specificPriceForProduct', 'products', $this->urlFragments['price']);
                }
            } catch (Exception $e) {
                $this->setError(500, $e->getMessage(), $e->getCode());
            }

            if (isset($this->urlFragments['language'])) {
                $this->_available_languages = $this->filterLanguage();
            } else {
                $this->_available_languages = Language::getIDs();
            }

            if (empty($this->_available_languages)) {
                $this->setError(400, 'language is not available', 81);
            }

            // Need to set available languages for the render object.
            // Thus we can filter i18n field for the output
            // @see WebserviceOutputXML::renderField() method for example
            $this->objOutput->objectRender->setLanguages($this->_available_languages);

            // check method and resource
            if (empty($this->errors) && $this->checkResource() && $this->checkHTTPMethod()) {
                // The resource list is necessary for build the output
                $this->objOutput->setWsResources($this->resourceList);

                // if the resource is a core entity...
                if (!isset($this->resourceList[$this->urlSegment[0]]['specific_management']) || !$this->resourceList[$this->urlSegment[0]]['specific_management']) {
                    // load resource configuration
                    if ($this->urlSegment[0] != '') {
                        /** @var ObjectModel $object */
                        $urlResource = $this->resourceList[$this->urlSegment[0]];
                        $object = new $urlResource['class']();
                        if (isset($this->resourceList[$this->urlSegment[0]]['parameters_attribute'])) {
                            $this->resourceConfiguration = $object->getWebserviceParameters($this->resourceList[$this->urlSegment[0]]['parameters_attribute']);
                        } else {
                            $this->resourceConfiguration = $object->getWebserviceParameters();
                        }
                    }
                    // execute the action
                    switch ($this->method) {
                        case 'GET':
                        case 'HEAD':
                            $this->executeEntityGetAndHead();
                            break;
                        case 'POST':
                            $this->executeEntityPost();
                            break;
                        case 'PUT':
                            $this->executeEntityPut();
                            break;
                        case 'DELETE':
                            $this->executeEntityDelete();
                            break;
                    }
                    // Need to set an object for the WebserviceOutputBuilder object in any case
                    // because schema need to get webserviceParameters of this object
                    if (isset($object)) {
                        $this->objects['empty'] = $object;
                    }
                } // if the management is specific
                else {
                    $specificObjectName = 'WebserviceSpecificManagement'.ucfirst(Tools::toCamelCase($this->urlSegment[0]));
                    if (!class_exists($specificObjectName)) {
                        $this->setError(501, sprintf('The specific management class is not implemented for the "%s" entity.', $this->urlSegment[0]), 124);
                    } else {
                        $this->objectSpecificManagement = new $specificObjectName();
                        $this->objectSpecificManagement->setObjectOutput($this->objOutput)
                            ->setWsObject($this);

                        try {
                            $this->objectSpecificManagement->manage();
                        } catch (WebserviceException $e) {
                            if ($e->getType() == WebserviceException::DID_YOU_MEAN) {
                                $this->setErrorDidYouMean($e->getStatus(), $e->getMessage(), $e->getWrongValue(), $e->getAvailableValues(), $e->getCode());
                            } elseif ($e->getType() == WebserviceException::SIMPLE) {
                                $this->setError($e->getStatus(), $e->getMessage(), $e->getCode());
                            }
                        }
                    }
                }
            }
        }
        $return = $this->returnOutput();
        unset($webserviceCall);
        unset($displayErrors);

        return $return;
    }

    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function webserviceChecks()
    {
        return (
            $this->isActivated() &&
            $this->authenticate() &&
            $this->groupShopExists($this->params) &&
            $this->shopExists($this->params) &&
            $this->shopHasRight($this->_key)
        );
    }

    /**
     * Set a webservice error
     *
     * @param int    $status
     * @param string $label
     * @param int    $code
     *
     * @return void
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setError($status, $label, $code)
    {
        if (isset($this->objOutput)) {
            $this->objOutput->setStatus($status);
        }
        $this->errors[] = [$code, $label];
    }

    /**
     * Set a webservice error and propose a new value near from the available values
     *
     * @param int    $num
     * @param string $label
     * @param array  $value
     * @param array  $availableValues
     * @param int    $code
     *
     * @return void
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setErrorDidYouMean($num, $label, $value, $availableValues, $code)
    {
        $this->setError($num, $label.'. Did you mean: "'.$this->getClosest($value, $availableValues).'"?'.(count($availableValues) > 1 ? ' The full list is: "'.implode('", "', $availableValues).'"' : ''), $code);
    }

    /**
     * Return the nearest value picked in the values list
     *
     * @param string $input
     * @param array  $words
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function getClosest($input, $words)
    {
        $shortest = -1;
        foreach ($words as $word) {
            $lev = levenshtein($input, $word);
            if ($lev == 0) {
                $closest = $word;
                $shortest = 0;
                break;
            }
            if ($lev <= $shortest || $shortest < 0) {
                $closest = $word;
                $shortest = $lev;
            }
        }

        return $closest;
    }

    /**
     * Used to replace the default PHP error handler, in order to display PHP errors in a XML format
     *
     * @param string $errno   contains the level of the error raised, as an integer
     * @param array  $errstr  contains the error message, as a string
     * @param array  $errfile errfile, which contains the filename that the error was raised in, as a string
     * @param array  $errline errline, which contains the line number the error was raised at, as an integer
     *
     * @return bool Always return true to avoid the default PHP error handler
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function webserviceErrorHandler($errno, $errstr, $errfile, $errline)
    {
        $displayErrors = strtolower(ini_get('display_errors')) != 'off';
        if (!(error_reporting() & $errno) || $displayErrors) {
            return false;
        }

        $errortype = [
            E_ERROR             => 'Error',
            E_WARNING           => 'Warning',
            E_PARSE             => 'Parse',
            E_NOTICE            => 'Notice',
            E_CORE_ERROR        => 'Core Error',
            E_CORE_WARNING      => 'Core Warning',
            E_COMPILE_ERROR     => 'Compile Error',
            E_COMPILE_WARNING   => 'Compile Warning',
            E_USER_ERROR        => 'Error',
            E_USER_WARNING      => 'User warning',
            E_USER_NOTICE       => 'User notice',
            E_STRICT            => 'Runtime Notice',
            E_RECOVERABLE_ERROR => 'Recoverable error',
        ];
        $type = (isset($errortype[$errno]) ? $errortype[$errno] : 'Unknown error');
        Tools::error_log('[PHP '.$type.' #'.$errno.'] '.$errstr.' ('.$errfile.', line '.$errline.')');

        switch ($errno) {
            case E_ERROR:
                WebserviceRequest::getInstance()->setError(500, '[PHP Error #'.$errno.'] '.$errstr.' ('.$errfile.', line '.$errline.')', 2);
                break;
            case E_WARNING:
                WebserviceRequest::getInstance()->setError(500, '[PHP Warning #'.$errno.'] '.$errstr.' ('.$errfile.', line '.$errline.')', 3);
                break;
            case E_PARSE:
                WebserviceRequest::getInstance()->setError(500, '[PHP Parse #'.$errno.'] '.$errstr.' ('.$errfile.', line '.$errline.')', 4);
                break;
            case E_NOTICE:
                WebserviceRequest::getInstance()->setError(500, '[PHP Notice #'.$errno.'] '.$errstr.' ('.$errfile.', line '.$errline.')', 5);
                break;
            case E_CORE_ERROR:
                WebserviceRequest::getInstance()->setError(500, '[PHP Core #'.$errno.'] '.$errstr.' ('.$errfile.', line '.$errline.')', 6);
                break;
            case E_CORE_WARNING:
                WebserviceRequest::getInstance()->setError(500, '[PHP Core warning #'.$errno.'] '.$errstr.' ('.$errfile.', line '.$errline.')', 7);
                break;
            case E_COMPILE_ERROR:
                WebserviceRequest::getInstance()->setError(500, '[PHP Compile #'.$errno.'] '.$errstr.' ('.$errfile.', line '.$errline.')', 8);
                break;
            case E_COMPILE_WARNING:
                WebserviceRequest::getInstance()->setError(500, '[PHP Compile warning #'.$errno.'] '.$errstr.' ('.$errfile.', line '.$errline.')', 9);
                break;
            case E_USER_ERROR:
                WebserviceRequest::getInstance()->setError(500, '[PHP Error #'.$errno.'] '.$errstr.' ('.$errfile.', line '.$errline.')', 10);
                break;
            case E_USER_WARNING:
                WebserviceRequest::getInstance()->setError(500, '[PHP User warning #'.$errno.'] '.$errstr.' ('.$errfile.', line '.$errline.')', 11);
                break;
            case E_USER_NOTICE:
                WebserviceRequest::getInstance()->setError(500, '[PHP User notice #'.$errno.'] '.$errstr.' ('.$errfile.', line '.$errline.')', 12);
                break;
            case E_STRICT:
                WebserviceRequest::getInstance()->setError(500, '[PHP Strict #'.$errno.'] '.$errstr.' ('.$errfile.', line '.$errline.')', 13);
                break;
            case E_RECOVERABLE_ERROR:
                WebserviceRequest::getInstance()->setError(500, '[PHP Recoverable error #'.$errno.'] '.$errstr.' ('.$errfile.', line '.$errline.')', 14);
                break;
            default:
                WebserviceRequest::getInstance()->setError(500, '[PHP Unknown error #'.$errno.'] '.$errstr.' ('.$errfile.', line '.$errline.')', 15);
        }

        return true;
    }

    /**
     * Check if there is one or more error
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function hasErrors()
    {
        return (bool) $this->errors;
    }

    /**
     * Check request authentication
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function authenticate()
    {
        if (!$this->hasErrors()) {
            if (is_null($this->_key)) {
                $this->setError(401, 'Please enter the authentication key as the login. No password required', 16);
            } else {
                if (empty($this->_key)) {
                    $this->setError(401, 'Authentication key is empty', 17);
                } elseif (strlen($this->_key) != '32') {
                    $this->setError(401, 'Invalid authentication key format', 18);
                } else {
                    if (WebserviceKey::isKeyActive($this->_key)) {
                        $this->keyPermissions = WebserviceKey::getPermissionForAccount($this->_key);
                    } else {
                        $this->setError(401, 'Authentification key is not active', 20);
                    }

                    if (!$this->keyPermissions) {
                        $this->setError(401, 'No permission for this authentication key', 21);
                    }
                }
            }
            if ($this->hasErrors()) {
                header('WWW-Authenticate: Basic realm="Welcome to PrestaShop Webservice, please enter the authentication key as the login. No password required."');
                $this->objOutput->setStatus(401);

                return false;
            } else {
                // only now we can say the access is authenticated
                $this->_authenticated = true;

                return true;
            }
        }
    }

    /**
     * Check webservice activation
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    protected function isActivated()
    {
        if (!Configuration::get('PS_WEBSERVICE')) {
            $this->setError(503, 'The thirty bees webservice is disabled. Please activate it in the thirty bees Back Office', 22);

            return false;
        }

        return true;
    }

    /**
     * @param $key
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    protected function shopHasRight($key)
    {
        $sql = 'SELECT 1
				FROM '._DB_PREFIX_.'webservice_account wsa LEFT JOIN '._DB_PREFIX_.'webservice_account_shop wsas ON (wsa.id_webservice_account = wsas.id_webservice_account)
				WHERE wsa.key = \''.pSQL($key).'\'';

        foreach (static::$shopIDs as $id_shop) {
            $OR[] = ' wsas.id_shop = '.(int) $id_shop.' ';
        }
        $sql .= ' AND ('.implode('OR', $OR).') ';
        if (!Db::getInstance()->getValue($sql)) {
            $this->setError(403, 'No permission for this key on this shop', 132);

            return false;
        }

        return true;
    }

    /**
     * @param $params
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function shopExists($params)
    {
        if (static::$shopIDs) {
            return true;
        }

        if (isset($params['id_shop'])) {
            if ($params['id_shop'] != 'all' && is_numeric($params['id_shop'])) {
                Shop::setContext(Shop::CONTEXT_SHOP, (int) $params['id_shop']);
                static::$shopIDs[] = (int) $params['id_shop'];

                return true;
            } elseif ($params['id_shop'] == 'all') {
                Shop::setContext(Shop::CONTEXT_ALL);
                static::$shopIDs = Shop::getShops(true, null, true);

                return true;
            }
        } else {
            static::$shopIDs[] = Context::getContext()->shop->id;

            return true;
        }
        $this->setError(404, 'This shop id does not exist', 999);

        return false;
    }

    /**
     * @param $params
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function groupShopExists($params)
    {
        if (isset($params['id_group_shop']) && is_numeric($params['id_group_shop'])) {
            Shop::setContext(Shop::CONTEXT_GROUP, (int) $params['id_group_shop']);
            static::$shopIDs = Shop::getShops(true, (int) $params['id_group_shop'], true);
            if (! static::$shopIDs) {
                // @FIXME Set ErrorCode !
                $this->setError(500, 'This group shop doesn\'t have shops', 999);

                return false;
            }
        }

        // id_group_shop isn't mandatory
        return true;
    }

    /**
     * Check HTTP method
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function checkHTTPMethod()
    {
        if (!in_array($this->method, ['GET', 'POST', 'PUT', 'DELETE', 'HEAD'])) {
            $this->setError(405, 'Method '.$this->method.' is not valid', 23);
        } elseif (isset($this->urlSegment[0]) && isset($this->resourceList[$this->urlSegment[0]]['forbidden_method']) && in_array($this->method, $this->resourceList[$this->urlSegment[0]]['forbidden_method'])) {
            $this->setError(405, 'Method '.$this->method.' is not allowed for the resource '.$this->urlSegment[0], 101);
        } elseif ($this->urlSegment[0] && !in_array($this->method, $this->keyPermissions[$this->urlSegment[0]])) {
            $this->setError(405, 'Method '.$this->method.' is not allowed for the resource '.$this->urlSegment[0].' with this authentication key', 25);
        } else {
            return true;
        }

        return false;
    }

    /**
     * Check resource validity
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function checkResource()
    {
        $this->resourceList = $this->getResources();
        $resourceNames = array_keys($this->resourceList);
        if ($this->urlSegment[0] == '') {
            $this->resourceConfiguration['objectsNodeName'] = 'resources';
        } elseif (in_array($this->urlSegment[0], $resourceNames)) {
            if (!in_array($this->urlSegment[0], array_keys($this->keyPermissions))) {
                $this->setError(401, 'Resource of type "'.$this->urlSegment[0].'" is not allowed with this authentication key', 26);

                return false;
            }
        } else {
            $this->setErrorDidYouMean(400, 'Resource of type "'.$this->urlSegment[0].'" does not exists', $this->urlSegment[0], $resourceNames, 27);

            return false;
        }

        return true;
    }

    /**
     *
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function setObjects()
    {
        $objects = [];
        $arrAvoidId = [];
        $ids = [];
        if (isset($this->urlFragments['id'])) {
            preg_match('#^\[(.*)\]$#Ui', $this->urlFragments['id'], $matches);
            if (count($matches) > 1) {
                $ids = explode(',', $matches[1]);
            }
        } else {
            $ids[] = (int) $this->urlSegment[1];
        }
        if (!empty($ids)) {
            foreach ($ids as $id) {
                $retrieveData = $this->resourceConfiguration['retrieveData'];
                $object = new $retrieveData['className']((int) $id);
                if (!$object->id) {
                    $arrAvoidId[] = $id;
                } else {
                    $objects[] = $object;
                }
            }
        }

        if (!empty($arrAvoidId) || empty($ids)) {
            $this->setError(404, 'Id(s) not exists: '.implode(', ', $arrAvoidId), 87);
            $this->_outputEnabled = true;
        }
    }

    protected function parseDisplayFields($str)
    {
        $bracketLevel = 0;
        $part = [];
        $tmp = '';
        $str_len = strlen($str);
        for ($i = 0; $i < $str_len; $i++) {
            if ($str[$i] == ',' && $bracketLevel == 0) {
                $part[] = $tmp;
                $tmp = '';
            } else {
                $tmp .= $str[$i];
            }
            if ($str[$i] == '[') {
                $bracketLevel++;
            }
            if ($str[$i] == ']') {
                $bracketLevel--;
            }
        }
        if ($tmp != '') {
            $part[] = $tmp;
        }
        $fields = [];
        foreach ($part as $str) {
            $fieldName = trim(substr($str, 0, (strpos($str, '[') === false ? strlen($str) : strpos($str, '['))));
            if (!isset($fields[$fieldName])) {
                $fields[$fieldName] = null;
            }
            if (strpos($str, '[') !== false) {
                $sub_fields = substr($str, strpos($str, '[') + 1, strlen($str) - strpos($str, '[') - 2);
                $tmp_array = [];
                if (strpos($sub_fields, ',') !== false) {
                    $tmp_array = explode(',', $sub_fields);
                } else {
                    $tmp_array = [$sub_fields];
                }
                $fields[$fieldName] = (is_array($fields[$fieldName])) ? array_merge($fields[$fieldName], $tmp_array) : $tmp_array;
            }
        }

        return $fields;
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setFieldsToDisplay()
    {
        // set the fields to display in the list : "full", "minimum", "field_1", "field_1,field_2,field_3"
        if (isset($this->urlFragments['display'])) {
            $this->fieldsToDisplay = $this->urlFragments['display'];
            if ($this->fieldsToDisplay != 'full' && $this->fieldsToDisplay != 'minimum') {
                preg_match('#^\[(.*)\]$#Ui', $this->fieldsToDisplay, $matches);
                if (count($matches)) {
                    $error = false;
                    $fieldsToTest = $this->parseDisplayFields($matches[1]);
                    foreach ($fieldsToTest as $fieldName => $part) {
                        // in case it is not an association
                        if (!is_array($part)) {
                            // We have to allow new specific field for price calculation too
                            $error = (!isset($this->resourceConfiguration['fields'][$fieldName]) && !isset($this->urlFragments['price'][$fieldName]));
                        } else {
                            // if this association does not exists
                            if (!array_key_exists($fieldName, $this->resourceConfiguration['associations'])) {
                                $error = true;
                            }

                            foreach ($part as $field) {
                                if ($field != 'id' && !array_key_exists($field, $this->resourceConfiguration['associations'][$fieldName]['fields'])) {
                                    $error = true;
                                    break;
                                }
                            }
                        }
                        if ($error) {
                            $this->setError(400, 'Unable to display this field "'.$fieldName.(is_array($part) ? ' (details : '.var_export($part, true).')' : '').'". However, these are available: '.implode(', ', array_keys($this->resourceConfiguration['fields'])), 35);

                            return false;
                        }
                    }
                    $this->fieldsToDisplay = $fieldsToTest;
                } else {
                    $this->setError(400, 'The \'display\' syntax is wrong. You can set \'full\' or \'[field_1,field_2,field_3,...]\'. These are available: '.implode(', ', array_keys($this->resourceConfiguration['fields'])), 36);

                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @return array | false
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function manageFilters()
    {
        // filtered fields which can not use filters : hidden_fields
        $availableFilters = [];
        // filtered i18n fields which can use filters
        $i18NAvailableFilters = [];
        foreach ($this->resourceConfiguration['fields'] as $fieldName => $field) {
            if ((!isset($this->resourceConfiguration['hidden_fields']) ||
                (isset($this->resourceConfiguration['hidden_fields']) && !in_array($fieldName, $this->resourceConfiguration['hidden_fields'])))
            ) {
                if ((!isset($field['i18n']) ||
                    (isset($field['i18n']) && !$field['i18n']))
                ) {
                    $availableFilters[] = $fieldName;
                } else {
                    $i18NAvailableFilters[] = $fieldName;
                }
            }
        }

        // Date feature : date=1
        if (!empty($this->urlFragments['date']) && $this->urlFragments['date']) {
            if (!in_array('date_add', $availableFilters)) {
                $availableFilters[] = 'date_add';
            }
            if (!in_array('date_upd', $availableFilters)) {
                $availableFilters[] = 'date_upd';
            }
            if (!array_key_exists('date_add', $this->resourceConfiguration['fields'])) {
                $this->resourceConfiguration['fields']['date_add'] = ['sqlId' => 'date_add'];
            }
            if (!array_key_exists('date_upd', $this->resourceConfiguration['fields'])) {
                $this->resourceConfiguration['fields']['date_upd'] = ['sqlId' => 'date_upd'];
            }
        } else {
            foreach ($availableFilters as $key => $value) {
                if ($value == 'date_add' || $value == 'date_upd') {
                    unset($availableFilters[$key]);
                }
            }
        }

        //construct SQL filter
        $sqlFilter = '';
        $sqlJoin = '';
        if ($this->urlFragments) {
            $schema = 'schema';
            // if we have to display the schema
            if (isset($this->urlFragments[$schema])) {
                if ($this->urlFragments[$schema] == 'blank' || $this->urlFragments[$schema] == 'synopsis') {
                    $this->schemaToDisplay = $this->urlFragments[$schema];
                    return [
                        'sql_join' => '',
                        'sql_filter' => '',
                        'sql_sort' => '',
                        'sql_limit' => ' LIMIT 1'
                    ];
                } else {
                    $this->setError(400, 'Please select a schema of type \'synopsis\' to get the whole schema informations (which fields are required, which kind of content...) or \'blank\' to get an empty schema to fill before using POST request', 28);

                    return false;
                }
            } else {
                // if there are filters
                if (isset($this->urlFragments['filter']) && is_array($this->urlFragments['filter'])) {
                    foreach ($this->urlFragments['filter'] as $field => $urlParam) {
                        if ($field != 'sort' && $field != 'limit') {
                            if (!in_array($field, $availableFilters)) {
                                // if there are linked tables
                                if (isset($this->resourceConfiguration['linked_tables']) && isset($this->resourceConfiguration['linked_tables'][$field])) {
                                    // contruct SQL join for linked tables
                                    $sqlJoin .= 'LEFT JOIN `'.bqSQL(_DB_PREFIX_.$this->resourceConfiguration['linked_tables'][$field]['table']).'` '.bqSQL($field).' ON (main.`'.bqSQL($this->resourceConfiguration['fields']['id']['sqlId']).'` = '.bqSQL($field).'.`'.bqSQL($this->resourceConfiguration['fields']['id']['sqlId']).'`)'."\n";

                                    // construct SQL filter for linked tables
                                    foreach ($urlParam as $field2 => $value) {
                                        if (isset($this->resourceConfiguration['linked_tables'][$field]['fields'][$field2])) {
                                            $linkedField = $this->resourceConfiguration['linked_tables'][$field]['fields'][$field2];
                                            $sqlFilter .= $this->getSQLRetrieveFilter($linkedField['sqlId'], $value, $field.'.');
                                        } else {
                                            $list = array_keys($this->resourceConfiguration['linked_tables'][$field]['fields']);
                                            $this->setErrorDidYouMean(400, 'This filter does not exist for this linked table', $field2, $list, 29);

                                            return false;
                                        }
                                    }
                                } elseif ($urlParam != '' && in_array($field, $i18NAvailableFilters)) {
                                    if (!is_array($urlParam)) {
                                        $urlParam = [$urlParam];
                                    }
                                    $sqlJoin .= 'LEFT JOIN `'.bqSQL(_DB_PREFIX_.$this->resourceConfiguration['retrieveData']['table']).'_lang` AS main_i18n ON (main.`'.pSQL($this->resourceConfiguration['fields']['id']['sqlId']).'` = main_i18n.`'.bqSQL($this->resourceConfiguration['fields']['id']['sqlId']).'`)'."\n";
                                    foreach ($urlParam as $field2 => $value) {
                                        $linkedField = $this->resourceConfiguration['fields'][$field];
                                        $sqlFilter .= $this->getSQLRetrieveFilter($linkedField['sqlId'], $value, 'main_i18n.');
                                        $languageFilter = '['.implode('|', $this->_available_languages).']';
                                        $sqlFilter .= $this->getSQLRetrieveFilter('id_lang', $languageFilter, 'main_i18n.');
                                    }
                                } // if there are filters on linked tables but there are no linked table
                                elseif (is_array($urlParam)) {
                                    if (isset($this->resourceConfiguration['linked_tables'])) {
                                        $this->setErrorDidYouMean(400, 'This linked table does not exist', $field, array_keys($this->resourceConfiguration['linked_tables']), 30);
                                    } else {
                                        $this->setError(400, 'There is no existing linked table for this resource', 31);
                                    }

                                    return false;
                                } else {
                                    $this->setErrorDidYouMean(400, 'This filter does not exist', $field, $availableFilters, 32);

                                    return false;
                                }
                            } elseif ($urlParam == '') {
                                $this->setError(400, 'The filter "'.$field.'" is malformed.', 33);

                                return false;
                            } else {
                                if (isset($this->resourceConfiguration['fields'][$field]['getter'])) {
                                    $this->setError(400, 'The field "'.$field.'" is dynamic. It is not possible to filter GET query with this field.', 34);

                                    return false;
                                } else {
                                    if (isset($this->resourceConfiguration['retrieveData']['tableAlias'])) {
                                        $sqlFilter .= $this->getSQLRetrieveFilter($this->resourceConfiguration['fields'][$field]['sqlId'], $urlParam, $this->resourceConfiguration['retrieveData']['tableAlias'].'.');
                                    } else {
                                        $sqlFilter .= $this->getSQLRetrieveFilter($this->resourceConfiguration['fields'][$field]['sqlId'], $urlParam);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if (!$this->setFieldsToDisplay()) {
            return false;
        }
        // construct SQL Sort
        $sqlSort = '';
        if (isset($this->urlFragments['sort'])) {
            preg_match('#^\[(.*)\]$#Ui', $this->urlFragments['sort'], $matches);
            if (count($matches) > 1) {
                $sorts = explode(',', $matches[1]);
            } else {
                $sorts = [$this->urlFragments['sort']];
            }

            $sqlSort .= ' ORDER BY ';

            foreach ($sorts as $sort) {
                $delimiterPosition = strrpos($sort, '_');
                if ($delimiterPosition !== false) {
                    $fieldName = substr($sort, 0, $delimiterPosition);
                    $direction = strtoupper(substr($sort, $delimiterPosition + 1));
                }
                if ($delimiterPosition === false || !in_array($direction, ['ASC', 'DESC'])) {
                    $this->setError(400, 'The "sort" value has to be formed as this example: "field_ASC" or \'[field_1_DESC,field_2_ASC,field_3_ASC,...]\' ("field" has to be an available field)', 37);

                    return false;
                } elseif (!in_array($fieldName, $availableFilters) && !in_array($fieldName, $i18NAvailableFilters)) {
                    $this->setError(400, 'Unable to filter by this field. However, these are available: '.implode(', ', $availableFilters).', for i18n fields:'.implode(', ', $i18NAvailableFilters), 38);

                    return false;
                } // for sort on i18n field
                elseif (in_array($fieldName, $i18NAvailableFilters)) {
                    if (!preg_match('#main_i18n#', $sqlJoin)) {
                        $sqlJoin .= 'LEFT JOIN `'._DB_PREFIX_.pSQL($this->resourceConfiguration['retrieveData']['table']).'_lang` AS main_i18n ON (main.`'.pSQL($this->resourceConfiguration['fields']['id']['sqlId']).'` = main_i18n.`'.pSQL($this->resourceConfiguration['fields']['id']['sqlId']).'`)'."\n";
                    }
                    $sqlSort .= 'main_i18n.`'.pSQL($this->resourceConfiguration['fields'][$fieldName]['sqlId']).'` '.$direction.', ';// ORDER BY main_i18n.`field` ASC|DESC
                } else {
                    /** @var ObjectModel $object */
                    $retrieveData = $this->resourceConfiguration['retrieveData'];
                    $object = new $retrieveData['className']();
                    $assoc = Shop::getAssoTable($this->resourceConfiguration['retrieveData']['table']);
                    if ($assoc !== false && $assoc['type'] == 'shop' && ($object->isMultiShopField($this->resourceConfiguration['fields'][$fieldName]['sqlId']) || $fieldName == 'id')) {
                        $tableAlias = 'multi_shop_'.$this->resourceConfiguration['retrieveData']['table'];
                    } else {
                        $tableAlias = '';
                    }
                    $sqlSort .= (isset($this->resourceConfiguration['retrieveData']['tableAlias']) ? '`'.bqSQL($this->resourceConfiguration['retrieveData']['tableAlias']).'`.' : '`'.bqSQL($tableAlias).'`.').'`'.pSQL($this->resourceConfiguration['fields'][$fieldName]['sqlId']).'` '.$direction.', ';// ORDER BY `field` ASC|DESC
                }
            }
            $sqlSort = rtrim($sqlSort, ', ')."\n";
        }

        //construct SQL Limit
        $sqlLimit = '';
        if (isset($this->urlFragments['limit'])) {
            $limitArgs = explode(',', $this->urlFragments['limit']);
            if (count($limitArgs) > 2) {
                $this->setError(400, 'The "limit" value has to be formed as this example: "5,25" or "10"', 39);

                return false;
            } else {
                $sqlLimit .= ' LIMIT '.(int) ($limitArgs[0]).(isset($limitArgs[1]) ? ', '.(int) ($limitArgs[1]) : '')."\n";// LIMIT X|X, Y
            }
        }

        return [
            'sql_join' => $sqlJoin,
            'sql_filter' => $sqlFilter,
            'sql_sort' => $sqlSort,
            'sql_limit' => $sqlLimit,
        ];
    }

    /**
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getFilteredObjectList()
    {
        $objects = [];
        $filters = $this->manageFilters();

        if (is_array($filters)) {
            $this->resourceConfiguration['retrieveData']['params'][] = $filters['sql_join'];
            $this->resourceConfiguration['retrieveData']['params'][] = $filters['sql_filter'];
            $this->resourceConfiguration['retrieveData']['params'][] = $filters['sql_sort'];
            $this->resourceConfiguration['retrieveData']['params'][] = $filters['sql_limit'];
            //list entities

            $retrieveData = $this->resourceConfiguration['retrieveData'];
            $tmp = new $retrieveData['className']();
            $sqlObjects = call_user_func_array([$tmp, $this->resourceConfiguration['retrieveData']['retrieveMethod']], $this->resourceConfiguration['retrieveData']['params']);
            if ($sqlObjects) {
                foreach ($sqlObjects as $sqlObject) {
                    if ($this->fieldsToDisplay == 'minimum') {
                        $obj = new $retrieveData['className']();
                        $obj->id = (int)$sqlObject[$this->resourceConfiguration['fields']['id']['sqlId']];
                        $objects[] = $obj;
                    } else {
                        $objects[] = new $retrieveData['className']((int)$sqlObject[$this->resourceConfiguration['fields']['id']['sqlId']]);
                    }
                }

            }
        }
        return $objects;
    }

    /**
     * @return array|bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getFilteredObjectDetails()
    {
        $objects = [];
        if (!isset($this->urlFragments['display'])) {
            $this->fieldsToDisplay = 'full';
        }

        //get entity details
        $retrieveData = $this->resourceConfiguration['retrieveData'];
        $object = new $retrieveData['className']((int) $this->urlSegment[1]);
        if ($object->id) {
            $objects[] = $object;
            // Check if Object is accessible for this/those id_shop
            $assoc = Shop::getAssoTable($this->resourceConfiguration['retrieveData']['table']);
            if ($assoc !== false) {
                $checkShopGroup = false;

                $sql = 'SELECT 1
	 						FROM `'.bqSQL(_DB_PREFIX_.$retrieveData['table']);
                if ($assoc['type'] != 'fk_shop') {
                    $sql .= '_'.$assoc['type'];
                } else {
                    $def = ObjectModel::getDefinition($this->resourceConfiguration['retrieveData']['className']);
                    if (isset($def['fields']) && isset($def['fields']['id_shop_group'])) {
                        $checkShopGroup = true;
                    }
                }
                $sql .= '`';

                foreach (static::$shopIDs as $idShop) {
                    $OR[] = ' (id_shop = '.(int) $idShop.($checkShopGroup ? ' OR (id_shop = 0 AND id_shop_group='.(int) Shop::getGroupFromShop((int) $idShop).')' : '').') ';
                }

                $check = ' WHERE ('.implode('OR', $OR).') AND `'.bqSQL($this->resourceConfiguration['fields']['id']['sqlId']).'` = '.(int) $this->urlSegment[1];
                if (!Db::getInstance()->getValue($sql.$check)) {
                    $this->setError(404, 'This '.$this->resourceConfiguration['retrieveData']['className'].' ('.(int) $this->urlSegment[1].') does not exists on this shop', 131);
                }
            }

            return $objects;
        }
        if (!count($this->errors)) {
            $this->objOutput->setStatus(404);
            $this->_outputEnabled = false;

            return false;
        }
    }

    /**
     * Execute GET and HEAD requests
     *
     * Build filter
     * Build fields display
     * Build sort
     * Build limit
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function executeEntityGetAndHead()
    {
        if ($this->resourceConfiguration['objectsNodeName'] != 'resources') {
            if (!isset($this->urlSegment[1]) || !strlen($this->urlSegment[1])) {
                $return = $this->getFilteredObjectList();
            } else {
                $return = $this->getFilteredObjectDetails();
            }

            if (!$return) {
                return false;
            } else {
                $this->objects = $return;
            }
        }

        return true;
    }

    /**
     * Execute POST method on a PrestaShop entity
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function executeEntityPost()
    {
        return $this->saveEntityFromXml(201);
    }

    /**
     * Execute PUT method on a PrestaShop entity
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function executeEntityPut()
    {
        return $this->saveEntityFromXml(200);
    }

    /**
     * Execute DELETE method on a PrestaShop entity
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function executeEntityDelete()
    {
        $objects = [];
        $arrAvoidId = [];
        $ids = [];
        if (isset($this->urlFragments['id'])) {
            preg_match('#^\[(.*)\]$#Ui', $this->urlFragments['id'], $matches);
            if (count($matches) > 1) {
                $ids = explode(',', $matches[1]);
            }
        } else {
            $ids[] = (int) $this->urlSegment[1];
        }
        if (!empty($ids)) {
            foreach ($ids as $id) {
                $retrieveData = $this->resourceConfiguration['retrieveData'];
                $object = new $retrieveData['className']((int) $id);
                if (!$object->id) {
                    $arrAvoidId[] = $id;
                } else {
                    $objects[] = $object;
                }
            }
        }

        if (!empty($arrAvoidId) || empty($ids)) {
            $this->setError(404, 'Id(s) not exists: '.implode(', ', $arrAvoidId), 87);
            $this->_outputEnabled = true;
        } else {
            foreach ($objects as $object) {
                /** @var ObjectModel $object */
                if (isset($this->resourceConfiguration['objectMethods']) && isset($this->resourceConfiguration['objectMethods']['delete'])) {
                    $resourceConfig = $this->resourceConfiguration['objectMethods']['delete'];
                    $result = $object->$resourceConfig();
                } else {
                    $result = $object->delete();
                }

                if (!$result) {
                    $arrAvoidId[] = $object->id;
                }
            }
            if (!empty($arrAvoidId)) {
                $this->setError(500, 'Id(s) wasn\'t deleted: '.implode(', ', $arrAvoidId), 88);
                $this->_outputEnabled = true;
            } else {
                $this->_outputEnabled = false;
            }
        }
    }

    /**
     * save Entity Object from XML
     *
     * @param int $successReturnCode
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    protected function saveEntityFromXml($successReturnCode)
    {
        try {
            $xml = @(new SimpleXMLElement($this->_inputXml));
        } catch (Exception $error) {
            $this->setError(500, 'XML error : '.$error->getMessage()."\n".'XML length : '.strlen($this->_inputXml)."\n".'Original XML : '.$this->_inputXml, 127);
            return false;
        }

        /** @var SimpleXMLElement|Countable $xmlEntities */
        $xmlEntities = $xml->children();
        $object = null;

        $ids = [];
        foreach ($xmlEntities as $entity) {
            // To cast in string allow to check null values
            if ((string) $entity->id != '') {
                $ids[] = (int) $entity->id;
            }
        }
        if ($this->method == 'PUT') {
            $ids2 = array_unique($ids);
            if (count($ids2) != count($ids)) {
                $this->setError(400, 'id is duplicate in request', 89);

                return false;
            }
            if (count($xmlEntities) != count($ids)) {
                $this->setError(400, 'id is required when modifying a resource', 90);

                return false;
            }
        } elseif ($this->method == 'POST' && count($ids) > 0) {
            $this->setError(400, 'id is forbidden when adding a new resource', 91);

            return false;
        }

        foreach ($xmlEntities as $xmlEntity) {
            /** @var SimpleXMLElement $xmlEntity */
            $attributes = $xmlEntity->children();

            /** @var ObjectModel $object */
            $retrieveData = $this->resourceConfiguration['retrieveData'];
            if ($this->method == 'POST') {
                $object = new $retrieveData['className']();
            } elseif ($this->method == 'PUT') {
                $object = new $retrieveData['className']((int) $attributes->id);
                if (!$object->id) {
                    $this->setError(404, 'Invalid ID', 92);

                    return false;
                }
            }
            $this->objects[] = $object;
            $i18n = false;
            // attributes
            foreach ($this->resourceConfiguration['fields'] as $fieldName => $fieldProperties) {
                $sqlId = $fieldProperties['sqlId'];

                if ($fieldName == 'id') {
                    $sqlId = $fieldName;
                }
                if (isset($attributes->$fieldName) && isset($fieldProperties['sqlId']) && (!isset($fieldProperties['i18n']) || !$fieldProperties['i18n'])) {
                    if (isset($fieldProperties['setter'])) {
                        // if we have to use a specific setter
                        if (!$fieldProperties['setter']) {
                            // if it's forbidden to set this field
                            $this->setError(400, 'parameter "'.$fieldName.'" not writable. Please remove this attribute of this XML', 93);

                            return false;
                        } else {
                            $setter = $fieldProperties['setter'];
                            $object->$setter((string) $attributes->$fieldName);
                        }
                    } elseif (property_exists($object, $sqlId)) {
                        $object->$sqlId = (string) $attributes->$fieldName;
                    } else {
                        $this->setError(400, 'Parameter "'.$fieldName.'" can\'t be set to the object "'.$this->resourceConfiguration['retrieveData']['className'].'"', 123);
                    }
                } elseif (isset($fieldProperties['required']) && $fieldProperties['required'] && !$fieldProperties['i18n']) {
                    $this->setError(400, 'parameter "'.$fieldName.'" required', 41);

                    return false;
                } elseif ((!isset($fieldProperties['required']) || !$fieldProperties['required']) && property_exists($object, $sqlId)) {
                    $object->$sqlId = null;
                }
                if (isset($fieldProperties['i18n']) && $fieldProperties['i18n']) {
                    $i18n = true;
                    if (isset($attributes->$fieldName, $attributes->$fieldName->language)) {
                        foreach ($attributes->$fieldName->language as $lang) {
                            /** @var SimpleXMLElement $lang */
                            $object->{$fieldName}[(int) $lang->attributes()->id] = (string) $lang;
                        }
                    } else {
                        $object->{$fieldName} = (string) $attributes->$fieldName;
                    }
                }
            }

            // Apply the modifiers if they exist
            foreach ($this->resourceConfiguration['fields'] as $fieldName => $fieldProperties) {
                if (isset($fieldProperties['modifier']) && isset($fieldProperties['modifier']['modifier']) && $fieldProperties['modifier']['http_method'] & constant('WebserviceRequest::HTTP_'.$this->method)) {
                    $object->{$fieldProperties['modifier']['modifier']}();
                }
            }

            if (!$this->hasErrors()) {
                if ($i18n && ($retValidateFieldsLang = $object->validateFieldsLang(false, true)) !== true) {
                    $this->setError(400, 'Validation error: "'.$retValidateFieldsLang.'"', 84);

                    return false;
                } elseif (($retValidateFields = $object->validateFields(false, true)) !== true) {
                    $this->setError(400, 'Validation error: "'.$retValidateFields.'"', 85);

                    return false;
                } else {
                    // Call alternative method for add/update
                    $objectMethod = ($this->method == 'POST' ? 'add' : 'update');
                    if (isset($this->resourceConfiguration['objectMethods']) && array_key_exists($objectMethod, $this->resourceConfiguration['objectMethods'])) {
                        $objectMethod = $this->resourceConfiguration['objectMethods'][$objectMethod];
                    }
                    $result = $object->{$objectMethod}();
                    if ($result) {
                        if (isset($attributes->associations)) {
                            foreach ($attributes->associations->children() as $association) {
                                /** @var SimpleXMLElement $association */
                                // associations
                                if (isset($this->resourceConfiguration['associations'][$association->getName()])) {
                                    $assocItems = $association->children();
                                    $values = [];
                                    foreach ($assocItems as $assocItem) {
                                        /** @var SimpleXMLElement $assocItem */
                                        $fields = $assocItem->children();
                                        $entry = [];
                                        foreach ($fields as $fieldName => $fieldValue) {
                                            $entry[$fieldName] = (string) $fieldValue;
                                        }
                                        $values[] = $entry;
                                    }
                                    $setter = $this->resourceConfiguration['associations'][$association->getName()]['setter'];
                                    if (!is_null($setter) && $setter && method_exists($object, $setter) && !$object->$setter($values)) {
                                        $this->setError(500, 'Error occurred while setting the '.$association->getName().' value', 85);

                                        return false;
                                    }
                                } elseif ($association->getName() != 'i18n') {
                                    $this->setError(400, 'The association "'.$association->getName().'" does not exists', 86);

                                    return false;
                                }
                            }
                        }
                        $assoc = Shop::getAssoTable($this->resourceConfiguration['retrieveData']['table']);
                        if ($assoc !== false && $assoc['type'] != 'fk_shop') {
                            // PUT nor POST is destructive, no deletion
                            $sql = 'INSERT IGNORE INTO `'.bqSQL(_DB_PREFIX_.$this->resourceConfiguration['retrieveData']['table'].'_'.$assoc['type']).'` (id_shop, '.pSQL($this->resourceConfiguration['fields']['id']['sqlId']).') VALUES ';
                            foreach (static::$shopIDs as $id) {
                                $sql .= '('.(int) $id.','.(int) $object->id.')';
                                if ($id != end(static::$shopIDs)) {
                                    $sql .= ', ';
                                }
                            }
                            Db::getInstance()->execute($sql);
                        }
                    } else {
                        $this->setError(500, 'Unable to save resource', 46);
                    }
                }
            }
        }
        if (!$this->hasErrors()) {
            $this->objOutput->setStatus($successReturnCode);

            return true;
        }
    }

    /**
     * get SQL retrieve Filter
     *
     * @param string $sqlId
     * @param string $filterValue
     * @param string $tableAlias = 'main.'
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function getSQLRetrieveFilter($sqlId, $filterValue, $tableAlias = 'main.')
    {
        $ret = '';
        preg_match('/^(.*)\[(.*)\](.*)$/', $filterValue, $matches);
        if (count($matches) > 1) {
            if ($matches[1] == '%' || $matches[3] == '%') {
                $ret .= ' AND '.bqSQL($tableAlias).'`'.bqSQL($sqlId).'` LIKE "'.pSQL($matches[1].$matches[2].$matches[3])."\"\n";
            } elseif ($matches[1] == '' && $matches[3] == '') {
                if (strpos($matches[2], '|') > 0) {
                    $values = explode('|', $matches[2]);
                    $ret .= ' AND (';
                    $temp = '';
                    foreach ($values as $value) {
                        $temp .= bqSQL($tableAlias).'`'.bqSQL($sqlId).'` = "'.bqSQL($value).'" OR ';
                    }
                    $ret .= rtrim($temp, 'OR ').')'."\n";
                } elseif (preg_match('/^([\d\.:\-\s]+),([\d\.:\-\s]+)$/', $matches[2], $matches3)) {
                    unset($matches3[0]);
                    if (count($matches3) > 0) {
                        sort($matches3);
                        $ret .= ' AND '.$tableAlias.'`'.bqSQL($sqlId).'` BETWEEN "'.pSQL($matches3[0]).'" AND "'.pSQL($matches3[1])."\"\n";
                    }
                } else {
                    $ret .= ' AND '.$tableAlias.'`'.bqSQL($sqlId).'`="'.pSQL($matches[2]).'"'."\n";
                }
            } elseif ($matches[1] == '>') {
                $ret .= ' AND '.$tableAlias.'`'.bqSQL($sqlId).'` > "'.pSQL($matches[2])."\"\n";
            } elseif ($matches[1] == '<') {
                $ret .= ' AND '.$tableAlias.'`'.bqSQL($sqlId).'` < "'.pSQL($matches[2])."\"\n";
            } elseif ($matches[1] == '!') {
                $multipleValues = explode('|', $matches[2]);
                foreach ($multipleValues as $value) {
                    $ret .= ' AND '.$tableAlias.'`'.bqSQL($sqlId).'` != "'.pSQL($value)."\"\n";
                }
            }
        } else {
            $ret .= ' AND '.$tableAlias.'`'.bqSQL($sqlId).'` '.(Validate::isFloat(pSQL($filterValue)) ? 'LIKE' : '=').' "'.pSQL($filterValue)."\"\n";
        }

        return $ret;
    }

    /**
     * @return array|bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function filterLanguage()
    {
        $arrLanguages = [];
        $lengthValues = strlen($this->urlFragments['language']);
        // if just one language is asked
        if (is_numeric($this->urlFragments['language'])) {
            $arrLanguages[] = (int) $this->urlFragments['language'];
        } // if a range or a list is asked
        elseif (strpos($this->urlFragments['language'], '[') === 0
            && strpos($this->urlFragments['language'], ']') === $lengthValues - 1
        ) {
            if (strpos($this->urlFragments['language'], '|') !== false
                xor strpos($this->urlFragments['language'], ',') !== false
            ) {
                $paramsValues = str_replace([']', '['], '', $this->urlFragments['language']);
                // it's a list
                if (strpos($paramsValues, '|') !== false) {
                    $listEnabledLang = explode('|', $paramsValues);
                    $arrLanguages = $listEnabledLang;
                } // it's a range
                elseif (strpos($paramsValues, ',') !== false) {
                    $rangeEnabledLang = explode(',', $paramsValues);
                    if (count($rangeEnabledLang) != 2) {
                        $this->setError(400, 'A range value for a language must contains only 2 values', 78);

                        return false;
                    }
                    for ($i = $rangeEnabledLang[0]; $i <= $rangeEnabledLang[1]; $i++) {
                        $arrLanguages[] = $i;
                    }
                }
            } elseif (preg_match('#\[(\d)+\]#Ui', $this->urlFragments['language'], $matchLang)) {
                $arrLanguages[] = $matchLang[1];
            }
        } else {
            $this->setError(400, 'language value is wrong', 79);

            return false;
        }

        $result = array_map('is_numeric', $arrLanguages);
        if (array_search(false, $result, true)) {
            $this->setError(400, 'Language ID must be numeric', 80);

            return false;
        }

        foreach ($arrLanguages as $key => $idLang) {
            if (!Language::getLanguage($idLang)) {
                unset($arrLanguages[$key]);
            }
        }

        return $arrLanguages;
    }

    /**
     * Thanks to the (WebserviceOutputBuilder) WebserviceKey::objOutput
     * Method build the output depend on the WebserviceRequest::outputFormat
     * and set HTTP header parameters.
     *
     * @return array with displaying informations (used in the dispatcher).
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws WebserviceException
     */
    protected function returnOutput()
    {
        $return = [];

        // write headers
        $time = round(microtime(true) - $this->_startTime, 3);
        $this->objOutput
            ->setHeaderParams('Access-Time', time())
            ->setHeaderParams('Execution-Time', $time);

        $return['type'] = strtolower($this->outputFormat);

        // write this header only now (avoid hackers happiness...)
        if ($this->_authenticated) {
            $this->objOutput
                ->setHeaderParams('PSWS-Version', _PS_VERSION_)
                ->setHeaderParams('TBWS-Version', _TB_VERSION_);
        }

        // If Specific Management is asked
        if ($this->objectSpecificManagement instanceof WebserviceSpecificManagementInterface) {
            try {
                $return['content'] = $this->objectSpecificManagement->getContent();
            } catch (WebserviceException $e) {
                if ($e->getType() == WebserviceException::DID_YOU_MEAN) {
                    $this->setErrorDidYouMean($e->getStatus(), $e->getMessage(), $e->getWrongValue(), $e->getAvailableValues(), $e->getCode());
                } elseif ($e->getType() == WebserviceException::SIMPLE) {
                    $this->setError($e->getStatus(), $e->getMessage(), $e->getCode());
                }
            }
        }

        // for use a general output
        if (!$this->hasErrors() && $this->objectSpecificManagement == null) {
            if (empty($this->objects)) {
                try {
                    $return['content'] = $this->objOutput->getResourcesList($this->keyPermissions);
                } catch (WebserviceException $e) {
                    if ($e->getType() == WebserviceException::DID_YOU_MEAN) {
                        $this->setErrorDidYouMean($e->getStatus(), $e->getMessage(), $e->getWrongValue(), $e->getAvailableValues(), $e->getCode());
                    } elseif ($e->getType() == WebserviceException::SIMPLE) {
                        $this->setError($e->getStatus(), $e->getMessage(), $e->getCode());
                    }
                }
            } else {
                try {
                    if (isset($this->urlSegment[1]) && !empty($this->urlSegment[1])) {
                        $typeOfView = WebserviceOutputBuilder::VIEW_DETAILS;
                    } else {
                        $typeOfView = WebserviceOutputBuilder::VIEW_LIST;
                    }

                    if (in_array($this->method, ['PUT', 'POST'])) {
                        $typeOfView = WebserviceOutputBuilder::VIEW_DETAILS;
                        $this->fieldsToDisplay = 'full';
                    }

                    $return['content'] = $this->objOutput->getContent($this->objects, $this->schemaToDisplay, $this->fieldsToDisplay, $this->depth, $typeOfView);
                } catch (WebserviceException $e) {
                    if ($e->getType() == WebserviceException::DID_YOU_MEAN) {
                        $this->setErrorDidYouMean($e->getStatus(), $e->getMessage(), $e->getWrongValue(), $e->getAvailableValues(), $e->getCode());
                    } elseif ($e->getType() == WebserviceException::SIMPLE) {
                        $this->setError($e->getStatus(), $e->getMessage(), $e->getCode());
                    }
                } catch (Exception $e) {
                    $this->setError(500, $e->getMessage(), $e->getCode());
                }
            }
        }

        // if the output is not enable, delete the content
        // the type content too
        if (!$this->_outputEnabled) {
            if (isset($return['type'])) {
                unset($return['type']);
            }
            if (isset($return['content'])) {
                unset($return['content']);
            }
        } elseif (isset($return['content'])) {
            $this->objOutput->setHeaderParams('Content-Sha1', sha1($return['content']));
        }

        // if errors happens when creating returned xml,
        // the usual xml content is replaced by the nice error handler content
        if ($this->hasErrors()) {
            $this->_outputEnabled = true;
            $return['content'] = $this->objOutput->getErrors($this->errors);
        }

        if (!isset($return['content']) || strlen($return['content']) <= 0) {
            $this->objOutput->setHeaderParams('Content-Type', '');
        }

        $return['headers'] = $this->objOutput->buildHeader();
        restore_error_handler();

        $logger = $this->getLogger();
        $logger->logResponse($return['content'], $this->errors, $time);

        return $return;
    }

    /**
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getAllHeaders()
    {

        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
        } else {
            $headers = array_merge($_ENV, $_SERVER);
            foreach ($headers as $key => $val) {
                //we need this header
                if (strpos(strtolower($key), 'content-type') !== false) {
                    continue;
                }
                if (strtoupper(substr($key, 0, 5)) != 'HTTP_') {
                    unset($headers[$key]);
                }
            }
        }

        //Normalize this array to Cased-Like-This structure.
        $retarr = [];
        foreach ($headers as $key => $value) {
            $key = preg_replace('/^HTTP_/i', '', $key);
            $key = str_replace(' ', '-', ucwords(strtolower(str_replace(['-', '_'], ' ', $key))));
            $retarr[$key] = $value;
        }
        ksort($retarr);
        return $retarr;
    }

    /**
     * @return array
     */
    private static function getWebserviceHeaders()
    {
        static $headers = null;
        if (is_null($headers)) {
            $interesting = [ static::HEADER_OUTPUT_FORMAT, static::HEADER_IO_FORMAT ];
            $headers = array_intersect_key(static::getAllHeaders(), array_flip($interesting));
        }
        return $headers;
    }

    /**
     * @return WebserviceLogger
     */
    protected function getLogger()
    {
        if (is_null($this->logger)) {
            $this->logger = new WebserviceLogger();
        }
        return $this->logger;
    }

}
