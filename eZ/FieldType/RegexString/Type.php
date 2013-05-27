<?php
namespace eZ\UnconBundle\eZ\FieldType\RegexString;

use eZ\Publish\Core\FieldType\TextLine;
use eZ\Publish\API\Repository\Values\ContentType\FieldDefinition;
use eZ\UnconBundle\eZ\FieldType\Validator\RegexValidator;

class Type extends TextLine\Type
{
    public function getFieldTypeIdentifier()
    {
        return "regexstring";
    }

    public function validateValidatorConfiguration( $validatorConfiguration )
    {
        $validationErrors = array();

        foreach ( (array)$validatorConfiguration as $validatorIdentifier => $constraints )
        {
            if ( $validatorIdentifier !== 'RegexValidator' )
            {
                $validationErrors[] = new ValidationError(
                    "Validator '%validator%' is unknown",
                    null,
                    array(
                        "validator" => $validatorIdentifier
                    )
                );

                continue;
            }
        }

        return $validationErrors;
    }

    public function validate( FieldDefinition $fieldDefinition, $fieldValue )
    {
        $validatorConfiguration = $fieldDefinition->getValidatorConfiguration();
        $constraints = isset( $validatorConfiguration['RegexValidator'] ) ?
            $validatorConfiguration['RegexValidator'] :
            array();
        $validator = new RegexValidator;
        $validator->initializeWithConstraints( $constraints );

        if ( !$validator->validate( $fieldValue ) )
            return $validator->getMessage();

        return array();
    }
}