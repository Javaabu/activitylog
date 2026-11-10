import { Alert, LinkButton, Pagination } from "@javaabu/forms";
import { FilterBar } from "@javaabu/filters";
import { usePage } from "@inertiajs/react";
import { ActivityTable } from "../../components/ActivityTable";
import type { LogsIndexProps } from "../../types";

interface Flash {
  flash?: { success?: string | null; error?: string | null };
}

/** activitylog::Logs/Index — the activity log, or one record's history, with filters */
export default function Index(props: LogsIndexProps) {
  const { strings, filters, logs } = props;
  const flash = (usePage().props as Flash).flash;

  return (
    <div className="flex flex-col gap-6" data-activitylog>
      <div className="flex flex-wrap items-center justify-between gap-4">
        <h1 className="text-2xl font-semibold">
          <bdi>{props.title}</bdi>
        </h1>

        {props.subject ? (
          <LinkButton url={props.urls.index} color="secondary" size="sm" startIcon="chevron-left">
            {strings.all_activity}
          </LinkButton>
        ) : null}
      </div>

      {flash?.success ? <Alert type="success">{flash.success}</Alert> : null}
      {flash?.error ? <Alert type="danger">{flash.error}</Alert> : null}

      <FilterBar filters={filters} export={props.can_export ? props.export_formats : undefined} />

      <ActivityTable
        logs={logs.data}
        strings={strings}
        empty={filters.active ? strings.no_matching_logs : strings.no_logs}
        pagination={logs.meta.last_page > 1 ? <Pagination paginator={logs} /> : null}
      />
    </div>
  );
}
