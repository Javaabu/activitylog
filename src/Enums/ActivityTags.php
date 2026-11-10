<?php

namespace Javaabu\Activitylog\Enums;

use Javaabu\Activitylog\Contracts\Tag;

/**
 * The built-in tags: model events, rollbacks, and what javaabu/auth and javaabu/permissions log
 */
enum ActivityTags: string implements Tag
{
    case CREATED = 'created';
    case UPDATED = 'updated';
    case DELETED = 'deleted';
    case RESTORED = 'restored';
    case ROLLED_BACK = 'rolled_back';
    case LOGIN = 'login';
    case LOGOUT = 'logout';
    case FAILED_LOGIN = 'failed_login';
    case LOCKOUT = 'lockout';
    case PASSWORD_RESET = 'password_reset';
    case PASSWORD_RESET_LINK_SENT = 'password_reset_link_sent';
    case PASSWORD_CHANGED = 'password_changed';
    case TWO_FACTOR_ENABLED = 'two_factor_enabled';
    case TWO_FACTOR_DISABLED = 'two_factor_disabled';
    case PASSKEY_ADDED = 'passkey_added';
    case PASSKEY_REMOVED = 'passkey_removed';
    case ROLE_UPDATED = 'role_updated';
    case PERMISSIONS_UPDATED = 'permissions_updated';
    case EXPORTED = 'exported';
    case DEFAULT = 'default';

    public function key(): string
    {
        return $this->value;
    }

    public function label(): string
    {
        return match ($this) {
            self::CREATED => __('Created'),
            self::UPDATED => __('Updated'),
            self::DELETED => __('Deleted'),
            self::RESTORED => __('Restored'),
            self::ROLLED_BACK => __('Rolled back'),
            self::LOGIN => __('Logged in'),
            self::LOGOUT => __('Logged out'),
            self::FAILED_LOGIN => __('Failed login'),
            self::LOCKOUT => __('Locked out'),
            self::PASSWORD_RESET => __('Password reset'),
            self::PASSWORD_RESET_LINK_SENT => __('Password reset link sent'),
            self::PASSWORD_CHANGED => __('Password changed'),
            self::TWO_FACTOR_ENABLED => __('Two-factor enabled'),
            self::TWO_FACTOR_DISABLED => __('Two-factor disabled'),
            self::PASSKEY_ADDED => __('Passkey added'),
            self::PASSKEY_REMOVED => __('Passkey removed'),
            self::ROLE_UPDATED => __('Role changed'),
            self::PERMISSIONS_UPDATED => __('Permissions changed'),
            self::EXPORTED => __('Exported'),
            self::DEFAULT => __('Activity'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::CREATED,
            self::TWO_FACTOR_ENABLED,
            self::PASSKEY_ADDED => 'success',
            self::UPDATED,
            self::ROLE_UPDATED,
            self::PERMISSIONS_UPDATED => 'info',
            self::DELETED,
            self::FAILED_LOGIN,
            self::LOCKOUT => 'danger',
            self::RESTORED,
            self::ROLLED_BACK,
            self::PASSWORD_RESET,
            self::PASSWORD_RESET_LINK_SENT,
            self::PASSWORD_CHANGED,
            self::TWO_FACTOR_DISABLED,
            self::PASSKEY_REMOVED => 'warning',
            self::EXPORTED => 'primary',
            self::LOGIN,
            self::LOGOUT,
            self::DEFAULT => 'secondary',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::CREATED => 'plus',
            self::UPDATED => 'pencil',
            self::DELETED => 'trash-2',
            self::RESTORED => 'rotate-ccw',
            self::ROLLED_BACK => 'undo-2',
            self::LOGIN => 'log-in',
            self::LOGOUT => 'log-out',
            self::FAILED_LOGIN => 'shield-alert',
            self::LOCKOUT => 'lock',
            self::PASSWORD_RESET,
            self::PASSWORD_CHANGED,
            self::PASSKEY_ADDED,
            self::PASSKEY_REMOVED => 'key-round',
            self::PASSWORD_RESET_LINK_SENT => 'mail',
            self::TWO_FACTOR_ENABLED => 'shield-check',
            self::TWO_FACTOR_DISABLED => 'shield-off',
            self::ROLE_UPDATED => 'users',
            self::PERMISSIONS_UPDATED => 'shield',
            self::EXPORTED => 'download',
            self::DEFAULT => 'activity',
        };
    }
}
