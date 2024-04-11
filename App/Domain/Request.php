<?php

namespace App\Domain;

use App\Exception\InvalidArgumentException;

class Request extends Domain
{
    protected $messages = [];
    protected $structure = [];
    protected $content = [];

    public function __construct(array $constructor)
    {
        parent::__construct($constructor);

        if ($this->validatefields()) {
            return $this->content;
        }

        throw new InvalidArgumentException('Invalid arguments provided: ' . implode("\n", $this->messages));
    }

    public function validatefields(): bool
    {
        $this->messages = [];
        foreach ($this->structure as $field => $value) {
            $functionName = "validate" . ucwords($value);
            if ($res = !$this->$functionName($this->content[$field])) {
                $this->messages[] = 'Invalid '. $field. ' value.';
            }
        }
        return count($this->messages) ? false : true;
    }

    protected function validateInt($value) : bool
    {
        //validate if is int
        return is_int($value);
    }

    protected function validateDouble($value) : bool
    {
        //validate if is double
        return is_int($value) || is_double($value);
    }

    protected function validateFloat($value) : bool
    {
        //validate if is float
        return is_float($value);
    }

    protected function validatePositiveInt($value) : bool
    {
        return (is_int($value) && $value>0);
    }

    protected function validatePositiveOrZeroInt($value) : bool
    {
        return (is_int($value) && $value>=0);
    }

    protected function validateString($value) : bool
    {
        //validate if is string
        return is_string($value);
    }

    protected function validateArray($value) : bool
    {
        return is_array($value);
    }

    protected function validateOptionalArray($value) : bool
    {
        return empty($value) || is_array($value);
    }

    protected function validateEmail($email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    protected function validatePassword($password): bool
    {
        // $pattern = "/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?!.* )(?=.*[^a-zA-Z0-9]).{8,16}$/"; // not working
        $pattern = "/^\S*(?=\S{8,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])\S*$/";
        return preg_match($pattern, $password);
    }

    protected function validateConfirmPassword($confirmPassword): bool
    {
        return $this->content['password'] === $confirmPassword ? true : false;
    }

    protected function validateName($name): bool
    {
        $tmp = explode(' ', trim($name));
        return count($tmp) > 1 ? true : false;
    }

    protected function validateCompany($name): bool
    {
        return strlen(trim($name)) > 2 ? true : false;
    }

    protected function validateEndUserAccount($value): bool
    {
        //ToDo
        return true;
    }

    protected function validateSfAccount($value): bool
    {
        //ToDo
        return true;
    }
}
