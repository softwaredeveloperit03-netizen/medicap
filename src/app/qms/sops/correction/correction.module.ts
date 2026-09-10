import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { HistoryComponent } from './history/history.component';
import { CorrectionComponent } from './correction/correction.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'new', component: CorrectionComponent },
  { path: 'history', component: HistoryComponent },

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
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class CorrectionModule { }
