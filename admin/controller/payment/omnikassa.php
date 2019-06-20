<?php
class ControllerPaymentOmnikassa extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('payment/omnikassa');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('omnikassa', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'));
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_all_zones'] = $this->language->get('text_all_zones');

		$data['entry_refresh_token'] = $this->language->get('entry_refresh_token');
        $data['entry_signing_key'] = $this->language->get('entry_signing_key');
		$data['entry_total'] = $this->language->get('entry_total');
		$data['entry_order_status'] = $this->language->get('entry_order_status');
		$data['entry_pending_status'] = $this->language->get('entry_pending_status');
		$data['entry_canceled_status'] = $this->language->get('entry_canceled_status');
		$data['entry_failed_status'] = $this->language->get('entry_failed_status');
		$data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_sort_order'] = $this->language->get('entry_sort_order');
        $data['entry_test_mode'] = $this->language->get('entry_test_mode');
        $data['entry_test_mode_production_value'] = $this->language->get('entry_test_mode_production_value');
        $data['entry_test_mode_sandbox_value'] = $this->language->get('entry_test_mode_sandbox_value');

		$data['help_total'] = $this->language->get('help_total');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['refresh_token'])) {
			$data['error_refresh_token'] = $this->error['refresh_token'];
		} else {
			$data['error_refresh_token'] = '';
		}
        
		if (isset($this->error['signing_key'])) {
			$data['error_signing_key'] = $this->error['signing_key'];
		} else {
			$data['error_signing_key'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], 'SSL')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_payment'),
			'href' => $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('payment/omnikassa', 'token=' . $this->session->data['token'], 'SSL')
		);

		$data['action'] = $this->url->link('payment/omnikassa', 'token=' . $this->session->data['token'], 'SSL');

		$data['cancel'] = $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL');

		if (isset($this->request->post['omnikassa_refresh_token'])) {
			$data['omnikassa_refresh_token'] = $this->request->post['omnikassa_refresh_token'];
		} else {
			$data['omnikassa_refresh_token'] = $this->config->get('omnikassa_refresh_token');
		}

		if (isset($this->request->post['omnikassa_signing_key'])) {
			$data['omnikassa_signing_key'] = $this->request->post['omnikassa_signing_key'];
		} else {
			$data['omnikassa_signing_key'] = $this->config->get('omnikassa_signing_key');
		}

		if (isset($this->request->post['omnikassa_total'])) {
			$data['omnikassa_total'] = $this->request->post['omnikassa_total'];
		} else {
			$data['omnikassa_total'] = $this->config->get('omnikassa_total');
		}

		if (isset($this->request->post['omnikassa_order_status_id'])) {
			$data['omnikassa_order_status_id'] = $this->request->post['omnikassa_order_status_id'];
		} else {
			$data['omnikassa_order_status_id'] = $this->config->get('omnikassa_order_status_id');
		}

		if (isset($this->request->post['omnikassa_pending_status_id'])) {
			$data['omnikassa_pending_status_id'] = $this->request->post['omnikassa_pending_status_id'];
		} else {
			$data['omnikassa_pending_status_id'] = $this->config->get('omnikassa_pending_status_id');
		}

		if (isset($this->request->post['omnikassa_canceled_status_id'])) {
			$data['omnikassa_canceled_status_id'] = $this->request->post['omnikassa_canceled_status_id'];
		} else {
			$data['omnikassa_canceled_status_id'] = $this->config->get('omnikassa_canceled_status_id');
		}

		if (isset($this->request->post['omnikassa_failed_status_id'])) {
			$data['omnikassa_failed_status_id'] = $this->request->post['omnikassa_failed_status_id'];
		} else {
			$data['omnikassa_failed_status_id'] = $this->config->get('omnikassa_failed_status_id');
		}

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['omnikassa_geo_zone_id'])) {
			$data['omnikassa_geo_zone_id'] = $this->request->post['omnikassa_geo_zone_id'];
		} else {
			$data['omnikassa_geo_zone_id'] = $this->config->get('omnikassa_geo_zone_id');
		}

		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		if (isset($this->request->post['omnikassa_status'])) {
			$data['omnikassa_status'] = $this->request->post['omnikassa_status'];
		} else {
			$data['omnikassa_status'] = $this->config->get('omnikassa_status');
		}

		if (isset($this->request->post['omnikassa_sort_order'])) {
			$data['omnikassa_sort_order'] = $this->request->post['omnikassa_sort_order'];
		} else {
			$data['omnikassa_sort_order'] = $this->config->get('omnikassa_sort_order');
		}

		if (isset($this->request->post['omnikassa_test_mode'])) {
			$data['omnikassa_test_mode'] = $this->request->post['omnikassa_test_mode'];
		} else {
			$data['omnikassa_test_mode'] = $this->config->get('omnikassa_test_mode');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('payment/omnikassa.tpl', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'payment/omnikassa')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['omnikassa_refresh_token']) {
			$this->error['refresh_token'] = $this->language->get('error_refresh_token');
		}
        
		if (!$this->request->post['omnikassa_signing_key']) {
			$this->error['signing_key'] = $this->language->get('error_signing_key');
		}

		return !$this->error;
	}
}