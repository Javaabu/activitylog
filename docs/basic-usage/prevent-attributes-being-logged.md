---
title: Prevent specific attributes being logged
sidebar_position: 4
---

To prevent certain attributes ever being logged, you can use the `$logExceptAttributes` static property on your model. For example to never log the `password`:

```php
use Javaabu\Activitylog\Traits\LogsActivity;

class User extends Model {
    use LogsActivity;
    
    protected static array $logExceptAttributes = ['password'];
}
```
