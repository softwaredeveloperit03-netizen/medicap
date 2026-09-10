import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { HomeComponent } from './home/home.component';
import { NewComponent } from './new/new.component';
import { RouterModule, Routes } from '@angular/router';
import { AutoCompleteModule } from 'primeng/autocomplete';
import { DropdownModule } from 'primeng/dropdown';
import { CheckingComponent } from './checking/checking.component';
import { ApprovalComponent } from './approval/approval.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: HomeComponent},
  { path: 'new', component: NewComponent},
  { path: 'checking', component: CheckingComponent},
  { path: 'approval', component: ApprovalComponent},
];

@NgModule({
  declarations: [HomeComponent, NewComponent, CheckingComponent, ApprovalComponent],
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
export class FinishModule { }
