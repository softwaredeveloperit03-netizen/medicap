import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { TreatmentrecordComponent } from './treatmentrecord/treatmentrecord.component';
import { StatuscardComponent } from './statuscard/statuscard.component';
import { TrainedaidersComponent } from './trainedaiders/trainedaiders.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  {path: '', component: DashboardComponent},
  {path: 'treatmentrecord', component: TreatmentrecordComponent},
  {path: 'statuscard', component: StatuscardComponent},
  {path: 'trainedaiders', component: TrainedaidersComponent}
];

@NgModule({
  declarations: [
    DashboardComponent,
    TreatmentrecordComponent,
    StatuscardComponent,
    TrainedaidersComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes),
  ]
})
export class FirstaidboxModule { }
