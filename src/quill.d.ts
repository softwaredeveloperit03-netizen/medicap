/**
 * Type declarations for quill and quill-better-table so Angular build can resolve them.
 * Quill's SVG assets are handled by extra-webpack.config.js (raw-loader).
 */
declare module 'quill' {
  const Quill: any;
  export default Quill;
}

declare module 'quill-better-table' {
  const QuillBetterTable: any;
  export default QuillBetterTable;
}
