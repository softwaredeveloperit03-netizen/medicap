import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { ProcedureComponent } from './procedure/procedure.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'procedure', component: ProcedureComponent},
  { path: 'checklist', loadChildren: () => import('./checklist/checklist.module').then(m=>m.ChecklistModule), data: {preload: false}},
  { path: 'documents', loadChildren: () => import('./documents/documents.module').then(m=>m.DocumentsModule), data: {preload: false}},
  { path: 'performace', loadChildren: () => import('./performace/performace.module').then(m=>m.PerformaceModule), data: {preload: false}},
  { path: 'report', loadChildren: () => import('./report/report.module').then(m=>m.ReportModule), data: {preload: false}},
  { path: 'environment', loadChildren: () => import('./environment/environment.module').then(m=>m.EnvironmentModule), data: {preload: false}},
  { path: 'variable', loadChildren: () => import('./variable/variable.module').then(m=>m.VariableModule), data: {preload: false}},

];

@NgModule({
  declarations: [
    DashboardComponent,
    ProcedureComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class PqModule { }
