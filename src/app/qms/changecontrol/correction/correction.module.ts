import { NgModule } from '@angular/core';
import { ClarityModule } from '@clr/angular';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';

import { HttpClientModule } from '@angular/common/http';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { DashboardComponent } from './dashboard/dashboard.component';
import { HistoryComponent } from './history/history.component';
import { CorrectionComponent } from './correction/correction.component';
import { TranslateModule } from '@ngx-translate/core';

 

const routes: Routes = [
  { path: '', component: DashboardComponent},  
   { path: 'correction1', component: CorrectionComponent},
   { path: 'history1', component: HistoryComponent},
  
];

@NgModule({
  declarations: [
    DashboardComponent,
    HistoryComponent,
    CorrectionComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ReactiveFormsModule,
    ClarityModule,
    HttpClientModule,
    RouterModule.forChild(routes)
  ]
})
export class CorrectionModule { }
