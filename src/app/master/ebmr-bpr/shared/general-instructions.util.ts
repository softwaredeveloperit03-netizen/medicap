import {
  ProcedureParagraph,
  assignProcedureNumbers,
  blankProcedureParagraph,
  procedureParagraphsToText,
} from './procedure.util';

export interface GeneralInstructionsContent {
  paragraphs: ProcedureParagraph[];
  /** Plain numbered text cache for reports / legacy readers */
  text?: string;
}

export function emptyGeneralInstructions(): GeneralInstructionsContent {
  return { paragraphs: [blankProcedureParagraph(1)], text: '' };
}

export function normalizeGeneralInstructions(raw: unknown): GeneralInstructionsContent {
  const base = emptyGeneralInstructions();
  if (raw && typeof raw === 'object' && !Array.isArray(raw)) {
    const obj = raw as GeneralInstructionsContent;
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

export function syncGeneralInstructionsText(content: GeneralInstructionsContent): void {
  if (!content) return;
  content.text = procedureParagraphsToText(content.paragraphs || []);
}

export function generalInstructionsHasContent(content: GeneralInstructionsContent | null | undefined): boolean {
  if (!content) return false;
  if ((content.text || '').trim()) return true;
  return (content.paragraphs || []).some((p) => (p.text || '').trim());
}

/** Map checklist master lines to level-1 instruction points. */
export function checklistLinesToParagraphs(lines: string[]): ProcedureParagraph[] {
  return lines
    .map((line) => String(line || '').trim())
    .filter(Boolean)
    .map((text) => ({ ...blankProcedureParagraph(1), text }));
}
