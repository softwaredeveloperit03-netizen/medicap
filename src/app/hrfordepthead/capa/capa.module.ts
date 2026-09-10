import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
 import { DashboardComponent } from './dashboard/dashboard.component';
import { CheckingComponent } from './checking/checking.component';
import { HeadcheckComponent } from './headcheck/headcheck.component';
import { NewComponent } from './new/new.component';
import { ExtededComponent } from './exteded/exteded.component';
import { ExtededreviewComponent } from './extededreview/extededreview.component';
import { EXTENSIONCAPAComponent } from './extensioncapa/extensioncapa.component';
import { DeptCommentComponent } from './dept-comment/dept-comment.component';


const routes: Routes = [
  { path: '', redirectTo: 'dashboard', pathMatch: 'full' },
  { path: 'dashboard', component: DashboardComponent },
  { path: 'checking', component: CheckingComponent },
  { path: 'headchecking', component: HeadcheckComponent },
  { path: 'new', component: NewComponent },
  { path: 'Extended', component: ExtededComponent },
  { path: 'ExtededreviewComponent', component: ExtededreviewComponent },
  { path: 'ExtensionCapa', component: EXTENSIONCAPAComponent },
  { path: 'ConcernDeptComment', component: DeptCommentComponent },
];
@NgModule({
  declarations: [DashboardComponent,CheckingComponent, HeadcheckComponent,NewComponent, ExtededComponent, ExtededreviewComponent, EXTENSIONCAPAComponent, DeptCommentComponent],
  imports: [
    SharedModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class CapaModule { }
