import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { DashboardComponent } from './dashboard/dashboard.component';
import { ClarityModule } from '@clr/angular';
import { ReviewComponent } from './review/review.component';
import { TranslateModule } from '@ngx-translate/core';

 import { HeadComponent } from './head/head.component';


const routes: Routes = [
  { path: '', component: DashboardComponent },
   { path: 'review', component: ReviewComponent },
   { path: 'headQa', component: HeadComponent },
 
];

@NgModule({
  declarations: [
    DashboardComponent, ReviewComponent,    HeadComponent,

 
  ],
  imports: [
    SharedModule, TranslateModule, 
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes),
  ],
})
export class PChangecontrolModule { }
