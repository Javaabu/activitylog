// javaabu/stencil imports its stylesheet as text, which Vite resolves in apps
declare module "*?raw" {
  const content: string;
  export default content;
}
