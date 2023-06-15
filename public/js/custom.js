class App {
	phoneMask(){
		var phone = document.querySelector("input[name='webhook[route_value]']");
		var preferredCountries = ['br', 'us', "gb", "es"];

		window.intlTelInput(phone, {
			utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@18.1.1/build/js/utils.js",
			preferredCountries: preferredCountries,
			nationalMode: true,
			initialCountry: 'br',
			placeholderNumberType:"MOBILE",
  			autoPlaceholder:"polite",

			customPlaceholder: function (
	            selectedCountryPlaceholder,
	            selectedCountryData
	          ) {
	            var full_phone = document.querySelector("input[name='webhook[route_value]']").value;

	            if (full_phone) {
					var clean_phone = full_phone.toString().replace(/\D/g, '')
					var edit_phone = clean_phone.replace(
						new RegExp('^' + selectedCountryData.dialCode, 'g'), ''
					)
					phone.value = edit_phone;
	            }

	            if (selectedCountryData.iso2 == 'br') {
					$("input[name='webhook[route_value]']").mask('(00) 0000-00000')
					return '(00) 0000-00000';
	            } 

	            return selectedCountryPlaceholder
	        }
		});

		var iti = window.intlTelInputGlobals.getInstance(phone);

		phone.addEventListener("countrychange", function() {
			var countryData = iti.getSelectedCountryData();

			if (countryData.iso2 == 'br') {
				$("input[name='webhook[route_value]']").mask("(00) 0000-00000");
			} else {
				$("input[name='webhook[route_value]']").unmask();
			}
		});

		$("input[name='webhook[route_value]']").on( "keyup", function() {
			var phone_number = iti.getNumber(intlTelInputUtils.numberFormat.E164);
			$("input[name='webhook[phone_number]']").val(phone_number.replace(/\D/g, ''));
			console.log(phone_number.replace(/\D/g, ''));
		} );
	}

	copyText() {
		// 		var clipboard = new ClipboardJS('');
		// console.log(clipboard);
	}
}

const app = new App();

window.onload = (event) => {
	app.phoneMask();
};

document.addEventListener("turbo:load", () => {
	app.phoneMask();
});