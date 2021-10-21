<?php

class ModelExtensionReportNotifyEvents extends Model {

    /*~~~~~~~~~~~~~~~~*/
    /* MODULE IMPORTS */
    /*~~~~~~~~~~~~~~~~*/

    const MODEL_STORE_PATH        = 'setting/store';
    const MODEL_STORE             = 'model_setting_store';

    const MODEL_ORDER_PATH        = 'checkout/order';
    const MODEL_ORDER             = 'model_checkout_order';

    const MODEL_CUSTOMER_PATH     = 'account/customer';
    const MODEL_CUSTOMER          = 'model_account_customer';

    const MODEL_RETURN_PATH       = 'account/return';
    const MODEL_RETURN            = 'model_account_return';

    const MODEL_PRODUCT_PATH      = 'catalog/product';
    const MODEL_PRODUCT           = 'model_catalog_product';

    /*~~~~~~~~~~~~~~~~~~~~~~*/
    /* -- CATALOG MODULE -- */
    /*~~~~~~~~~~~~~~~~~~~~~~*/

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
        $this->load->model(self::MODEL_PRODUCT_PATH);

        $this->load->language('extension/report/' . self::MODULE_NAME);
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
                }; break;
                case 5: {
                    $route .= '__status_change';
                }; break;
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
            $this->_orders[$id] = $this->{self::MODEL_ORDER}->getOrder($id);
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
            $this->_order_products[$order_id] = $this->{self::MODEL_ORDER}->getOrderProducts($order_id);
        }

        return $this->_order_products[$order_id];
    }

    /**
     * @param $id
     *
     * @return array
     */
    private function getProduct($id) {
        if (!array_key_exists($id, array_keys($this->_products))) {
            $this->_products[$id] = $this->{self::MODEL_PRODUCT}->getProduct($id);
        }

        return $this->_products[$id];
    }

    /**
     * @param $id
     *
     * @return array
     */
    private function getReturn($id) {
        if (!array_key_exists($id, array_keys($this->_returns))) {
            $this->_returns[$id] = $this->{self::MODEL_RETURN}->getReturn($id);
        }

        return $this->_returns[$id];
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
                'url'      => $this->config->get('config_secure') ? $this->config->get('site_ssl') : $this->config->get('site_ssl'),
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

                $tags = [];

                $tags = array_merge($tags, $this->fillTagList($store, self::TG_STORE, $eventTags));
                $tags = array_merge($tags, $this->fillTagList($order, self::TG_ORDER, $eventTags));
                $tags = array_merge($tags, $this->fillTagList($order, self::TG_ORDER_P, $eventTags));
                $tags = array_merge($tags, $this->fillTagList($order, self::TG_ORDER_S, $eventTags));
                $tags = array_merge($tags, $this->fillTagList($customer, self::TG_CUSTOMER, $eventTags));

                $sets[] = $tags;

            }; break;
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

            }; break;
            case self::EVENT_USER_NEW: {

                $customer = $this->getCustomer($id);
                $store    = $this->getStore($customer['store_id']);

                $tags = [];

                $tags = array_merge($tags, $this->fillTagList($store, self::TG_STORE, $eventTags));
                $tags = array_merge($tags, $this->fillTagList($customer, self::TG_CUSTOMER, $eventTags));

                $sets[] = $tags;

            }; break;
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

                    $tags = [];

                    $tags = array_merge($tags, $this->fillTagList($store, self::TG_STORE, $eventTags));
                    $tags = array_merge($tags, $this->fillTagList($order, self::TG_ORDER, $eventTags));
                    $tags = array_merge($tags, $this->fillTagList($order, self::TG_ORDER_P, $eventTags));
                    $tags = array_merge($tags, $this->fillTagList($order, self::TG_ORDER_S, $eventTags));
                    $tags = array_merge($tags, $this->fillTagList($return, self::TG_RETURN, $eventTags));
                    $tags = array_merge($tags, $this->fillTagList($product, self::TG_PRODUCT, $eventTags));
                    $tags = array_merge($tags, $this->fillTagList($customer, self::TG_CUSTOMER, $eventTags));

                    $sets[] = $tags;

                }; break;
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

            $response = curl_exec($ch);

            curl_close($ch);
        }
    }
}
