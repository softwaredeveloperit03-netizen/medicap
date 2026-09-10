import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { OfficeComponent } from './office/office.component';
import { CabinComponent } from './cabin/cabin.component';
import { BathroomComponent } from './bathroom/bathroom.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { FactoryComponent } from './factory/factory.component';
import { GeneralComponent } from './general/general.component';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'office', component: OfficeComponent},
  { path: 'cabin', component: CabinComponent},
  { path: 'bath', component: BathroomComponent},
  { path: 'factory', component: FactoryComponent},
  { path: 'general', component: GeneralComponent},
];
@NgModule({
  declarations: [
    OfficeComponent,
    CabinComponent,
    BathroomComponent,
    DashboardComponent,
    FactoryComponent,
    GeneralComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class DailyModule { }
