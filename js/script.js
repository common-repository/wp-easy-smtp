(function($) {
	var $doc = $(document);
	$doc.ready(function() {
		var $settings_form = $doc.find('#wpesmtp_settings_form'),
			$testing_form = $doc.find('#wpesmtp_testing_form'),
			$fields = $settings_form.find('.field'),
			$inputs = $settings_form.find('input'),
			defaults = $inputs.serializeJSON(),
			defaults2 = $inputs.serializeJSON();
		/* 
		 *add notice about changing in the settings page 
		 */
		$settings_form
			.on('change', 'input', $.debounce( 50, function(event) {
				var newObj = $inputs.serializeJSON();

				$('#wpesmtp-settings-notice')[JSON.stringify( defaults2 ) != JSON.stringify( newObj ) ? 'show' : 'hide']();
			} ) )
			.on('change.mailer', 'input[name="wpesmtp_mailer"]', function(event) {
				var value = this.value;

				var $host = $inputs.filter('[name="wpesmtp_smtp_host"]'),
					$auth = $inputs.filter('[name="wpesmtp_smtp_autentication"]'),
					$encryption = $inputs.filter('[name="wpesmtp_smtp_type_encryption"]'),
					$port = $inputs.filter('[name="wpesmtp_smtp_port"]');

				$fields.show();
				if (value !== 'smtp') {
					$fields.filter('[rel="host"], [rel="auth"]').hide();
					$auth.filter('[value="yes"]').prop('checked', true);
				}

				if (value === 'smtp') {
					$inputs.filter('[name="wpesmtp_smtp_host"]').val(defaults.wpesmtp_smtp_host);
					$encryption.filter('[value="ssl"]').prop('checked', true);
					$port.val('465');
				} else if (value === 'gmail') {
					$fields.filter('[rel="host"], [rel="auth"], [rel="encryption"], [rel="port"]').hide();
					$host.val('smtp.gmail.com');
				} else if (value === 'yahoo') {
					$fields.filter('[rel="host"], [rel="auth"], [rel="encryption"], [rel="port"]').hide();
					$host.val('smtp.mail.yahoo.com');
				} else if (value === 'hotmail') {
					$fields.filter('[rel="host"], [rel="auth"], [rel="encryption"], [rel="port"]').hide();
					$host.val('smtp.live.com');
				} else if (value === 'sendgrid')
					$host.val('smtp.sendgrid.net');
				else if (value === 'sparkpost')
					$host.val('smtp.sparkpostmail.com');
				else if (value === 'postmark')
					$host.val('smtp.postmarkapp.com');
				else if (value === 'mandrill')
					$host.val('smtp.mandrillapp.com');
				else if (value === 'pepipost')
					$host.val('smtp.pepipost.com');

				if (['gmail', 'hotmail', 'sendgrid', 'sparkpost', 'postmark', 'mandrill', 'pepipost'].indexOf(value) !== -1) {
					$encryption.filter('[value="tls"]').prop('checked', true);
					$port.val('587');
				} else if (['yahoo'].indexOf(value) !== -1) {
					$auth.filter('[value="yes"]').prop('checked', true);
					$encryption.filter('[value="ssl"]').prop('checked', true);
					$port.val('465');
				}

				defaults.wpesmtp_mailer = value;
			})
			.on('change', 'input[name="wpesmtp_smtp_type_encryption"]', function(event) {
				var value = this.value;

				if (value === 'none') {
					$inputs.filter('[name="wpesmtp_smtp_port"]').val('25');
				} else if (value === 'ssl') {
					$inputs.filter('[name="wpesmtp_smtp_port"]').val('465');
				} else if (value === 'tls') {
					$inputs.filter('[name="wpesmtp_smtp_port"]').val('587');
				}
			});

		$settings_form.find('input[name="wpesmtp_mailer"]:checked').trigger('change.mailer');


		$testing_form
			.on('change', 'input[name="wpesmtp_send_to"]', function(event) {
				var value = this.value;

				$(this).parents('td').find('#send_to')[value === 'custom' ? 'show' : 'hide']();
			});

		$testing_form.find('input[name="wpesmtp_send_to"]:checked').trigger('change');

		$doc.find('#wpesmtp-mail').on('submit', 'form', function() {
			var $settings_form = $(this),
				$message = $settings_form.find('.wpesmtp_ajax_message'),
				serialize = $settings_form.serializeJSON();

			serialize.action = "wpesmtp";
			hideLoader($settings_form);
			showLoader($settings_form);

			$.ajax({
					method: "POST",
					url: ajaxurl,
					data: serialize
				})
				.done(function(data) {
					hideLoader($settings_form);
					data = JSON.parse(data);

					if (data.status === 200) {
						$message.stop().addClass('show').removeClass('warning').html('<h3>' + data.message + '</h3>');
						$message.wait(3000).removeClass('show');
						$('#wpesmtp-settings-notice').hide();
					} else {
						$message.stop().addClass('show').addClass('warning').html('<h3>' + data.message + '</h3><ul>' + data.error.join('') + '</ul>');
					}
				})
				.fail(function(data) {
					hideLoader($settings_form);
					$message.hide();
				});

			return false;
		});

		function showLoader($element) {
			var $loader = $element.find('.circle-loader');
			$loader[0].style.display = 'inline-block';
		}

		function hideLoader($element) {
			var $loader = $element.find('.circle-loader');
			$loader.removeClass('load-complete').hide();
		}

	});

	var rCRLF = /\r?\n/g,
		rsubmitterTypes = /^(?:submit|button|image|reset|file)$/i,
		rsubmittable = /^(?:input|select|textarea|keygen)/i,
		rcheckableType = (/^(?:checkbox|radio)$/i);

	$.fn.serializeJSON = function(filter, defaultObj) {
		"use strict";

		var array = this.map(function() {
				// Can add propHook for "elements" to filter or add form elements
				var elements = $.prop(this, "elements");
				return elements ? $.makeArray(elements) : this;
			})
			.filter(function() {
				var type = this.type;

				// Use .is( ":disabled" ) so that fieldset[disabled] works
				return this.name && !$(this).is(":disabled") &&
					rsubmittable.test(this.nodeName) && !rsubmitterTypes.test(type) &&
					(this.checked || !rcheckableType.test(type));
			})
			.map(function(i, elem) {
				var val = $(this).val(),
					name = elem.name;

				return val == null || (filter && !val) || (defaultObj && defaultObj[name] === val) ?
					null :
					$.isArray(val) ?
					$.map(val, function(val) {
						return {
							name: name,
							value: val.replace(rCRLF, "\r\n")
						};
					}) : {
						name: name,
						value: val.replace(rCRLF, "\r\n")
					};
			}).get();

		var serialize = deparam($.param(array));

		return serialize;
	};

	function deparam(params, coerce) {
		var obj = {},
			coerce_types = {
				'true': !0,
				'false': !1,
				'null': null
			};

		// Iterate over all name=value pairs.
		$.each(params.replace(/\+/g, ' ').split('&'), function(j, v) {
			var param = v.split('='),
				key = decodeURIComponent(param[0]),
				val,
				cur = obj,
				i = 0,

				// If key is more complex than 'foo', like 'a[]' or 'a[b][c]', split it
				// into its component parts.
				keys = key.split(']['),
				keys_last = keys.length - 1;

			// If the first keys part contains [ and the last ends with ], then []
			// are correctly balanced.
			if (/\[/.test(keys[0]) && /\]$/.test(keys[keys_last])) {
				// Remove the trailing ] from the last keys part.
				keys[keys_last] = keys[keys_last].replace(/\]$/, '');

				// Split first keys part into two parts on the [ and add them back onto
				// the beginning of the keys array.
				keys = keys.shift().split('[').concat(keys);

				keys_last = keys.length - 1;
			} else {
				// Basic 'foo' style key.
				keys_last = 0;
			}

			// Are we dealing with a name=value pair, or just a name?
			if (param.length === 2) {
				val = decodeURIComponent(param[1]);

				// Coerce values.
				if (coerce) {
					val = val && !isNaN(val) ? +val // number
						:
						val === 'undefined' ? undefined // undefined
						:
						coerce_types[val] !== undefined ? coerce_types[val] // true, false, null
						:
						val; // string
				}

				if (keys_last) {
					// Complex key, build deep object structure based on a few rules:
					// * The 'cur' pointer starts at the object top-level.
					// * [] = array push (n is set to array length), [n] = array if n is 
					//   numeric, otherwise object.
					// * If at the last keys part, set the value.
					// * For each keys part, if the current level is undefined create an
					//   object or array based on the type of the next keys part.
					// * Move the 'cur' pointer to the next level.
					// * Rinse & repeat.
					for (; i <= keys_last; i++) {
						key = keys[i] === '' ? cur.length : keys[i];
						cur = cur[key] = i < keys_last ? cur[key] || (keys[i + 1] && isNaN(keys[i + 1]) ? {} : []) : val;
					}

				} else {
					// Simple key, even simpler rules, since only scalars and shallow
					// arrays are allowed.

					if ($.isArray(obj[key])) {
						// val is already an array, so push on the next value.
						obj[key].push(val);

					} else if (obj[key] !== undefined) {
						// val isn't an array, but since a second value has been specified,
						// convert val into an array.
						obj[key] = [obj[key], val];

					} else {
						// val is a scalar.
						obj[key] = val;
					}
				}

			} else if (key) {
				// No value was defined, so set something meaningful.
				obj[key] = coerce ? undefined : '';
			}
		});

		return obj;
	}

	function jQueryDummy($real, delay, _fncQueue) {
		// A Fake jQuery-like object that allows us to resolve the entire jQuery
		// method chain, pause, and resume execution later.

		var dummy = this;
		this._fncQueue = (typeof _fncQueue === 'undefined') ? [] : _fncQueue;
		this._delayCompleted = false;
		this._$real = $real;

		if (typeof delay === 'number' && delay >= 0 && delay < Infinity)
			this.timeoutKey = window.setTimeout(function() {
				dummy._performDummyQueueActions();
			}, delay);

		else if (delay !== null && typeof delay === 'object' && typeof delay.promise === 'function')
			delay.then(function() {
				dummy._performDummyQueueActions();
			});

		else if (typeof delay === 'string')
			$real.one(delay, function() {
				dummy._performDummyQueueActions();
			});

		else
			return $real;
	}

	jQueryDummy.prototype._addToQueue = function(fnc, arg) {
		// When dummy functions are called, the name of the function and
		// arguments are put into a queue to execute later

		this._fncQueue.unshift({
			fnc: fnc,
			arg: arg
		});

		if (this._delayCompleted)
			return this._performDummyQueueActions();
		else
			return this;
	};

	jQueryDummy.prototype._performDummyQueueActions = function() {
		// Start executing queued actions.  If another `wait` is encountered,
		// pass the remaining stack to a new jQueryDummy

		this._delayCompleted = true;

		var next;
		while (this._fncQueue.length > 0) {
			next = this._fncQueue.pop();

			if (next.fnc === 'wait') {
				next.arg.push(this._fncQueue);
				return this._$real = this._$real[next.fnc].apply(this._$real, next.arg);
			}

			this._$real = this._$real[next.fnc].apply(this._$real, next.arg);
		}

		return this;
	};

	$.fn.wait = function(delay, _queue) {
		// Creates dummy object that dequeues after a times delay OR promise

		return new jQueryDummy(this, delay, _queue);
	};

	for (var fnc in $.fn) {
		// Add shadow methods for all jQuery methods in existence.  Will not
		// shadow methods added to jQuery _after_ this!
		// skip non-function properties or properties of Object.prototype

		if (typeof $.fn[fnc] !== 'function' || !$.fn.hasOwnProperty(fnc))
			continue;

		jQueryDummy.prototype[fnc] = (function(fnc) {
			return function() {
				var arg = Array.prototype.slice.call(arguments);
				return this._addToQueue(fnc, arg);
			};
		})(fnc);
	}
	var jq_throttle;

	// Method: jQuery.throttle
	$.throttle = jq_throttle = function(delay, no_trailing, callback, debounce_mode) {
		// After wrapper has stopped being called, this timeout ensures that
		// `callback` is executed at the proper times in `throttle` and `end`
		// debounce modes.
		var timeout_id,

			// Keep track of the last time `callback` was executed.
			last_exec = 0;

		// `no_trailing` defaults to falsy.
		if (typeof no_trailing !== 'boolean') {
			debounce_mode = callback;
			callback = no_trailing;
			no_trailing = undefined;
		}

		// The `wrapper` function encapsulates all of the throttling / debouncing
		// functionality and when executed will limit the rate at which `callback`
		// is executed.
		function wrapper() {
			var that = this,
				elapsed = +new Date() - last_exec,
				args = arguments;

			// Execute `callback` and update the `last_exec` timestamp.
			function exec() {
				last_exec = +new Date();
				callback.apply(that, args);
			};

			// If `debounce_mode` is true (at_begin) this is used to clear the flag
			// to allow future `callback` executions.
			function clear() {
				timeout_id = undefined;
			};

			if (debounce_mode && !timeout_id) {
				// Since `wrapper` is being called for the first time and
				// `debounce_mode` is true (at_begin), execute `callback`.
				exec();
			}

			// Clear any existing timeout.
			timeout_id && clearTimeout(timeout_id);

			if (debounce_mode === undefined && elapsed > delay) {
				// In throttle mode, if `delay` time has been exceeded, execute
				// `callback`.
				exec();

			} else if (no_trailing !== true) {
				// In trailing throttle mode, since `delay` time has not been
				// exceeded, schedule `callback` to execute `delay` ms after most
				// recent execution.
				// 
				// If `debounce_mode` is true (at_begin), schedule `clear` to execute
				// after `delay` ms.
				// 
				// If `debounce_mode` is false (at end), schedule `callback` to
				// execute after `delay` ms.
				timeout_id = setTimeout(debounce_mode ? clear : exec, debounce_mode === undefined ? delay - elapsed : delay);
			}
		};

		// Set the guid of `wrapper` function to the same of original callback, so
		// it can be removed in jQuery 1.4+ .unbind or .die by using the original
		// callback as a reference.
		if ($.guid) {
			wrapper.guid = callback.guid = callback.guid || $.guid++;
		}

		// Return the wrapper function.
		return wrapper;
	};

	// Method: jQuery.debounce
	$.debounce = function(delay, at_begin, callback) {
		return callback === undefined ?
			jq_throttle(delay, at_begin, false) :
			jq_throttle(delay, callback, at_begin !== false);
	};
})(jQuery);