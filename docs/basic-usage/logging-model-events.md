---
title: Logging model events
sidebar_position: 1
---

To log model events, simply add the `LogsActivity` trait to your model.

```php
use Javaabu\Activitylog\Traits\LogsActivity;

class Category extends Model {
    use LogsActivity;
}
```

By default, this trait is configured to log all model attributes except for `hidden` attributes. It will not submit empty logs and a log is created only if an attribute value changes.
