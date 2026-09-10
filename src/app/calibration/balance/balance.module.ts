import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { HomeComponent } from './home/home.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { NewComponent } from './new/new.component';
import { RouterModule, Routes } from '@angular/router';
import { DailypendingComponent } from './dailypending/dailypending.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: HomeComponent},
  { path: 'new', component: NewComponent},
  { path: 'dailypending', component: DailypendingComponent},
];

@NgModule({
  declarations: [HomeComponent, NewComponent, DailypendingComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class BalanceModule { }
