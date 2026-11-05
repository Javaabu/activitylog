import { Status } from "@javaabu/forms";
import type { ActivityData, ActivityParty, ActivitylogStrings } from "../types";
import { ActivityTag } from "./ActivityTag";

export interface ActivityDetailsProps {
  log: ActivityData;
  strings: ActivitylogStrings;
}

function PartyName({ party }: { party: ActivityParty }) {
  if (party.url) {
    return (
      <a href={party.url} className="font-medium hover:underline">
        <bdi>{party.name}</bdi>
      </a>
    );
  }

  return <bdi className="font-medium">{party.name}</bdi>;
}

/** Who, what and when of a log */
export function ActivityDetails({ log, strings }: ActivityDetailsProps) {
  return (
    <dl className="grid gap-4 sm:grid-cols-2" data-activitylog-details>
      <div>
        <dt className="text-muted-foreground text-sm">{strings.tag}</dt>
        <dd>
          <ActivityTag tag={log.tag} />
        </dd>
      </div>
      <div>
        <dt className="text-muted-foreground text-sm">{strings.date}</dt>
        <dd>{log.created_at ? <time dateTime={log.created_at}>{log.created_at_formatted}</time> : null}</dd>
      </div>
      <div>
        <dt className="text-muted-foreground text-sm">{strings.user}</dt>
        <dd>
          {log.causer?.exists ? (
            <PartyName party={log.causer} />
          ) : (
            <span className="text-muted-foreground">{log.causer ? strings.deleted_user : strings.system}</span>
          )}
        </dd>
      </div>
      {log.subject ? (
        <div>
          <dt className="text-muted-foreground text-sm">{strings.record}</dt>
          <dd className="flex flex-wrap items-center gap-2">
            <span>{log.subject.type_label}</span>
            {log.subject.exists ? (
              <PartyName party={log.subject} />
            ) : (
              <>
                <bdi>{log.subject.name}</bdi>
                <Status color="secondary" label={strings.deleted_record} />
              </>
            )}
            {log.links.history ? (
              <a href={log.links.history} className="text-sm hover:underline">
                {strings.history}
              </a>
            ) : null}
          </dd>
        </div>
      ) : null}
      {log.ip ? (
        <div>
          <dt className="text-muted-foreground text-sm">{strings.ip}</dt>
          <dd>
            <bdi className="tabular-nums">{log.ip}</bdi>
          </dd>
        </div>
      ) : null}
      {log.log_name ? (
        <div>
          <dt className="text-muted-foreground text-sm">{strings.log_name}</dt>
          <dd>
            <bdi>{log.log_name}</bdi>
          </dd>
        </div>
      ) : null}
    </dl>
  );
}
