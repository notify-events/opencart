<?php
class ControllerExtensionReportNotifyEvents extends Controller
{
    const MODULE_NAME         = 'notify_events';

    const PAGE_INDEX          = 'index';

    const PAGE_CHANNELS       = 'channels';
    const PAGE_CHANNEL_FORM   = 'channel_form';
    const PAGE_CHANNEL_DELETE = 'channel_delete';

    const PAGE_EVENTS         = 'events';
    const PAGE_EVENT_FORM     = 'event_form';
    const PAGE_EVENT_DELETE   = 'event_delete';

    const PAGE_TEST           = 'test';

    public function __construct($registry) {
        parent::__construct($registry);

        $this->load->language('extension/report/' . self::MODULE_NAME);
    }

    /*~~~~~~~~~~~~~~~~~~~~~~~~~*/
    /* Admin interface actions */
    /*~~~~~~~~~~~~~~~~~~~~~~~~~*/

    public function index() {
        $this->renderOutput(self::PAGE_INDEX, [
            'channels_link' => $this->urlToPage(self::PAGE_CHANNELS),
            'events_link'   => $this->urlToPage(self::PAGE_EVENTS),
        ]);
    }

    public function events() {

        $this->load->model('extension/report/' . self::MODULE_NAME);

        $layout_data['add_btn_target_modal']  = '#eventAddModal';
        $layout_data['add_btn_text']          = $this->language->get('common_add_btn_text');
        $layout_data['add_event_modal_title'] = $this->language->get('add_event_modal_title');

        $layout_data['delete_target_form']    = '#form-events';
        $layout_data['delete_btn_text']       = $this->language->get('common_delete_btn_text');
        $layout_data['delete_confirm']        = $this->language->get('common_delete_confirm_text');

        $page_data['panel_title'] = $this->language->get('event_list');
        $page_data['delete']      = $this->urlToPage(self::PAGE_EVENT_DELETE);

        // Подгрузим доступные типы событий
        $page_data['event_classes'] = $this->model_extension_report_notify_events->getEventClasses();

        foreach ($page_data['event_classes'] as &$group) {
            foreach ($group['items'] as &$class_item) {
                $class_item['link'] = $this->urlToPage(self::PAGE_EVENT_FORM, ['event_class' => $class_item['class']]);
            }
        }

        $query_params = $this->request->get;

        $page   = array_key_exists('page',  $query_params) ? $query_params['page']  : 1;
        $limit  = $this->config->get('config_limit_admin');
        $offset = $page > 1 ? $limit * ($page - 1) : 0;

        $page_data['total'] = $this->model_extension_report_notify_events->getEventsTotal();
        $page_data['items'] = $this->model_extension_report_notify_events->getEvents($limit, $offset);

        // Подгрузим каналы
        $channels = $this->model_extension_report_notify_events->getChannels();
        $channel_labels = array_column($channels, 'title', 'id');

        foreach ($page_data['items'] as &$item) {
            $item['enabled']     = ($item['enabled'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'));
            $item['priority']    = $this->language->get('priority_' . $item['priority']);
            $item['update_link'] = $this->urlToPage(self::PAGE_EVENT_FORM, ['event_id' => $item['id']]);

            $channel_ids = json_decode($item['channel_ids']) ?: [];

            $item['channels'] = [];
            if (is_array($channel_ids)) {
                foreach ($channel_ids as $channel_id) {
                    $item['channels'][] = $channel_labels[$channel_id];
                }
            }

            $item['channels_txt'] = implode(', ', $item['channels']);
        }

        $pagination = new Pagination();

        $pagination->total = $page_data['total'];
        $pagination->page  = $page;
        $pagination->limit = $limit;
        $pagination->url   = $this->urlToPage(self::PAGE_EVENTS, ['page'  => '{page}']);

        $page_data['pagination'] = $pagination->render();

        $page_data['results'] = $this->getPaginationText($page_data['total'], $page);

        $this->renderOutput(self::PAGE_EVENTS, $page_data, $layout_data);
    }

    public function event_form() {

        $this->load->model('extension/report/' . self::MODULE_NAME);

        $query_params = $this->request->get;

        $event_id = array_key_exists('event_id', $query_params) ? $query_params['event_id'] : null;

        if ($event_id) {

            $page_data['item'] = $this->model_extension_report_notify_events->getEvent($event_id);

        } else {

            $event_class = array_key_exists('event_class', $query_params) ? $query_params['event_class'] : null;

            if (empty($event_class)) {
                $this->setAlert('alert-danger', 'fa-exclamation-circle', $this->language->get('event_class_undefined'));

                $this->response->redirect($this->urlToPage(self::PAGE_EVENTS));
            }

            $page_data['item'] = $this->model_extension_report_notify_events->getEventDefaults($event_class);
        }

        $layout_data['back_btn_link'] = $this->urlToPage(self::PAGE_EVENTS);
        $layout_data['back_btn_text'] = $this->language->get('back_to_events');

        $page_data['title']           = $event_id ? $page_data['item']['title'] : $this->language->get('new_event_heading');
        $page_data['required_fields'] = $this->model_extension_report_notify_events->getEventRequiredFields();
        $page_data['channels']        = $this->model_extension_report_notify_events->getChannels();
        $page_data['priorities']      = $this->model_extension_report_notify_events->getPriorityList();
        $page_data['tags']            = $this->model_extension_report_notify_events->getEventsTagsWithLabels($page_data['item']['event_class']);

        // Форму пытаются сохранить
        if ($this->request->server['REQUEST_METHOD'] == 'POST') {

            // Форматируем данные
            $data = $this->model_extension_report_notify_events->formatEventData($this->request->post);

            // Записываем текущие данные для отображения
            $page_data['item'] = $data;

            // Валидируем данные
            if (empty($errors = $this->model_extension_report_notify_events->validateEvent($data))) {

                if ($event_id) {
                    $this->model_extension_report_notify_events->updateEvent($event_id, $data);
                } else {
                    $this->model_extension_report_notify_events->createEvent($data);
                }

                $this->setAlert('alert-success', 'fa-check-circle', $this->language->get('event_added_successfully'));
                $this->response->redirect($this->urlToPage(self::PAGE_EVENTS));
            }

            $page_data['errors'] = $errors;

            if (array_key_exists('_popup_error', $errors)) {
                $this->setAlert('alert-danger', 'fa-times-circle', $errors['_popup_error']);
            }
        }

        $this->renderOutput(self::PAGE_EVENT_FORM, $page_data, $layout_data);
    }

    public function event_delete() {

        $params = $this->request->post;

        if (!empty($params['selected'])) {
            $this->load->model('extension/report/' . self::MODULE_NAME);

            foreach ($params['selected'] as $event_id) {
                $this->model_extension_report_notify_events->deleteEvent($event_id);
            }

            $this->setAlert('alert-success', 'fa-check-circle', $this->language->get('event_deleted_successfully'));
        }

        $this->response->redirect($this->urlToPage(self::PAGE_EVENTS));
    }

    public function channels() {

        $this->load->model('extension/report/' . self::MODULE_NAME);

        $layout_data['add_btn_link']       = $this->urlToPage(self::PAGE_CHANNEL_FORM);
        $layout_data['add_btn_text']       = $this->language->get('common_add_btn_text');

        $layout_data['delete_target_form'] = '#form-channels';
        $layout_data['delete_btn_text']    = $this->language->get('common_delete_btn_text');
        $layout_data['delete_confirm']     = $this->language->get('common_delete_confirm_text');

        $page_data['panel_title'] = $this->language->get('channels_list');
        $page_data['delete']      = $this->urlToPage(self::PAGE_CHANNEL_DELETE);

        $query_params = $this->request->get;

        $page   = array_key_exists('page',  $query_params) ? $query_params['page']  : 1;
        $limit  = $this->config->get('config_limit_admin');
        $offset = $page > 1 ? $limit * ($page - 1) : 0;

        $page_data['total'] = $this->model_extension_report_notify_events->getChannelsTotal();
        $page_data['items'] = $this->model_extension_report_notify_events->getChannels($limit, $offset);

        foreach ($page_data['items'] as &$item) {
            $item['update_link'] = $this->urlToPage(self::PAGE_CHANNEL_FORM, ['channel_id' => $item['id']]);
        }

        $pagination = new Pagination();

        $pagination->total = $page_data['total'];
        $pagination->page  = $page;
        $pagination->limit = $limit;
        $pagination->url   = $this->urlToPage(self::PAGE_CHANNELS, ['page'  => '{page}']);

        $page_data['pagination'] = $pagination->render();

        $page_data['results'] = $this->getPaginationText($page_data['total'], $page);

        $this->renderOutput(self::PAGE_CHANNELS, $page_data, $layout_data);
    }

    public function channel_form() {

        $this->load->model('extension/report/' . self::MODULE_NAME);

        $query_params = $this->request->get;

        $channel_id = array_key_exists('channel_id', $query_params) ? $query_params['channel_id'] : null;

        if ($channel_id) {
            $page_data['item'] = $this->model_extension_report_notify_events->getChannel($channel_id);
        }

        $layout_data['back_btn_link'] = $this->urlToPage(self::PAGE_CHANNELS);
        $layout_data['back_btn_text'] = $this->language->get('back_to_channels');

        $page_data['title']           = $channel_id ? $page_data['item']['title'] : $this->language->get('new_channel_heading');
        $page_data['required_fields'] = $this->model_extension_report_notify_events->getChannelRequiredFields();

        // Форму пытаются сохранить
        if ($this->request->server['REQUEST_METHOD'] == 'POST') {

            // Форматируем данные
            $data = $this->model_extension_report_notify_events->formatChannelData($this->request->post);

            // Записываем текущие данные для отображения
            $page_data['item'] = $data;

            // Валидируем данные
            if (empty($errors = $this->model_extension_report_notify_events->validateChannel($data))) {

                if ($channel_id) {
                    $this->model_extension_report_notify_events->updateChannel($channel_id, $data);
                } else {
                    $this->model_extension_report_notify_events->createChannel($data);
                }

                $this->setAlert('alert-success', 'fa-check-circle', $this->language->get('channel_added_successfully'));
                $this->response->redirect($this->urlToPage(self::PAGE_CHANNELS));
            }

            $page_data['errors'] = $errors;

            if (array_key_exists('_popup_error', $errors)) {
                $this->setAlert('alert-danger', 'fa-times-circle', $errors['_popup_error']);
            }
        }

        $this->renderOutput(self::PAGE_CHANNEL_FORM, $page_data, $layout_data);
    }

    public function channel_delete() {

        $params = $this->request->post;

        if (!empty($params['selected'])) {
            $this->load->model('extension/report/' . self::MODULE_NAME);

            foreach ($params['selected'] as $channel_id) {
                $this->model_extension_report_notify_events->deleteChannel($channel_id);
            }

            $this->setAlert('alert-success', 'fa-check-circle', $this->language->get('channel_deleted_successfully'));
        }

        $this->response->redirect($this->urlToPage(self::PAGE_CHANNELS));
    }

    public function test() {

        $this->load->model('extension/report/' . self::MODULE_NAME);

        $page_data['subject'] = $this->language->get('test_default_subject');
        $page_data['message'] = $this->language->get('test_default_message');

        $page_data['channels']        = $this->model_extension_report_notify_events->getChannels();
        $page_data['required_fields'] = $this->model_extension_report_notify_events->getTestRequiredFields();

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {

            $data = $this->request->post;

            $this->load->model('extension/report/' . self::MODULE_NAME);

            // Валидируем данные
            if (empty($errors = $this->model_extension_report_notify_events->validateTest($data))) {

                $this->model_extension_report_notify_events->sendTestMessage($data);

                $this->setAlert('alert-success', 'fa-check-circle', $this->language->get('test_message_sent_successfully'));
            }

            $page_data['subject']    = $data['subject'];
            $page_data['message']    = $data['message'];
            $page_data['channel_id'] = $data['channel_id'];

            $page_data['errors']     = $errors;

            if (array_key_exists('_popup_error', $errors)) {
                $this->setAlert('alert-danger', 'fa-times-circle', $errors['_popup_error']);
            }
        }

        $this->renderOutput(self::PAGE_TEST, $page_data);
    }

    /*~~~~~~~~~~~~~~~~~~~~~~~~~~~*/
    /* Admin interface functions */
    /*~~~~~~~~~~~~~~~~~~~~~~~~~~~*/

    private function urlToPage($page, $params = []) {
        $params['user_token'] = $this->session->data['user_token'];

        if ($page == self::PAGE_INDEX) {
            return $this->url->link('extension/report/' . self::MODULE_NAME, http_build_query($params), true);
        }

        return $this->url->link('extension/report/' . self::MODULE_NAME . '/' . $page, http_build_query($params), true);
    }

    private function getBreadcrumbs() {

        $params = ['user_token' => $this->session->data['user_token']];

        $breadcrumbs = [
            [
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', http_build_query($params), true),
            ],
            [
                'text' => $this->language->get('text_extension'),
                'href' => $this->url->link('marketplace/extension', http_build_query(array_merge($params, ['type' => 'report'])), true),
            ],
            [
                'text' => $this->language->get('heading_title'),
                'href' => $this->urlToPage(self::PAGE_INDEX, $params),
            ],
        ];

        return $breadcrumbs;
    }

    private function getPaginationText($total, $page) {
        $limit = $this->config->get('config_limit_admin');

        return sprintf($this->language->get('text_pagination'), ($total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($total - $limit)) ? $total : ((($page - 1) * $limit) + $limit), $total, ceil($total / $limit));
    }

    private function getTabs($active_page) {

        $pages  = [self::PAGE_INDEX, self::PAGE_CHANNELS, self::PAGE_EVENTS, self::PAGE_TEST];

        $pageActiveTabs = [
            self::PAGE_INDEX        => self::PAGE_INDEX,
            self::PAGE_CHANNELS     => self::PAGE_CHANNELS,
            self::PAGE_CHANNEL_FORM => self::PAGE_CHANNELS,
            self::PAGE_EVENTS       => self::PAGE_EVENTS,
            self::PAGE_EVENT_FORM   => self::PAGE_EVENTS,
            self::PAGE_TEST         => self::PAGE_TEST,
        ];

        $result = [];

        foreach ($pages as $page) {
            $result[$page] = [
                'class' => $pageActiveTabs[$active_page] == $page ? 'active' : '',
                'link'  => $this->urlToPage($page),
                'title' => $this->language->get('page_title_' . $page),
            ];
        }

        return $result;
    }

    private function setAlert($class, $icon, $text) {
        $this->session->data['ne_alerts'][] = ['class' => $class, 'icon'  => $icon, 'text'  => $text];
    }

    private function renderOutput($active_page, $page_data = [], $layout_data = []) {

        if (array_key_exists('ne_alerts', $this->session->data)) {
            $layout_data['alerts'] = $this->session->data['ne_alerts'];
            unset($this->session->data['ne_alerts']);
        }

        $data['layout_title'] = $this->language->get('page_title_' . $active_page);

        $this->document->setTitle($data['layout_title']);

        $data['breadcrumbs']  = $this->getBreadcrumbs();
        $data['layout_tabs']  = $this->getTabs($active_page);

        $data['header']       = $this->load->controller('common/header');
        $data['column_left']  = $this->load->controller('common/column_left');
        $data['footer']       = $this->load->controller('common/footer');

        $data['page_content'] = $this->load->view('extension/report/' . self::MODULE_NAME . '/' . $active_page, $page_data);

        $this->response->setOutput($this->load->view('extension/report/' . self::MODULE_NAME . '/layout', array_merge($data, $layout_data)));
    }

    /*~~~~~~~~~~~~~~~*/
    /* Event Handler */
    /*~~~~~~~~~~~~~~~*/

    public function handleEvent($route, $args, $id) {
        $this->load->model('extension/report/' . self::MODULE_NAME);
        $this->model_extension_report_notify_events->runEventHandling($route, $args, $id);
    }

    /*~~~~~~~~*/
    /* System */
    /*~~~~~~~~*/

    public function install() {
        $this->load->model('setting/setting');
        $this->load->model('extension/report/' . self::MODULE_NAME);

        $this->model_extension_report_notify_events->createTables();
        $this->model_extension_report_notify_events->createTriggers();

        $this->model_setting_setting->editSetting('report_' . self::MODULE_NAME, ['report_' . self::MODULE_NAME . '_status' => true]);
    }

    public function uninstall() {
        $this->load->model('setting/setting');
        $this->load->model('extension/report/' . self::MODULE_NAME);

        $this->model_extension_report_notify_events->dropTables();
        $this->model_extension_report_notify_events->dropTriggers();

        $this->model_setting_setting->editSetting('report_' . self::MODULE_NAME, ['report_' . self::MODULE_NAME . '_status' => false]);
    }
}
