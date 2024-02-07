---
title: Customising log options
---

If you want further customization of the logging options, you can override the `getActivitylogOptions` methods of the `LogsActivity` trait:

```php
use Javaabu\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Category extends Model {
    use LogsActivity;
    
    public function getActivitylogOptions(): LogOptions
    {
        /** @var Model $this */
        return LogOptions::defaults()
            ->logOnly(static::$logAttributes ?? ['*'])
            ->dontLogIfAttributesChangedOnly((static::$ignoreChangedAttributes ?? []))
            ->logExcept(array_merge($this->getHidden(), (static::$logExceptAttributes ?? [])))
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
```
