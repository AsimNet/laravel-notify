<?php

namespace Asimnet\Notify\Enums;

/**
 * Comparison operators for segment conditions.
 *
 * عوامل المقارنة لشروط الشريحة.
 */
enum ComparisonOperator: string
{
    // Text operators
    case Equals = 'equals';
    case NotEqual = 'notEqual';
    case Contains = 'contains';
    case NotContains = 'notContains';
    case StartsWith = 'startsWith';
    case EndsWith = 'endsWith';
    case Blank = 'blank';
    case NotBlank = 'notBlank';

    // Number operators (also used for dates)
    case GreaterThan = 'greaterThan';
    case GreaterThanOrEqual = 'greaterThanOrEqual';
    case LessThan = 'lessThan';
    case LessThanOrEqual = 'lessThanOrEqual';
    case InRange = 'inRange';
}
