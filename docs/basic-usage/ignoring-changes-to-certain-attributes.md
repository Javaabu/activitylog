---
title: Ignoring changes to certain attributes
sidebar_position: 3
---

If you want to ignore changes to certain attributes, you can use the `$ignoreChangedAttributes` static property on your model. In the example below a log won't be created if only the `updated_at` or `created_at` attributes changes:

```php
use Javaabu\Activitylog\Traits\LogsActivity;

class Category extends Model {
    use LogsActivity;
    
    protected static array $ignoreChangedAttributes = ['created_at', 'updated_at'];
}
```
