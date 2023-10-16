<?php namespace ProcessWire\GraphQL\Type\Fieldtype;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\InputObjectType;
use ProcessWire\GraphQL\Cache;
use ProcessWire\InputfieldSelectMultiple;
use ProcessWire\Field;
use ProcessWire\GraphQL\Utils;
use ProcessWire\GraphQL\Type\Fieldtype\Traits\SetValueTrait;
use ProcessWire\GraphQL\Type\Fieldtype\Traits\FieldTrait;
use ProcessWire\Page;

class FieldtypeCombo
{
  use FieldTrait;
  use SetValueTrait;

  public static $name = 'FieldtypeCombo';

  public static $inputName = 'FieldtypeComboInput';

  public static $description = 'Field that stores single and multi select options.';

  public static function type($field)
  {
    $type = Cache::type($field->name, function () use ($field) {
      return new ObjectType([
        'name' => $field->name,
        'description' => self::$description,
        'fields' => self::getFields($field)
      ]);
    });

    return $type;
  }

  public static function field($field)
  {
    return Cache::field($field->name, function () use ($field) {
      // description
      $desc = $field->description;
      if (!$desc) {
        $desc = "Field with the type of {$field->type}";
      }

      return [
        'name' => $field->name,
        'description' => $desc,
        'type' => self::type($field),
        'resolve' => function (Page $page, array $args) use ($field) {
          $fieldName = $field->name;

          return $page->$fieldName;
        }
      ];
    });
  }


  public static function inputType($field)
  {
    return Cache::type(self::getInputName($field), function () use ($field) {

      $type = new InputObjectType([
        'name' => self::getInputName($field),
        'description' => self::$description,
        'fields' => self::getFields($field)
      ]);

      return $type;
    });
  }

  public static function inputField($field)
  {
    return Cache::field("combo--{$field->name}", function () use ($field) {
      // description
      $desc = $field->description;
      if (!$desc) {
        $desc = "Field with the type of {$field->type}";
      }

      return [
        'name' => $field->name,
        'description' => $desc,
        'type' => self::inputType($field),
      ];
    });
  }

  public static function getName(Field $field = null)
  {
    if ($field instanceof Field) {
      return Utils::normalizeTypeName("{$field->name}".self::$inputName);
    }

    return self::$name;
  }

  public static function getInputName(Field $field = null)
  {
    if ($field instanceof Field) {
      return Utils::normalizeTypeName("{$field->name}".self::$inputName);
    }

    return self::$inputName;
  }

  public static function getFields($field) {
    $subfields = $field->getComboSettings()->getSubfields();
    $fields = [];

    foreach ($subfields as $subfield) {
      $fieldClass = self::pwSubfieldToGraphqlClass($subfield);
      if (is_null($fieldClass)) {
        continue;
      }

	  //TODO check permissions

      $fieldSettings = $fieldClass::field($subfield);

      if ($subfield->required) {
        $fieldSettings['type'] = Type::nonNull($fieldSettings['type']);
      }

      $fields[] = $fieldSettings;
      // bd($fieldSettings);
    }


    return $fields;
  }

  public static function pwSubfieldToGraphqlClass($subfield)
  {

	// replace underscores with empty
	  $type = Utils::normalizeTypeName($subfield->type);

    // use local field if available
    $className =
      "\\ProcessWire\\GraphQL\\Type\\Fieldtype\\Fieldtype" . $type;

    if (class_exists($className)) {
      return $className;
    }

    //TODO third party field

    return null;
  }
}
