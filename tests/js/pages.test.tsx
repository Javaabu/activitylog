import { render, screen } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import Index from "../../resources/js/Pages/Logs/Index";
import Rollback from "../../resources/js/Pages/Logs/Rollback";
import Show from "../../resources/js/Pages/Logs/Show";
import { changes, indexProps, makeLog, strings } from "./fixtures";

const page = vi.hoisted(() => ({ props: {} as Record<string, unknown> }));

vi.mock("@inertiajs/react", () => ({
  usePage: () => page,
  router: { visit: vi.fn(), reload: vi.fn() },
}));

beforeEach(() => {
  page.props = {};
});

const urls = { index: "/logs", show: "/logs/12", rollback: "/logs/12/rollback" };
const routes = { index: "logs.index", show: "logs.show", "rollback.confirm": "logs.rollback.confirm", rollback: "logs.rollback" };

describe("Logs/Index", () => {
  it("renders the filters and the logs", () => {
    const { container } = render(<Index {...indexProps()} />);

    expect(screen.getByRole("heading", { name: "Activity log" })).toBeTruthy();
    expect(container.querySelector("form#filter")).not.toBeNull();
    expect(container.querySelector('[data-activity-log="12"]')).not.toBeNull();
    expect(container.querySelector('button[name="download"]')).toBeNull();
  });

  it("links back to all activity on a record's history", () => {
    render(<Index {...indexProps({ subject: { type: "post", id: "3", name: "ފައިނަލް", type_label: "Post" }, title: "History of ފައިނަލް" })} />);

    expect(screen.getByText("All activity").closest("a")?.getAttribute("href")).toBe("/logs");
  });

  it("shows the export button to users who may export", () => {
    const { container } = render(<Index {...indexProps({ can_export: true })} />);

    expect(container.querySelector('button[name="download"]')).not.toBeNull();
  });

  it("shows the flash message", () => {
    page.props = { flash: { success: "The record was rolled back." } };

    render(<Index {...indexProps()} />);

    expect(screen.getByText("The record was rolled back.")).toBeTruthy();
  });
});

describe("Logs/Show", () => {
  it("renders the description, details, changes and properties", () => {
    const { container } = render(
      <Show
        title="Log #12"
        log={makeLog()}
        changes={changes}
        properties={{ reason: "ސަބަބު", meta: { a: 1 } }}
        can_rollback={false}
        routes={routes}
        urls={urls}
        strings={strings}
      />,
    );

    expect(container.querySelector("[data-activitylog-description]")?.innerHTML).toContain("<bdi>އަހުމަދު</bdi>");
    expect(container.querySelector("[data-activitylog-details]")?.textContent).toContain("127.0.0.1");
    expect(container.querySelector('[data-attribute="title"]')).not.toBeNull();
    expect(screen.getByText("ސަބަބު")).toBeTruthy();
    expect(screen.getByText('{"a":1}')).toBeTruthy();
    expect(screen.queryByText("Roll back")).toBeNull();
  });

  it("links to the rollback for users who may", () => {
    render(<Show title="Log #12" log={makeLog()} changes={changes} properties={{}} can_rollback routes={routes} urls={urls} strings={strings} />);

    expect(screen.getByText("Roll back").closest("a")?.getAttribute("href")).toBe("/logs/12/rollback");
  });
});

describe("Logs/Rollback", () => {
  it("shows the current and restored values and posts the rollback", () => {
    const { container } = render(
      <Rollback title="Roll back to log #12" log={makeLog()} changes={changes} routes={routes} urls={urls} strings={strings} />,
    );

    expect(screen.getAllByText("Current value").length).toBeGreaterThan(0);

    const form = container.querySelector("form")!;

    expect(form.getAttribute("action")).toBe("/logs/12/rollback");
    expect(form.getAttribute("method")?.toUpperCase()).toBe("POST");
    expect(form.querySelector('button[type="submit"]')?.textContent).toContain("Roll back");
  });
});
