<?php
/**
 * Copyright © 2018 Appmerce - Applications for Ecommerce
 * http://www.appmerce.com
 */

class ModelPaymentOmnikassa extends Model {
	public function getMethod($address, $total) {
		$this->load->language('payment/omnikassa');

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('omnikassa_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

		if ($this->config->get('omnikassa_total') > 0 && $this->config->get('omnikassa_total') > $total) {
			$status = false;
		} elseif (!$this->config->get('omnikassa_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}

		$method_data = array();

		if ($status) {
			$method_data = array(
				'code'       => 'omnikassa',
				'title'      => $this->language->get('text_title'),
				'terms'      => '',
				'sort_order' => $this->config->get('omnikassa_sort_order')
			);
		}

		return $method_data;
	}
}