import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';

import { HplcHubDashboardComponent } from './hub/hub-dashboard.component';
import { HplcPageShellComponent } from './shared/hplc-page-shell.component';
import { ColumnMasterComponent } from './column/master/column-master.component';
import { ColumnOrderingComponent } from './column/ordering/column-ordering.component';
import { ColumnReceivingComponent } from './column/receiving/column-receiving.component';
import { ColumnRegenerationComponent } from './column/regeneration/column-regeneration.component';
import { ColumnLogbookComponent } from './column/logbook/column-logbook.component';
import { ColumnDestructionComponent } from './column/destruction/column-destruction.component';
import { SolutionPrepComponent } from './solution/solution-prep.component';
import { SolutionIssuanceComponent } from './solution/solution-issuance.component';
import { SolutionLogComponent } from './solution/solution-log.component';
import { HplcAnalysisComponent } from './analysis/hplc-analysis.component';
import { ChromatogramInterpretationComponent } from './interpretation/chromatogram-interpretation.component';
import { SystemSuitabilityComponent } from './suitability/system-suitability.component';
import { HplcCalibrationComponent } from './calibration/hplc-calibration.component';
import { HplcAuditTrailComponent } from './audit/hplc-audit-trail.component';

const solutionPrep = (solutionType: string, formKind: string) => ({
  component: SolutionPrepComponent,
  data: { solutionType, formKind },
});

const routes: Routes = [
  { path: '', redirectTo: 'hub', pathMatch: 'full' },
  { path: 'hub', component: HplcHubDashboardComponent },
  { path: 'column/master', component: ColumnMasterComponent },
  { path: 'column/ordering', component: ColumnOrderingComponent },
  { path: 'column/receiving', component: ColumnReceivingComponent },
  { path: 'column/regeneration', component: ColumnRegenerationComponent },
  { path: 'column/logbook', component: ColumnLogbookComponent },
  { path: 'column/destruction', component: ColumnDestructionComponent },
  { path: 'solution/mobile-phase', ...solutionPrep('Mobile Phase', 'mp') },
  { path: 'solution/diluent', ...solutionPrep('Diluent', 'dil') },
  { path: 'solution/stock', ...solutionPrep('Stock Standard', 'stk') },
  { path: 'solution/dilution', ...solutionPrep('Dilution', 'dilution') },
  { path: 'solution/issuance', component: SolutionIssuanceComponent },
  { path: 'solution/log', component: SolutionLogComponent },
  { path: 'analysis', component: HplcAnalysisComponent },
  { path: 'interpretation', component: ChromatogramInterpretationComponent },
  { path: 'suitability', component: SystemSuitabilityComponent },
  { path: 'calibration', component: HplcCalibrationComponent },
  { path: 'audit', component: HplcAuditTrailComponent },
  /** Legacy redirects */
  { path: 'inward', redirectTo: 'column/receiving', pathMatch: 'full' },
  { path: 'discard', redirectTo: 'column/destruction', pathMatch: 'full' },
];

@NgModule({
  declarations: [
    HplcHubDashboardComponent,
    HplcPageShellComponent,
    ColumnMasterComponent,
    ColumnOrderingComponent,
    ColumnReceivingComponent,
    ColumnRegenerationComponent,
    ColumnLogbookComponent,
    ColumnDestructionComponent,
    SolutionPrepComponent,
    SolutionIssuanceComponent,
    SolutionLogComponent,
    HplcAnalysisComponent,
    ChromatogramInterpretationComponent,
    SystemSuitabilityComponent,
    HplcCalibrationComponent,
    HplcAuditTrailComponent,
  ],
  imports: [TranslateModule, CommonModule, FormsModule, ClarityModule, RouterModule.forChild(routes)],
})
export class HplcModule {}
