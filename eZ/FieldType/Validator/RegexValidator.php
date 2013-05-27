<?php
namespace eZ\UnconBundle\eZ\FieldType\Validator;

use eZ\Publish\Core\FieldType\Validator;
use eZ\Publish\Core\FieldType\ValidationError;
use eZ\Publish\Core\FieldType\Value as BaseValue;

/**
 * Validator for checking validity of email addresses. Both form and MX record validity checking are provided
 *
 * @property int $maxStringLength The maximum allowed length of the string.
 * @property int $minStringLength The minimum allowed length of the string.
 */
class RegexValidator extends Validator
{
    protected $constraints = array(
        "Regex" => false,
    );

    protected $constraintsSchema = array(
        "Regex" => array(
            "type" => "string",
        )
    );

    /**
     * @abstract
     *
     * @param mixed $constraints
     *
     * @return mixed
     */
    public function validateConstraints( $constraints )
    {
        $validationErrors = array();
        foreach ( $constraints as $name => $value )
        {
            switch ( $name )
            {
                case "Regex":

                    if ( $value !== false )
                    {
                        $delimiter = substr( $value, 0, 1 );
                        if ( strstr( substr( $value, 1 ), $delimiter ) === false )
                        {
                            $validationErrors[] = new ValidationError(
                                "Validator parameter '%parameter%' value must be a valid PCRE",
                                null,
                                array(
                                    "parameter" => $name
                                )
                            );
                        }
                    }
                    break;
                default:
                    $validationErrors[] = new ValidationError(
                        "Validator parameter '%parameter%' is unknown",
                        null,
                        array(
                            "parameter" => $name
                        )
                    );
            }
        }

        return $validationErrors;
    }

    /**
     * Perform validation on $value.
     *
     * Will return true when all constraints are matched. If one or more
     * constraints fail, the method will return false.
     *
     * When a check against a constraint has failed, an entry will be added to the
     * $errors array.
     *
     * @abstract
     *
     * @param \eZ\Publish\Core\FieldType\Value $value
     *
     * @return boolean
     */
    public function validate( BaseValue $value )
    {
        if ( $this->constraints['Regex'] === false )
            return true;

        if ( preg_match( $this->constraints['Regex'], $value->text ) )
        {
            return true;
        }

        $this->errors[] = new ValidationError(
            "The value must match the format.",
            null,
            array()
        );
        return false;
    }
}
