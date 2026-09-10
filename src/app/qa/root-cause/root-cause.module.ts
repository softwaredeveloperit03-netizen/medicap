import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { NewComponent } from './new/new.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { CheckingComponent } from './checking/checking.component';
import { ApproveComponent } from './approve/approve.component';
import { LogComponent } from './log/log.component';
import { TrackerComponent } from './tracker/tracker.component';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new', component: NewComponent},
  { path: 'checking', component: CheckingComponent},
  { path: 'approve', component: ApproveComponent},
  { path: 'log', component: LogComponent},
  { path: 'tracker', component: TrackerComponent},
];

@NgModule({
  declarations: [
    NewComponent,
    CheckingComponent,
    ApproveComponent,
    DashboardComponent,
    LogComponent,
    TrackerComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ReactiveFormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class RootCauseModule { }
