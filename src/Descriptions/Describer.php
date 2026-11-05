<?php

namespace Javaabu\Activitylog\Descriptions;

use BackedEnum;
use Carbon\Carbon;
use Closure;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Javaabu\Activitylog\Activitylog;
use Javaabu\Activitylog\Models\Activity;
use Javaabu\Activitylog\Support\Translations;
use Javaabu\Activitylog\Tags\ActivityTag;
use Stringable;
use Throwable;
use UnitEnum;

/**
 * Turns a log into a readable sentence in the current locale:
 * "Ahmed updated the status of Invoice #12 from Draft to Paid"
 *
 * Looks for, in order: the subject's describeActivity(), descriptions registered with
 * Activitylog::describe() or `activitylog.descriptions` for the subject's model, then for any
 * model, the built-in sentences, and finally the tag label. Bind a subclass to change it.
 */
class Describer
{
    /** Up to this many changed attributes are named; more are counted */
    public int $max_listed_attributes = 3;

    /** Values longer than this are shortened */
    public int $max_value_length = 60;

    /** @var array<class-string, array<string, string>> */
    protected array $casts = [];

    public function describe(Activity $activity): Description
    {
        if ($description = $this->fromSubject($activity)) {
            return $description;
        }

        $replacements = $this->replacements($activity);

        if ($custom = $this->registered($activity)) {
            return $this->fromCustom($custom, $activity, $replacements);
        }

        if ($template = $this->builtIn($activity, $replacements)) {
            return new Description(__($template), $replacements);
        }

        return $this->fallback($activity, $replacements);
    }

    /**
     * The values of the placeholders
     *
     * @return array<string, string>
     */
    public function replacements(Activity $activity): array
    {
        $model = Activitylog::morphedModel($activity->subject_type);

        [$attributes, $old, $new] = Translations::expand(
            $model,
            $activity->changedAttributes(),
            $activity->oldValues(),
            $activity->newValues(),
        );

        $first = $attributes[0] ?? null;

        return [
            'causer' => $this->causerName($activity),
            'subject' => $this->subjectName($activity),
            'type' => $this->modelLabel($model ?? $activity->subject_type),
            'tag' => $activity->tag()->label(),
            'attribute' => $first ? $this->attributeLabel($model, $first) : '',
            'attributes' => $this->attributeList($model, $attributes),
            'count' => (string) count($attributes),
            'old' => $first ? $this->formatValue($model, $first, $old[$first] ?? null) : '',
            'new' => $first ? $this->formatValue($model, $first, $new[$first] ?? null) : '',
            'date' => $this->formatDate($activity->getProperty('rolled_back_to_date')),
            'description' => $activity->description ?? '',
        ];
    }

    public function causerName(Activity $activity): string
    {
        if (! $activity->causer_type) {
            return __('System');
        }

        return $activity->causer
            ? Activitylog::displayName($activity->causer)
            : __('A deleted user');
    }

    public function subjectName(Activity $activity): string
    {
        if (! $activity->subject_type) {
            return '';
        }

        if ($activity->subject) {
            return Activitylog::displayName($activity->subject);
        }

        return "#{$activity->subject_id}";
    }

    /**
     * The label of a model class, from its activitylogLabel(), or its class name ("Invoice item")
     */
    public function modelLabel(?string $model): string
    {
        if (! $model) {
            return '';
        }

        if (method_exists($model, 'activitylogLabel')) {
            return $model::activitylogLabel();
        }

        return __(Str::of(class_basename($model))->snake(' ')->ucfirst()->toString());
    }

