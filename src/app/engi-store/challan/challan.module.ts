 

 import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { LogComponent } from './log/log.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { DashboardComponent } from './dashboard/dashboard.component';
import { DropdownModule } from 'primeng/dropdown';
import { AutoCompleteModule } from 'primeng/autocomplete';
import { HoldComponent } from './hold/hold.component';
import { FrompoComponent } from './frompo/frompo.component';
import { VerificationComponent } from './verification/verification.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'log', component: LogComponent},
  { path: 'hold', component: HoldComponent},
  { path: 'frompo', component: FrompoComponent},
  { path: 'verification', component: VerificationComponent},
];

@NgModule({
  declarations: [DashboardComponent, LogComponent, HoldComponent, FrompoComponent, VerificationComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    AutoCompleteModule,
    DropdownModule,
    
    RouterModule.forChild(routes)
  ]
})
export class ChallanModule { }
 