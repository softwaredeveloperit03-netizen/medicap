import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { NewComponent } from './new/new.component';
import { FormsModule } from '@angular/forms';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { DashboardComponent } from './dashboard/dashboard.component';
 import { HoldComponent } from './hold/hold.component';
import { InterviwerComponent } from './interviwer/interviwer.component';
import { MultiSelectModule } from 'primeng/multiselect';
import { DropdownModule } from 'primeng/dropdown';
import { ReactiveFormsModule } from '@angular/forms';
import { FinalHrRoundComponent } from './final-hr-round/final-hr-round.component';
import { LogComponent } from './log/log.component';
import { TranslateModule } from '@ngx-translate/core';
import { SharedModule } from 'src/app/shared/shared.module';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new', component: NewComponent},
   { path: 'hold', component: HoldComponent},
  { path: 'interviewerChecklist', component: InterviwerComponent},
  { path: 'finalHrRound', component: FinalHrRoundComponent},
  { path: 'log', component: LogComponent},

];

@NgModule({
  declarations: [NewComponent,  DashboardComponent, 
    HoldComponent, InterviwerComponent, FinalHrRoundComponent, LogComponent,],
  imports: [ TranslateModule,
    SharedModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    MultiSelectModule,
    DropdownModule,
    ReactiveFormsModule,
    RouterModule.forChild(routes)
  ]
})
export class CandidateModule { }
