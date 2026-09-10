import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { ClarityModule } from '@clr/angular';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { RouterModule, Routes } from '@angular/router';
import { WorkingStandardQualicicationComponent } from './working-standard-qualicication/working-standard-qualicication.component';
import { NewComponent } from './new/new.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'working-standard-qualicication', component: WorkingStandardQualicicationComponent},
  { path: 'new', component: NewComponent},
];

@NgModule({
  declarations: [
    DashboardComponent,
    WorkingStandardQualicicationComponent,NewComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    ClarityModule,
    FormsModule,
    RouterModule.forChild(routes)
  ]
})
export class QualificationModule { }
