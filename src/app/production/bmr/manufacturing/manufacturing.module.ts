import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { StartComponent } from './start/start.component';
import { DispensingComponent } from './dispensing/dispensing.component';
import { ReceivingComponent } from './receiving/receiving.component';
import { InprocessComponent } from './inprocess/inprocess.component';
import { UploadComponent } from './upload/upload.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { RequisitionComponent } from './requisition/requisition.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'start', component: StartComponent},
  { path: 'receiving', component: ReceivingComponent},
  { path: 'inprocess', component: InprocessComponent},
  { path: 'upload', component: UploadComponent},
  { path: 'requisition', component: RequisitionComponent},
  { path: 'dispensing', loadChildren: () => import('./dispensing/dispensing.module').then(m=>m.DispensingModule)},
];

@NgModule({
  declarations: [DashboardComponent, StartComponent, DispensingComponent, ReceivingComponent, InprocessComponent, UploadComponent, RequisitionComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class ManufacturingModule { }
