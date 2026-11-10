import { Table, TableCell, TableHeading, TableRow } from "@javaabu/forms";
import type { ActivityChange, ActivitylogStrings } from "../types";

export interface ActivityDiffProps {
  changes: ActivityChange[];
  strings: Pick<ActivitylogStrings, "attribute" | "old_value" | "new_value" | "no_changes">;
  oldLabel?: string;
  newLabel?: string;
}

/** The attribute-by-attribute changes of a log, or of a rollback */
export function ActivityDiff({ changes, strings, oldLabel, newLabel }: ActivityDiffProps) {
  const oldHeading = oldLabel ?? strings.old_value;
  const newHeading = newLabel ?? strings.new_value;

  if (!changes.length) {
    return <p className="text-muted-foreground">{strings.no_changes}</p>;
  }

  return (
    <Table
      model="changes"
      noCheckbox
      noBulk
      data-activitylog-diff
      headers={
        <>
          <TableHeading label={strings.attribute} />
          <TableHeading label={oldHeading} />
          <TableHeading label={newHeading} />
        </>
      }
    >
      {changes.map((row) => (
        <TableRow key={row.attribute} modelId={row.attribute} noCheckbox data-attribute={row.attribute}>
          <TableCell name="attribute" label={strings.attribute}>
            <span className="font-medium">{row.label}</span>
          </TableCell>
          <TableCell name="old" label={oldHeading}>
            {row.old !== null ? (
              <del className="text-destructive whitespace-pre-line break-words no-underline" dir="auto">
                {row.old}
              </del>
            ) : null}
          </TableCell>
          <TableCell name="new" label={newHeading}>
            {row.new !== null ? (
              <ins className="text-success whitespace-pre-line break-words no-underline" dir="auto">
                {row.new}
              </ins>
            ) : null}
          </TableCell>
        </TableRow>
      ))}
    </Table>
  );
}
