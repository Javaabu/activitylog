import { Alert, Card, LinkButton } from "@javaabu/forms";
import { usePage } from "@inertiajs/react";
import { ActivityDescription } from "../../components/ActivityDescription";
import { ActivityDetails } from "../../components/ActivityDetails";
import { ActivityDiff } from "../../components/ActivityDiff";
import type { LogsShowProps } from "../../types";

interface Flash {
  flash?: { success?: string | null; error?: string | null };
}

function propertyLabel(key: string): string {
  const text = key.replace(/_/g, " ");

  return text.charAt(0).toUpperCase() + text.slice(1);
}

function propertyValue(value: unknown): string {
  if (value === null || value === undefined) {
    return "";
  }

  return typeof value === "object" ? JSON.stringify(value) : String(value);
}

/** activitylog::Logs/Show — a log, who did it, and what changed */
export default function Show(props: LogsShowProps) {
  const { log, strings } = props;
  const flash = (usePage().props as Flash).flash;
  const properties = Object.entries(props.properties ?? {});

  return (
    <div className="flex flex-col gap-6" data-activitylog data-activity-log={log.id}>
      <div className="flex flex-wrap items-center justify-between gap-4">
        <h1 className="text-2xl font-semibold">{props.title}</h1>

        <div className="flex flex-wrap items-center gap-2">
          {props.can_rollback && log.links.rollback ? (
            <LinkButton url={log.links.rollback} color="warning" size="sm">
              {strings.rollback}
            </LinkButton>
          ) : null}
          <LinkButton url={props.urls.index} color="secondary" size="sm" startIcon="chevron-left">
            {strings.back}
          </LinkButton>
        </div>
      </div>

      {flash?.success ? <Alert type="success">{flash.success}</Alert> : null}
      {flash?.error ? <Alert type="danger">{flash.error}</Alert> : null}

      <Card>
        <p className="mb-4 text-lg" data-activitylog-description>
          <ActivityDescription log={log} />
        </p>
        <ActivityDetails log={log} strings={strings} />
      </Card>

      <Card title={strings.changes}>
        <ActivityDiff changes={props.changes} strings={strings} />
      </Card>

      {properties.length ? (
        <Card title={strings.properties}>
          <dl className="grid gap-4 sm:grid-cols-2" data-activitylog-properties>
            {properties.map(([key, value]) => (
              <div key={key}>
                <dt className="text-muted-foreground text-sm">{propertyLabel(key)}</dt>
                <dd className="break-words" dir="auto">
                  {propertyValue(value)}
                </dd>
              </div>
            ))}
          </dl>
        </Card>
      ) : null}
    </div>
  );
}
