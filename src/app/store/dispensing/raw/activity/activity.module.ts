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
import { InprocessFormulationComponent } from './inprocess-formulation/inprocess-formulation.component';
import { SpinprocessFormulationComponent } from './spinprocess-formulation/spinprocess-formulation.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes =[
  { path: '', component: DashboardComponent},
  { path:'start',component:StartComponent },
  { path: 'complete',component:CompleteComponent},
  { path: 'inprocess',component:InprocessComponent},
  { path: 'inprocess-formulation',component:InprocessFormulationComponent},
  { path: 'spinprocess-formulation',component:SpinprocessFormulationComponent}
];

@NgModule({
  declarations: [
    DashboardComponent,
    StartComponent,
    InprocessComponent,
    CompleteComponent,
    InprocessFormulationComponent,
    SpinprocessFormulationComponent
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
