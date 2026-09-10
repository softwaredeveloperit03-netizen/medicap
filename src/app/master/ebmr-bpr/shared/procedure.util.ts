export interface ProcedureParagraph {
  level: number;
  text: string;
  bold?: boolean;
  italic?: boolean;
}

export interface NumberedParagraph extends ProcedureParagraph {
  number: string;
}

/** Assign 1.0 / 1.1.0 / 1.1.1.0 style numbers to a flat paragraph list. */
export function assignProcedureNumbers(items: ProcedureParagraph[]): NumberedParagraph[] {
  const c1 = { n: 0 };
  const c2 = { n: 0 };
  const c3 = { n: 0 };
  return (items || []).map((item) => {
    const lv = Math.min(3, Math.max(1, Number(item.level) || 1));
    if (lv === 1) {
      c1.n++;
      c2.n = 0;
      c3.n = 0;
      return { ...item, level: 1, number: c1.n + '.0' };
    }
    if (lv === 2) {
      c2.n++;
      c3.n = 0;
      return { ...item, level: 2, number: c1.n + '.' + c2.n + '.0' };
    }
    c3.n++;
    return { ...item, level: 3, number: c1.n + '.' + c2.n + '.' + c3.n + '.0' };
  });
}

export function procedureParagraphsToText(items: ProcedureParagraph[]): string {
  return assignProcedureNumbers(items)
    .map((p) => {
      const plain = stripHtmlToPlain(p.text || '');
      return (p.number ? p.number + '  ' : '') + plain;
    })
    .filter((line) => line.trim())
    .join('\n');
}

/** Strip HTML tags for plain-text preview / export. */
export function stripHtmlToPlain(html: string): string {
  if (!html) return '';
  if (typeof document === 'undefined') {
    return html.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
  }
  const el = document.createElement('div');
  el.innerHTML = html;
  return (el.textContent || el.innerText || '').replace(/\s+/g, ' ').trim();
}

export function procedureHasContent(text: string): boolean {
  if (!text) return false;
  if (/<img|table|proc-ipc-block/i.test(text)) return true;
  return stripHtmlToPlain(text).length > 0;
}

export function blankProcedureParagraph(level = 1): ProcedureParagraph {
  return { level, text: '', bold: false, italic: false };
}
