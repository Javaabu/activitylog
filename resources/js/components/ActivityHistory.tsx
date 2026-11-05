import { Card, LinkButton } from "@javaabu/forms";
import type * as React from "react";
import type { ActivityHistoryData } from "../types";
import { ActivityTable } from "./ActivityTable";

export interface ActivityHistoryProps {
  /** `Activitylog::history($model)` from PHP */
  history: ActivityHistoryData;
  title?: React.ReactNode;
  className?: string;
}

/** A record's latest logs, for the app's own pages (`<x-activitylog::history>`) */
export function ActivityHistory({ history, title, className }: ActivityHistoryProps) {
  const { strings } = history;

  return (
    <Card
      title={title ?? strings.history}
      className={className}
      footer={
        history.url ? (
          <LinkButton url={history.url} color="secondary" size="sm">
            {strings.all_activity}
          </LinkButton>
        ) : undefined
      }
    >
      <div data-activitylog-history>
        <ActivityTable logs={history.logs} strings={strings} />
      </div>
    </Card>
  );
}
