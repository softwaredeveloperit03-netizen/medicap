import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { NewComponent } from './new/new.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { TwoDigitDecimaNumberDirective } from 'src/app/two-digit-decima-number.directive';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'new', component: NewComponent },
];

@NgModule({
  declarations: [
    NewComponent,
    DashboardComponent,
    TwoDigitDecimaNumberDirective
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule, 
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class SalaryHeadModule { }
