import { render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import { ActivityDiff, ActivityHistory, ActivityTable, ActivityTag } from "@javaabu/activitylog";
import { changes, makeLog, strings } from "./fixtures";

describe("ActivityTag", () => {
  it("renders the label with the SVG icon", () => {
    const { container } = render(<ActivityTag tag={makeLog().tag} />);

    expect(screen.getByText("Updated")).toBeTruthy();
    expect(container.querySelector('[data-activity-tag="updated"] svg path')).not.toBeNull();
  });

  it("renders an icon font when there's no SVG", () => {
    const { container } = render(
      <ActivityTag tag={{ key: "sent", label: "Sent", color: "info", icon: "fa-solid fa-paper-plane", svg: null }} />,
    );

    expect(container.querySelector("i.fa-paper-plane")).not.toBeNull();
  });
});

describe("ActivityDiff", () => {
  it("shows the old and new value of each attribute", () => {
    const { container } = render(<ActivityDiff changes={changes} strings={strings} />);

    expect(container.querySelector('[data-attribute="title"] del')?.textContent).toBe("Draft");
    expect(container.querySelector('[data-attribute="title"] ins')?.textContent).toBe("ފައިނަލް");
  });

  it("leaves out the side a log has no value for", () => {
    const { container } = render(
      <ActivityDiff changes={[{ attribute: "title", label: "Title", old: null, new: "ރިޕޯޓު", changed: true }]} strings={strings} />,
    );

    expect(container.querySelector("del")).toBeNull();
    expect(container.querySelector("ins")?.textContent).toBe("ރިޕޯޓު");
  });

  it("says when nothing changed", () => {
    render(<ActivityDiff changes={[]} strings={strings} />);

    expect(screen.getByText("No changes were logged.")).toBeTruthy();
  });

  it("takes headings for a rollback", () => {
    render(<ActivityDiff changes={changes} strings={strings} oldLabel="Current value" newLabel="Value after rolling back" />);

    expect(screen.getAllByText("Current value").length).toBeGreaterThan(0);
  });
});

describe("ActivityTable", () => {
  it("lists the logs with their tag, description and user", () => {
    const { container } = render(<ActivityTable logs={[makeLog()]} strings={strings} />);

    const row = container.querySelector('[data-activity-log="12"]')!;

    expect(row.querySelector('[data-activity-tag="updated"]')).not.toBeNull();
    expect(row.innerHTML).toContain("<bdi>އަހުމަދު</bdi> updated the");
    expect(row.querySelector('a[href="/logs/12"]')).not.toBeNull();
    expect(row.querySelector("time")?.getAttribute("datetime")).toBe("2026-11-05T09:30:00+00:00");
  });

  it("shows the system, deleted users, and no link without view", () => {
    const { container } = render(
      <ActivityTable
        logs={[
          makeLog({ id: 1, causer: null }),
          makeLog({ id: 2, causer: { type: "user", type_label: "User", id: 9, name: "#9", exists: false, url: null }, can: { view: false } }),
        ]}
        strings={strings}
      />,
    );

    expect(screen.getByText("System")).toBeTruthy();
    expect(screen.getByText("A deleted user")).toBeTruthy();
    expect(container.querySelector('[data-activity-log="2"] a')).toBeNull();
  });

  it("says when there are no logs", () => {
    render(<ActivityTable logs={[]} strings={strings} empty="No activity matches these filters." />);

    expect(screen.getByText("No activity matches these filters.")).toBeTruthy();
  });
});

describe("ActivityHistory", () => {
  it("shows a record's logs with a link to all of them", () => {
    const { container } = render(<ActivityHistory history={{ logs: [makeLog()], url: "/logs?subject_type=post&subject_id=3", strings }} />);

    expect(container.querySelector("[data-activitylog-history] [data-activity-log]")).not.toBeNull();
    expect(container.querySelector('a[href="/logs?subject_type=post&subject_id=3"]')?.textContent).toContain("All activity");
  });

  it("has no link when the user may not see the log pages", () => {
    const { container } = render(<ActivityHistory history={{ logs: [], url: null, strings }} />);

    expect(container.querySelector("a")).toBeNull();
  });
});
