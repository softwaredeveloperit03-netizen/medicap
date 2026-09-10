import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { StartComponent } from './start/start.component';
import { InprocessComponent } from './inprocess/inprocess.component';
import { CompleteComponent } from './complete/complete.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { SpinprocessComponent } from './spinprocess/spinprocess.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes =[
  { path: '', component: DashboardComponent},
  { path:'start',component:StartComponent },
  { path: 'complete',component:CompleteComponent},
  { path: 'inprocess',component:InprocessComponent},
  { path: 'spinprocess',component:SpinprocessComponent}
];

@NgModule({
  declarations: [
    DashboardComponent,
    StartComponent,
    InprocessComponent,
    CompleteComponent,
    SpinprocessComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class ActivityModule { }
