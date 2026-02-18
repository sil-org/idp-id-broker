<?php

namespace common\sync;

use Exception;
use InvalidArgumentException;

/*
 * SyncUser is a user model representing ID Store user records using normalized field names.
 */
class SyncUser
{
    public const ACTIVE = 'active';
    public const DISPLAY_NAME = 'display_name';
    public const EMAIL = 'email';
    public const EMPLOYEE_ID = 'employee_id';
    public const FIRST_NAME = 'first_name';
    public const GROUPS = 'groups';
    public const HR_CONTACT_NAME = 'hr_contact_name';
    public const HR_CONTACT_EMAIL = 'hr_contact_email';
    public const LAST_NAME = 'last_name';
    public const LOCKED = 'locked';
    public const MANAGER_EMAIL = 'manager_email';
    public const PERSONAL_EMAIL = 'personal_email';
    public const REQUIRE_MFA = 'require_mfa';
    public const USERNAME = 'username';

    /**
     * The values (indexed by field name) for the fields which have been set.
     *
     * @var array<string,mixed>
     */
    private $values = [];

    /**
     * Create a new User model from the given user info, which must be an
     * associative array with keys matching this class's constants and which
     * must contain at least an `employee_id`.
     *
     * @param array $userInfo The user info for populating this User object.
     */
    public function __construct($userInfo = [])
    {
        if (empty($userInfo[self::EMPLOYEE_ID])) {
            throw new InvalidArgumentException('Employee ID cannot be empty.', 1493733219);
        }

        // Set all of the provided fields, taking whatever value was given.
        foreach (self::getAllFieldNames() as $fieldName) {
            if (array_key_exists($fieldName, $userInfo)) {
                $this->values[$fieldName] = $userInfo[$fieldName];
            }
        }

        // Ensure fields with stricter constraints have valid values.
        $this->values[self::EMPLOYEE_ID] = (string)$userInfo[self::EMPLOYEE_ID];
        $this->setLocked($userInfo[self::LOCKED] ?? null);
        $this->setRequireMfa($userInfo[self::REQUIRE_MFA] ?? null);
    }

    /**
     * Get the list of all of the field names supported by this User model.
     *
     * @return string[]
     */
    public static function getAllFieldNames()
    {
        return [
            self::ACTIVE,
            self::DISPLAY_NAME,
            self::EMAIL,
            self::EMPLOYEE_ID,
            self::FIRST_NAME,
            self::GROUPS,
            self::HR_CONTACT_NAME,
            self::HR_CONTACT_EMAIL,
            self::LAST_NAME,
            self::LOCKED,
            self::MANAGER_EMAIL,
            self::PERSONAL_EMAIL,
            self::REQUIRE_MFA,
            self::USERNAME,
        ];
    }

    /**
     * @return string
     */
    public function getEmployeeId()
    {
        return $this->values[self::EMPLOYEE_ID];
    }

    /**
     * @return null|string
     */
    public function getFirstName()
    {
        return $this->values[self::FIRST_NAME] ?? null;
    }

    /**
     * @return null|string
     */
    public function getLastName()
    {
        return $this->values[self::LAST_NAME] ?? null;
    }

    /**
     * @return null|string
     */
    public function getDisplayName()
    {
        return $this->values[self::DISPLAY_NAME] ?? null;
    }

    /**
     * @return null|string
     */
    public function getUsername()
    {
        return $this->values[self::USERNAME] ?? null;
    }

    /**
     * @return null|string
     */
    public function getEmail()
    {
        return $this->values[self::EMAIL] ?? null;
    }

    /**
     * @return null|string
     */
    public function getActive()
    {
        return $this->values[self::ACTIVE] ?? null;
    }

    /**
     * @return null|string
     */
    public function getLocked()
    {
        return $this->values[self::LOCKED] ?? null;
    }

    /**
     * @return null|string
     */
    public function getManagerEmail()
    {
        return $this->values[self::MANAGER_EMAIL] ?? null;
    }

    /**
     * @return null|string
     */
    public function getRequireMfa()
    {
        return $this->values[self::REQUIRE_MFA] ?? null;
    }

    /**
     * @return null|string
     */
    public function getPersonalEmail()
    {
        return $this->values[self::PERSONAL_EMAIL] ?? null;
    }

    /**
     * @return null|string
     */
    public function getGroups()
    {
        return $this->values[self::GROUPS] ?? null;
    }

    /**
     * @return null|string
     */
    public function getHRContactName()
    {
        return $this->values[self::HR_CONTACT_NAME] ?? null;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getHRContactEmail(): string
    {
        $email = $this->values[self::HR_CONTACT_EMAIL] ?? null;

        if (empty($email)) {
            throw new Exception("HR Contact Email is empty");
        }

        return $email;
    }

    /**
     * @return string
     */
    public function getStringForLogMessage()
    {
        return sprintf(
            'ID: "%s" username: "%s" email: "%s"',
            // use var_export so null becomes "NULL"
            var_export($this->getEmployeeId(), true),
            var_export($this->getUsername(), true),
            var_export($this->getEmail(), true)
        );
    }

    public function __toString()
    {
        return \json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }

    protected function isAffirmative($value)
    {
        if ($value === null) {
            return false;
        } elseif (is_bool($value)) {
            return $value;
        }

        $lowercasedValue = strtolower(trim($value));

        return in_array($lowercasedValue, ['true', 'yes', '1'], true);
    }

    protected function setLocked($input)
    {
        if ($input === null) {
            return;
        }

        $this->values[self::LOCKED] = $this->isAffirmative($input) ? 'yes' : 'no';
    }

    protected function setRequireMfa($input)
    {
        if ($input === null) {
            return;
        }

        $this->values[self::REQUIRE_MFA] = $this->isAffirmative($input) ? 'yes' : 'no';
    }

    /**
     * Get this User object's data as an associative array.
     *
     * NOTE: This will return all fields that have been explicitly set,
     * regardless of what value they were set to (even null).
     *
     * @return array
     */
    public function toArray()
    {
        return $this->values;
    }
}
