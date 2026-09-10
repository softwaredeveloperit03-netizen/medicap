import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { SharedModule } from 'src/app/shared/shared.module';
import { TranslateModule } from '@ngx-translate/core';
import { DashboardComponent } from './dashboard/dashboard.component';
import { GeneralTrainingComponent } from './general-training/general-training.component';
import { GmpTrainingComponent } from './gmp-training/gmp-training.component';
import { GroupTrainingComponent } from './group-training/group-training.component';
import { OjtTrainingComponent } from './ojt-training/ojt-training.component';

const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'general/new', component: GeneralTrainingComponent, data: { view: 'new' } },
  { path: 'general/log', component: GeneralTrainingComponent, data: { view: 'log' } },
  { path: 'gmp/new', component: GmpTrainingComponent, data: { view: 'new' } },
  { path: 'gmp/log', component: GmpTrainingComponent, data: { view: 'log' } },
  { path: 'group/new', component: GroupTrainingComponent, data: { view: 'new' } },
  { path: 'group/log', component: GroupTrainingComponent, data: { view: 'log' } },
  { path: 'ojt/new', component: OjtTrainingComponent, data: { view: 'new' } },
  { path: 'ojt/log', component: OjtTrainingComponent, data: { view: 'log' } },
];

@NgModule({
  declarations: [
    DashboardComponent,
    GeneralTrainingComponent,
    GmpTrainingComponent,
    GroupTrainingComponent,
    OjtTrainingComponent,
  ],
  imports: [
    SharedModule,
    TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes),
  ],
})
export class EmployeeTrainingRecordsModule {}
