 


 import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { DashboardComponent } from './dashboard/dashboard.component';
import { ClarityModule } from '@clr/angular';
import { ReviewComponent } from './review/review.component';
import { NewComponent } from './new/new.component';
import { ImpactComponent } from './impact/impact.component';
import { LogComponent } from './log/log.component';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'new', component: NewComponent },
  { path: 'review', component: ReviewComponent },
  { path: 'impact', component: ImpactComponent },
  { path: 'log', component: LogComponent },
 
];

@NgModule({
  declarations: [
    DashboardComponent,NewComponent,ReviewComponent, ImpactComponent, LogComponent
 
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes),
  ],
})
export class PdeviatonModule { }
