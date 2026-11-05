import { defineConfig } from "vitest/config";

// @javaabu/filters and @javaabu/stencil are the React components javaabu/filters and
// javaabu/stencil ship in their Composer packages. Apps get them through
// javaabu/manifest; here they come from vendor/ (run composer install first).
export default defineConfig({
  resolve: {
    alias: {
      "@javaabu/activitylog": new URL("./resources/js", import.meta.url).pathname,
      "@javaabu/filters": new URL("./vendor/javaabu/filters/resources/js", import.meta.url).pathname,
      "@javaabu/stencil": new URL("./vendor/javaabu/stencil/resources/js", import.meta.url).pathname,
    },
  },
  test: {
    environment: "jsdom",
    globals: true,
    include: ["tests/js/**/*.test.{ts,tsx}"],
    setupFiles: ["tests/js/setup.ts"],
  },
});
