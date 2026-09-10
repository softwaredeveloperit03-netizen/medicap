import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { HttpClientModule } from '@angular/common/http';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { NewComponent } from './new/new.component';
import { ReviewComponent } from './review/review.component';
import { LogComponent } from './log/log.component';
import { SampleComponent } from './sample/sample.component';
import { MultiSelectModule } from 'primeng/multiselect';
import { HeadComponent } from './head/head.component';
import { ReciecveCompComponent } from './reciecve-comp/reciecve-comp.component';
import { TranslateModule } from '@ngx-translate/core';

 
const routes: Routes = [
  { path: '', redirectTo: 'dashboard', pathMatch: 'full' },
  { path: 'dashboard', component: DashboardComponent },
  { path: 'medical-complaint', component: NewComponent },
  { path: 'medical-inspection', component: ReviewComponent },
  { path: 'medical-log', component: LogComponent },
  { path: 'sample', component: SampleComponent },
  { path: 'head', component: HeadComponent },
  { path: 'receive_comp', component: ReciecveCompComponent }
];

@NgModule({
  declarations: [DashboardComponent, NewComponent, ReviewComponent,ReciecveCompComponent, LogComponent, SampleComponent, HeadComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ReactiveFormsModule,
    ClarityModule,
    MultiSelectModule,
    HttpClientModule,
    RouterModule.forChild(routes)
  ]
})
export class ComplaintModule { }
