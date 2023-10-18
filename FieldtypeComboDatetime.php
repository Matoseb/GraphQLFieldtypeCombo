<?php namespace ProcessWire\GraphQL\Type\Fieldtype;

use ProcessWire\GraphQL\Type\Fieldtype\FieldtypeDatetime;
use ProcessWire\GraphQL\Cache;
use GraphQL\Type\Definition\Type;

class FieldtypeComboDatetime extends FieldtypeDatetime
{ 

  public static function field($field)
  {


    $oldField = FieldtypeDatetime::field($field);

    return Cache::field("combo--" . $field->name, function () use ($field, $oldField) {
      // description
        

        // clone array
        $newField = $oldField;
        
        $newField['resolve'] = function ($combo, array $args) use ($field) {
            $name = $field->name;
            $rawValue = $combo->$name; // ComboValueFormatted

            if (isset($args['format'])) {
              $format = $args['format'];
              if ($rawValue) {
                return date($format, $rawValue);
              } else {
                return "";
              }
            }
            
            return $rawValue;
          };

      return $newField;
    });
  }

  public static function setValue($combo, $field, $value)
  {
  	$fieldName = $field->name;
  	$combo->$fieldName = $value->format('Y-m-d H:i:s');
  }
}