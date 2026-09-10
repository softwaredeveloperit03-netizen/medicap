import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FirefightComponent } from './firefight/firefight.component';
import { CleaningschdComponent } from './cleaningschd/cleaningschd.component';
import { CleaningrecComponent } from './cleaningrec/cleaningrec.component';
import { InspectionfhydComponent } from './inspectionfhyd/inspectionfhyd.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'firefight', component: FirefightComponent },
  { path: 'cleaningschd', component: CleaningschdComponent },
  { path: 'cleaningrec', component: CleaningrecComponent },
  { path: 'inspectionfhyd', component: InspectionfhydComponent },
];

@NgModule({
  declarations: [
    DashboardComponent,
    FirefightComponent,
    CleaningschdComponent,
    CleaningrecComponent,
    InspectionfhydComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes),
  ]
})
export class FirehydrantModule { }
