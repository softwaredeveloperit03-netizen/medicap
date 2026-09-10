import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'register-audit', loadChildren: () => import('./register-audit/register-audit.module').then(m=>m.RegisterAuditModule), data: {preload: false}},
  { path: 'select-audit', loadChildren: () => import('./select-audit/select-audit.module').then(m=>m.SelectAuditModule), data: {preload: false}},


];

@NgModule({
  declarations: [
    DashboardComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class ExternalAuditModule { }
