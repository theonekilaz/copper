<?php


namespace Copper\Component\Validator;


use Copper\Component\DB\DBModel;
use Copper\Entity\ValidatorResponseResultEntity;
use Copper\FunctionResponse;
use Copper\FunctionResponse\ValidatorResponse;
use Copper\Handler\VarHandler;
use Copper\Kernel;
use Copper\Traits\ComponentHandlerTrait;

class ValidatorHandler
{
    use ComponentHandlerTrait;

    const VALUE_TYPE_IS_NOT_STRING = 'valueTypeIsNotString';
    const VALUE_TYPE_IS_NOT_INTEGER = 'valueTypeIsNotInteger';
    const VALUE_TYPE_IS_NOT_FLOAT = 'valueTypeIsNotFloat';
    const VALUE_TYPE_IS_NOT_DECIMAL = 'valueTypeIsNotDecimal';
    const VALUE_TYPE_IS_NOT_BOOLEAN = 'valueTypeIsNotBoolean';
    const VALUE_TYPE_IS_NOT_ALPHABETIC_OR_NUMERIC = 'valueTypeIsNotAlphabeticOrNumeric';
    const VALUE_TYPE_IS_NOT_ALPHABETIC = 'valueTypeIsNotAlphabetic';
    const VALUE_TYPE_IS_NOT_NUMERIC = 'valueTypeIsNotNumeric';
    const WRONG_VALIDATION_TYPE = 'wrongValidationType';

    const WRONG_ENUM_VALUE = 'wrongEnumValue';
    const TOO_MANY_DECIMAL_DIGITS = 'tooManyDecimalDigits';
    const VALUE_IS_NOT_POSITIVE = 'valueIsNotPositive';
    const VALUE_IS_NOT_NEGATIVE = 'valueIsNotNegative';
    const VALUE_IS_LESS_THAN_MINIMUM = 'valueIsLessThanMinimum';
    const VALUE_IS_GREATER_THAN_MAXIMUM = 'valueIsGreaterThanMaximum';
    const MIN_LENGTH_REQUIRED = 'minLengthRequired';
    const MAX_LENGTH_REACHED = 'maxLengthReached';
    const WRONG_LENGTH = 'wrongLength';
    const VALUE_CANNOT_BE_EMPTY = 'valueCannotBeEmpty';
    const VALUE_DOES_NOT_MATCH = 'valueDoesNotMatch';

    const INVALID_VALUE_FORMAT = 'invalidValueFormat';
    const INVALID_EMAIL_FORMAT = 'invalidEmailFormat';
    const INVALID_PHONE_FORMAT = 'invalidPhoneFormat';
    const INVALID_DATE_FORMAT = 'invalidDateFormat';
    const INVALID_TIME_FORMAT = 'invalidTimeFormat';
    const INVALID_DATETIME_FORMAT = 'invalidDateTimeFormat';
    const INVALID_YEAR_FORMAT = 'invalidYearFormat';

    /** @var ValidatorRule[] */
    private $rules;

    /** @var ValidatorConfigurator */
    public $config;

    /**
     * ValidatorHandler constructor.
     *
     * @param string $configFilename
     * @param ValidatorConfigurator|null $config
     */
    public function __construct(string $configFilename = Kernel::VALIDATOR_CONFIG_FILE, ValidatorConfigurator $config = null)
    {
        $this->config = $config ?? $this->configure(ValidatorConfigurator::class, $configFilename);

        $this->rules = [];
    }

    /**
     * @param ValidatorRule $rule
     * @return ValidatorRule
     */
    public function addRule(ValidatorRule $rule)
    {
        $this->rules[] = $rule;

        return $rule;
    }

    // -------------------- Shortcuts --------------------

    /**
     * @param string $name
     * @param bool $required
     *
     * @return ValidatorRule
     */
    public function addStringRule(string $name, $required = false)
    {
        return $this->addRule(ValidatorRule::string($name, $required));
    }

    /**
     * @param string $name
     * @param string $match
     *
     * @return ValidatorRule
     */
    public function addMatchRule(string $name, string $match)
    {
        return $this->addRule(ValidatorRule::string($name, true))->match($match);
    }

    /**
     * @param string $name
     * @param bool $required
     *
     * @return ValidatorRule
     */
    public function addIntegerRule(string $name, $required = false)
    {
        return $this->addRule(ValidatorRule::integer($name, $required));
    }

