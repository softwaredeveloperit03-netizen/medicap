import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { AnnoucementComponent } from './annoucement/annoucement.component';
import { StartComponent } from './start/start.component';
import { ReviewComponent } from './review/review.component';
import { LogComponent } from './log/log.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { DashboardComponent } from './dashboard/dashboard.component';
import { InprocessComponent } from './inprocess/inprocess.component';
import { MultiSelectModule } from 'primeng/multiselect';
import { DropdownModule } from 'primeng/dropdown';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'announcement', component: AnnoucementComponent},
  { path: 'start', component: StartComponent},
  { path: 'review', component: ReviewComponent},
  { path: 'log', component: LogComponent},
  { path: 'inprocess', component: InprocessComponent}
];

@NgModule({
  declarations: [AnnoucementComponent, StartComponent, ReviewComponent, LogComponent, DashboardComponent, InprocessComponent],
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
export class MeetingModule { }
