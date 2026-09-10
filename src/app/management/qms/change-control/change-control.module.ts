import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { NewComponent } from './new/new.component';
import { LogComponent } from './log/log.component';
import { ReviewComponent } from './review/review.component';
import { ImplementationComponent } from './implementation/implementation.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new', component: NewComponent},
  { path: 'log', component: LogComponent},
  { path: 'review', component: ReviewComponent},
  { path: 'implementation', component: ImplementationComponent},

];

@NgModule({
  declarations: [
    DashboardComponent,
    NewComponent,
    LogComponent,
    ReviewComponent,
    ImplementationComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class ChangeControlModule { }
