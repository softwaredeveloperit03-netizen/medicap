import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { DeptheadreviewComponent } from './deptheadreview/deptheadreview.component';
import { QaheadreviewComponent } from './qaheadreview/qaheadreview.component';
 import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'deptHeadReview', component: DeptheadreviewComponent },
  { path: 'qaHeadReview', component: QaheadreviewComponent },
 

]

@NgModule({
  declarations: [
    DashboardComponent,
    DeptheadreviewComponent,
    QaheadreviewComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes),
  ]
})
export class SopModule { }
