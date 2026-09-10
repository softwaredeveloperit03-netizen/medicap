import { NgModule } from '@angular/core';
import { SharedModule } from 'src/app/shared/shared.module';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';

import {FormsModule} from '@angular/forms'

const routes:Routes=[
  { path: '', component: DashboardComponent},
  { path: 'new', component: NewComponent},
];

@NgModule({
  declarations: [
    DashboardComponent,
    NewComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    RouterModule.forChild(routes)
  ]
})
export class QaMasterModule { }
