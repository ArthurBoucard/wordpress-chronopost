<?php

function generate_chronopost_label_for_seller($order_id) {
    $order = wc_get_order($order_id);

    $customer_firstname = $order->get_billing_first_name();
    $customer_lastname  = $order->get_billing_last_name();
    $customer_fullname  = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
    $customer_address1  = $order->get_billing_address_1();
    $customer_address2  = $order->get_billing_address_2();
    $customer_zip       = $order->get_billing_postcode();
    $customer_city      = $order->get_billing_city();
    $customer_country   = $order->get_billing_country();
    $customer_phone     = $order->get_billing_phone();
    $customer_email     = $order->get_billing_email();

    $recipient_fullname = $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name();
    $recipient_company  = $order->get_shipping_company();
    $recipient_address1 = $order->get_shipping_address_1();
    $recipient_address2 = $order->get_shipping_address_2();
    $recipient_zip      = $order->get_shipping_postcode();
    $recipient_city     = $order->get_shipping_city();
    $recipient_country  = $order->get_shipping_country();
    $recipient_phone    = $order->get_shipping_phone();

	// Retrieve relay data (id) /!\

	foreach ($order->get_items() as $item_id => $item) {
		$product = $item->get_product();
		$product_id = $product->get_id();
		$product_post = get_post($product_id);
		$vendor_id = $product_post->post_author;

		$shipper_fullname  = get_user_meta($vendor_id, 'first_name', true) . ' ' . get_user_meta($vendor_id, 'last_name', true);
		$shipper_address1  = get_user_meta($vendor_id, 'billing_address_1', true) ?: 'UNKNOWN ADDRESS';
		$shipper_address2  = get_user_meta($vendor_id, 'billing_address_2', true) ?: '';
		$shipper_zip       = get_user_meta($vendor_id, 'billing_postcode', true) ?: 'UNKNOWN ZIP';
		$shipper_city      = get_user_meta($vendor_id, 'billing_city', true) ?: 'UNKNOWN CITY';
		$shipper_country   = get_user_meta($vendor_id, 'billing_country', true) ?: 'FR';
		$shipper_phone     = get_user_meta($vendor_id, 'billing_phone', true) ?: 'UNKNOWN PHONE';
		$user = get_user_by('id', $vendor_id);
		$shipper_email     = $user ? $user->user_email : '';

		$total_weight = 0;
		$total_length = 0;
		$total_width  = 0;
		$total_height = 0;
		$parcel_dimensions_json = $order->get_meta('_parcels_dimensions');
		if (!empty($parcel_dimensions_json)) {
			$parcel_dimensions = json_decode($parcel_dimensions_json, true);
			if (isset($parcel_dimensions['1'])) {
				$dimensions = $parcel_dimensions['1'];
				// $total_weight += floatval($dimensions['weight']);
				$total_length += floatval($dimensions['length']);
				$total_width  += floatval($dimensions['width']);
				$total_height += floatval($dimensions['height']);
			}
		}
		$weight = $order->get_meta('poids_colis');
		if (!$weight) {
			$weight = 'UNKNOWN WEIGHT';
		}
		if ($weight == '0-1') {
			$total_weight = 1;
		} elseif ($weight == '1-3') {
			$total_weight = 2;
		} elseif ($weight == '3-5') {
			$total_weight = 4;
		} elseif ($weight == '5-10') {
			$total_weight = 8;
		} else {
			$total_weight = 10;
		}

		$xml_request = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:cxf="http://cxf.shipping.soap.chronopost.fr/">
	   <soapenv:Header/>
		   <soapenv:Body>
			  <cxf:shippingMultiParcelV4>
				  <headerValue>
					<accountNumber>19869502</accountNumber>
					<idEmit>CHRFR</idEmit>
					<identWebPro></identWebPro>
					<subAccount></subAccount>
				 </headerValue>

				 <shipperValue>
					<shipperAdress1>' . $shipper_address1 . '</shipperAdress1>
					<shipperAdress2>' . $shipper_address2 . '</shipperAdress2>
					<shipperCity>' . $shipper_city . '</shipperCity>
					<shipperCivility>M</shipperCivility>
					<shipperContactName>' . $shipper_fullname . '</shipperContactName>
					<shipperCountry>' . $shipper_country . '</shipperCountry>
					<shipperCountryName>FRANCE</shipperCountryName>
					<shipperEmail></shipperEmail>
					<shipperMobilePhone>' . $shipper_phone . '</shipperMobilePhone>
					<shipperName>' . $shipper_fullname . '</shipperName>
					<shipperName2></shipperName2>
					<shipperPhone>' . $shipper_phone . '</shipperPhone>
					<shipperPreAlert>0</shipperPreAlert>
					<shipperZipCode>' . $shipper_zip . '</shipperZipCode>
				 </shipperValue>

				 <customerValue>
					<customerAdress1>' . $customer_address1 . '</customerAdress1>
					<customerAdress2>' . $customer_address2 . '</customerAdress2>
					<customerCity>' . $customer_city . '</customerCity>
					<customerCivility>M</customerCivility>
					<customerContactName>' . $shipper_fullname . 'CUST</customerContactName>
					<customerCountry>' . $shipper_country . '</customerCountry>
					<customerCountryName>FRANCE</customerCountryName>
					<customerEmail>' . $customer_email . '</customerEmail>
					<customerMobilePhone>' . $customer_phone . '</customerMobilePhone>
					<customerName>' . $customer_firstname . '</customerName>
					<customerName2>' . $customer_lastname . '</customerName2>
					<customerPhone>' . $customer_phone . '</customerPhone>
					<customerPreAlert></customerPreAlert>
					<customerZipCode>' . $customer_zip . '</customerZipCode>
					<printAsSender></printAsSender>
				 </customerValue>

				 <recipientValue>
					<recipientAdress1>' . $recipient_address1 . '</recipientAdress1>
					<recipientAdress2>' . $recipient_address2 . '</recipientAdress2>
					<recipientCity>' . $recipient_city . '</recipientCity>
					<recipientContactName>' . $recipient_fullname . '</recipientContactName>
					<recipientCountry>' . $recipient_country . '</recipientCountry>
					<recipientCountryName>FRANCE</recipientCountryName>
					<recipientEmail></recipientEmail>
					<recipientMobilePhone></recipientMobilePhone>
					<recipientName>' . $recipient_company . '</recipientName>
					<recipientName2>' . $recipient_fullname . '</recipientName2>
					<recipientPhone>' . $recipient_phone . '</recipientPhone>
					<recipientPreAlert>0</recipientPreAlert>
					<recipientZipCode>' . $recipient_zip . '</recipientZipCode>
				 </recipientValue>

				 <refValue>
					<recipientRef>COMMANDE NUM ' . $order_id . '</recipientRef>
					<shipperRef>REF EXPE</shipperRef>
					<idRelais>1786S</idRelais>
				 </refValue>

				 <skybillValue>
					<bulkNumber> </bulkNumber>
					<codCurrency> </codCurrency>
					<codValue> </codValue>
					<content1> </content1>
					<content2> </content2>
					<content3> </content3>
					<content4> </content4>
					<content5> </content5>
					<customsCurrency> </customsCurrency>
					<customsValue> </customsValue>
					<evtCode>DC</evtCode>
					<insuredCurrency> </insuredCurrency>
					<insuredValue> </insuredValue>
					<latitude> </latitude>
					<longitude> </longitude>
					<masterSkybillNumber> </masterSkybillNumber>
					<objectType>MAR</objectType>
					<portCurrency> </portCurrency>
					<portValue> </portValue>
					<productCode>5E</productCode>
					<qualite></qualite>
					<service>0</service>
					<as>A15</as>
					<shipDate></shipDate>
					<shipHour></shipHour>
					<skybillRank></skybillRank>
					<source></source>
					<weight>' . $total_weight . '</weight>
					<weightUnit>KGM</weightUnit>
					<height>' . $total_height . '</height>
					<length>' . $total_length . '</length>
					<width>' . $total_width . '</width>
					<alternateProductCode></alternateProductCode>
				 </skybillValue>

				 <skybillParamsValue>
					<duplicata>N</duplicata>
					<mode>PPR</mode>
					<withReservation>0</withReservation>
				 </skybillParamsValue>

				 <password>255562</password>
				 <modeRetour>2</modeRetour>
				 <numberOfParcel>1</numberOfParcel>
				 <version>2.0</version>
				 <multiParcel>N</multiParcel>
			  </cxf:shippingMultiParcelV4>
		   </soapenv:Body>
		</soapenv:Envelope>';

		$api_url = "https://ws.chronopost.fr/shipping-cxf/ShippingServiceWS";
		$response = wp_remote_post($api_url, array(
			'body'    => $xml_request,
			'headers' => array(
				'Content-Type' => 'application/soap+xml;charset=UTF-8',
			),
		));

		if (is_wp_error($response)) {
			error_log("Chronopost API Error: " . $response->get_error_message());
			return;
		}

		$response_data = wp_remote_retrieve_body($response);
		$response_data = preg_replace('/^\xEF\xBB\xBF/', '', $response_data);
		$response_data_no_ns = preg_replace('/(<\/?)(\w+:)/', '$1', $response_data);
		libxml_use_internal_errors(true);
		$xml = simplexml_load_string($response_data_no_ns, 'SimpleXMLElement', LIBXML_NOCDATA);
		if (!$xml) {
			$errors = libxml_get_errors();
			foreach ($errors as $error) {
				error_log("XML Parsing Error: " . $error->message);
			}
			libxml_clear_errors();
			error_log("Error parsing XML response");
			return;
		}

		$pdf_nodes = $xml->xpath('//pdfEtiquette');
		if (empty($pdf_nodes)) {
			error_log("pdfEtiquette not found in response");
			return;
		}
		$pdf_base64 = (string) $pdf_nodes[0];

		$pdf_data = base64_decode($pdf_base64);

		$upload_dir = wp_upload_dir();
		$pdf_filename = 'chronopost_label_' . $order_id . '.pdf';
		$pdf_filepath = trailingslashit($upload_dir['basedir']) . $pdf_filename;
		file_put_contents($pdf_filepath, $pdf_data);

		$to      = $shipper_email;
		$subject = 'Votre produit a été acheté pour la commande #' . $order_id;
		$message = '
			<html>
			<body>
				<p>Veuillez trouver ci-joint l\'étiquette (PDF) de commande à mettre sur votre colis.</p>
				<p>La commande concerne le produit : ' . $product->get_name() . '</p>
			</body>
			</html>';
		$headers = array('Content-Type: text/html; charset=UTF-8');

		$mail_sent = wp_mail($to, $subject, $message, $headers, array($pdf_filepath));
		if ($mail_sent) {
			error_log("Email sent successfully with PDF attachment.");
		} else {
			error_log("Failed to send email with PDF attachment.");
		}
	}

    error_log("DONE: Chronopost Label Process Completed for Order #$order_id");
}
add_action('woocommerce_order_status_processing', 'generate_chronopost_label_for_seller');