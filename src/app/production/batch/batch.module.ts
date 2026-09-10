import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { AwaitingComponent } from './awaiting/awaiting.component';
import { AwatingproceedComponent } from './awatingproceed/awatingproceed.component';
import { LogComponent } from './log/log.component';
import { CompletedComponent } from './completed/completed.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { InprocessComponent } from './inprocess/inprocess.component';
import { NewComponent } from './new/new.component';
import { DispensingComponent } from './dispensing/dispensing.component';
import { TransfredComponent } from './transfred/transfred.component';
import { YieldComponent } from './yield/yield.component';
import { WorkComponent } from './work/work.component';
import { RequisitionComponent } from './requisition/requisition.component';
import { PlanComponent } from './plan/plan.component';
import { CheckingComponent } from './checking/checking.component';
import { ApprovalComponent } from './approval/approval.component';
import { SendComponent } from './send/send.component';
import {MultiSelectModule} from 'primeng/multiselect';
import { SpawaitingComponent } from './spawaiting/spawaiting.component';
import { SpinprocessComponent } from './spinprocess/spinprocess.component';
import { LabelapprovalComponent } from './labelapproval/labelapproval.component';
import { EditorModule } from '@tinymce/tinymce-angular';
import { QuillModule } from 'ngx-quill';
import { AwatingproceedcheckComponent } from './awatingproceedcheck/awatingproceedcheck.component';

const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'send', component: SendComponent},
  { path: 'plan', component: PlanComponent},
  { path: 'awaiting', component: AwaitingComponent},
  { path: 'awaitingproceed/:product_code', component: AwatingproceedComponent},
  { path: 'awatingproceedcheck/:product_code', component: AwatingproceedcheckComponent},
  { path: 'awaiting', component: AwaitingComponent},
  { path: 'spawaiting', component: SpawaitingComponent},
  { path: 'requisition', component: RequisitionComponent},
  { path: 'inprocess', component: InprocessComponent},
  { path: 'spinprocess', component: SpinprocessComponent},
  { path: 'checking', component: CheckingComponent},
  { path: 'approval', component: ApprovalComponent},
  { path: 'completed', component: CompletedComponent},
  { path: 'transfred', component: TransfredComponent},
  { path: 'log', component: LogComponent},
  { path: 'label', component: LabelapprovalComponent},
  { path: 'new/:id', component: NewComponent},
  /* { path: 'dispensing/:id', component: DispensingComponent}, */
  { path: 'yield', component: YieldComponent},
  /* { path: 'work/:id', component: WorkComponent}, */
  { path: 'qa', loadChildren: () => import('./qa/qa.module').then(m=>m.QaModule)},
  { path: 'work', loadChildren: () => import('./work/work.module').then(m=>m.WorkModule)},
  { path: 'dispensing', loadChildren: () => import('./dispensing/dispensing.module').then(m=>m.DispensingModule)},
  { path: 'packing', loadChildren: () => import('./packing/packing.module').then(m=>m.PackingModule)},
];

@NgModule({
  declarations: [DashboardComponent, AwaitingComponent, LogComponent, CompletedComponent, InprocessComponent, NewComponent, DispensingComponent, TransfredComponent, YieldComponent, WorkComponent, RequisitionComponent, PlanComponent, CheckingComponent, ApprovalComponent, SendComponent, SpawaitingComponent, SpinprocessComponent,AwatingproceedComponent, LabelapprovalComponent, AwatingproceedcheckComponent],
  imports: [
    SharedModule,
    CommonModule,
    EditorModule,
    
    QuillModule.forRoot(),
    
    MultiSelectModule,      
    
    FormsModule,
    ClarityModule,
    MultiSelectModule,
    RouterModule.forChild(routes)
  ]
})
export class BatchModule { }
