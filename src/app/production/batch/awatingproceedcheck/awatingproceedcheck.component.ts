import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { forkJoin, of } from 'rxjs';
import { catchError, finalize } from 'rxjs/operators';
import { DataAccessService } from 'src/app/data-access.service';
import {
  BmrFormStorageService,
  BmrLocalSnapshot,
  BmrPrintOrderedStageSection,
  BmrPrintOrderedStepBlock,
  BmrPrintOrderedSubstepBlock,
  BmrStepSnapshot,
  BmrSubstepSnapshot,
} from '../bmr-form-storage.service';
import {
  buildEbmrPayloadView,
  EbmrPayloadView,
  formatEbmrCell,
} from '../ebmr-payload-view';
import { DataService } from '../data.service';

declare let alertify;

@Component({
  selector: 'app-awatingproceedcheck',
  templateUrl: './awatingproceedcheck.component.html',
  styleUrls: ['./awatingproceedcheck.component.css'],
})
export class AwatingproceedcheckComponent implements OnInit {
  selectedResult: any;
  snapshot: BmrLocalSnapshot | null = null;
  stepRows: BmrStepSnapshot[] = [];
  substepRows: BmrSubstepSnapshot[] = [];
  /** Saved data grouped by Stage → Step → Substep (same order as Proceed / BMR master) */
  bmrOrderedSections: BmrPrintOrderedStageSection[] = [];
  /** Saves whose ids were not found under the loaded BMR tree (template change or API miss) */
  bmrOrphanSteps: BmrStepSnapshot[] = [];
  bmrOrphanSubsteps: BmrSubstepSnapshot[] = [];
  /** False when getProcesses_BMRviewProceed did not return Stages (ordering falls back to orphans-only grouping) */
  bmrStagesLoadedOk = false;
  /** True while GET ebmr_filled_step_api.php?type=get_batch_steps is in flight */
  loadingSnapshot = false;
  /** Shown when server returned nothing useful or request failed */
  snapshotHint: string | null = null;
  /** When saves exist but BMR master tree API failed — explain flat orphan grouping */
  bmrStructureHint: string | null = null;

  constructor(
    private dataService: DataService,
    private dataAccess: DataAccessService,
    private route: ActivatedRoute,
    private router: Router,
    private bmrFormStorage: BmrFormStorageService
  ) {}

  ngOnInit(): void {
    this.selectedResult = this.dataService.getData();
    if (!this.selectedResult) {
      this.router.navigate(['/prod-f-ebmr/batch/awaiting']);
      return;
    }
    this.route.paramMap.subscribe((params) => {
      const pc = params.get('product_code');
      if (pc && pc !== this.selectedResult['product_code']) {
        alertify.error('Product code does not match the selected batch.');
        this.router.navigate(['/prod-f-ebmr/batch/awaiting']);
        return;
      }
      this.reloadSnapshot();
    });
  }

  reloadSnapshot(): void {
    if (!this.selectedResult) {
      return;
    }
    const pc = this.selectedResult['product_code'];
    const wo = this.selectedResult['work_order_no'];
    const bn = this.selectedResult['batch_number'];
    const pn = this.selectedResult['product_name'];
    this.loadingSnapshot = true;
    this.snapshotHint = null;
    this.bmrStructureHint = null;
    const stagesUrl =
      'bmr/process.php?type=getProcesses_BMRviewProceed&product_code=' +
      encodeURIComponent(String(pc)) +
      '&batch_number=' +
      encodeURIComponent(String(bn)) +
      '&work_order_no=' +
      encodeURIComponent(String(wo));

    forkJoin({
      serverSnap: this.bmrFormStorage
        .fetchServerSnapshot(pc, wo, bn, pn)
        .pipe(catchError(() => of(null))),
      stagesRes: this.dataAccess
        .get(stagesUrl)
        .pipe(catchError(() => of(null))),
    })
      .pipe(finalize(() => (this.loadingSnapshot = false)))
      .subscribe({
        next: ({ serverSnap, stagesRes }) => {
          const local = this.bmrFormStorage.getSnapshot(pc, wo, bn);
          this.snapshot = this.bmrFormStorage.mergeServerAndLocal(
            local,
            serverSnap
          );
          this.applyRowsFromSnapshot();
          this.bmrStagesLoadedOk = this.stagesApiReturnedTree(stagesRes);
          this.rebuildBmrOrderedView(stagesRes);
          const hasRows =
            this.stepRows.length > 0 || this.substepRows.length > 0;
          if (!serverSnap && !local) {
            this.snapshotHint =
              'No data from server and nothing in browser storage for this batch. Save steps from Proceed first, or check API / network.';
          } else if (!serverSnap && local) {
            this.snapshotHint =
              'Could not load from server; showing browser copy only. Use Refresh after checking your connection.';
          } else if (
            serverSnap &&
            !hasRows &&
            serverSnap.approvalStatus !== 'approved'
          ) {
            this.snapshotHint =
              'Server has no saved fills for this batch yet. Submit saves from Proceed to store rows in ebmr_filled_step.';
          }
          if (hasRows && !this.bmrStagesLoadedOk) {
            this.bmrStructureHint =
              'Could not load BMR stage/step order from process.php. Saved rows appear under “Outside current BMR tree” sorted by id.';
          }
        },
        error: () => {
          const local = this.bmrFormStorage.getSnapshot(pc, wo, bn);
          this.snapshot = local;
          this.applyRowsFromSnapshot();
          this.bmrStagesLoadedOk = false;
          this.rebuildBmrOrderedView(null);
          this.snapshotHint =
            'Server request failed. Showing local data only if the browser has a copy.';
        },
      });
  }