    /**
     * @param string $name
     * @param bool $required
     *
     * @return ValidatorRule
     */
    public function addIntegerNegativeRule(string $name, $required = false)
    {
        return $this->addRule(ValidatorRule::integer($name, $required))->negative();
    }

    /**
     * @param string $name
     * @param bool $required
     *
     * @return ValidatorRule
     */
    public function addIntegerPositiveRule(string $name, $required = false)
    {
        return $this->addRule(ValidatorRule::integer($name, $required))->positive();
    }

    /**
     * @param string $name
     * @param bool $required
     *
     * @return ValidatorRule
     */
    public function addBooleanRule(string $name, $required = false)
    {
        return $this->addRule(ValidatorRule::boolean($name, $required));
    }

    /**
     * @param string $name
     * @param bool $required
     * @param int|null $maxDecimals
     *
     * @return ValidatorRule
     */
    public function addFloatRule(string $name, $required = false, $maxDecimals = null)
    {
        return $this->addRule(ValidatorRule::float($name, $required, $maxDecimals));
    }

    /**
     * @param string $name
     * @param bool $required
     * @param int|null $maxDecimals
     *
     * @return ValidatorRule
     */
    public function addFloatNegativeRule(string $name, $required = false, $maxDecimals = null)
    {
        return $this->addRule(ValidatorRule::float($name, $required, $maxDecimals))->negative();
    }

    /**
     * @param string $name
     * @param bool $required
     * @param int|null $maxDecimals
     *
     * @return ValidatorRule
     */
    public function addFloatPositiveRule(string $name, $required = false, $maxDecimals = null)
    {
        return $this->addRule(ValidatorRule::float($name, $required, $maxDecimals))->positive();
    }

    /**
     * @param string $name
     * @param bool $required
     * @param int|null $maxDecimals
     *
     * @return ValidatorRule
     */
    public function addDecimalRule(string $name, $required = false, $maxDecimals = null)
    {
        return $this->addRule(ValidatorRule::decimal($name, $required, $maxDecimals));
    }

    /**
     * @param string $name
     * @param bool $required
     * @param int|null $maxDecimals
     *
     * @return ValidatorRule
     */
    public function addDecimalNegativeRule(string $name, $required = false, $maxDecimals = null)
    {
        return $this->addRule(ValidatorRule::decimal($name, $required, $maxDecimals))->negative();
    }

    /**
     * @param string $name
     * @param bool $required
     * @param int|null $maxDecimals
     *
     * @return ValidatorRule
     */
    public function addDecimalPositiveRule(string $name, $required = false, $maxDecimals = null)
    {
        return $this->addRule(ValidatorRule::decimal($name, $required, $maxDecimals))->positive();
    }

    /**
     * @param string $name
     * @param bool $required
     *
     * @return ValidatorRule
     */
    public function addEmailRule(string $name, $required = false)
    {
        return $this->addRule(ValidatorRule::email($name, $required));
    }

    /**
     * @param string $name
     * @param bool $required
     *
     * @return ValidatorRule
     */
    public function addPhoneRule(string $name, $required = false)
    {
        return $this->addRule(ValidatorRule::phone($name, $required));
    }

    /**
     * @param string $name
     * @param array|null $values
     * @param bool $required
     *
     * @return ValidatorRule
     */
    public function addEnumRule(string $name, $values = null, $required = false)
    {
        return $this->addRule(ValidatorRule::enum($name, $values, $required));
    }

    /**
     * @param string $name
     * @param DBModel $model
     * @param bool $required
     *
     * @return ValidatorRule
     */
    public function addModelEnumRule(string $name, DBModel $model, $required = false)
    {
        $values = $model->getFieldEnumValues($name);

        return $this->addRule(ValidatorRule::enum($name, $values, $required));
    }

    /**
     * @param string $name
     * @param bool $required
     * @param string|null $dateFormat
     *
     * @return ValidatorRule
     */
    public function addDateRule(string $name, $required = false, $dateFormat = null)
    {
        return $this->addRule(ValidatorRule::date($name, $required, $dateFormat));
    }

    /**
     * @param string $name
     * @param string|int $year
     * @param string|int $month
     * @param string|int $day
     * @param bool $required
     *
     * @return ValidatorRule
     */
    public function addSplitDateRule(string $name, string $year, string $month, string $day, $required = false)
    {
        return $this->addRule(ValidatorRule::splitDate($name, $year, $month, $day, $required));
    }

