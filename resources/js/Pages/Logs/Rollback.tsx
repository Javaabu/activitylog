import { Alert, Card, Form, LinkButton, Submit } from "@javaabu/forms";
import { usePage } from "@inertiajs/react";
import { ActivityDescription } from "../../components/ActivityDescription";
import { ActivityDetails } from "../../components/ActivityDetails";
import { ActivityDiff } from "../../components/ActivityDiff";
import type { LogsRollbackProps } from "../../types";

interface Flash {
  flash?: { success?: string | null; error?: string | null };
}

/** activitylog::Logs/Rollback — what rolling back to a log changes, and the button to do it */
export default function Rollback(props: LogsRollbackProps) {
  const { log, strings, urls } = props;
  const flash = (usePage().props as Flash).flash;
  const back = urls.show ?? urls.index;

  return (
    <div className="flex flex-col gap-6" data-activitylog data-activitylog-rollback={log.id}>
      <div className="flex flex-wrap items-center justify-between gap-4">
        <h1 className="text-2xl font-semibold">{props.title}</h1>
        <LinkButton url={back} color="secondary" size="sm" startIcon="chevron-left">
          {strings.back_to_log}
        </LinkButton>
      </div>

      {flash?.error ? <Alert type="danger">{flash.error}</Alert> : null}

      <Card>
        <p className="mb-4 text-lg">
          <ActivityDescription log={log} />
        </p>
        <ActivityDetails log={log} strings={strings} />
      </Card>

      <Card
        title={strings.rollback_title}
        footer={
          urls.rollback ? (
            <Form method="POST" action={urls.rollback} className="flex flex-wrap items-center gap-2">
              <Submit color="warning">{strings.rollback}</Submit>
              <LinkButton url={back} color="secondary">
                {strings.cancel}
              </LinkButton>
            </Form>
          ) : undefined
        }
      >
        <p className="text-muted-foreground mb-4">{strings.rollback_intro}</p>
        <ActivityDiff
          changes={props.changes}
          strings={strings}
          oldLabel={strings.current_value}
          newLabel={strings.restored_value}
        />
      </Card>
    </div>
  );
}
