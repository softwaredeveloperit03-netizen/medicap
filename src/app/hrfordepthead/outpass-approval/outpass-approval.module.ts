import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { OutpassApprovalDashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: OutpassApprovalDashboardComponent }
];

@NgModule({
  declarations: [
    OutpassApprovalDashboardComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class OutpassApprovalModule {}
