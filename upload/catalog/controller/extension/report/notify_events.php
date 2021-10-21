<?php
class ControllerExtensionReportNotifyEvents extends Controller {
    /**
     * @param $route
     * @param $args
     * @param $id
     */
    public function handleEvent($route, $args, $id) {
        $this->load->model('extension/report/notify_events');
        $this->model_extension_report_notify_events->runEventHandling($route, $args, $id);
    }
}
