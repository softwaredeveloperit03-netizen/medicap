import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { BalanceRoutingModule } from './balance-routing.module';
import { HomeComponent } from './home/home.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { ListComponent } from './list/list.component';
import { DailyComponent } from './daily/daily.component';
import { DailyCheckingComponent } from './daily-checking/daily-checking.component';
import { UncertinityComponent } from './uncertinity/uncertinity.component';
import { UncertinityCheckingComponent } from './uncertinity-checking/uncertinity-checking.component';
import { CleaningComponent } from './cleaning/cleaning.component';
import { TranslateModule } from '@ngx-translate/core';



@NgModule({
  declarations: [HomeComponent, ListComponent, DailyComponent, DailyCheckingComponent, UncertinityComponent, UncertinityCheckingComponent, CleaningComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    BalanceRoutingModule,
    FormsModule,
    ClarityModule
  ]
})
export class BalanceModule { }
