import { LinkButton, Table, TableCell, TableEmptyRow, TableHeading, TableRow } from "@javaabu/forms";
import type * as React from "react";
import type { ActivityData, ActivitylogStrings } from "../types";
import { ActivityDescription } from "./ActivityDescription";
import { ActivityTag } from "./ActivityTag";

export interface ActivityTableProps {
  logs: ActivityData[];
  strings: ActivitylogStrings;
  /** Shown when there are no logs. Default: strings.no_logs */
  empty?: React.ReactNode;
  pagination?: React.ReactNode;
}

/** Logs as a table: date, tag, description, user and a link to the log */
export function ActivityTable({ logs, strings, empty, pagination }: ActivityTableProps) {
  return (
    <Table
      model="logs"
      noCheckbox
      noBulk
      data-activitylog-table
      headers={
        <>
          <TableHeading label={strings.date} />
          <TableHeading label={strings.tag} />
          <TableHeading label={strings.description} />
          <TableHeading label={strings.user} />
          <TableHeading label={strings.actions} />
        </>
      }
      pagination={pagination}
    >
      {logs.length ? (
        logs.map((log) => (
          <TableRow key={log.id} modelId={log.id} noCheckbox data-activity-log={log.id}>
            <TableCell name="created_at" label={strings.date}>
              {log.created_at ? (
                <time dateTime={log.created_at} title={log.created_at_formatted ?? undefined}>
                  {log.created_at_for_humans}
                </time>
              ) : null}
            </TableCell>
            <TableCell name="tag" label={strings.tag}>
              <ActivityTag tag={log.tag} />
            </TableCell>
            <TableCell name="description" label={strings.description}>
              <ActivityDescription log={log} />
            </TableCell>
            <TableCell name="causer" label={strings.user}>
              {log.causer ? (
                log.causer.exists ? (
                  <bdi>{log.causer.name}</bdi>
                ) : (
                  <span className="text-muted-foreground">{strings.deleted_user}</span>
                )
              ) : (
                <span className="text-muted-foreground">{strings.system}</span>
              )}
            </TableCell>
            <TableCell name="actions" label={strings.actions}>
              {log.can.view && log.links.show ? (
                <LinkButton url={log.links.show} color="secondary" size="sm">
                  {strings.view}
                </LinkButton>
              ) : null}
            </TableCell>
          </TableRow>
        ))
      ) : (
        <TableEmptyRow columns={5} noCheckbox>
          {empty ?? strings.no_logs}
        </TableEmptyRow>
      )}
    </Table>
  );
}
