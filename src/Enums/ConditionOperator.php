<?php

namespace Asimnet\Notify\Enums;

/**
 * Logical operator for combining conditions in a group.
 *
 * العامل المنطقي لدمج الشروط في مجموعة.
 */
enum ConditionOperator: string
{
    case And = 'and';
    case Or = 'or';
}
