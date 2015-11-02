jQuery(document).ready(function() {
	var pp_acceptance = "<div style=\"text-align:center\"><a href=\"https://www.paypal.com/uk/webapps/mpp/paypal-popup\" title=\"How PayPal Works\" onclick=\"javascript:window.open('https://www.paypal.com/uk/webapps/mpp/paypal-popup','WIPaypal','toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=1060, height=700'); return false;\"><img src=\"//127.0.0.1/git/cc/modules/plugins/PayPal_Pro/images/acceptance_marks_US.png\" border=\"0\" alt=\"Now accepting PayPal\"></a></div>";
	$("body").append(pp_acceptance);
});