    /**
     * @param string $name
     * @param bool $required
     *
     * @return ValidatorRule
     */
    public function addTimeRule(string $name, $required = false)
    {
        return $this->addRule(ValidatorRule::time($name, $required));
    }

    /**
     * @param string $name
     * @param bool $required
     *
     * @return ValidatorRule
     */
    public function addDateTimeRule(string $name, $required = false)
    {
        return $this->addRule(ValidatorRule::datetime($name, $required));
    }

    /**
     * @param string $name
     * @param bool $required
     *
     * @return ValidatorRule
     */
    public function addYearRule(string $name, $required = false)
    {
        return $this->addRule(ValidatorRule::year($name, $required));
    }

    /**
     * @param string $name
     * @param bool $required
     *
     * @return ValidatorRule
     */
    public function addNumericRule(string $name, $required = false)
    {
        return $this->addRule(ValidatorRule::numeric($name, $required));
    }

    /**
     * @param string $name
     * @param bool $required
     * @param bool $allowSpaces
     *
     * @return ValidatorRule
     */
    public function addAlphaRule(string $name, $required = false, $allowSpaces = true)
    {
        return $this->addRule(ValidatorRule::alpha($name, $required, $allowSpaces));
    }

    /**
     * @param string $name
     * @param bool $required
     * @param bool $allowSpaces
     *
     * @return ValidatorRule
     */
    public function addAlphaNumericRule(string $name, $required = false, $allowSpaces = true)
    {
        return $this->addRule(ValidatorRule::alphaNumeric($name, $required, $allowSpaces));
    }

    // -------- END of shortcuts ----------------

    public function clearRules()
    {
        $this->rules = [];
    }

    public function validateModel($params, DBModel $model)
    {
        // TODO ... need all rules validation
        return FunctionResponse::createSuccess('ok');

        foreach ($model->getFieldNames() as $name) {
            $field = $model->getFieldByName($name);

            $length = $field->getMaxLength();

            if ($field->typeIsInteger())
                $rule = ValidatorRule::integer($name, ($field->getNull() !== false))->maxLength($field->getLength());

            else if ($field->typeIsFloat())
                $rule = ValidatorRule::float($name);

            else if ($field->typeIsBoolean())
                $rule = ValidatorRule::boolean($name);

            else if ($field->typeIsEnum())
                $rule = ValidatorRule::enum($name)->maxLength($field->getLength());

            else if ($field->typeIsDecimal())
                $rule = ValidatorRule::decimal($name, $field->getLength()[0], $field->getLength()[1]);

            else if ($field->typeIsDate())
                $rule = ValidatorRule::date($name, ($field->getNull() !== false));

            else if ($field->typeIsTime())
                $rule = ValidatorRule::time($name);

            else if ($field->typeIsDatetime())
                $rule = ValidatorRule::datetime($name);

            else if ($field->typeIsYear())
                $rule = ValidatorRule::year($name);

            else
                $rule = ValidatorRule::string($name);

            if ($field->getNull() === false)
                $rule->required();

            $this->addRule($rule);
        }
    }

    /**
     * @param FunctionResponse $validationRes
     * @param string|null $lang
     * @param string|null $textClass
     *
     * @return ValidatorResponseResultEntity
     */
    private function translateErrorRes(FunctionResponse $validationRes, ?string $lang, ?string $textClass)
    {
        $result = $validationRes->result;

        $result = VarHandler::isArray($result) ? $result : [$result];

        $msg = $validationRes->msg;
        if ($textClass !== null && method_exists($textClass, $validationRes->msg))
            $msg = $textClass::{$validationRes->msg}($lang, $result);

        return new ValidatorResponseResultEntity($validationRes->msg, $msg, $validationRes->result, $validationRes->status);
    }

    /**
     * @param array $params
     * @param string $lang
     * @param string|null $textClass
     *
     * @return ValidatorResponse
     */
    public function validate(array $params, $lang = 'en', string $textClass = null)
    {
        $response = new ValidatorResponse();

        $errors = [];

        foreach ($this->rules as $rule) {
            $validationRes = $rule->validate($params, $rule->getName());

            if ($validationRes->hasError())
                $errors[$rule->getName()] = $this->translateErrorRes($validationRes, $lang, $textClass);
        }

        return $response->successOrError(count($errors) === 0, $errors);
    }
}