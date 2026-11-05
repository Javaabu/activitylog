import type { ActivityData, ActivitylogStrings, LogsIndexProps } from "../../resources/js/types";

export const strings: ActivitylogStrings = {
  title: "Activity log",
  date: "Date",
  tag: "Tag",
  description: "Description",
  user: "User",
  record: "Record",
  ip: "IP address",
  log_name: "Log",
  actions: "Actions",
  view: "View",
  history: "History",
  changes: "Changes",
  attribute: "Attribute",
  old_value: "Old value",
  new_value: "New value",
  current_value: "Current value",
  restored_value: "Value after rolling back",
  properties: "Details",
  no_changes: "No changes were logged.",
  no_logs: "No activity yet",
  no_matching_logs: "No activity matches these filters.",
  rollback: "Roll back",
  rollback_title: "Roll back to this version",
  rollback_intro: "These attributes will be restored to the values they had after this log. The rollback is logged.",
  cancel: "Cancel",
  back: "Back to the activity log",
  back_to_log: "Back to the log",
  system: "System",
  deleted_user: "A deleted user",
  deleted_record: "Deleted",
  all_activity: "All activity",
};

export function makeLog(overrides: Partial<ActivityData> = {}): ActivityData {
  return {
    id: 12,
    event: "updated",
    log_name: "default",
    tag: { key: "updated", label: "Updated", color: "info", icon: "pencil", svg: '<path d="M1 1"/>' },
    description: "އަހުމަދު updated the title of Post ފައިނަލް from Draft to ފައިނަލް",
    description_html:
      "<bdi>އަހުމަދު</bdi> updated the <bdi>title</bdi> of <bdi>Post</bdi> <bdi>ފައިނަލް</bdi> from <bdi>Draft</bdi> to <bdi>ފައިނަލް</bdi>",
    causer: { type: "user", type_label: "User", id: 1, name: "އަހުމަދު", exists: true, url: null },
    subject: { type: "post", type_label: "Post", id: 3, name: "ފައިނަލް", exists: true, url: "/posts/3" },
    changed_attributes: ["title"],
    ip: "127.0.0.1",
    can: { view: true },
    links: { show: "/logs/12", rollback: "/logs/12/rollback", history: "/logs?subject_type=post&subject_id=3" },
    created_at: "2026-11-05T09:30:00+00:00",
    created_at_for_humans: "2 hours ago",
    created_at_formatted: "November 5, 2026 9:30 AM",
    ...overrides,
  };
}

export const changes = [{ attribute: "title", label: "Title", old: "Draft", new: "ފައިނަލް", changed: true }];

export const filters = {
  id: "filter",
  action: "/logs",
  form: { type: "form", components: [], method: "GET", embedded: true },
  hidden: {},
  active: 0,
  chips: [],
  clearUrl: "/logs",
  resetUrl: "/logs",
  sort: { name: "created_at", direction: "DESC", default: "created_at", options: [], parameter: "orderby", directionParameter: "order" },
  strings: {},
} as unknown as LogsIndexProps["filters"];

export function indexProps(overrides: Partial<LogsIndexProps> = {}): LogsIndexProps {
  return {
    title: "Activity log",
    logs: {
      data: [makeLog()],
      links: {},
      meta: { current_page: 1, last_page: 1, per_page: 20, total: 1, from: 1, to: 1, path: "/logs", links: [] },
    },
    filters,
    subject: null,
    can_export: false,
    export_formats: { xlsx: "Excel", csv: "CSV" },
    routes: { index: "logs.index", show: "logs.show", "rollback.confirm": "logs.rollback.confirm", rollback: "logs.rollback" },
    urls: { index: "/logs", show: null, rollback: null },
    strings,
    ...overrides,
  };
}
