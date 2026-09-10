import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { CalenderComponent } from './calender/calender.component';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { DashboardComponent } from './dashboard/dashboard.component';
import { InhouseComponent } from './inhouse/inhouse.component';
import { ExternalComponent } from './external/external.component';
import { ChecklistComponent } from './checklist/checklist.component';
import { NewchecklistComponent } from './newchecklist/newchecklist.component';
import { CalendarModule } from 'angular-calendar';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  {
    path: 'equipment',
    loadChildren: () =>
      import('src/app/shared/equipment-dept-calibration/equipment-dept-calibration.module').then(
        (m) => m.EquipmentDeptCalibrationModule
      ),
    data: { performDepartment: 'Engineering', closeRoute: '/engineering/calibration' },
  },
  { path: 'inhouse', component: InhouseComponent},
  { path: 'external', component: ExternalComponent},
  { path: 'calender', component: CalenderComponent},
  { path: 'newChecklist1', component: NewchecklistComponent},
  { path: 'checklist', component: ChecklistComponent},
];

@NgModule({
  declarations: [CalenderComponent, DashboardComponent, InhouseComponent, ExternalComponent,
     ChecklistComponent, NewchecklistComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    CalendarModule,
    ReactiveFormsModule,
    RouterModule.forChild(routes)
  ]
})
export class CalibrationModule { }
