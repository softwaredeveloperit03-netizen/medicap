import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { ApprovalComponent } from './approval/approval.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { LogComponent } from './log/log.component';
import { MultiSelectModule } from 'primeng/multiselect';
import { DropdownModule } from 'primeng/dropdown';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new', component: NewComponent},
  { path: 'approval', component: ApprovalComponent},
  { path: 'log', component: LogComponent},
  { path: 'equipment', loadChildren: () => import('./equipment/equipment.module').then(m=>m.EquipmentModule), data: {preload: false}},
  { path: 'general', loadChildren: () => import('./general/general.module').then(m=>m.GeneralModule), data: {preload: false}},
  { path: 'spare', loadChildren: () => import('./spare/spare.module').then(m=>m.SpareModule), data: {preload: false}},

  ];
  
@NgModule({
  declarations: [DashboardComponent, LogComponent,NewComponent,ApprovalComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    MultiSelectModule,
    DropdownModule,
    RouterModule.forChild(routes)
  ]
})
export class IndendModule { }