  private applyRowsFromSnapshot(): void {
    this.stepRows = this.snapshot
      ? this.bmrFormStorage.stepEntries(this.snapshot)
      : [];
    this.substepRows = this.snapshot
      ? this.bmrFormStorage.substepEntries(this.snapshot)
      : [];
  }

  /** Same null-step filtering as awatingproceed.component.ts */
  private filterNullSteps(stages: any[]): any[] {
    if (!stages || !Array.isArray(stages)) {
      return [];
    }
    return stages.map((stage: any) => {
      if (stage && stage['Steps'] && Array.isArray(stage['Steps'])) {
        stage['Steps'] = stage['Steps'].filter(
          (step: any) => step !== null && step !== undefined
        );
      } else if (stage) {
        stage['Steps'] = [];
      }
      return stage;
    });
  }

  /** True when process.php returned a Stages array (may be empty). */
  private stagesApiReturnedTree(response: any): boolean {
    return !!(
      response &&
      Array.isArray(response) &&
      response[0] &&
      Array.isArray(response[0]['Stages'])
    );
  }

  private parseBmrStages(response: any): any[] {
    if (!response || !Array.isArray(response) || !response[0]) {
      return [];
    }
    const raw = response[0]['Stages'];
    if (!raw || !Array.isArray(raw)) {
      return [];
    }
    return this.filterNullSteps(raw);
  }

