import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { InprocessChecksComponent } from './inprocess-checks/inprocess-checks.component';
import { WashwaterComponent } from './washwater/washwater.component';
import { DispensingApprovalComponent } from './dispensing-approval/dispensing-approval.component';
import { ProdapprovalComponent } from './prodapproval/prodapproval.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'inprocesschecks', component: InprocessChecksComponent},
  { path: 'wash', component:WashwaterComponent},
  { path: 'dispensing', component:DispensingApprovalComponent},
  { path: 'prodapproval', component:ProdapprovalComponent},
  { path: 'inprocess-sampling', loadChildren: () => import('./inprocess-sampling/inprocess-sampling.module').then(m=>m.InprocessSamplingModule), data: {preload: false}},
  { path: 'lineclearance', loadChildren: () => import('./lineclearance/lineclearance.module').then(m=>m.LineclearanceModule), data: {preload: false}},
  { path: 'material', loadChildren: () => import('./additional-material/additional-material.module').then(m=>m.AdditionalMaterialModule), data: {preload: false}}

];

@NgModule({
  declarations: [DashboardComponent,InprocessChecksComponent, WashwaterComponent, DispensingApprovalComponent, ProdapprovalComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class IpqaModule { }
