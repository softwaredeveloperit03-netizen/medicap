import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { NewComponent } from './new/new.component';
import { LogComponent } from './log/log.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'log', component: LogComponent},
  // { path: 'new', component: NewComponent},
  { path: 'leave', loadChildren: () => import('./leave/leave.module').then(m=>m.LeaveModule), data: {preload: false}},
  { path: 'checklist-master', loadChildren: () => import('./checklist-master/checklist-master.module').then(m=>m.ChecklistMasterModule), data: {preload: false}},
  { path: 'asset', loadChildren: () => import('./asset/asset.module').then(m=>m.AssetModule), data: {preload: false}},

];

@NgModule({
  declarations: [ DashboardComponent,LogComponent, NewComponent,],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class HraModule { }
