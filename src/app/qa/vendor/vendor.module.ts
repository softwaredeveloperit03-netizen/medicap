import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { ClarityModule } from '@clr/angular';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { RouterModule, Routes } from '@angular/router';
import { ChecklistsComponent } from './checklists/checklists.component';
import { ApprovalComponent } from './approval/approval.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'checklistmaster', component: ChecklistsComponent },
  { path: 'vendorForApproval', component: ApprovalComponent },

];

@NgModule({
  declarations: [
    DashboardComponent,
    ChecklistsComponent,
    ApprovalComponent,
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes),
  ],
})
export class VendorModule {}
