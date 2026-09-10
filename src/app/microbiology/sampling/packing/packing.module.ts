import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { CheckingComponent } from './checking/checking.component';
import { ApprovalComponent } from './approval/approval.component';
import { LogComponent } from './log/log.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'checking', component: CheckingComponent},
  { path: 'approval', component: ApprovalComponent},
  { path: 'log', component: LogComponent},
  { path: 'allocation', loadChildren: () => import('./allocation/allocation.module').then(m=>m.AllocationModule), data: {preload: false}},
  { path: 'sample', loadChildren: () => import('./sample/sample.module').then(m=>m.SampleModule), data: {preload: false}},
  { path: 'label', loadChildren: () => import('./label/label.module').then(m=>m.LabelModule), data: {preload: false}},
];

@NgModule({
  declarations: [DashboardComponent, CheckingComponent, ApprovalComponent, LogComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class PackingModule { }
