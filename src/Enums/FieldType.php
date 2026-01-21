<?php

namespace Asimnet\Notify\Enums;

/**
 * Types of fields that can be used in segment conditions.
 *
 * أنواع الحقول التي يمكن استخدامها في شروط الشريحة.
 */
enum FieldType: string
{
    case Text = 'text';
    case Number = 'number';
    case Date = 'date';
    case Set = 'set';
}
