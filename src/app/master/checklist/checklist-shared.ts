export const EVALUATION_PARAMETER_MAP: { [key: string]: string } = {
  '1': 'Remarks',
  '2': 'Yes/No',
  '3': 'Ok/Not Ok',
  '4': 'Satisfactory/Not Satisfactory',
  '5': 'Applicable/Not Applicable',
  '6': 'Cleaned/Not Cleaned',
  '7': 'Sealed/Unsealed',
  '8': 'Verified/Not Verified',
  '9': 'Covered/Uncovered',
  '10': 'Suitable / Non Suitable',
  '11': 'Exceptional / Above Average / Average / Below Average / Unsatisfactory',
};

export function getEvaluationParameterName(value: string): string {
  return EVALUATION_PARAMETER_MAP[value] || 'Unknown';
}

export interface GroupedChecklistRow {
  key: string;
  department: string;
  module: string;
  form_name: string;
  sectionCount: number;
  checkpointCount: number;
  entry_by: string;
  approve_by: string;
  status: string;
  sections: any[];
}

export function buildGroupedChecklistList(rows: any[]): GroupedChecklistRow[] {
  const map = new Map<string, GroupedChecklistRow>();
  (rows || []).forEach((row) => {
    const key = `${row.department || ''}|${row.module || ''}|${row.form_name || ''}|${row.status || ''}`;
    if (!map.has(key)) {
      map.set(key, {
        key,
        department: row.department,
        module: row.module,
        form_name: row.form_name,
        sectionCount: 0,
        checkpointCount: 0,
        entry_by: row.entry_by || 'NA',
        approve_by: row.approve_by || 'NA',
        status: row.status || 'pending',
        sections: [],
      });
    }
    const group = map.get(key);
    group.sections.push(row);
    group.sectionCount = group.sections.length;
    group.checkpointCount += Array.isArray(row.checkList) ? row.checkList.length : 0;
    if (row.entry_by) {
      group.entry_by = row.entry_by;
    }
    if (row.approve_by) {
      group.approve_by = row.approve_by;
    }
  });
  return Array.from(map.values());
}

export function buildChecklistTransactionSections(rows: any[]): any[] {
  const sections: any[] = [];
  const map = new Map<string, any>();
  (rows || []).forEach((row) => {
    const heading = row.heading || row.section_heading || 'Checklist';
    if (!map.has(heading)) {
      const section = { heading, items: [] as any[] };
      map.set(heading, section);
      sections.push(section);
    }
    map.get(heading).items.push(row);
  });
  return sections;
}

/** Map checklist_answers saved inside receiving_details when transaction rows are missing. */
export function normalizeChecklistAnswersFallback(answers: any[] | null | undefined): any[] {
  if (!Array.isArray(answers)) {
    return [];
  }
  return answers.map((answer) => ({
    params: answer?.check_point ?? '',
    master_check_point: answer?.check_point ?? '',
    chk_value: answer?.check ?? '',
    heading: answer?.heading || 'Receiving Checklist',
    chk_type: answer?.evl_pr ?? '',
  }));
}

export function resolveReceivingChecklistRows(
  transactionRows: any[] | null | undefined,
  receivingDetails: any
): any[] {
  const rows = Array.isArray(transactionRows) ? transactionRows : [];
  if (rows.length > 0) {
    return rows;
  }
  const details =
    receivingDetails && typeof receivingDetails === 'object'
      ? receivingDetails
      : null;
  const answers = details?.checklist_answers;
  return normalizeChecklistAnswersFallback(answers);
}

export function resolveReceivingDamageChecklistRows(
  transactionRows: any[] | null | undefined,
  receivingDetails: any,
  damageChecklistColumn?: any
): any[] {
  const rows = Array.isArray(transactionRows) ? transactionRows : [];
  if (rows.length > 0) {
    return rows;
  }
  const details =
    receivingDetails && typeof receivingDetails === 'object'
      ? receivingDetails
      : null;
  const answers = details?.damage_checklist_answers;
  const fromDetails = normalizeChecklistAnswersFallback(answers);
  if (fromDetails.length > 0) {
    return fromDetails;
  }

  let columnItems = damageChecklistColumn;
  if (typeof columnItems === 'string' && columnItems.trim() !== '') {
    try {
      columnItems = JSON.parse(columnItems);
    } catch {
      columnItems = null;
    }
  }
  if (Array.isArray(columnItems) && columnItems.length > 0) {
    return normalizeChecklistAnswersFallback(
      columnItems.map((answer) => ({
        check_point: answer?.check_point ?? answer?.params ?? '',
        check: answer?.check ?? answer?.chk_value ?? '',
        heading: answer?.heading ?? 'Damage Container Checklist',
        evl_pr: answer?.evl_pr ?? answer?.chk_type ?? '',
        module: answer?.module ?? 'Receiving',
        form_name: answer?.form_name ?? answer?.form ?? 'Damage',
        department: answer?.department ?? answer?.deprt ?? 'Store',
        id: answer?.id ?? '',
      }))
    );
  }

  return [];
}

function isChecklistFooterLabel(label: string): boolean {
  const lower = label.toLowerCase();
  return lower.includes('contact purchasing associate');
}

/** Flat readonly rows matching awaiting checklist layout (header / item / footer). */
export function buildChecklistReadonlyRows(checklistRows: any[]): any[] {
  const sections = buildChecklistTransactionSections(checklistRows);
  const rows: any[] = [];

  sections.forEach((section, sIdx) => {
    const heading = String(section.heading || '').trim();
    if (heading) {
      rows.push({
        type: 'header',
        title: heading,
        showPoNo: heading.toLowerCase().includes('po#') || heading.includes('PO#_'),
      });
    }

    (section.items || []).forEach((item: any, idx: number) => {
      const label = String(
        item.params || item.master_check_point || item.check_point || ''
      ).trim();
      if (!label) {
        return;
      }
      if (isChecklistFooterLabel(label)) {
        rows.push({ type: 'footer', title: label });
        return;
      }
      rows.push({
        type: 'item',
        rowKey: `s${sIdx}_${idx}`,
        item: {
          check_point: label,
          check: item.chk_value ?? item.check ?? '',
          evl_pr: String(item.chk_type ?? item.evl_pr ?? ''),
        },
      });
    });
  });

  return rows;
}

export function hasChecklistFooterRow(rows: any[]): boolean {
  return (rows || []).some((row) => row?.type === 'footer');
}
