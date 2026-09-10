import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { GawnTestingComponent } from './gawn-testing/gawn-testing.component';
import { GownsDistributionComponent } from './gowns-distribution/gowns-distribution.component';
import { LaundryComponent } from './laundry/laundry.component';
import {CleaningComponent} from'./cleaning/cleaning.component';
import { LaundryRegistrationComponent } from './laundry-registration/laundry-registration.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  {path:'Cleaning',component: CleaningComponent},
  { path: 'gawn-testing', component:GawnTestingComponent},
  { path: 'gowns-distribution', component: GownsDistributionComponent},
  { path: 'laundry', component: LaundryComponent},
  { path: 'laundry-registrayion', component: LaundryRegistrationComponent}
];


@NgModule({
  declarations: [ CleaningComponent,
    GawnTestingComponent,
    GownsDistributionComponent,
    LaundryComponent,
    LaundryRegistrationComponent,
    DashboardComponent,
   
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes),
    ReactiveFormsModule
  ]
})
export class LaundryModule { }