  /**
   * Walk Stages → Steps → Substeps in master order; attach saved snapshot rows by id.
   * Unmatched saves go to {@link bmrOrphanSteps} / {@link bmrOrphanSubsteps}.
   */
  private rebuildBmrOrderedView(stagesResponse: any): void {
    this.bmrOrderedSections = [];
    this.bmrOrphanSteps = [];
    this.bmrOrphanSubsteps = [];
    if (!this.snapshot) {
      return;
    }

    const stages = this.parseBmrStages(stagesResponse);
    const stepSnaps = this.snapshot.steps;
    const subSnaps = this.snapshot.substeps;

    const seenStepIds = new Set<string>();
    const seenSubKeys = new Set<string>();

    for (let si = 0; si < stages.length; si++) {
      const stage = stages[si];
      const stageTitle =
        (stage && (stage['stages'] || stage['stage_name'])) ||
        `Stage ${si + 1}`;
      const stepsArr = (stage && stage['Steps']) || [];
      const stepBlocks: BmrPrintOrderedStepBlock[] = [];

      for (let ti = 0; ti < stepsArr.length; ti++) {
        const stp = stepsArr[ti];
        if (!stp) {
          continue;
        }
        const sid = String(stp['id']);
        const stepName = stp['step'] != null ? String(stp['step']) : '';
        const stepSaved = stepSnaps[sid] || null;

        const subBlocks: BmrPrintOrderedSubstepBlock[] = [];
        const subs = stp['Substeps'];
        if (subs && Array.isArray(subs)) {
          for (let ui = 0; ui < subs.length; ui++) {
            const sub = subs[ui];
            if (!sub) {
              continue;
            }
            const subid = String(sub['id']);
            const key = `${sid}__${subid}`;
            const saved = subSnaps[key];
            if (saved) {
              subBlocks.push({
                subOrdinal: ui + 1,
                subId: subid,
                subName:
                  sub['substep'] != null
                    ? String(sub['substep'])
                    : sub['name'] != null
                      ? String(sub['name'])
                      : '—',
                saved,
              });
              seenSubKeys.add(key);
            }
          }
        }

        if (stepSaved) {
          seenStepIds.add(sid);
        }
        if (stepSaved || subBlocks.length > 0) {
          stepBlocks.push({
            stepOrdinal: ti + 1,
            stepId: sid,
            stepName,
            stepSaved,
            substeps: subBlocks,
          });
        }
      }

      if (stepBlocks.length > 0) {
        this.bmrOrderedSections.push({
          stageOrdinal: si + 1,
          stageTitle: String(stageTitle),
          steps: stepBlocks,
        });
      }
    }

    for (const id of Object.keys(stepSnaps)) {
      if (!seenStepIds.has(id)) {
        this.bmrOrphanSteps.push(stepSnaps[id]);
      }
    }
    for (const key of Object.keys(subSnaps)) {
      if (!seenSubKeys.has(key)) {
        this.bmrOrphanSubsteps.push(subSnaps[key]);
      }
    }

    this.bmrOrphanSteps.sort((a, b) =>
      this.idSortKey(String(a.stepId), String(b.stepId))
    );
    this.bmrOrphanSubsteps.sort((a, b) => {
      const c = this.idSortKey(String(a.stepId), String(b.stepId));
      return c !== 0 ? c : this.idSortKey(String(a.subId), String(b.subId));
    });
  }

  private idSortKey(a: string, b: string): number {
    const na = Number(a);
    const nb = Number(b);
    if (
      a !== '' &&
      b !== '' &&
      !Number.isNaN(na) &&
      !Number.isNaN(nb) &&
      String(na) === a &&
      String(nb) === b
    ) {
      return na - nb;
    }
    return a.localeCompare(b, undefined, { numeric: true });
  }

  approve(): void {
    if (!this.selectedResult) {
      return;
    }
    if (this.stepRows.length === 0 && this.substepRows.length === 0) {
      alertify.error('No step data on server yet. Save steps from Proceed first.');
      return;
    }
    this.bmrFormStorage.approveOnServer(this.selectedResult).subscribe(
      (res: any) => {
        if (res && res['status'] === 'success') {
          alertify.success(
            'Batch approved on server. PDF on Awaiting uses saved step data when approved.'
          );
          this.reloadSnapshot();
        } else {
          alertify.error(res && res['msg'] ? res['msg'] : 'Approve failed');
        }
      },
      () => alertify.error('Approve request failed')
    );
  }

  preview(): void {
    if (!this.snapshot) {
      return;
    }
    const sr = this.selectedResult;
    const coverProductLine = [sr?.product_name, sr?.grade]
      .filter((x) => x != null && String(x).trim() !== '')
      .join(' ');
    this.bmrFormStorage.openPrintableReport(this.snapshot, {
      orderedSections:
        this.bmrOrderedSections.length > 0 ? this.bmrOrderedSections : null,
      orphanSteps: this.bmrOrphanSteps.length > 0 ? this.bmrOrphanSteps : null,
      orphanSubsteps:
        this.bmrOrphanSubsteps.length > 0 ? this.bmrOrphanSubsteps : null,
      coverProductLine: coverProductLine || null,
    });
  }

  close(): void {
    this.router.navigate(['/prod-f-ebmr/batch/awaiting']);
  }

  /** Template: structured table(s) for a main step row. */
  payloadViewForStep(row: BmrStepSnapshot): EbmrPayloadView {
    return buildEbmrPayloadView(row.stepName, row.data);
  }

  /** Template: substeps have no step title — infer columns from data. */
  payloadViewForSubstep(row: BmrSubstepSnapshot): EbmrPayloadView {
    return buildEbmrPayloadView(undefined, row.data);
  }

  /** Prefer substep title from BMR when inferring table columns. */
  payloadViewForOrderedSub(block: BmrPrintOrderedSubstepBlock): EbmrPayloadView {
    return buildEbmrPayloadView(block.subName, block.saved.data);
  }

  formatCell = formatEbmrCell;
}
