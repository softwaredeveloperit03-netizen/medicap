import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';

import { IndexmasterComponent } from './indexmaster/indexmaster.component';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { DashboardComponent } from './dashboard/dashboard.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},

  { path: 'index', component: IndexmasterComponent},
  
];


@NgModule({
  declarations: [
    IndexmasterComponent,
    DashboardComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    ClarityModule,
    RouterModule,
    FormsModule,
    RouterModule.forChild(routes)

  ]
})
export class DossierIndexMasterModule { }
