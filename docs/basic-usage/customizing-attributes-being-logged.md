---
title: Customizing attributes being logged
sidebar_position: 2
---

To customize which attributes you want to log, you can use the `$logAttributes` static property on your model. For example to only log the `name` and `slug`:

```php
use Javaabu\Activitylog\Traits\LogsActivity;

class Category extends Model {
    use LogsActivity;
    
    protected static array $logAttributes = ['name', 'slug'];
}
```
