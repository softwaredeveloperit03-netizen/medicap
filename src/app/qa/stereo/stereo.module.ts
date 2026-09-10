import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { DistructionFormComponent } from './distruction-form/distruction-form.component';
import { StereoDistructionLogComponent } from './stereo-distruction-log/stereo-distruction-log.component';
import { StereoIssuanceComponent } from './stereo-issuance/stereo-issuance.component';
import { StereoOrderComponent } from './stereo-order/stereo-order.component';
import { StereoReceivingComponent } from './stereo-receiving/stereo-receiving.component';
import { TranslateModule } from '@ngx-translate/core';


const routes:Routes=[
  {path:'',component:DashboardComponent},
  {path:'distruction',component:DistructionFormComponent},
  {path:'log',component:StereoDistructionLogComponent},
  {path:'issuance',component:StereoIssuanceComponent},
  {path:'order',component:StereoOrderComponent},
  {path:'receving',component:StereoReceivingComponent}
]

@NgModule({
  declarations: [
    DashboardComponent,
    DistructionFormComponent,
    StereoDistructionLogComponent,
    StereoIssuanceComponent,
    StereoOrderComponent,
    StereoReceivingComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class StereoModule { }
