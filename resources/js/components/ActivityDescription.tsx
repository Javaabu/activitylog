import type { ActivityData } from "../types";

export interface ActivityDescriptionProps {
  log: Pick<ActivityData, "description" | "description_html">;
  className?: string;
}

/**
 * The readable description. The HTML comes from PHP with every value escaped and wrapped in
 * <bdi>, so names in another script read correctly in a right-to-left sentence.
 */
export function ActivityDescription({ log, className }: ActivityDescriptionProps) {
  return <span dir="auto" className={className} dangerouslySetInnerHTML={{ __html: log.description_html }} />;
}
