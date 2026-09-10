import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { CompletedComponent } from './completed/completed.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { DispensingComponent } from './dispensing/dispensing.component';
import { UploadComponent } from './upload/upload.component';
import { StartComponent } from './start/start.component';
import { ManufacturingComponent } from './manufacturing/manufacturing.component';
import { YieldComponent } from './yield/yield.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'start', component: StartComponent},
  { path: 'manufacturing', component: ManufacturingComponent},
  { path: 'completed', component: CompletedComponent},
  { path: 'upload', component: UploadComponent},
  { path: 'yield', component: YieldComponent},
  { path: 'dispensing', loadChildren: () => import('./dispensing/dispensing.module').then(m=>m.DispensingModule)},
];

@NgModule({
  declarations: [DashboardComponent, CompletedComponent, DispensingComponent, UploadComponent, StartComponent, ManufacturingComponent, YieldComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class BatchModule { }
