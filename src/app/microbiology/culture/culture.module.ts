import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { CultureMaintainanceComponent } from './culture-maintainance/culture-maintainance.component';
import { IdentificationComponent } from './identification/identification.component';
import { MasterComponent } from './master/master.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'maintenance', component: CultureMaintainanceComponent},
  { path: 'identification', component: IdentificationComponent},
  { path: 'master', component: MasterComponent}
];

@NgModule({
  declarations: [DashboardComponent, CultureMaintainanceComponent, IdentificationComponent, MasterComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class CultureModule { }
