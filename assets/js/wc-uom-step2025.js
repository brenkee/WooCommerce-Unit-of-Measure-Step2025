(function ($) {
'use strict';

function parseNumber(value) {
var num = parseFloat(value);
return isNaN(num) ? 0 : num;
}

function getTolerance(precision) {
var epsilon = Math.pow(10, -(precision || 0)) / 2;
return epsilon > 0 ? epsilon : 1e-6;
}

function countDecimals(numberString) {
    if (typeof numberString !== 'string') {
        return 0;
    }

    var parts = numberString.split('.');
    return parts.length === 2 ? parts[1].length : 0;
}

function getRules($input) {
    var dataStep = parseNumber($input.data('wcuom-step'));
    var attrStep = parseNumber($input.attr('step'));
    var step = dataStep || attrStep || 1;

    var minAttr = typeof $input.data('wcuom-min') !== 'undefined' ? $input.data('wcuom-min') : $input.attr('min');
    var maxAttr = typeof $input.data('wcuom-max') !== 'undefined' ? $input.data('wcuom-max') : $input.attr('max');

    var min = minAttr === '' || typeof minAttr === 'undefined' ? step : parseNumber(minAttr);
    var max = maxAttr === '' || typeof maxAttr === 'undefined' ? '' : parseNumber(maxAttr);

    var precision = parseInt($input.data('wcuom-precision'), 10);
    if (isNaN(precision)) {
        var stepDecimals = countDecimals(String($input.attr('step') || ''));
        var minDecimals = countDecimals(String(minAttr || ''));
        precision = Math.max(stepDecimals, minDecimals);
    }

    var allowDecimals = $input.data('wcuom-allow-decimal') === 'yes';
    if (!allowDecimals && (step % 1 !== 0 || min % 1 !== 0)) {
        allowDecimals = true;
    }

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

function hideInlineMessage($input) {
var $wrapper = $input.closest('.quantity');

if (!$wrapper.length) {
return;
}

var $message = $wrapper.find('.wcuom-inline-message');

if (!$message.length) {
return;
}

var hideTimer = $message.data('wcuom-hide-timer');

if (hideTimer) {
clearTimeout(hideTimer);
}

$message.stop(true, true).fadeOut(300, function () {
$message.removeClass('is-visible');
});
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

$message.stop(true, true).text(message).fadeIn(200).addClass('is-visible');

var existingTimer = $message.data('wcuom-hide-timer');

if (existingTimer) {
clearTimeout(existingTimer);
}

$message.data('wcuom-hide-timer', setTimeout(function () {
$message.fadeOut(400, function () {
$message.removeClass('is-visible');
});
}, 5000));
}

function adjustInput($input, direction) {
var rules = getRules($input);
var raw = $input.val();
var parsedRequested = parseFloat(raw);
var hasRequestedNumber = !isNaN(parsedRequested);
var requested = hasRequestedNumber ? parsedRequested : 0;
var adjusted = closestValid(hasRequestedNumber ? parsedRequested : NaN, rules, direction || 'nearest');
var isAdjusting = $input.data('wcuom-adjusting') === true;
var tolerance = getTolerance(rules.precision);

if (isAdjusting) {
return;
}

var difference = Math.abs(adjusted - requested);
var shouldShowMessage = !hasRequestedNumber || difference > tolerance;

if (shouldShowMessage) {
showInlineMessage($input, raw, adjusted);
} else {
hideInlineMessage($input);
}

$input.data('wcuom-adjusting', true);
$input.val(adjusted).trigger('change');
$input.data('wcuom-adjusting', false);
}

$(document).on('click', '.quantity .ct-increase', function (event) {
    event.preventDefault();
    event.stopPropagation();
    event.stopImmediatePropagation();
    var $wrapper = $(this).closest('.quantity');
    var $input = $wrapper.find('input.qty').first();

    if ($input.length) {
        adjustInput($input, 'up');
    }
});

$(document).on('click', '.quantity .ct-decrease', function (event) {
    event.preventDefault();
    event.stopPropagation();
    event.stopImmediatePropagation();
    var $wrapper = $(this).closest('.quantity');
    var $input = $wrapper.find('input.qty').first();

    if ($input.length) {
        adjustInput($input, 'down');
    }
});

$(document).on('change blur', '.quantity input.qty', function () {
adjustInput($(this), 'nearest');
});
})(jQuery);
