import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { CorrectionComponent } from './correction/correction.component';
import { HistoryComponent } from './history/history.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
   { path: 'correction', component: CorrectionComponent},
  { path: 'history', component: HistoryComponent},
]

@NgModule({
  declarations: [
    CorrectionComponent,
    HistoryComponent,
    DashboardComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class CorrectionModule { }