    /**
     * The label of an attribute: the model's activitylogAttributeLabels(), the app's
     * `validation.attributes`, or the attribute name ("published at")
     */
    public function attributeLabel(?string $model, string $attribute): string
    {
        if ($translation = Translations::parseKey($attribute)) {
            [$field, $locale] = $translation;

            return "{$this->attributeLabel($model, $field)} (" . Translations::languageName($locale) . ')';
        }

        if ($model && method_exists($model, 'activitylogAttributeLabels')) {
            $labels = $model::activitylogAttributeLabels();

            if (isset($labels[$attribute])) {
                return $labels[$attribute];
            }
        }

        $key = "validation.attributes.{$attribute}";

        if (trans()->has($key)) {
            return trans($key);
        }

        $name = Str::endsWith($attribute, '_id') ? Str::beforeLast($attribute, '_id') : $attribute;

        return __(str_replace(['_', '.', '->'], ' ', $name));
    }

    /**
     * "title", "title and status", "title, status and body"
     *
     * @param  array<int, string>  $attributes
     */
    public function attributeList(?string $model, array $attributes): string
    {
        $labels = array_map(fn (string $attribute) => $this->attributeLabel($model, $attribute), $attributes);

        if (count($labels) <= 1) {
            return $labels[0] ?? '';
        }

        $last = array_pop($labels);

        return __(':list and :last', ['list' => implode(__(', '), $labels), 'last' => $last]);
    }

    /**
     * A logged value as text: enum labels, Yes/No, lists, "empty", and long text shortened
     * unless $shorten is false
     */
    public function formatValue(?string $model, string $attribute, mixed $value, bool $shorten = true): string
    {
        if ($translation = Translations::parseKey($attribute)) {
            $attribute = $translation[0];
        }

        $limit = $shorten ? $this->max_value_length : PHP_INT_MAX;

        if ($value === null || $value === '' || $value === []) {
            return __('empty');
        }

        if ($enum = $this->castEnum($model, $attribute)) {
            $value = $enum::tryFrom($value) ?? $value;
        }

        if ($value instanceof UnitEnum) {
            return $this->enumLabel($value);
        }

        if (is_bool($value)) {
            return $value ? __('Yes') : __('No');
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i');
        }

        if (is_array($value)) {
            return Str::limit($this->formatList($value), $limit);
        }

        if ($value instanceof Stringable || is_scalar($value)) {
            return Str::limit(trim((string) $value), $limit);
        }

        return Str::limit((string) json_encode($value, JSON_UNESCAPED_UNICODE), $limit);
    }

    protected function fromSubject(Activity $activity): ?Description
    {
        $subject = $activity->subject_type ? $activity->subject : null;

        if (! $subject || ! method_exists($subject, 'describeActivity')) {
            return null;
        }

        $description = $subject->describeActivity($activity);

        if ($description === null || $description === '') {
            return null;
        }

        return $description instanceof Description
            ? $description
            : Description::plain((string) $description);
    }

    protected function registered(Activity $activity): Closure|string|null
    {
        $key = $activity->event ?: $activity->description;
        $model = Activitylog::morphedModel($activity->subject_type);

        return Activitylog::findDescription($key, $model);
    }

    /**
     * @param  array<string, string>  $replacements
     */
    protected function fromCustom(Closure|string $custom, Activity $activity, array $replacements): Description
    {
        if (is_string($custom)) {
            return new Description(__($custom), $replacements);
        }

        $description = $custom($activity, $replacements);

        if ($description instanceof Description) {
            return $description;
        }

        return new Description((string) $description, $replacements);
    }

