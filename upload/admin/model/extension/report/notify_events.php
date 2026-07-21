<?php

class ModelExtensionReportNotifyEvents extends Model {

    /*~~~~~~~~~~~~~~~~*/
    /* MODULE IMPORTS */
    /*~~~~~~~~~~~~~~~~*/

    const MODEL_STORE_PATH        = 'setting/store';
    const MODEL_STORE             = 'model_setting_store';

    const MODEL_ORDER_PATH        = 'sale/order';
    const MODEL_ORDER             = 'model_sale_order';

    const MODEL_CUSTOMER_PATH     = 'customer/customer';
    const MODEL_CUSTOMER          = 'model_customer_customer';

    const MODEL_RETURN_PATH       = 'sale/return';
    const MODEL_RETURN            = 'model_sale_return';

    const MODEL_RETURN_A_PATH     = 'localisation/return_action';
    const MODEL_RETURN_A          = 'model_localisation_return_action';

    const MODEL_RETURN_R_PATH     = 'localisation/return_reason';
    const MODEL_RETURN_R          = 'model_localisation_return_reason';

    const MODEL_RETURN_S_PATH     = 'localisation/return_status';
    const MODEL_RETURN_S          = 'model_localisation_return_status';

    const MODEL_STOCK_STATUS_PATH = 'localisation/stock_status';
    const MODEL_STOCK_STATUS      = 'model_localisation_stock_status';

    const MODEL_PRODUCT_PATH      = 'catalog/product';
    const MODEL_PRODUCT           = 'model_catalog_product';

    const MODEL_MANUFACTURER_PATH = 'catalog/manufacturer';
    const MODEL_MANUFACTURER      = 'model_catalog_manufacturer';

    /*~~~~~~~~~~~~~~~~~~~~*/
    /* -- ADMIN MODULE -- */
    /*~~~~~~~~~~~~~~~~~~~~*/

    const SCENARIO_CREATE = 'create';
    const SCENARIO_UPDATE = 'update';

    const MODEL_RULES = [
        self::TYPE_CHANNEL => [
            'required' => ['title', 'token'],
            'string'   => ['title', 'token'],
        ],
        self::TYPE_EVENT => [
            'required'     => ['title', 'event_class', 'subject', 'message', 'channel_ids'],
            'string'       => ['title', 'event_class', 'subject', 'message', 'priority'],
            'boolean'      => ['enabled'],
            'array'        => ['channel_ids'],
            'channelExist' => ['channel_ids'],
        ],
        self::TYPE_TEST => [
            'required'     => ['channel_id', 'subject', 'message'],
            'string'       => ['channel_id', 'subject', 'message'],
            'channelExist' => ['channel_id'],
        ],
    ];

    const TYPE_TABLE = [
        self::TYPE_CHANNEL => self::TABLE_CHANNEL,
        self::TYPE_EVENT   => self::TABLE_EVENT,
    ];

    const EVENT_HANDLER = 'extension/report/' . self::MODULE_NAME . '/handleEvent';

    /* Triggers */

    const TRIGGER_CMCO_AOH_A = 'catalog/model/checkout/order/addOrderHistory/after'; // LOW_STOCK, OUT_OF_STOCK, ORDER_NEW, ORDER_STATUS_CHANGE
    const TRIGGER_CMAR_AR_A  = 'catalog/model/account/return/addReturn/after';       // RETURN_NEW
    const TRIGGER_CMAC_AC_A  = 'catalog/model/account/customer/addCustomer/after';   // USER_NEW
    const TRIGGER_AMSR_AR_A  = 'admin/model/sale/return/addReturn/after';            // RETURN_NEW
    const TRIGGER_AMSR_ARH_A = 'admin/model/sale/return/addReturnHistory/after';     // RETURN_STATUS_CHANGE

    public $errors = [];

    /**
     * @param $registry
     */
    public function __construct($registry)
    {
        parent::__construct($registry);

        $this->load->model(self::MODEL_STORE_PATH);
        $this->load->model(self::MODEL_ORDER_PATH);
        $this->load->model(self::MODEL_CUSTOMER_PATH);
        $this->load->model(self::MODEL_RETURN_PATH);
        $this->load->model(self::MODEL_RETURN_A_PATH);
        $this->load->model(self::MODEL_RETURN_R_PATH);
        $this->load->model(self::MODEL_RETURN_S_PATH);
        $this->load->model(self::MODEL_PRODUCT_PATH);
        $this->load->model(self::MODEL_MANUFACTURER_PATH);
        $this->load->model(self::MODEL_STOCK_STATUS_PATH);

        $this->load->language('extension/report/' . self::MODULE_NAME);
    }

    /*~~~~~~~~~~~~~~*/
    /* Installation */
    /*~~~~~~~~~~~~~~*/

    public function createTables() {
        $tableOptions = 'ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci';

        $this->db->query('
            CREATE TABLE IF NOT EXISTS `' . self::TABLE_CHANNEL . '` (
                `id`    INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `title` VARCHAR(255)     NOT NULL,
                `token` VARCHAR(255)     NOT NULL,
                PRIMARY KEY (`id`)
            ) ' . $tableOptions
        );

        $this->db->query('
            CREATE TABLE IF NOT EXISTS `' . self::TABLE_EVENT . '` (
                `id`          INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `enabled`     TINYINT(1)       NOT NULL,
                `title`       VARCHAR(255)     NOT NULL,
                `event_class` VARCHAR(255)     NOT NULL,
                `channel_ids` text,
                `subject`     VARCHAR(255)     NOT NULL,
                `message`     VARCHAR(4096)    NOT NULL,
                `priority`    VARCHAR(255),
                PRIMARY KEY (`id`)
            ) ' . $tableOptions
        );
    }

