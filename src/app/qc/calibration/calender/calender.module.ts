import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { DashboardComponent } from './dashboard/dashboard.component';
import { CheckingComponent } from './checking/checking.component';
import { LogComponent } from './log/log.component';
import { FormComponent } from './form/form.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  {path: '', component: DashboardComponent},
  {path:'new', component: FormComponent},
  {path:'form', component: FormComponent},
  {path:'checking', component: CheckingComponent},
  {path: 'log', component: LogComponent},
]
@NgModule({
  declarations: [DashboardComponent, CheckingComponent, LogComponent, FormComponent],
  imports: [
    SharedModule,
    TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes),
  ],
})
export class CalenderModule { }
