import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { NewComponent } from './new/new.component';
import { ApprovalComponent } from './approval/approval.component';
import { LogComponent } from './log/log.component';
import { BarcodesComponent } from './barcodes/barcodes.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { DashboardComponent } from './dashboard/dashboard.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new', component: NewComponent},
  { path: 'approval', component: ApprovalComponent},
  { path: 'log', component: LogComponent},
  { path: 'barcodes', component: BarcodesComponent}
];

@NgModule({
  declarations: [NewComponent, ApprovalComponent, LogComponent, DashboardComponent, BarcodesComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class UsagesModule { }
