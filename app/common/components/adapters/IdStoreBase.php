<?php

namespace common\components\adapters;

use common\sync\SyncUser;
use yii\base\Component;

abstract class IdStoreBase extends Component implements IdStoreInterface
{
    public const ADAPTER_FAKE = 'fake';
    public const ADAPTER_GOOGLE_SHEETS = 'googlesheets';
    public const ADAPTER_WORKDAY = 'workday';
    public const ADAPTER_SECURE_USER = 'secureuser';

    protected static $adapters = [
        self::ADAPTER_FAKE => FakeIdStore::class,
        self::ADAPTER_GOOGLE_SHEETS => GoogleSheetsIdStore::class,
        self::ADAPTER_WORKDAY => WorkdayIdStore::class,
        self::ADAPTER_SECURE_USER => SecureUserIdStore::class,
    ];

    public static function getAdapterClassFor($adapterName)
    {
        if (array_key_exists($adapterName, self::$adapters)) {
            return self::$adapters[$adapterName];
        }

        throw new \InvalidArgumentException(sprintf(
            "Unknown ID Store adapter (%s). Must be one of the following: \n%s\n",
            $adapterName,
            join("\n", array_keys(self::$adapters))
        ), 1491316896);
    }

    /**
     * Convert user information keyed on ID Store field names into a SyncUser object.
     *
     * @param array $idStoreUserInfo SyncUser info from ID Store.
     * @return SyncUser An object representing that user info in a standard way.
     */
    protected static function getAsUser($idStoreUserInfo)
    {
        return new SyncUser(self::translateToInternalFieldNames($idStoreUserInfo));
    }

    /**
     * Convert information about a list of users (each being an array of user
     * information keyed on ID Store field names) into a list of SyncUser objects.
     *
     * @param array[] $idStoreUserInfoList A list of users' info from ID Store.
     * @return SyncUser[] A list of objects representing those users' info in a
     *     standard way.
     */
    protected static function getAsUsers($idStoreUserInfoList)
    {
        return array_map(function ($entry) {
            return self::getAsUser($entry);
        }, $idStoreUserInfoList);
    }

    /**
     * Get the list of Internal field names, indexed by the equivalent ID Store
     * field names.
     *
     * @var array<string,string>
     */
    abstract public static function getFieldNameMap();

    /**
     * Get the internal field name corresponding to the given ID Store field
     * name. If there is no such internal field, return null.
     *
     * @param string $idStoreFieldName
     * @return string|null
     */
    protected static function getInternalFieldNameFor(string $idStoreFieldName)
    {
        $internalFieldNames = static::getFieldNameMap();
        return $internalFieldNames[$idStoreFieldName] ?? null;
    }

    /**
     * Take the given user info and translate the keys from the field names used
     * by the ID Store to those used internally.
     *
     * @param array $userFromIdStore
     * @return array The array of user information, keyed on the internal
     *     version of the field names.
     */
    public static function translateToInternalFieldNames(array $userFromIdStore)
    {
        $internalUser = [];

        foreach ($userFromIdStore as $idStoreFieldName => $value) {
            $internalFieldName = self::getInternalFieldNameFor($idStoreFieldName);
            if ($internalFieldName !== null) {
                $internalUser[$internalFieldName] = $value;
            }
        }

        return $internalUser;
    }

    /**
     * {@inheritdoc}
     */
    public function updateSyncDatesIfSupported(array $employeeIds)
    {
        // Does nothing unless overridden in a subclass.
    }
}
