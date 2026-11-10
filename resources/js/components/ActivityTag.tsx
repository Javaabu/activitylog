import { Status } from "@javaabu/forms";
import type { ActivityTagData } from "../types";

export interface ActivityTagProps {
  tag: ActivityTagData;
  className?: string;
}

/** A log's tag: its colour, icon and label (`<x-activitylog::tag>`) */
export function ActivityTag({ tag, className }: ActivityTagProps) {
  return (
    <Status color={tag.color} className={className}>
      <span className="inline-flex items-center gap-1" data-activity-tag={tag.key}>
        {tag.svg ? (
          <svg
            className="size-3.5 shrink-0"
            aria-hidden="true"
            focusable="false"
            xmlns="http://www.w3.org/2000/svg"
            width="1em"
            height="1em"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth={2}
            strokeLinecap="round"
            strokeLinejoin="round"
            dangerouslySetInnerHTML={{ __html: tag.svg }}
          />
        ) : tag.icon ? (
          <i className={tag.icon} aria-hidden="true" />
        ) : null}
        <span>{tag.label}</span>
      </span>
    </Status>
  );
}
