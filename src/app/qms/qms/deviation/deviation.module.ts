import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { CheckingComponent } from './checking/checking.component';
import { ReviewComponent } from './review/review.component';
import { LogComponent } from './log/log.component';
import { CapaComponent } from './capa/capa.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { MultiSelectModule } from 'primeng/multiselect';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new', component: NewComponent},
  { path: 'checking', component: CheckingComponent},
  { path: 'review', component: ReviewComponent},
  { path: 'log', component: LogComponent},
  { path: 'capa', component: CapaComponent}
];
@NgModule({
  declarations: [DashboardComponent, NewComponent, CheckingComponent, ReviewComponent, LogComponent, CapaComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    MultiSelectModule,
    RouterModule.forChild(routes)
  ]
})
export class DeviationModule { }
