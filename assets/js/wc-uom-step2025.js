(function ($) {
'use strict';

function parseNumber(value) {
var num = parseFloat(value);
return isNaN(num) ? 0 : num;
}

function getRules($input) {
var step = parseNumber($input.data('wcuom-step')) || 1;
var minAttr = $input.data('wcuom-min');
var maxAttr = $input.data('wcuom-max');
var min = minAttr === '' || typeof minAttr === 'undefined' ? step : parseNumber(minAttr);
var max = maxAttr === '' || typeof maxAttr === 'undefined' ? '' : parseNumber(maxAttr);
var precision = parseInt($input.data('wcuom-precision'), 10);
precision = isNaN(precision) ? 0 : precision;
var allowDecimals = $input.data('wcuom-allow-decimal') === 'yes';

if (!allowDecimals) {
precision = 0;
step = Math.max(1, Math.round(step));
min = Math.max(1, Math.round(min));
}

return {
step: step > 0 ? step : 1,
min: min > 0 ? min : step,
max: max === '' ? '' : max,
precision: precision,
allowDecimals: allowDecimals
};
}

function closestValid(value, rules, direction) {
var step = rules.step > 0 ? rules.step : 1;
var min = rules.min > 0 ? rules.min : step;
var max = rules.max;
var qty = isNaN(value) ? min : value;

if (qty < min) {
qty = min;
}

if (max !== '' && qty > max) {
qty = max;
}

var steps = (qty - min) / step;

if (direction === 'up') {
steps = Math.ceil(steps - 1e-6);
} else if (direction === 'down') {
steps = Math.floor(steps + 1e-6);
} else {
steps = Math.round(steps);
}

qty = min + (steps * step);

if (max !== '' && qty > max) {
qty = max;
}

if (qty < min) {
qty = min;
}

return parseFloat(qty.toFixed(rules.precision));
}

function showInlineMessage($input, requested, adjusted) {
if (typeof WCUOMStep2025 === 'undefined' || !WCUOMStep2025.noticeText) {
return;
}

var message = WCUOMStep2025.noticeText
.replace('{product}', $input.data('wcuom-product') || '')
.replace('{requested}', requested)
.replace('{quantity}', adjusted);

var $wrapper = $input.closest('.quantity');

if (!$wrapper.length) {
return;
}

var $message = $wrapper.find('.wcuom-inline-message');

if (!$message.length) {
$message = $('<div class="wcuom-inline-message" aria-live="polite"></div>');
$wrapper.append($message);
}

$message.text(message);
}

function adjustInput($input, direction) {
var rules = getRules($input);
var raw = $input.val();
var requested = parseNumber(raw);
var adjusted = closestValid(requested, rules, direction || 'nearest');
var isAdjusting = $input.data('wcuom-adjusting') === true;

if (isAdjusting) {
return;
}

if (adjusted !== requested && !(isNaN(requested) && adjusted === rules.min)) {
showInlineMessage($input, raw, adjusted);
}

$input.data('wcuom-adjusting', true);
$input.val(adjusted).trigger('change');
$input.data('wcuom-adjusting', false);
}

$(document).on('click', '.quantity .ct-increase', function (event) {
event.preventDefault();
var $input = $(this).siblings('input.qty');

if ($input.length) {
adjustInput($input, 'up');
}
});

$(document).on('click', '.quantity .ct-decrease', function (event) {
event.preventDefault();
var $input = $(this).siblings('input.qty');

if ($input.length) {
adjustInput($input, 'down');
}
});

$(document).on('change blur', '.quantity input.qty', function () {
adjustInput($(this), 'nearest');
});
})(jQuery);
