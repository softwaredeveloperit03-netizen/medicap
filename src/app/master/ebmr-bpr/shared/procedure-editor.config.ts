import Quill from 'quill';
import { stripHtmlToPlain } from './procedure.util';

export { stripHtmlToPlain };
/** Shared Quill toolbar for BMR procedure drafting. */
export const PROCEDURE_QUILL_MODULES = {
  toolbar: [
    [{ font: [] }, { size: ['small', false, 'large', 'huge'] }],
    ['bold', 'italic', 'underline', 'strike'],
    [{ color: [] }, { background: [] }],
    [{ script: 'sub' }, { script: 'super' }],
    [{ header: [1, 2, 3, 4, 5, 6, false] }],
    [{ align: [] }],
    [{ list: 'ordered' }, { list: 'bullet' }, { indent: '-1' }, { indent: '+1' }],
    ['blockquote', 'code-block'],
    ['link'],
    ['clean'],
  ],
};

export function insertHtmlAtCursor(quill: InstanceType<typeof Quill>, html: string): void {
  const range = quill.getSelection(true);
  const index = range ? range.index : quill.getLength();
  quill.clipboard.dangerouslyPasteHTML(index, html, 'user');
  quill.setSelection(index + 1, 0, 'user');
}

export function insertTableHtml(rows: number, cols: number): string {
  const r = Math.max(1, Math.min(20, rows || 3));
  const c = Math.max(1, Math.min(12, cols || 3));
  let html = '<table class="proc-draft-table"><tbody>';
  for (let ri = 0; ri < r; ri++) {
    html += '<tr>';
    for (let ci = 0; ci < c; ci++) {
      html += ri === 0 ? `<th>Header ${ci + 1}</th>` : '<td>&nbsp;</td>';
    }
    html += '</tr>';
  }
  html += '</tbody></table><p><br></p>';
  return html;
}

export function insertInProcessBlockHtml(): string {
  return (
    '<div class="proc-ipc-block">' +
    '<p><strong>In-Process Check</strong></p>' +
    '<table class="proc-draft-table proc-ipc-inner"><tbody>' +
    '<tr><th>Parameter</th><th>Target / Limit</th><th>Actual</th><th>UOM</th><th>Done By / Date</th></tr>' +
    '<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>' +
    '</tbody></table></div><p><br></p>'
  );
}
