import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { ApprovedComponent } from './approved/approved.component';
import { QuarantineComponent } from './quarantine/quarantine.component';
import { ClarityModule } from '@clr/angular';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { RouterModule, Routes } from '@angular/router';
import { WorkingComponent } from './working/working.component';
import { ExpiredComponent } from './expired/expired.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'approved', component: ApprovedComponent},
  { path: 'quarantine', component: QuarantineComponent},
  { path: 'working', component: WorkingComponent},
  { path: 'expired', component: ExpiredComponent},

];


@NgModule({
  declarations: [
    DashboardComponent,
    ApprovedComponent,
    QuarantineComponent,
    WorkingComponent,
    ExpiredComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    ClarityModule,
    FormsModule,
    RouterModule.forChild(routes)
  ]
})
export class StockReportModule { }
