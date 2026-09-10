import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { SolutionpreparationComponent } from './solutionpreparation/solutionpreparation.component';
import { SoftenerregenrationComponent } from './softenerregenration/softenerregenration.component';
import { SoftwaterplantComponent } from './softwaterplant/softwaterplant.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'solution_preparation', component: SolutionpreparationComponent},
  { path: 'solution_generation', component: SoftenerregenrationComponent},
  { path: 'softwater_plant', component: SoftwaterplantComponent},

 
];


@NgModule({
  declarations: [
    SolutionpreparationComponent,
    SoftenerregenrationComponent,
    SoftwaterplantComponent,
    DashboardComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    ClarityModule,
    FormsModule,
    RouterModule.forChild(routes)


  ]
})
export class SoftWaterModule { }
