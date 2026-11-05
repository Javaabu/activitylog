import type { FiltersData } from "@javaabu/filters";

/** A tag as Activitylog::tagData() serializes it */
export interface ActivityTagData {
  key: string;
  label: string;
  /** A javaabu/forms status colour: primary, secondary, success, info, warning, danger */
  color: string;
  icon: string;
  /** The icon's inner SVG markup, or null when `icon` is icon-font CSS classes */
  svg: string | null;
}

/** The causer or subject of a log */
export interface ActivityParty {
  type: string;
  type_label: string;
  id: string | number | null;
  name: string;
  exists: boolean;
  url: string | null;
}

/** One attribute of a diff */
export interface ActivityChange {
  attribute: string;
  label: string;
  /** The old value as text, or null when the log has no old value (e.g. a created model) */
  old: string | null;
  new: string | null;
  changed: boolean;
}

/** A log as ActivityResource serializes it */
export interface ActivityData {
  id: number;
  event: string | null;
  log_name: string | null;
  tag: ActivityTagData;
  description: string;
  /** The description with values escaped and wrapped in <bdi> */
  description_html: string;
  causer: ActivityParty | null;
  subject: ActivityParty | null;
  changed_attributes: string[];
  /** Only on the show page */
  changes?: ActivityChange[];
  properties?: Record<string, unknown>;
  ip: string | null;
  can: { view: boolean; rollback?: boolean };
  rollback?: { possible: boolean; reason: string | null };
  links: { show: string | null; rollback: string | null; history: string | null };
  created_at: string | null;
  created_at_for_humans: string | null;
  created_at_formatted: string | null;
}

export interface ActivitylogStrings {
  title: string;
  date: string;
  tag: string;
  description: string;
  user: string;
  record: string;
  ip: string;
  log_name: string;
  actions: string;
  view: string;
  history: string;
  changes: string;
  attribute: string;
  old_value: string;
  new_value: string;
  current_value: string;
  restored_value: string;
  properties: string;
  no_changes: string;
  no_logs: string;
  no_matching_logs: string;
  rollback: string;
  rollback_title: string;
  rollback_intro: string;
  cancel: string;
  back: string;
  back_to_log: string;
  system: string;
  deleted_user: string;
  deleted_record: string;
  all_activity: string;
}

export interface Paginated<T> {
  data: T[];
  links: Record<string, string | null>;
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    path: string;
    links: Array<{ url: string | null; label: string; active: boolean }>;
  };
}

export interface ActivitylogRoutes {
  index: string;
  show: string;
  "rollback.confirm": string;
  rollback: string;
}

export interface ActivitylogUrls {
  index: string;
  show: string | null;
  rollback: string | null;
}

/** Props shared by the pages */
export interface ActivitylogPageProps {
  title: string;
  routes: ActivitylogRoutes;
  urls: ActivitylogUrls;
  strings: ActivitylogStrings;
}

export interface LogsIndexProps extends ActivitylogPageProps {
  logs: Paginated<ActivityData>;
  filters: FiltersData;
  /** The record whose history this is, when the index is filtered to one */
  subject: { type: string; id: string; name: string; type_label: string } | null;
  can_export: boolean;
  export_formats: Record<string, string>;
}

export interface LogsShowProps extends ActivitylogPageProps {
  log: ActivityData;
  changes: ActivityChange[];
  properties: Record<string, unknown>;
  can_rollback: boolean;
}

export interface LogsRollbackProps extends ActivitylogPageProps {
  log: ActivityData;
  changes: ActivityChange[];
}

/** What Activitylog::history() returns, for <ActivityHistory> on an app's own page */
export interface ActivityHistoryData {
  logs: ActivityData[];
  url: string | null;
  strings: ActivitylogStrings;
}
