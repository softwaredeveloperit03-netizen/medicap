import {
  ProcedureParagraph,
  assignProcedureNumbers,
  blankProcedureParagraph,
  procedureParagraphsToText,
} from './procedure.util';

export interface SafetyPrecautionsContent {
  paragraphs: ProcedureParagraph[];
  /** Plain numbered text cache for reports / legacy readers */
  text?: string;
}

export function emptySafetyPrecautions(): SafetyPrecautionsContent {
  return { paragraphs: [blankProcedureParagraph(1)], text: '' };
}

export function normalizeSafetyPrecautions(raw: unknown): SafetyPrecautionsContent {
  const base = emptySafetyPrecautions();
  if (raw && typeof raw === 'object' && !Array.isArray(raw)) {
    const obj = raw as SafetyPrecautionsContent;
    const paragraphs = Array.isArray(obj.paragraphs) ? obj.paragraphs.map((p) => ({ ...p })) : [];
    return {
      paragraphs: paragraphs.length ? paragraphs : [blankProcedureParagraph(1)],
      text: obj.text || '',
    };
  }
  if (typeof raw === 'string' && raw.trim()) {
    const paragraphs = raw
      .split('\n')
      .map((line) => line.trim())
      .filter(Boolean)
      .map((line) => {
        const m = line.match(/^([\d]+(?:\.[\d]+)*\.?0?)\s+(.+)$/);
        if (m) {
          const segs = m[1].replace(/\.0$/, '').split('.').filter(Boolean);
          const level = Math.min(3, Math.max(1, segs.length));
          return { level, text: m[2], bold: false, italic: false };
        }
        return { ...blankProcedureParagraph(1), text: line };
      });
    return { paragraphs: paragraphs.length ? paragraphs : [blankProcedureParagraph(1)], text: raw };
  }
  return base;
}

export function syncSafetyText(content: SafetyPrecautionsContent): void {
  if (!content) return;
  content.text = procedureParagraphsToText(content.paragraphs || []);
}

export function safetyHasContent(content: SafetyPrecautionsContent | null | undefined): boolean {
  if (!content) return false;
  if ((content.text || '').trim()) return true;
  return (content.paragraphs || []).some((p) => (p.text || '').trim());
}