    /**
     * The sentence for a built-in event, as an untranslated template
     *
     * @param  array<string, string>  $replacements
     */
    protected function builtIn(Activity $activity, array $replacements): ?string
    {
        $key = $activity->event ?: $activity->description;

        return match ($key) {
            'created' => ':causer created :type :subject',
            'updated' => $this->updatedTemplate($activity, $replacements),
            'deleted' => ':causer deleted :type :subject',
            'restored' => ':causer restored :type :subject',
            'rolled_back' => $replacements['date'] !== ''
                ? ':causer rolled back :type :subject to the version of :date'
                : ':causer rolled back :type :subject',
            'login' => ':causer logged in',
            'logout' => ':causer logged out',
            'failed_login' => ':causer failed to log in',
            'lockout' => ':causer was locked out after too many login attempts',
            'password_reset' => ':causer reset their password',
            'password_reset_link_sent' => ':causer asked for a password reset link',
            'password_changed' => ':causer changed their password',
            'two_factor_enabled' => ':causer turned on two-factor authentication',
            'two_factor_disabled' => ':causer turned off two-factor authentication',
            'passkey_added' => ':causer added a passkey',
            'passkey_removed' => ':causer removed a passkey',
            'role_updated' => ':causer changed the role of :subject from :old to :new',
            'permissions_updated' => ':causer changed the permissions of :type :subject',
            'exported' => ':causer exported :type :subject',
            default => null,
        };
    }

    /**
     * @param  array<string, string>  $replacements
     */
    protected function updatedTemplate(Activity $activity, array $replacements): string
    {
        [$attributes, $old, $new] = Translations::expand(
            Activitylog::morphedModel($activity->subject_type),
            $activity->changedAttributes(),
            $activity->oldValues(),
            $activity->newValues(),
        );

        $count = count($attributes);

        if ($count === 0) {
            return ':causer updated :type :subject';
        }

        if ($count > $this->max_listed_attributes) {
            return ':causer updated :count attributes of :type :subject';
        }

        if ($count > 1) {
            return ':causer updated the :attributes of :type :subject';
        }

        $attribute = $attributes[0];

        if (! array_key_exists($attribute, $old) || $old[$attribute] === null) {
            return ':causer set the :attribute of :type :subject to :new';
        }

        if (($new[$attribute] ?? null) === null) {
            return ':causer cleared the :attribute of :type :subject';
        }

        return ':causer updated the :attribute of :type :subject from :old to :new';
    }

    /**
     * A log nothing describes: its own text when it's a sentence, otherwise its tag
     *
     * @param  array<string, string>  $replacements
     */
    protected function fallback(Activity $activity, array $replacements): Description
    {
        $description = (string) $activity->description;

        if (str_contains(trim($description), ' ')) {
            return Description::plain($description);
        }

        if ($description !== '' && $activity->tagKey() === null) {
            $replacements['tag'] = __(ActivityTag::defaultLabel($description));
        }

        $template = $activity->subject_type
            ? ':tag: :type :subject, by :causer'
            : ':tag, by :causer';

        return new Description(__($template), $replacements);
    }

    /**
     * @return class-string<BackedEnum>|null
     */
    protected function castEnum(?string $model, string $attribute): ?string
    {
        if (! $model || ! is_a($model, Model::class, true)) {
            return null;
        }

        $this->casts[$model] ??= (new $model)->getCasts();

        $cast = $this->casts[$model][$attribute] ?? null;

        if (! is_string($cast)) {
            return null;
        }

        return is_a($cast, BackedEnum::class, true) ? $cast : null;
    }

    protected function enumLabel(UnitEnum $enum): string
    {
        if (method_exists($enum, 'label')) {
            return (string) $enum->label();
        }

        if (method_exists($enum, 'getLabel')) {
            return (string) $enum->getLabel();
        }

        return Str::headline($enum->name);
    }

    /**
     * @param  array<array-key, mixed>  $values
     */
    protected function formatList(array $values): string
    {
        if (! array_is_list($values) || Arr::first($values, fn (mixed $value) => ! is_scalar($value)) !== null) {
            return (string) json_encode($values, JSON_UNESCAPED_UNICODE);
        }

        return implode(__(', '), array_map(fn (mixed $value) => $this->formatValue(null, '', $value), $values));
    }

    protected function formatDate(mixed $date): string
    {
        if (! $date) {
            return '';
        }

        try {
            return Carbon::parse($date)->translatedFormat('j F Y, H:i');
        } catch (Throwable) {
            return (string) $date;
        }
    }
}
