




import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { AppraisalDeptHeadComponent } from './appraisal-dept-head/appraisal-dept-head.component';
import { DeptheadhrComponent } from './deptheadhr/deptheadhr.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { ClarityModule } from '@clr/angular';
import { ShiftchngeComponent } from './shiftchnge/shiftchnge.component';
import { ResignComponent } from './resign/resign.component';
import {ArtworkComponent} from './artwork/artwork.component';
import { FinalArtworkComponent } from './final-artwork/final-artwork.component';
  
import { NewreqComponent } from './newreq/newreq.component';
import { StockapprovalComponent } from './stockapproval/stockapproval.component';
import { DeviationHodComponent } from './deviation-hod/deviation-hod.component';
import { QaDeviationComponent } from './qa-deviation/qa-deviation.component';
import { DevReviewComponent } from './dev-review/dev-review.component';
import { DeviationDecComponent } from './deviation-dec/deviation-dec.component';
import { ChngcontrolQAComponent } from './chngcontrol-qa/chngcontrol-qa.component';
import { CcPrimaryReviewComponent } from './cc-primary-review/cc-primary-review.component';
import { AddDeptComponent } from './add-dept/add-dept.component';
import { PoapprovalComponent } from './poapproval/poapproval.component';
import { DeptheadCalibrationComponent } from './calibration/depthead-calibration.component';
import { TranslateModule } from '@ngx-translate/core';
import { SharedModule } from '../shared/shared.module';
import { DocsIconsModule } from '../floating-docs-popup/docs-icons.module';

 


const routes: Routes = [
  { path: '', component: DeptheadhrComponent },
  { path: 'appraisal_request', component: AppraisalDeptHeadComponent },
  { path: 'shift_request', component: ShiftchngeComponent },
  { path: 'artwork', component: ArtworkComponent },
  { path: 'qaartwork', component: FinalArtworkComponent },
  { path: 'stockTranferReq', component: NewreqComponent },
  { path: 'stockapproval', component: StockapprovalComponent },
  { path: 'devHod', component: DeviationHodComponent },
  { path: 'qa_deviation', component: QaDeviationComponent },
  { path: 'review_deviation', component: DevReviewComponent },
  { path: 'decision', component: DeviationDecComponent },
  { path: 'ChangecontrolQA', component: ChngcontrolQAComponent },
  { path: 'resign', component: ResignComponent },
  { path: 'devdec', component: DeviationDecComponent },
  { path: 'CcPrimaryReview', component: CcPrimaryReviewComponent },
  { path: 'Add_dept', component: AddDeptComponent },
  { path: 'poApproval', component: PoapprovalComponent },
  { path: 'calibration', component: DeptheadCalibrationComponent },

 {
    path: 'pdeviaton',
    loadChildren: () =>
      import('./pdeviaton/pdeviaton.module').then(
        (m) => m.PdeviatonModule
      ),
    data: { preload: false },
  },
  { path: 'requisition', loadChildren: () => import('./requisition/requisition.module').then(m=>m.RequisitionModule), data: {preload: false}},

  {
    path: 'jobres', 
    loadChildren: () =>
      import('./jobres/jobres.module').then((m) => m.JobresModule),
    data: { preload: false },
  },
  {
    path: 'shift',
    loadChildren: () =>
      import('./shift/shift.module').then((m) => m.ShiftModule),
    data: { preload: false },
  },
  {
    path: 'deviation',
    loadChildren: () =>
      import('./deviation/deviation.module').then((m) => m.DeviationModule),
    data: { preload: false },
  },

  
 {
    path: 'visitor-pass',
    loadChildren: () =>
      import('./visitor-pass/visitor-pass.module').then(
        (m) => m.VisitorPassModule
      ),
    data: { preload: false },
  },
  {
    path: 'outpass-approval',
    loadChildren: () =>
      import('./outpass-approval/outpass-approval.module').then(
        (m) => m.OutpassApprovalModule
      ),
    data: { preload: false },
  },


  {
    path: 'leave',
    loadChildren: () =>
      import('./leave/leave.module').then((m) => m.LeaveModule),
    data: { preload: false },
  },
  {
    path: 'cc',
    loadChildren: () =>
      import('./cc/cc.module').then((m) => m.CcModule),
    data: { preload: false },
  },
  {
    path: 'indent',
    loadChildren: () =>
      import('./indent/indent.module').then((m) => m.IndentModule),
    data: { preload: false },
  },
  {
    path: 'duty',
    loadChildren: () => import('./duty/duty.module').then((m) => m.DutyModule),
    data: { preload: false },
  },
  {
    path: 'training',
    loadChildren: () =>
      import('./training/training.module').then((m) => m.TrainingModule),
    data: { preload: false },
  },
  {
    path: 'sop',
    loadChildren: () =>
      import('./sop/sop.module').then((m) => m.SopModule),
    data: { preload: false },
  },
  {
    path: 'risk',
    loadChildren: () =>
      import('./risk/risk.module').then((m) => m.RiskModule),
    data: { preload: false },
  },
  {
    path: 'capa',
    loadChildren: () =>
      import('./capa/capa.module').then((m) => m.CapaModule),
    data: { preload: false },
  },
  {
    path: 'incident',
    loadChildren: () => import('./incident/incident.module').then((m) => m.IncidentModule),
    data: { preload: false },
  },
  {
    path: 'p-changecontrol',
    loadChildren: () => import('./p-changecontrol/p-changecontrol.module').then((m) => m.PChangecontrolModule),
    data: { preload: false },
  },
];





@NgModule({
  declarations: [
    AppraisalDeptHeadComponent,
    DeptheadhrComponent,
    ShiftchngeComponent,
    ResignComponent,
    PoapprovalComponent,
    ArtworkComponent,
    DeviationDecComponent,
    FinalArtworkComponent,
    NewreqComponent,
    StockapprovalComponent,
    DeviationHodComponent,
    QaDeviationComponent,
    DevReviewComponent,
    ChngcontrolQAComponent,
    CcPrimaryReviewComponent,
    AddDeptComponent,
    DeptheadCalibrationComponent,
    
  ],
  imports: [ TranslateModule,
    SharedModule,
    DocsIconsModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes),
  ],
})
export class HrfordeptheadModule {}
