import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { NewComponent } from './new/new.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { AllComponent } from '../attendance/all/all.component';
import { IndividualComponent } from '../attendance/individual/individual.component';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new', component: NewComponent}
];

@NgModule({
  declarations: [
    NewComponent,
    DashboardComponent,
   
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    RouterModule.forChild(routes)
  ]
})
export class AchivementModule { }
