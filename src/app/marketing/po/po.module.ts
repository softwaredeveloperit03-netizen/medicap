import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { ApprovalComponent } from './approval/approval.component';
import { LogComponent } from './log/log.component';
import { FormsModule } from '@angular/forms';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { MultiSelectModule } from 'primeng/multiselect';
import { DropdownModule } from 'primeng/dropdown';
import { CalendarModule } from 'primeng/calendar';
import { SummeryComponent } from './summery/summery.component';
import { FoComponent } from './fo/fo.component';
import { ProcessingComponent } from './processing/processing.component';
import { SharedModule } from 'src/app/shared/shared.module';
import { TranslateModule } from '@ngx-translate/core';

const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'new', component: NewComponent },
  { path: 'approval', component: ApprovalComponent },
  { path: 'log', component: LogComponent },
  { path: 'summery', component: SummeryComponent },
  { path: 'fo', component: FoComponent },
  { path: 'processing', component: ProcessingComponent },
];

@NgModule({
  declarations: [
    DashboardComponent,
    NewComponent,
    ApprovalComponent,
    LogComponent,
    SummeryComponent,
    FoComponent,
    ProcessingComponent,
  ],
  imports: [
    SharedModule,
    TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    MultiSelectModule,
    DropdownModule,
    CalendarModule,
    RouterModule.forChild(routes),
  ],
})
export class PoModule {}