    public function createTriggers() {
        $this->load->model('setting/event');

        $this->model_setting_event->addEvent(self::MODULE_NAME . '_event__cmco_aoh_a', self::TRIGGER_CMCO_AOH_A, self::EVENT_HANDLER);
        $this->model_setting_event->addEvent(self::MODULE_NAME . '_event__cmar_ar_a',  self::TRIGGER_CMAR_AR_A,  self::EVENT_HANDLER);
        $this->model_setting_event->addEvent(self::MODULE_NAME . '_event__cmac_ac_a',  self::TRIGGER_CMAC_AC_A,  self::EVENT_HANDLER);
        $this->model_setting_event->addEvent(self::MODULE_NAME . '_event__amsr_ar_a',  self::TRIGGER_AMSR_AR_A,  self::EVENT_HANDLER);
        $this->model_setting_event->addEvent(self::MODULE_NAME . '_event__amsr_arh_a', self::TRIGGER_AMSR_ARH_A, self::EVENT_HANDLER);
    }

    public function dropTables() {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "ne_channel`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "ne_event`");
    }

    public function dropTriggers() {
        $this->load->model('setting/event');

        $this->model_setting_event->deleteEventByCode(self::MODULE_NAME . '_event__cmco_aoh_a');
        $this->model_setting_event->deleteEventByCode(self::MODULE_NAME . '_event__cmar_ar_a');
        $this->model_setting_event->deleteEventByCode(self::MODULE_NAME . '_event__cmac_ac_a');
        $this->model_setting_event->deleteEventByCode(self::MODULE_NAME . '_event__amsr_ar_a');
        $this->model_setting_event->deleteEventByCode(self::MODULE_NAME . '_event__amsr_arh_a');
    }

    /*~~~~~~~~*/
    /* Common */
    /*~~~~~~~~*/

    public function getEventsTagsWithLabels($event_class) {
        $result = [];

        foreach ($this->getEventsTags($event_class) as $group => $tags) {

            $tagsWithLabels = [];

            foreach ($tags as $tag => $property) {
                $key = $group . '_' . $tag;
                $tagsWithLabels[$key] = $this->language->get('tl_' . $key);
            }

            $result[$group] = [
                'group' => $this->language->get('gtl_' . $group),
                'tags' => $tagsWithLabels,
            ];
        }

        return $result;
    }

    public function getErrors() {
        return $this->errors;
    }

    public function getPriorityList() {
        return [
            self::PRIORITY_HIGHEST => $this->language->get('priority_highest'),
            self::PRIORITY_HIGH    => $this->language->get('priority_high'),
            self::PRIORITY_NORMAL  => $this->language->get('priority_normal'),
            self::PRIORITY_LOW     => $this->language->get('priority_low'),
            self::PRIORITY_LOWEST  => $this->language->get('priority_lowest'),
        ];
    }

    public function getEventClasses() {
        return [
            [
                'title' => $this->language->get(self::EVENT_GROUP_PRODUCT),
                'items' => [
                    [
                        'class' => self::EVENT_PRODUCT_OUT_OF_STOCK,
                        'title' => $this->language->get(self::EVENT_PRODUCT_OUT_OF_STOCK),
                    ],
                ],
            ],
            [
                'title' => $this->language->get(self::EVENT_GROUP_ORDER),
                'items' => [
                    [
                        'class' => self::EVENT_ORDER_NEW,
                        'title' => $this->language->get(self::EVENT_ORDER_NEW),
                    ],
                    [
                        'class' => self::EVENT_ORDER_STATUS_CHANGE,
                        'title' => $this->language->get(self::EVENT_ORDER_STATUS_CHANGE),
                    ],
                    [
                        'class' => self::EVENT_RETURN_NEW,
                        'title' => $this->language->get(self::EVENT_RETURN_NEW),
                    ],
                    [
                        'class' => self::EVENT_RETURN_STATUS_CHANGE,
                        'title' => $this->language->get(self::EVENT_RETURN_STATUS_CHANGE),
                    ],
                ],
            ],
            [
                'title' => $this->language->get(self::EVENT_GROUP_USER),
                'items' => [
                    [
                        'class' => self::EVENT_USER_NEW,
                        'title' => $this->language->get(self::EVENT_USER_NEW),
                    ],
                ],
            ],
        ];
    }

    public function getEventDefaultSubject($event_class) {
        return $this->language->get(self::EVENT_USER_NEW . '_ds');
    }

    public function getEventDefaultMessage($event_class) {
        return $this->language->get(self::EVENT_USER_NEW . '_dm');
    }

    private function getRequiredFields($type) {
        if (!array_key_exists($type, self::MODEL_RULES)) {
            return [];
        }

        if (!array_key_exists('required', self::MODEL_RULES[$type])) {
            return [];
        }

        return self::MODEL_RULES[$type]['required'];
    }

    private function getSafeAttributes($type) {

        $attributes = [];

        foreach (self::MODEL_RULES[$type] as $fields) {
            foreach ($fields as $field) {
                $attributes[$field] = true;
            }
        }

        return array_keys($attributes);
    }

    private function validateModel($type, $data) {

        foreach (self::MODEL_RULES[$type] as $rule => $fields) {
            foreach ($fields as $field) {
                switch ($rule) {
                    case 'required': {
                        if (!array_key_exists($field, $data) || empty($data[$field])) {
                            $this->errors[$field] = $this->language->get('error_required_params');
                        }
                    } break;
                    case 'string': {
                        if (array_key_exists($field, $data) && !is_string($data[$field])) {
                            $this->errors[$field] = $this->language->get('error_not_a_string');
                        }
                    } break;
                    case 'integer': {
                        if (array_key_exists($field, $data) && !is_int($data[$field])) {
                            $this->errors[$field] = $this->language->get('error_not_an_integer');
                        }
                    } break;
                    case 'boolean': {
                        if (array_key_exists($field, $data) && !is_bool($data[$field])) {
                            $this->errors[$field] = $this->language->get('error_not_a_boolean');
                        }
                    } break;
                    case 'array': {
                        if (array_key_exists($field, $data) && !is_array($data[$field])) {
                            $this->errors[$field] = $this->language->get('error_not_an_array');
                        }
                    } break;
                    case 'channelExist': {
                        $channelList = $this->getChannels();
                        $channel_ids = array_column($channelList, 'id');

                        if (is_array($data[$field])) {
                            $notExist = count($data[$field]) != count(array_intersect($channel_ids, $data[$field]));
                        } else {
                            $notExist = !in_array($data[$field], $channel_ids);
                        }

                        if (array_key_exists($field, $data) && $notExist) {
                            $this->errors['_popup_error'] = $this->language->get('channel_not_found');
                        }
                    } break;
                }
            }
        }

        return $this->errors;
    }

    private function getModelsTotal($table) {
        $query = $this->db->query('SELECT COUNT(*) AS total FROM ' . $table);

        return $query->row['total'];
    }

    private function getModels($table, $limit = null, $offset = null) {
        $sql = 'SELECT * FROM ' . $table . ' ORDER BY id';

        if ($limit) {
            $sql .= ' LIMIT ' . (int)$limit;
        }

        if ($offset) {
            $sql .= ' OFFSET ' . (int)$offset;
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    private function getModel($table, $id) {
        $query = $this->db->query('SELECT * FROM ' . $table . ' WHERE `id` = ' . (int)$id);

        $items = $query->rows;

        if (empty($items)) {
            $this->errors['common'] = $this->language->get('object_not_found');

            return false;
        }

        return $items[0];
    }

    private function updateModel($scenario, $type, $data, $id = null) {

        if ($scenario == self::SCENARIO_UPDATE && $id == null) {
            $this->errors['id'] = $this->language->get('error_required_params');
            return false;
        }

        $field_set = [];

        foreach ($this->getSafeAttributes($type) as $attribute) {

            if (!array_key_exists($attribute, $data)) {
                continue;
            }

            $value = $data[$attribute];

            if (is_string($value)) {
                $value = strip_tags($value, '<b><i><a>');
                $value = '\'' . $this->db->escape(trim($value)) . '\'';
            }

            if (is_bool($value)) {
                $value = $value ? 'TRUE' : 'FALSE';
            }

            if (is_array($value)) {
                $value = '\'' . json_encode($value, JSON_UNESCAPED_UNICODE) . '\'';
            }

            $field_set[] = '`' . $attribute . '` = ' .  $value;
        }

        if ($scenario == self::SCENARIO_CREATE) {
            $sql = 'INSERT INTO `' . self::TYPE_TABLE[$type] . '` SET ' . implode(', ', $field_set);
        } else {
            $sql = 'UPDATE `' . self::TYPE_TABLE[$type] . '` SET ' . implode(', ', $field_set) . ' WHERE `id` = ' . (int)$id;
        }

        $this->db->query($sql);

        return $this->db->getLastId();
    }

    private function deleteModel($type, $id) {
        $this->db->query('DELETE FROM `' . self::TYPE_TABLE[$type] . '` WHERE `id` = ' . (int)$id);

        return (bool)$this->db->countAffected();
    }

    private function formatData($type, $data) {

        switch ($type) {
            case self::TYPE_EVENT: {
                $data['enabled']     = array_key_exists('enabled', $data);
                $data['channel_ids'] = array_key_exists('channel_ids', $data) && is_array($data['channel_ids']) ?
                    array_keys($data['channel_ids']) : [];
            }
                break;
        }

        return $data;
    }

    /*~~~~~~~~*/
    /* Events */
    /*~~~~~~~~*/

    public function getEventDefaults($event_class) {
        return [
            'title'       => htmlspecialchars($this->language->get($event_class)),
            'subject'     => htmlspecialchars($this->language->get($event_class . '_ds')),
            'message'     => htmlspecialchars($this->language->get($event_class . '_dm')),
            'priority'    => self::PRIORITY_NORMAL,
            'event_class' => $event_class,
            'enabled'     => true,
        ];
    }

    public function getEventsTotal() {
        return $this->getModelsTotal(self::TABLE_EVENT);
    }

    public function getEvents($limit = null, $offset = null) {
        return $this->getModels(self::TABLE_EVENT, $limit, $offset);
    }

    public function getEvent($id) {
        return $this->getModel(self::TABLE_EVENT, $id);
    }

    public function getEventRequiredFields() {
        return $this->getRequiredFields(self::TYPE_EVENT);
    }

    public function validateEvent($data) {
        return $this->validateModel(self::TYPE_EVENT, $data);
    }

    public function formatEventData($data) {
        return $this->formatData(self::TYPE_EVENT, $data);
    }

    public function createEvent($data) {
        return $this->updateModel(self::SCENARIO_CREATE, self::TYPE_EVENT, $data);
    }

    public function updateEvent($id, $data) {
        return $this->updateModel(self::SCENARIO_UPDATE, self::TYPE_EVENT, $data, $id);
    }

    public function deleteEvent($id) {
        return $this->deleteModel(self::TYPE_EVENT, $id);
    }

    /*~~~~~~~~~~*/
    /* Channels */
    /*~~~~~~~~~~*/

    public function getChannelsTotal() {
        return $this->getModelsTotal(self::TABLE_CHANNEL);
    }

    public function getChannels($limit = null, $offset = null) {
        return $this->getModels(self::TABLE_CHANNEL, $limit, $offset);
    }

    public function getChannel($id) {
        return $this->getModel(self::TABLE_CHANNEL, $id);
    }

    public function getChannelRequiredFields() {
        return $this->getRequiredFields(self::TYPE_CHANNEL);
    }

    public function validateChannel($data) {
        return $this->validateModel(self::TYPE_CHANNEL, $data);
    }

    public function formatChannelData($data) {
        return $this->formatData(self::TYPE_CHANNEL, $data);
    }

    public function createChannel($data) {
        return $this->updateModel(self::SCENARIO_CREATE, self::TYPE_CHANNEL, $data);
    }

    public function updateChannel($id, $data) {
        return $this->updateModel(self::SCENARIO_UPDATE, self::TYPE_CHANNEL, $data, $id);
    }

    public function deleteChannel($id) {
        // Todo: make via table
        $events = $this->getEvents();

        foreach ($events as $event) {
            $channel_ids = json_decode($event['channel_ids'], true);

            if (!in_array($id, $channel_ids)) {
                continue;
            }

            $event['channel_ids'] = array_diff($channel_ids, [$id]);

            $this->updateEvent($event['id'], $event);
        }

        return $this->deleteModel(self::TYPE_CHANNEL, $id);
    }

    /*~~~~~~*/
    /* Test */
    /*~~~~~~*/

    public function validateTest($data) {
        return $this->validateModel(self::TYPE_TEST, $data);
    }

    public function getTestRequiredFields() {
        return $this->getRequiredFields(self::TYPE_TEST);
    }

    /**
     * @param $data
     * @return bool
     */
    public function sendTestMessage($data) {

        $channels = [$data['channel_id']];

        $message = [
            'channels' => $channels,
            'title'    => $data['subject'],
            'text'     => $data['message'],
        ];

        $this->getChannelTokens($channels);

        $this->sendNotification($message);

        return true;
    }

    /*~~~~~~~~~~~~~~~~~~~~~*/
    /* -- COMMON MODULE -- */
    /*~~~~~~~~~~~~~~~~~~~~~*/

    const MODULE_NAME    = 'notify_events';
    const NE_WEBHOOK_URL = 'https://notify.events/api/v1/channel/source/%s/execute';

    const TYPE_CHANNEL   = 'channel';
    const TYPE_EVENT     = 'event';
    const TYPE_TEST      = 'test';

    const TABLE_CHANNEL  = DB_PREFIX . 'ne_' . self::TYPE_CHANNEL;
    const TABLE_EVENT    = DB_PREFIX . 'ne_' . self::TYPE_EVENT;

    /* Tag Groups */

    const TG_STORE    = 'store';
    const TG_ORDER    = 'order';
    const TG_ORDER_P  = 'order_payment';
    const TG_ORDER_S  = 'order_shipping';
    const TG_RETURN   = 'return';
    const TG_PRODUCT  = 'product';
    const TG_CUSTOMER = 'customer';

    /* Priorities */

    const PRIORITY_HIGHEST = 'highest';
    const PRIORITY_HIGH    = 'high';
    const PRIORITY_NORMAL  = 'normal';
    const PRIORITY_LOW     = 'low';
    const PRIORITY_LOWEST  = 'lowest';

    /* Events */

    const EVENT_GROUP_PRODUCT        = 'event_group_product';
    const EVENT_GROUP_ORDER          = 'event_group_order';
    const EVENT_GROUP_USER           = 'event_group_user';

    const EVENT_USER_NEW             = 'user_new';
    const EVENT_ORDER_NEW            = 'order_new';
    const EVENT_ORDER_STATUS_CHANGE  = 'order_status_change';
    const EVENT_RETURN_NEW           = 'return_new';
    const EVENT_RETURN_STATUS_CHANGE = 'return_status_change';
    const EVENT_PRODUCT_OUT_OF_STOCK = 'product_out_of_stock';

    const ROUTE_EVENT_CLASSES = [
        'checkout/order/addOrderHistory__new_order' => [
            self::EVENT_PRODUCT_OUT_OF_STOCK,
            self::EVENT_ORDER_NEW,
        ],
        'checkout/order/addOrderHistory__status_change' => [
            self::EVENT_ORDER_STATUS_CHANGE,
        ],
        'account/customer/addCustomer' => [
            self::EVENT_USER_NEW,
        ],
        'account/return/addReturn' => [
            self::EVENT_RETURN_NEW,
        ],
        'sale/return/addReturn' => [
            self::EVENT_RETURN_NEW,
        ],
        'sale/return/addReturnHistory' => [
            self::EVENT_RETURN_STATUS_CHANGE,
        ],
    ];

    private $_channel_tokens  = [];

    private $_stores          = [];

    private $_orders          = [];
    private $_order_products  = [];

    private $_products        = [];
    private $_manufacturers   = [];
    private $_stock_statuses  = [];

    private $_returns         = [];
    private $_returnActions   = [];
    private $_returnReasons   = [];
    private $_returnStatuses  = [];

    private $_customers       = [];

    /*~~~~~~~~~~~~~~~~~~~~~~~~~~*/
    /* Event handling functions */
    /*~~~~~~~~~~~~~~~~~~~~~~~~~~*/

    /**
     * @param $route
     * @param $args
     * @param $id
     */
    public function runEventHandling($route, $args, $id) {
        // Todo: Google for better solution
        if ($route == 'checkout/order/addOrderHistory') {
            switch (count($args)) {
                case 2: {
                    $route .= '__new_order';
                }
                    break;
                case 5: {
                    $route .= '__status_change';
                }
                    break;
            }
        }

        $routeEventClasses = $this->getRouteEventClasses();

        if (!array_key_exists($route, $routeEventClasses)) {
            return;
        }

        $subscribedEvents = $this->getSubscribedEvents();

        $subscribedClasses = [];
        foreach ($subscribedEvents as $event) {
            $subscribedClasses[$event['event_class']] = true;
        }

        $currentEventClasses = $routeEventClasses[$route];
        $classesToHandle     = array_intersect(array_keys($subscribedClasses), $currentEventClasses);

        if (empty($classesToHandle)) {
            return;
        }

        foreach ($subscribedEvents as $event) {
            $eventClass = $event['event_class'];

            if (!in_array($eventClass, $classesToHandle)) {
                continue;
            }

            $sets = $this->prepareSetsOfTags($eventClass, $args, $id);

            foreach ($sets as $tags) {
                $this->handleEvent($eventClass, $tags);
            }
        }
    }

    /**
     * @param string $eventClass
     * @param array $tags
     * @return bool
     */
    public function handleEvent($eventClass, $tags) {

        $events = $this->getEventsByClassName($eventClass);

        $messages    = [];
        $channel_ids = [];

        foreach ($events as $event) {

            $target_ids = (array)json_decode($event['channel_ids'], true);

            if (empty($target_ids)) {
                continue;
            }

            $channel_ids = array_merge($channel_ids, $target_ids);

            $messages[] = [
                'title'    => $this->prepareTemplate($event['subject'], $tags),
                'text'     => nl2br($this->prepareTemplate($event['message'], $tags)),
                'priority' => $event['priority'],
                'channels' => $target_ids,
            ];

        }

        if (empty($messages)) {
            return true;
        }

        $this->getChannelTokens(array_unique($channel_ids));

        foreach ($messages as $message) {
            $this->sendNotification($message);
        }

        return true;
    }

    /**
     * @return array
     */
    public function getSubscribedEvents() {
        $events = $this->db->query('SELECT * FROM ' . self::TABLE_EVENT . ' WHERE enabled = 1')->rows;

        if (!$events) {
            return [];
        }

        return $events;
    }

    /**
     * @param string $eventClass
     * @return array
     */
    private function getEventsByClassName($eventClass) {
        $events = $this->db->query('
            SELECT * FROM ' . self::TABLE_EVENT . ' 
            WHERE event_class = \'' . $this->db->escape($eventClass) . '\' AND enabled = 1
        ')->rows;

        if (!$events) {
            return [];
        }

        return $events;
    }

    /**
     * @param $id
     *
     * @return array
     */
    private function getCustomer($id) {
        if (!array_key_exists($id, array_keys($this->_customers))) {
            $this->_customers[$id] = $this->{self::MODEL_CUSTOMER}->getCustomer($id);
        }

        return $this->_customers[$id];
    }

    /**
     * @param $id
     *
     * @return array
     */
    private function getOrder($id) {
        if (!array_key_exists($id, array_keys($this->_orders))) {
            $order = $this->{self::MODEL_ORDER}->getOrder($id);

            // Format the order total using the store's configured decimal places.
            if (!empty($order)) {
                $order['total'] = $this->currency->format($order['total'], $order['currency_code'], $order['currency_value']);
            }

            $this->_orders[$id] = $order;
        }

        return $this->_orders[$id];
    }

    /**
     * @param $order_id
     *
     * @return array
     */
    private function getOrderProducts($order_id) {
        if (!array_key_exists($order_id, array_keys($this->_order_products))) {
            $model = $this->{self::MODEL_ORDER};

            // OpenCart 4 renamed getOrderProducts() to getProducts(); support both.
            // Models are wrapped in a Proxy, so probe with isset() (method_exists() fails on Proxy).
            $this->_order_products[$order_id] = isset($model->getProducts)
                ? $model->getProducts($order_id)
                : $model->getOrderProducts($order_id);
        }

        return $this->_order_products[$order_id];
    }

    /**
     * @param $order_id
     * @param $order_product_id
     *
     * @return array
     */
    private function getOrderOptions($order_id, $order_product_id) {
        $model = $this->{self::MODEL_ORDER};

        // OpenCart 4 renamed getOrderOptions() to getOptions(); support both.
        // Models are wrapped in a Proxy, so probe with isset() (method_exists() fails on Proxy).
        return isset($model->getOptions)
            ? $model->getOptions($order_id, $order_product_id)
            : $model->getOrderOptions($order_id, $order_product_id);
    }

    /**
     * Build a multi-line list of order products (name, quantity, price) with their selected options.
     *
     * @param $order_id
     * @param $order
     *
     * @return string
     */
    private function getOrderProductsLists($order_id, $order) {
        $products = $this->getOrderProducts($order_id);

        if (empty($products)) {
            return ['short' => '', 'full' => ''];
        }

        // Fetch SKUs for every ordered product in a single query (no per-item lookups).
        $skus = $this->getProductSkus($products);

        $short_items = [];
        $full_items  = [];
        $number      = 0;

        foreach ($products as $product) {
            $number++;

            $price = $this->currency->format($product['price'], $order['currency_code'], $order['currency_value']);

            // Model[ / SKU] — the " / SKU" part is appended only when a SKU is set.
            $model_sku = $product['model'];

            if (!empty($skus[$product['product_id']])) {
                $model_sku .= ' / ' . $skus[$product['product_id']];
            }

            // Shared product line: "N) QTY x NAME (MODEL[ / SKU]) - PRICE".
            $product_line = sprintf('%d) %d x %s (%s) - %s', $number, (int)$product['quantity'], $product['name'], $model_sku, $price);

            // Short: just the product line.
            $short_items[] = $product_line;

            // Full: product line followed by each option as a "- " line.
            $full_lines = [$product_line];

            foreach ($this->getOrderOptions($order_id, $product['order_product_id']) as $option) {
                $full_lines[] = sprintf('- %s: %s', $option['name'], $option['value']);
            }

            $full_items[] = implode(PHP_EOL, $full_lines);
        }

        return [
            'short' => implode(PHP_EOL, $short_items),
            'full'  => implode(PHP_EOL . PHP_EOL, $full_items),
        ];
    }

    /**
     * Batch-fetch SKUs for the given order products in a single query.
     *
     * @param $products
     *
     * @return array  map of product_id => sku
     */
    private function getProductSkus($products) {
        $ids = [];

        foreach ($products as $product) {
            if (!empty($product['product_id'])) {
                $ids[(int)$product['product_id']] = (int)$product['product_id'];
            }
        }

        if (empty($ids)) {
            return [];
        }

        $rows = $this->db->query('
            SELECT `product_id`, `sku` FROM `' . DB_PREFIX . 'product`
            WHERE `product_id` IN (' . implode(', ', $ids) . ')
        ')->rows;

        $skus = [];

        foreach ($rows as $row) {
            $skus[$row['product_id']] = $row['sku'];
        }

        return $skus;
    }

    /**
     * @param $id
     *
     * @return array
     */
    private function getProduct($id) {
        if (!array_key_exists($id, array_keys($this->_products))) {
            $product = $this->{self::MODEL_PRODUCT}->getProduct($id);

            if (!empty($manufacturer = $this->getManufacturer($product['manufacturer_id']))) {
                $product['manufacturer'] = $manufacturer['name'];
            }

            if (!empty($stockStatus = $this->getStockStatus($product['stock_status_id']))) {
                $product['stock_status'] = $stockStatus['name'];
            }

            // Format the product price using the store's configured decimal places.
            $product['price'] = $this->currency->format($product['price'], $this->config->get('config_currency'));

            $this->_products[$id] = $product;
        }

        return $this->_products[$id];
    }

    /**
     * @param $id
     *
     * @return array
     */
    private function getManufacturer($id) {
        if (!array_key_exists($id, array_keys($this->_manufacturers))) {
            $this->_manufacturers[$id] = $this->{self::MODEL_MANUFACTURER}->getManufacturer($id);
        }

        return $this->_manufacturers[$id];
    }

    /**
     * @param $id
     *
     * @return array
     */
    private function getStockStatus($id) {
        if (!array_key_exists($id, array_keys($this->_stock_statuses))) {
            $this->_stock_statuses[$id] = $this->{self::MODEL_STOCK_STATUS}->getStockStatus($id);
        }

        return $this->_stock_statuses[$id];
    }

    /**
     * @param $id
     *
     * @return array
     */
    private function getReturn($id) {
        if (!array_key_exists($id, array_keys($this->_returns))) {
            $return = $this->{self::MODEL_RETURN}->getReturn($id);

            if (!empty($return_action = $this->getReturnAction($return['return_action_id']))) {
                $return['action'] = $return_action['name'];
            }

            if (!empty($return_reason = $this->getReturnReason($return['return_reason_id']))) {
                $return['reason'] = $return_reason['name'];
            }

            if (!empty($return_status = $this->getReturnStatus($return['return_status_id']))) {
                $return['status'] = $return_status['name'];
            }

            $this->_returns[$id] = $return;
        }

        return $this->_returns[$id];
    }

    /**
     * @param $id
     *
     * @return array
     */
    private function getReturnAction($id) {
        if (!array_key_exists($id, array_keys($this->_returnActions))) {
            $this->_returnActions[$id] = $this->{self::MODEL_RETURN_A}->getReturnAction($id);
        }

        return $this->_returnActions[$id];
    }

    /**
     * @param $id
     *
     * @return array
     */
    private function getReturnReason($id) {
        if (!array_key_exists($id, array_keys($this->_returnReasons))) {
            $this->_returnReasons[$id] = $this->{self::MODEL_RETURN_R}->getReturnReason($id);
        }

        return $this->_returnReasons[$id];
    }

    /**
     * @param $id
     *
     * @return array
     */
    private function getReturnStatus($id) {
        if (!array_key_exists($id, array_keys($this->_returnStatuses))) {
            $this->_returnStatuses[$id] = $this->{self::MODEL_RETURN_S}->getReturnStatus($id);
        }

        return $this->_returnStatuses[$id];
    }

    /**
     * @param $id
     *
     * @return array
     */
    private function getStore($id) {
        if (!array_key_exists(0, $this->_stores)) {
            // Smile and wave, boys. Smile and wave…
            $this->_stores[0] = [
                'store_id' => 0,
                'name'     => $this->config->get('config_name'),
                'url'      => $this->config->get('config_secure') ? HTTPS_SERVER : HTTP_SERVER,
            ];
        }

        if (!array_key_exists($id, array_keys($this->_stores))) {
            $stores = $this->{self::MODEL_STORE}->getStores();

            foreach ($stores as $store) {
                $this->_stores[$store['store_id']] = $store;
            }
        }

        return $this->_stores[$id];
    }

    /**
     * @return array
     */
    private function getRouteEventClasses() {
        return self::ROUTE_EVENT_CLASSES;
    }

    /**
     * @param null $event_class
     * @return array
     */
    public function getEventsTags($event_class = null) {

        $storeTags = [
            'name' => 'name',
            'url'  => 'url',
        ];

        $orderTags = [
            'id'         => 'order_id',
            'total'      => 'total',
            'status'     => 'order_status',
            'currency'   => 'currency_code',
            'created_at' => 'date_added',
            'products'              => 'products',
            'products_with_options' => 'products_with_options',
        ];

        $orderPaymentTags = [
            'firstname' => 'payment_firstname',
            'lastname'  => 'payment_lastname',
            'company'   => 'payment_company',
            'postcode'  => 'payment_postcode',
            'city'      => 'payment_city',
            'zone'      => 'payment_zone',
            'country'   => 'payment_country',
            'method'    => 'payment_method',
        ];

        $orderShippingTags = [
            'firstname' => 'shipping_firstname',
            'lastname'  => 'shipping_lastname',
            'company'   => 'shipping_company',
            'postcode'  => 'shipping_postcode',
            'city'      => 'shipping_city',
            'zone'      => 'shipping_zone',
            'country'   => 'shipping_country',
            'method'    => 'shipping_method',
        ];

        $returnTags = [
            'id'         => 'return_id',
            'reason'     => 'reason',
            'action'     => 'action',
            'status'     => 'status',
            'created_at' => 'date_added',
            'comment'    => 'comment',
        ];

        $productTags = [
            'id'           => 'product_id',
            'name'         => 'name',
            'model'        => 'model',
            'quantity'     => 'quantity',
            'stock_status' => 'stock_status',
            'manufacturer' => 'manufacturer',
            'price'        => 'price',
        ];

        $customerTags = [
            'id'           => 'customer_id',
            'firstname'    => 'firstname',
            'lastname'     => 'lastname',
            'email'        => 'email',
            'telephone'    => 'telephone',
        ];

        $tags = [
            self::EVENT_ORDER_NEW => [
                self::TG_STORE    => $storeTags,
                self::TG_ORDER    => $orderTags,
                self::TG_ORDER_P  => $orderPaymentTags,
                self::TG_ORDER_S  => $orderShippingTags,
                self::TG_CUSTOMER => $customerTags,
            ],
            self::EVENT_USER_NEW => [
                self::TG_STORE    => $storeTags,
                self::TG_CUSTOMER => $customerTags,
            ],
            self::EVENT_ORDER_STATUS_CHANGE => [
                self::TG_STORE    => $storeTags,
                self::TG_ORDER    => $orderTags,
                self::TG_ORDER_P  => $orderPaymentTags,
                self::TG_ORDER_S  => $orderShippingTags,
                self::TG_CUSTOMER => $customerTags,
            ],
            self::EVENT_RETURN_NEW => [
                self::TG_STORE    => $storeTags,
                self::TG_ORDER    => $orderTags,
                self::TG_ORDER_P  => $orderPaymentTags,
                self::TG_ORDER_S  => $orderShippingTags,
                self::TG_RETURN   => $returnTags,
                self::TG_PRODUCT  => $productTags,
                self::TG_CUSTOMER => $customerTags,
            ],
            self::EVENT_RETURN_STATUS_CHANGE => [
                self::TG_STORE    => $storeTags,
                self::TG_ORDER    => $orderTags,
                self::TG_ORDER_P  => $orderPaymentTags,
                self::TG_ORDER_S  => $orderShippingTags,
                self::TG_RETURN   => $returnTags,
                self::TG_PRODUCT  => $productTags,
                self::TG_CUSTOMER => $customerTags,
            ],
            self::EVENT_PRODUCT_OUT_OF_STOCK => [
                self::TG_STORE   => $storeTags,
                self::TG_PRODUCT => $productTags,
            ],
        ];

        if ($event_class) {
            return $tags[$event_class];
        }

        return $tags;
    }

    /**
     * @param $object
     * @param $group
     * @param $tags
     * @return array
     */
    private function fillTagList($object, $group, $tags) {
        $result = [];

        foreach ($tags[$group] as $key => $property) {
            $result[$group . '_' . $key] = $object[$property];
        }

        return $result;
    }

    /**
     * @param string  $event_class
     * @param array   $args
     * @param integer $id
     *
     * @return array
     */
    private function prepareSetsOfTags($event_class, $args, $id) {

        $sets = [];
        $eventTags = $this->getEventsTags($event_class);

        switch ($event_class) {
            case self::EVENT_ORDER_NEW:
            case self::EVENT_ORDER_STATUS_CHANGE: {

                $order_id = $args[0];

                $order    = $this->getOrder($order_id);
                $store    = $this->getStore($order['store_id']);
                $customer = $this->getCustomer($order['customer_id']);

                $products_lists = $this->getOrderProductsLists($order_id, $order);
                $order['products'] = $products_lists['short'];
                $order['products_with_options'] = $products_lists['full'];

                $tags = [];

                $tags = array_merge($tags, $this->fillTagList($store, self::TG_STORE, $eventTags));
                $tags = array_merge($tags, $this->fillTagList($order, self::TG_ORDER, $eventTags));
                $tags = array_merge($tags, $this->fillTagList($order, self::TG_ORDER_P, $eventTags));
                $tags = array_merge($tags, $this->fillTagList($order, self::TG_ORDER_S, $eventTags));
                $tags = array_merge($tags, $this->fillTagList($customer, self::TG_CUSTOMER, $eventTags));

                $sets[] = $tags;

            }
            break;
            case self::EVENT_PRODUCT_OUT_OF_STOCK: {

                $order_id = $args[0];

                $order    = $this->getOrder($order_id);
                $store    = $this->getStore($order['store_id']);
                $products = $this->getOrderProducts($order_id);

                foreach ($products as $row) {
                    $tags = [];
                    $product = $this->getProduct($row['product_id']);

                    if ($product['quantity'] > 0) {
                        continue;
                    }

                    $tags = array_merge($tags, $this->fillTagList($store, self::TG_STORE, $eventTags));
                    $tags = array_merge($tags, $this->fillTagList($product, self::TG_PRODUCT, $eventTags));

                    $sets[] = $tags;
                }

            }
                break;
            case self::EVENT_USER_NEW: {

                $customer = $this->getCustomer($id);
                $store    = $this->getStore($customer['store_id']);

                $tags = [];

                $tags = array_merge($tags, $this->fillTagList($store, self::TG_STORE, $eventTags));
                $tags = array_merge($tags, $this->fillTagList($customer, self::TG_CUSTOMER, $eventTags));

                $sets[] = $tags;

            }
                break;
            case self::EVENT_RETURN_NEW:
            case self::EVENT_RETURN_STATUS_CHANGE:
            {
                if (!$id) {
                    $id = $args[0];
                }

                $return   = $this->getReturn($id);
                $order    = $this->getOrder($return['order_id']);
                $store    = $this->getStore($order['store_id']);
                $product  = $this->getProduct($return['product_id']);
                $customer = $this->getCustomer($return['customer_id']);

                $products_lists = $this->getOrderProductsLists($order['order_id'], $order);
                $order['products'] = $products_lists['short'];
                $order['products_with_options'] = $products_lists['full'];

                $tags = [];

                $tags = array_merge($tags, $this->fillTagList($store, self::TG_STORE, $eventTags));
                $tags = array_merge($tags, $this->fillTagList($order, self::TG_ORDER, $eventTags));
                $tags = array_merge($tags, $this->fillTagList($order, self::TG_ORDER_P, $eventTags));
                $tags = array_merge($tags, $this->fillTagList($order, self::TG_ORDER_S, $eventTags));
                $tags = array_merge($tags, $this->fillTagList($return, self::TG_RETURN, $eventTags));
                $tags = array_merge($tags, $this->fillTagList($product, self::TG_PRODUCT, $eventTags));
                $tags = array_merge($tags, $this->fillTagList($customer, self::TG_CUSTOMER, $eventTags));

                $sets[] = $tags;

            }
            break;
        }

        return $sets;
    }

    /**
     * @param array $ids
     * @return array
     */
    private function getChannelTokens($ids) {
        $channels = $this->db->query('
            SELECT id, token FROM ' . self::TABLE_CHANNEL . '
            WHERE id IN (' . implode(', ', $ids) . ') 
        ')->rows;

        if (!$channels) {
            return [];
        }

        $this->_channel_tokens = [];

        foreach ($channels as $channel) {
            $this->_channel_tokens[$channel['id']] = $channel['token'];
        }

        return $this->_channel_tokens;
    }

    /**
     * @param string $template
     * @param array $tags
     * @return string
     */
    private function prepareTemplate($template, $tags) {

        if (preg_match_all('#\[([a-z0-9-_]+)\]#i', $template, $matches) === false) {
            return $template;
        }

        $used_tags = array_unique($matches[1]);

        foreach ($used_tags as $tag) {
            $template = str_replace('[' . $tag . ']', $tags[$tag], $template);
        }

        return $template;
    }

    /**
     * @param array $message
     */
    private function sendNotification($message) {

        foreach ($message['channels'] as $channel_id) {
            $token = $this->_channel_tokens[$channel_id];

            $message['title'] = html_entity_decode($message['title']);
            $message['text']  = html_entity_decode($message['text']);

            $url = sprintf(self::NE_WEBHOOK_URL, $token);

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($message));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            curl_exec($ch);

            curl_close($ch);
        }
    }
}
